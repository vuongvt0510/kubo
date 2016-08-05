<?php if ( !defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH .'controllers/modules/APP_Api_authenticatable.php';
require_once SHAREDPATH .'core/APP_Operator.php';

/**
 * Application_controller
 *
 * @property APP_Config config
 * @property object agent
 * @property object output
 * @method object _api ($api_name)
 *
 * @package Controller
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class Application_controller extends APP_Controller
{
    use APP_Api_authenticatable;

    public $layout = "layouts/base";

    /**
     * Application_controller constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_before_filter('_find_current_user');
        $this->_before_filter('_require_login');
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
//        $app_name = $this->config->item('app_name');
//        if (isset($data['page_title']) && !empty($data['page_title'])) {
//            $data['title'] = $app_name . '|' . $data['page_title'];
//        } else {
//            $data['page_title'] = NULL;
//        }
//
//        // assign meta data
//        $data['meta'] = $this->_meta();
//
//        $data['device_type'] = $this->agent->is_smart_phone() ? 'SP' : 'PC';
//        $data['is_android'] = $this->agent->is_android() ? TRUE : FALSE;
//        $data['current_user'] = !empty($this->current_user) ? $this->current_user : null;
//
//        if (isset($data['pagination'])) {
//            $data['pagination'] = $this->get_pagination($data['pagination']);
//        }
//        //assign breadcrumb
//        if(isset($this->_breadcrumb)){
//            $data['breadcrumb'] = $this->_breadcrumb;
//        }

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
     * Build pagination params
     * @param array $pagination
     * @return array
     */
    private function get_pagination($pagination = [])
    {
        $limit = isset($pagination['limit']) ? $pagination['limit'] : PAGINATION_DEFAULT_LIMIT;
        $page = isset($pagination['page']) ? $pagination['page'] : 1;
        $total = empty($pagination['total']) ? 0 : (int) $pagination['total'];
        $total_page = ceil($total / $limit);
        $adj = 5;
        $offset = ($page - 1) * $limit + 1;

        // Prepare search parameters
        $search = '';

        if (!empty($pagination['search'])) {
            // Remove page parameter
            if (isset($pagination['search']['p'])) {
                unset($pagination['search']['p']);
            }

            $search = http_build_query($pagination['search']);
        }

        return [
            'adj' => $adj,
            'search' => $search,
            'items_per_page' => $limit ,
            'page' => $page,
            'total' => $total,
            'total_page' => $total_page,
            'display' => $total > $limit ? PAGINATION_DEFAULT_LIMIT : $total,
            'offset' => $offset,
            'limit' => ($offset + $limit - 1) > $total ? $total : $offset + $limit - 1,
            'min_display' => $page - $adj < 0 ? 1 : $page - $adj,
            'max_display' => $page + $adj > $total_page ? $total_page : $page + $adj
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

        if (!empty($params['p']) && is_numeric($params['p']) && $params['p'] > 0) {
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
        if (!$this->current_user->is_administrator() || !$this->current_user->is_login()) {
            $this->_redirect('login');
        }
    }

    /**
     * Find current user
     */
    public function _find_current_user()
    {
        $this->load->model('admin_model');

        $this->current_user = new APP_Anonymous_operator;
        APP_Model::set_operator($this->current_user);

        $admin_id = $this->session->userdata('admin_id');
        if (empty($admin_id)) {

            $cname = null;
            $token = null;
            if (is_array($_COOKIE)) {
                foreach (array_keys($_COOKIE) AS $k) {
                    if (!preg_match('/^STVA\_/', $k)) {
                        continue;
                    }

                    $cname = $k;
                    $token = $this->input->cookie($k);
                }
            }

            if ($cname && $token) {
                $res = $this->admin_model->get_autologin($token);

                if ($res && ('STVA_' . md5($res->token) === $cname)) {
                    $admin_id = (int)$res->admin_id;
                }
            }

            if (!$admin_id) {
                return;
            }
        }

        // MEMO: status active:通常
        $admin = $this->admin_model->find($admin_id);
        if (empty($admin)) {
            return;
        }

        $this->current_user = $admin;

        APP_Model::set_operator($this->current_user);

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
