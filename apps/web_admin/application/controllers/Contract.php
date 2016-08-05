<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Contract extends Application_controller
{

    /**
     * Contract constructor.
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Show contract monthly payment history of user
     *
     * @param null $user_id
     */
    public function history($user_id = null)
    {
        $view_data = [
            'menu_active' => 'li_user'
        ];

        // get user detail
        $user = $this->_api('user')->get_detail([
            'id'=> $user_id,
            'get_all' => TRUE
        ]);

        if (!isset($user['result'])) {
            return redirect('user/search');
        }

        $view_data['user'] = $user['result'];


        $pagination = [
            'page' => (int) $this->input->get_or_default('p', 1),
            'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
        ];

        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

        $res = $this->_api('user_contract')->get_history_list([
            'user_id' => (int) $user_id,
            'limit' => $pagination['limit'],
            'offset' => $pagination['offset']
        ]);

        if (isset($res['result'])) {
            $view_data['items'] = $res['result']['items'];
            $pagination['total'] = $res['result']['total'];
        }
        $view_data['search'] = $pagination['search'] = $this->input->get();
        $view_data['pagination'] = $pagination;

        $this->_breadcrumb = [
            [
                'link' => '/contract/history/' . $user_id,
                'name' => '月額会員決済履歴'
            ]
        ];

        return $this->_render($view_data);
    }
}
