<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH.'controllers/modules/APP_Api_authenticatable.php';
require_once SHAREDPATH.'core/APP_Operator.php';

/**
 * Application_controller
 *
 * @property APP_Config config
 * @property object agent
 * @property object output
 *
 * @package Controller
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class Application_controller extends APP_Controller
{
    use APP_Api_authenticatable;

    /**
     * Application_controller constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // profilerを無効化
        $this->output->enable_profiler(FALSE);
    }

    /**
     * @param array $data
     * @param string $template_path
     * @param bool|TRUE $layout
     *
     * @throws APP_Api_internal_call_exception
     * @throws APP_DB_exception_duplicate_key_entry
     * @throws APP_Exception
     * @throws Exception
     */
    public function _render($data = [], $template_path = NULL, $layout = TRUE)
    {
        // 共通のタイトル
        $app_name = $this->config->item('app_name');
        if (isset($data['page_title']) && !empty($data['page_title'])) {
            $data['title'] = $app_name . '|' . $data['page_title'];
        } else {
            $data['page_title'] = NULL;
        }

        // assign meta data
        $data['meta'] = $this->_meta(); 

        $data['device_type'] = $this->agent->is_smart_phone() ? 'SP' : 'PC';
        $data['is_android'] = $this->agent->is_android() ? TRUE : FALSE;

        parent::_render($data, $template_path, $layout);
    }

    /**
     * _meta
     *
     * Fetch meta information of HTML
     *
     * @access public
     * @return array
     */
    public function _meta()
    {
        return [
        ];
    }

    /**
     * Set params of list
     *
     * @return array
     */
    public function _params()
    {
        $params = $this->input->param();
        
        if (empty($params)) {
            $params = [];
        }

        if (empty($params['limit'])) {
            $params['limit'] = self::LISTLIMIT;
        }
        
        if (empty($params['offset'])) {
            $params['offset'] = 0;
        }

        if (!empty($params['p']) && is_numeric($params['p']) && $params['p'] > 0){
            $params['offset'] = ($params['p'] - 1) * $params['limit'];
        } else {
            unset($params['p']);
        }

        return $params; 
    }

    /**
     * Require login
     */
    public function _require_login()
    {

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
            // 権限がない場合は、404表示とする
            // パラメータ不備の場合は、404表示とする
            case APP_Api::NOT_FOUND:
            case APP_Api::FORBIDDEN:
            case APP_Api::INVALID_PARAMS:
                if (ENVIRONMENT != 'development') {
                    return $this->_render_404();
                }
                break;

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
}
