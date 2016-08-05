<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// TODO: config/database.php の $active_record の フラグをチェックして読み込むように修正
require_once dirname(__FILE__) . "/APP_DB_active_record.php";
require_once dirname(__FILE__) . "/APP_Operator.php";

require_once dirname(__FILE__) . "/APP_Api_external_call_exception.php";
require_once dirname(__FILE__) . "/APP_Api_internal_call_exception.php";

/**
 * APP_Controller
 *
 * @property object response
 * @property object output
 * @property object input
 * @property object session
 * @property object config
 * @property object router
 * @property APP_Smarty smarty
 * @author Yoshikazu Ozawa
 */
class APP_Controller extends CI_Controller {

    /**
     * レイアウト設定
     * @var string
     */
    public $layout = FALSE;

    /**
     * テンプレートエンジン
     * @var string
     */
    public $template_engine = NULL;

    /**
     * データベース利用
     * @var bool
     */
    public $use_database = TRUE;

    /**
     * フィルタ設定
     * @var array
     */
    public $before_filter = array();

    /**
     * スキップ設定
     * @var array
     */
    public $_skipped = FALSE;

    /**
     * ログインユーザー
     * @var object
     */
    public $current_user = NULL;

    /**
     * DBインスタンス設定 (setting db instances)
     * @var array
     */
    protected $database_config = array(
        'db'  => 'master',  // master
        'dbs' => 'slave',   // slave
    );

    /**
     * Fatalエラーハンドリング
     *
     * DBの接続を全てロールバックして、コネクションを切断する
     * エラー内容をError_notifierを通してメールで送付する
     *
     * @access public
     * @return bool
     */
    static public function shutdown_handler()
    {
        $error = error_get_last();

        if (empty($error)) {
            return TRUE;
        }

        $severity = $error['type'];
        $message = $error['message'];
        $filepath = $error['file'];
        $line = $error['line'];

        switch ($severity)
        {
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_RECOVERABLE_ERROR:
        case E_USER_ERROR:
            $_error =& load_class('Exceptions', 'core');
            $severity = (! isset($_error->levels[$severity])) ? $severity : $_error->levels[$severity];

            $subject = sprintf('PHP Fatal Error %s', $message);
            $body = sprintf('Severity: %s --> %s %s:%s', $severity, $message, $filepath, $line);

            $CI =& get_instance();
            if (isset($CI) && isset($CI->_error_notifier)) {
                $CI->_error_notifier->send($body, array('subject' => $subject));
            }

            // 全てのコネクションを切断
            if (class_exists("APP_DB_active_record")) {
                APP_DB_active_record::close_all();
            }

            log_message("fatal", sprintf("%s %s:%s", $message, $filepath, $line));
            break;
        }

        return FALSE;
    }

    /**
     * コンストラクタ (constructor function)
     * デフォルト以外へのデータベースへの接続や指定されてモデルやヘルパの自動読み込みを行う
     * (Automatically load helpers and databases)
     *
     * @access public
     * @throws APP_DB_Exception
     * @throws Exception
     */
    public function __construct()
    {
        register_shutdown_function("APP_Controller::shutdown_handler");

        parent::__construct();

        if (ENVIRONMENT == 'development' && ! $this->input->is_ajax_request()) {
            $this->output->enable_profiler(TRUE);
        }

        // ヘルパー読み込み (read helpers)
        $this->load->helper('common_helper');
        $this->load->helper('model_helper');
        $this->load->helper('render_helper');

        // ライブラリ読み込み (read libraries)
        $this->load->library('user_agent', null, 'agent');

        $this->load->library("response");

        // エラー通知ドライバの選定
        if (FALSE !== $this->config->item('error_notifier_driver')) {
            $this->load->library($this->config->item('error_notifier_driver'), NULL, "_error_notifier");
        }

        // テンプレートエンジンの選定
        if ( ! isset($this->template_engine)) {
            $engine = $this->config->item('template_engine');
            $this->template_engine = empty($engine) ? 'default' : $engine;
        }

        try {
            if ($this->use_database === TRUE) {
                // データベースへ接続 (connect to database)
                foreach ($this->database_config as $variable => $name) {
                    if ( ! isset($this->{$variable})) {
                        $this->{$variable} = $this->load->database($name, TRUE);
                    }
                }
            }
        } catch (APP_DB_Exception $e) {
            if (ENVIRONMENT !== 'production') {
                throw $e;
            }
            $this->_render_500();
        }

        $this->current_user = new APP_Anonymous_operator();
    }

