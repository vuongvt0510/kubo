<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Rabipoint extends Application_controller
{

    public function __construct()
    {
        parent::__construct();

        // Add breadcrumb
        $this->_breadcrumb = [
            [
                'link' => '/news',
                'name' => 'お知らせ一覧'
            ]
        ];

        $this->load->model('point_exchange_model');
    }

    /**
     * Admin rabipoint manager
     *
     * @throws APP_Api_internal_call_exception
     */
    public function index($user_id = null)
    {
        // Check permission
        if (!$this->current_user->has_permission('RABIPOINT_LIST')) {
            return redirect('/');
        }

        $view_data = [
            'menu_active' => 'li_user'
        ];

        // Get user detail
        $user = $this->_api('user')->get_detail([
            'id'=> $user_id,
            'get_all' => TRUE
        ]);

        if (!isset($user['result'])) {
            return redirect('user/search');
        }

        // Get point detail
        $rabipoint = $this->_api('user_rabipoint')->get_detail([
            'user_id' => $user_id
        ]);

        $pagination = [
            'page' => (int) $this->input->get_or_default('p', 1),
            'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
        ];

        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

        // Get point history
        $rabipoint_history = $this->_api('user_rabipoint')->get_list([
            'user_id' => $user_id,
            'explanation' => TRUE,
            'limit' => $pagination['limit'],
            'offset' => $pagination['offset']
        ]);

        $pagination['search'] = $this->input->get();

        $view_data['user'] = $user['result'];
        $view_data['rabipoint'] = $rabipoint['result'];
        $view_data['pagination'] = $pagination;
        $view_data['rabipoint_history'] = $rabipoint_history['result']['items'];
        $view_data['pagination']['total'] = $rabipoint_history['result']['total'];

        $this->_render($view_data);
    }

    /**
     * Admin list point exchange
     *
     * @param int $user_id
     */
    public function exchange($user_id = null)
    {
        // Check permission
        if (!$this->current_user->has_permission('RABIPOINT_EXCHANGE_LIST')) {
            return redirect('/');
        }

        $view_data = [
            'menu_active' => 'li_user'
        ];

        // Get user detail
        $user = $this->_api('user')->get_detail([
            'id'=> $user_id,
            'get_all' => TRUE
        ]);

        if (!isset($user['result'])) {
            return redirect('user/search');
        }

        $pagination = [
            'page' => (int) $this->input->get_or_default('p', 1),
            'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
        ];

        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];


        // Get point expired
        $point_exchange = $this->_api('point_exchange')->get_list([
            'user_id' => $user_id,
            'status' => array_keys($this->point_exchange_model->get_all_status()),
            'limit' => $pagination['limit'],
            'offset' => $pagination['offset']
        ]);

        $pagination['search'] = $this->input->get();

        $view_data['user'] = $user['result'];
        $view_data['point_exchange'] = !empty($point_exchange['result']['items']) ? $point_exchange['result']['items'] : [];

        // add pagination for expired point
        $view_data['pagination'] = $pagination;
        $view_data['pagination']['total'] = !empty($point_exchange['result']['total']) ? $point_exchange['result']['total'] : 0;

        // user rabipoint detail
        $view_data['user_rabipoint'] = $this->_internal_api('user_rabipoint', 'get_detail', [
            'user_id' => $user_id
        ]);

        $this->_render($view_data);
    }

    /**
     * Add point to user
     */
    public function add_point()
    {
        // Check permission
        if (!$this->current_user->has_permission('RABIPOINT_CREATE')) {
            return $this->_render_404();
        }

        if ($this->input->is_post()) {
            // Get point detail
            $res = $this->_api('user_rabipoint')->create_rp_admin($this->input->post());

            if ($res['submit']) {
                $this->_flash_message('ラビポイントを付与しました');
                return redirect('/rabipoint/'.$this->input->post('user_id'));
            }
        }
    }
}
