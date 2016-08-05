<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once  SHAREDPATH . 'third_party/Smarty/libs/Smarty.class.php';

/**
 * Smarty Class
 *
 * CodeIgniterをSmartyで扱うためのクラス
 *
 * @author  Kepler Gelotte, arranged by Yoshikazu Ozawa
 * @link http://www.coolphptools.com/codeigniter-smarty
 */
class APP_Smarty extends Smarty {

    /**
     * CodeIgniterインスタンス
     * @var APP_Controller
     */
    public $CI = NULL;

    /**
     * ユーザーエージェント判定クラス
     * @var APP_User_agent
     */
    public $agent = NULL;

    /**
     * コンテンツバッファ
     * @var array
     */
    protected $content_buffers = array();

    /**
     * コンストラクタ
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct();

        // 設定ファイル読み込み
        $files = array(
            APPPATH . "config/" . ENVIRONMENT . "/smarty.php",
            APPPATH . "config/smarty.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/smarty.php",
            SHAREDPATH . "config/smarty.php"
        );

        foreach ($files as $f) {
            if (file_exists($f)) {
                include $f;
                break;
            }
        }

        if (!empty($smarty['plugins_dir'])) {
            foreach ($smarty['plugins_dir'] as $d) {
                $this->addPluginsDir($d);
            }
            unset($smarty['plugins_dir']);
        }

        foreach ($smarty as $k => $v) {
            $this->{$k} = $v;
        }

        if (function_exists('get_instance') && class_exists('CI_Controller')) {
            $this->CI =& get_instance();

            if (method_exists($this, 'assignByRef')) {
                $this->assignByRef("CI", $this->CI);
                $this->assignByRef("config", $this->CI->config->config);
            }

            if (!empty($this->CI) && !empty($this->CI->load)) {
                $this->CI->load->library("user_agent");
                $this->agent =& $this->CI->agent;
            }
        }

        if (empty($agent)) {
            require_once BASEPATH . "libraries/User_agent.php";
            require_once SHAREDPATH . "libraries/APP_User_agent.php";
            $this->agent = new APP_User_agent();
        }

        $this->assign('ENVIRONMENT', ENVIRONMENT);

        log_message('debug', "Smarty Class Initialized");
    }

    /**
     * 描画
     *
     * @access public
     * @param string $view テンプレート名
     * @param array $data アサインするパラメータ群
     * @param mixed $return 出力データをそのまま返すかどうか
     * @return mixed
     *
     * @throws Exception
     * @throws SmartyException
     */
    public function view($view, $data = array(), $return = FALSE)
    {
        // テンプレートへのデータのアサイン
        foreach ($data as $key => $val) {
            $this->assign($key, $val);
        }

        $auto = TRUE;

        if (is_array($return)) {
            $options = $return;

            $return = isset($options['return']) ? $options['return'] : FALSE;
            $auto = isset($options['auto_select']) ? $options['auto_select'] : TRUE;
        }

        $dir = pathinfo($view, PATHINFO_DIRNAME);
        $filename = pathinfo($view, PATHINFO_FILENAME);
        $ext = pathinfo($view, PATHINFO_EXTENSION);

        // TODO: 拡張子の見分け方はもうちょっと整理した方がよい
        if ($ext == '') $ext = 'html';

        $base_path = lcfirst($dir) . "/" . $filename;

        $files[] = $base_path . "." . $ext;

        if ($auto) {
            if (get_class($this->agent) === "APP_User_agent" || is_subclass_of($this->agent, "APP_User_agent")) {
                if ($this->agent->is_smart_phone()) {
                    array_unshift($files, $base_path . ".sp" . "." . $ext);

                    if ($this->agent->is_iphone()) {
                        array_unshift($files, $base_path . ".iphone" . "." . $ext);
                    }

                    if ($this->agent->is_android()) {
                        array_unshift($files, $base_path . ".android" . "." . $ext);
                    }
                }

                if ($this->agent->is_feature_phone()) {
                    array_unshift($files, $base_path . ".mobile" . "." . $ext);
                }
            }
        }

        foreach ($files as $template) {
            if ($this->templateExists($template)) {
                $output_template = $this->fetch($template);

                if ($return === TRUE) {
                    return $output_template;
                }

                $CI =& get_instance();
                if (method_exists($CI->output, 'set_output')) {
                    $CI->output->set_output($output_template);
                } else {
                    $CI->output->final_output = $output_template;
                }

                return;
            }
        }

        throw new SmartyException(sprintf("Unable to load template files %s", implode(",", $files)));
    }

    /**
     * バッファリングしておいたコンテンツを取得する
     *
     * @access public
     * @param string $name
     * @return string
     */
    public function _content_yield($name)
    {
        if ( ! array_key_exists($name, $this->content_buffers)) {
            return "";
        }
        return implode("\n", $this->content_buffers[$name]);
    }

    /**
     * バッファリング開始
     * 指定した名前をキーにコンテンツのバッファリングを開始する
     *
     * @access public
     * @param string $name
     * @return void
     */
    public function _content_start($name)
    {
        $this->content_names[] = $name;
        ob_start();
    }

    /**
     * バッファリング終了
     * MY_Controller::content_start() で開始したバッファリングを終了する
     *
     * @access public
     * @return void
     * @todo バッファリングのネストに対応させる
     */
    public function _content_end()
    {
        $name = array_pop($this->content_names);
        $buffer = ob_get_contents();

        $this->_content_add($name, $buffer);

        @ob_end_clean();
    }

    /**
     * @var array
     */
    private $content_names = array();

    /**
     * バッファリングするコンテンツを保存する
     * 指定した名前をキーにバッファリングしたコンテンツを保存する
     *
     * @access public
     * @param string $name
     * @param string $content
     */
    public function _content_add($name, $content)
    {
        $this->content_buffers[$name][] = $content;
    }
}