    /**
     * before_filter等で前処理で終了したい場合に _skip_action() を
     * 呼び出すことでアクションを実行しないようにすることができる
     *
     * @access public
     *
     * @param string $method アクション名
     * @param array $params アクションを呼び出す引数
     *
     * @throws Exception
     */
    public function _remap($method, $params = array())
    {
        if ($this->_skipped) return;

        if ( ! method_exists($this, $method)) {
            return $this->_render_404();
        }

        try {
            return call_user_func_array(array($this, $method), $params);
        } catch (Exception $e) {

            // トランザクションをすべてロールバック
            if (class_exists("APP_DB_active_record")) {
                APP_DB_active_record::trans_rollback_all();
            }

            try {
                // エラーハンドラを呼び出す
                $this->_catch_exception($e);
            } catch (Exception $o) {

                // 全てのコネクションを切断
                if (class_exists("APP_DB_active_record")) {
                    APP_DB_active_record::close_all();
                }

                if (isset($this->_error_notifier)) {
                    $this->_error_notifier->send_exception($o);
                }

                if (ENVIRONMENT === 'development') {
                    throw $o;
                }

                return $this->_render_500();
            }

        }
    }

    /**
     * 例外時の動作
     *
     * @access public
     *
     * @param Exception $e
     *
     * @return bool 例外通知するかどうか
     * @throws APP_Api_call_exception
     * @throws Exception
     */
    public function _catch_exception(Exception $e)
    {
        // 内部APIエラー呼び出しの場合のエラーハンドリング
        if ($e instanceof APP_Api_call_exception) {

            switch ($e->getCode()) {

            // レコードが見つからない場合は 404 とする
            case APP_Api::NOT_FOUND:
                return $this->_render_404();

            // 未認証の場合は ログイン認証処理 を呼び出すこととする
            case APP_Api::UNAUTHORIZED:
                if (method_exists($this, '_require_login')) {
                    return $this->_require_login();
                }
                break;

            default:
                break;
            }
        }

        throw $e;
    }

    /**
     * APIをロード
     *
     * @access protected
     * @param string $name
     * @param array $options
     * @return object
     */
    protected function & _api($name, $options = array())
    {
        $name = $name . "_api";

        $this->load->library("API/{$name}", array_merge(array(
            "operator" => $this->current_user
        ), $options));

        $property = @end(explode("/", $name));

        return $this->{$property};
    }

    /**
     * APIの呼び出し
     *
     * @access protected
     *
     * @param string $name
     * @param string $method
     * @param array $params
     * @param array $options
     *
     * @return array
     * @throws APP_Api_external_call_exception
     */
    protected function _external_api($name, $method, $params = array(), $options = array())
    {
        try {
            log_message("INFO", "[API:{$name}/{$method}] request is " . json_encode(log_mask($params)));

            $response = $this->_api($name, $options)->$method(empty($params) ? array() : $params, $options);

            log_message("INFO", "[API:{$name}/{$method}] response is " . json_encode(log_mask($response)));
        } catch (Exception $e) {
            throw new APP_Api_external_call_exception($name, $method, NULL, $e);
        }

        $this->_build_json($response, $options);
    }

    /**
     * APIの呼び出し
     *
     * @access protected
     *
     * @param string $name
     * @param string $method
     * @param array $params
     * @param array $options
     *
     * @return array
     * @throws APP_Api_internal_call_exception
     */
    protected function _internal_api($name, $method, $params = array(), $options = array())
    {
        $options = array_merge(array(
            'throw_exception' => TRUE
        ), $options);

        try {
            log_message("INFO", "[API:{$name}/{$method}] request is " . json_encode(log_mask($params)));

            $response = $this->_api($name, $options)->$method(empty($params) ? array() : $params, $options);

            log_message("INFO", "[API:{$name}/{$method}] response is " . json_encode(log_mask($response)));

        } catch (Exception $e) {
            throw new APP_Api_internal_call_exception($name, $method, NULL, $e);
        }

        if ($options['throw_exception'] === TRUE && ($response["success"] !== TRUE || $response["submit"] !== TRUE)) {
            throw new APP_Api_internal_call_exception($name, $method, $response, NULL);
        }

        return empty($response['result']) ? array() : $response['result'];
    }

    /**
     * 特定のGETパラメータのみを取得する
     *
     * @return array
     * @internal param string $name ...
     */
    public function _extract_query()
    {
        $query = array();
        foreach (func_get_args() as $name) {
            $value = $this->input->get($name);
            if ($value !== FALSE) {
                $query[$name] = $value;
            }
        }
        return $query;
    }

    /**
     * 指定したフィールド名からアップロードされたファイルを読み込む
     *
     * @access public
     * @param string $name
     * @return mixed
     */
    public function _upload_image($name = 'image')
    {
        $this->load->library('upload');
        if ($this->upload->do_upload('image') === FALSE) {
            return FALSE;
        }
        return $this->upload->data('image');
    }

    /**
     * JSON出力
     * 成功時のJSON出力を行う
     * callbackパラメータが付与されていた場合は自動的にJSONP形式で返す
     *
     * @access public
     *
     * @param mixed $data
     * @param array $extra
     * @param array $options
     */
    public function _true_json($data = NULL, $extra = array(), $options = array())
    {
        $result = $this->response->true_json($data, $extra);
        $this->_build_json($result, $options);
    }

    /**
     * JSON出力
     * 登録・更新失敗時のJSON出力を行う
     * callbackパラメータが付与されていた場合は自動的にJSONP形式で返す
     *
     * @access public
     *
     * @param $invalid_fields
     * @param array $extra
     * @param array $options
     *
     */
    public function _submit_false_json($invalid_fields, $extra = array(), $options = array())
    {
        $result = $this->response->submit_false_json($invalid_fields, $extra);
        $this->_build_json($result, $options);
    }

    /**
     * JSON出力
     * 失敗時のJSON出力を行う
     * callbackパラメータが付与されていた場合は自動的にJSONP形式で返す
     *
     * @access public
     *
     * @param $errcode
     * @param string $errmsg
     * @param array $extra
     * @param array $options
     */
    public function _false_json($errcode, $errmsg = NULL, $extra = array(), $options = array()) 
    {
        $result = $this->response->false_json($errcode, $errmsg, $extra);
        $this->_build_json($result, $options);
    }

    /**
     * JSON出力
     * callbackパラメータが付与されていた場合は自動的にJSONP形式で返す
     *
     * @access public
     *
     * @param array $result
     * @param array $options
     *
     * @internal param options $extra
     */
    public function _build_json($result, $options = array())
    {
        $options = array_merge(array(
            'http_status' => 200,
            'header' => TRUE,
            'enable_jsonp' => FALSE
        ), $options);

        $this->output->enable_profiler(FALSE);
        $this->_skip_action();

        $this->output->set_status_header($options['http_status']);

        $result = json_encode($result);

        if ($options['enable_jsonp'] && ($callback = $this->input->get_post("callback"))) {
            if (!preg_match("/^[a-zA-z0-9_]+$/", $callback)) {
                $options['enable_jsonp'] = FALSE;
                $this->_false_json(APP_Response::INVALID_PARAMS, NULL, NULL, $options);
                return;
            }
            if ($options['header']) {
                $this->output->set_content_type("application/javascript; charset=UTF-8");
            }
            $result = $this->input->get_post("callback") . "(" . $result . ")";
        } else {
            if ($options['header']) {
                $this->output->set_content_type("application/json");
            }
        }

        $this->output->set_output($result);
    }

    /**
     * フィルタ設定
     *
     * @access public
     * @param string $name
     * @param array $options
     * @return void
     */
    public function _before_filter($name, $options = array())
    {
        $filters = array();

        foreach ($this->before_filter as $f) {
            if ($f['name'] != $name) {
                array_push($filters, $f);
            }
        }

        array_push($filters, array_merge(array('name' => $name), $options));

        $this->before_filter = $filters;
    }

    /**
     * フィルタスキップ設定
     *
     * @access public
     * @param string $name
     * @param array $options
     * @return void
     */
    public function _skip_before_filter($name, $options = array())
    {
        $filters = array();
        $target = NULL;

        foreach ($this->before_filter as $f) {
            if ($f['name'] == $name) {
                $target = $f;
            } else {
                array_push($filters, $f);
            }
        }

        // フィルタ設定されていないので無視する
        if ( ! isset($target)) {
            return;
        }

        if (isset($options['only']) || isset($options['except'])) {
            // TODO: 細かいスキップ設定を実装する
        } else {
            // オプションが設定されていない場合は、すべてのアクションでフィルター設定の無視する
            $this->before_filter = $filters;
        }
    }

    /**
     * アクションスキップ
     * Before Filer内でこのメソッドが呼ばれると、コントローラのアクションは呼ばれない
     *
     * @access public
     * @return void
     */
    public function _skip_action()
    {
        $this->_skipped = TRUE;
    }

    /**
     * XHRリクエスト以外を許可しない
     *
     * @access public
     * @return void
     */
    public function _accept_only_ajax_request()
    {
        if ( ! $this->input->is_ajax_request()) {
            $this->_render_404();
        }
    }

    /**
     * POSTリクエスト以外を許可しない
     *
     * @access public
     * @return void
     */
    public function _accept_only_post_request()
    {
        if (! $this->input->is_post()) {
            $this->_render_404();
        }
    }

    /**
     * SSLアクセス以外を許可しない
     *
     * @access public
     * @return void
     */
    public function _accept_only_secure()
    {
        $enabled = $this->config->item('ssl_enabled');
        if ($enabled === FALSE) {
            return;
        }

        if (!$this->input->is_ssl()) {
            $this->_render_404();
        }
    }

    /**
     * SSLでのアクセスのみ許可
     *
     * @access public
     * @return void
     */
    public function _accept_only_ssl()
    {
        $this->_accept_only_secure();
    }

    /**
     * SSL以外でアクセスした場合はSSLへ強制的に遷移
     *
     * @access public
     * @return void
     */
    public function _redirect_secure()
    {
        $enabled = $this->config->item('ssl_enabled');
        if ($enabled === FALSE) {
            return;
        }

        if ($this->input->is_ssl()) {
            return;
        }

        $this->load->helper('url');

        $url = current_url(TRUE);

        $params = $this->input->get();
        if (!empty($params)) {
            $url .= "?" . http_build_query($params);
        }

        $this->_redirect($url);
    }

    /**
     * SSLでアクセスした場合はSSLに遷移
     */
    public function _redirect_ssl()
    {
        $this->_redirect_secure();
    }

    /**
     * SSLでアクセスした場合は、HTTPへ強制的に遷移させる
     *
     * @access public
     * @return void
     */
    public function _redirect_insecure()
    {
        $enabled = $this->config->item('ssl_enabled');
        if ($enabled === FALSE) {
            return;
        }

        if (!$this->input->is_ssl()) {
            return;
        }

        $this->load->helper('url');

        $url = current_url(FALSE);

        $params = $this->input->get();
        if (!empty($params)) {
            $url .= "?" . http_build_query($params);
        }

        $this->_redirect($url);
    }

    /**
     * テンプレートエンジンインスタンス
     *
     * @access public
     * @return mixed
     */
    public function _template_engine()
    {
        switch ($this->template_engine) {
        case 'smarty':
            $this->load->library('smarty');
            return $this->smarty;
            break;
        default:
            return $this->load;
            break;
        }
    }

    /**
     * テンプレート描画
     * 指定されたファイルパスのテンプレートを基にページを表示する
     * before filter メソッドでこのメソッドが呼び出された場合は、アクションがスキップされる
     *
     * @access public
     * @param string $template_path
     * @param array $data
     * @param bool $layout
     */
    public function _render($data = array(), $template_path = NULL, $layout = TRUE)
    {
        $this->_skip_action();

        $data['resource_minified'] = $this->config->item('resource_minified');
        $data['javascript_debug'] = $this->config->item('javascript_debug');

        if (isset($this->session)) {
            $data['flash_message'] = $this->session->flashdata('__notice');
            if (empty($data['flash_message'])) {
                $data['flash_message'] = NULL;
            }
        }

        if ( ! isset($template_path)) {
            $dir = $this->router->fetch_directory();
            $template_path = (empty($dir) || $dir === "/" ? "" : $dir) . $this->router->fetch_class() . "/" . $this->router->fetch_method();
        }

        $layout_path = FALSE;

        if ($layout !== FALSE) {
            $layout_path = ($layout === TRUE) ? $this->layout : $layout;
        }

        // テンプレートエンジンの選定
        $engine = $this->_template_engine();

        $content = $engine->view($template_path, $data, ($layout_path !== FALSE));
        if ($layout_path !== FALSE) {
            $engine->_content_add('main', $content);
            $engine->view($layout_path, $data);
        }
    }

    /**
     * CSVダウンロード出力
     *
     * @access public
     *
     * @param array $filename 出力ファイル名
     * @param array $data CSVデータ
     * @param array $header CSVヘッダー
     * @param array|string $to_encoding 出力エンコーディング
     */
    public function _render_csv($filename, $data, $header = array(), $to_encoding = 'SJIS-win')
    {
        $this->output->enable_profiler(FALSE);

        // 元のエンコーディングを取得
        $from_encoding = ini_get('mbstring.internal_encoding');
        $from_encoding = empty($from_encoding) ? 'UTF-8' : $from_encoding;

        // CSVにパース
        $csv_string = $this->_build_csv($data, $header);

        // ヘッダー送出
        $this->output->set_header('Content-Type: application/octet-stream');
        $this->output->set_header('Content-Disposition: attachment; filename=' . $filename . '.csv');
        $this->output->set_header("Pragma: private");
        $this->output->set_header("Cache-Control: private");
        $this->output->set_header("Expires: 60");

        $this->output->set_output(mb_convert_encoding($csv_string, $to_encoding, $from_encoding));

        return;
    }

    /**
     * 404 APIレスポンス
     * 404ページ
     *
     * @access public
     *
     * @param string $message
     * @param string $submessage
     * @param string $format
     */
    public function _render_404($message = '', $submessage = '', $format = NULL)
    {
        if (empty($message)) $message = 'お探しのページは見つかりません';
        if (empty($submessage)) $submessage = 'ご指定のページは削除されたか、移動した可能性がございます。';
        $this->_render_error($message, $submessage, 404, array('format' => $format, 'template_path' => 'error_404'));
    }

    /**
     * 500 APIレスポンス
     * 500ページ
     *
     * @access public
     * @param string $format
     * @param string $message
     * @return void
     */
    public function _render_500($message = '', $submessage = '', $format = NULL)
    {
        if (empty($message)) $message = 'エラーが発生しました';
        if (empty($submessage)) $submessage = 'しばらく経ってから再度お試しください';
        $this->_render_error($message, $submessage, 500, array('format' => $format));
    }

    /**
     * エラー レスポンス
     * エラーページ
     *
     * @access protected
     *
     * @param string $message
     * @param string $submessage
     * @param mixed $status
     * @param array $options
     *
     * @internal param string $format
     * @internal param string $template_path
     */
    public function _render_error($message, $submessage, $status = '500', $options = array())
    {
        $options = array_merge(array(
            'template_path' => 'error_general',
            'format' => NULL
        ), $options);

        $this->_skip_action();

        switch(empty($options['format']) ? $this->router->fetch_format() : $options['format']) {
        case "json":
            $this->output->set_status_header($status);
            if (empty($message)) $message = '不正なアクセスです。';
            echo $this->_false_json(APP_Response::BAD_REQUEST, $message);
            break;
        default:
            $_error =& load_class('Exceptions', 'core');
            echo $_error->show_error($message, $submessage, $options['template_path'], $status);
            break;
        }
    }

    /**
     * リダイレクト
     * 指定されたURLへのリダイレクトを行う
     * before filter メソッドでこのメソッドが呼び出された場合は、アクションがスキップされる
     *
     * @access public
     * @param string $path リダイレクトURL
     * @return void
     */
    public function _redirect($path) {
        $this->_skip_action();

        $this->load->helper('url');

        if (func_num_args() > 1) {
            $path = site_url(func_get_args());
        } else if (is_array($path)) {
            $path = site_url($path);
        }
        redirect($path);
    }

    /**
     * フラッシュメッセージを設定する
     *
     * @access public
     * @param string $message
     * @return void
     */
    public function _flash_message($message)
    {
        $this->load->library("session");
        $this->session->set_flashdata('__notice', $message);
    }

    /**
     * フラッシュメッセージを次回に持ち越す
     *
     * @access public
     * @return void
     */
    public function _keep_flash_message()
    {
        $this->load->library("session");
        $this->session->keep_flashdata('__notice');
    }

    /**
     * CSV文字列生成
     *
     * @access public
     * @param array $data CSVデータ
     * @param array $header CSVヘッダー
     * @return string
     */
    protected function _build_csv($data, $header = array())
    {
        // ヘッダーの挿入
        if (isset($header) && !empty($header)) {
            // データはダブルクォーテーションで囲む
            foreach (array_keys($header) as $key) {
                $header[$key] = '"' . $header[$key] . '"';
            }

            $csv = implode(',', $header) . "\n";

        } else {
            $csv = '';
        }

        // データの挿入
        foreach ($data as $row) {
            // データはダブルクォーテーションで囲む
            foreach (array_keys($row) as $key) {
                $row[$key] = '"' . $row[$key] . '"';
            }

            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }

}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
