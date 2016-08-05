<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Admin extends Application_controller
{

    public function __construct()
    {
        parent::__construct();
        $this->_breadcrumb = [
            [
                'link' => '/admin',
                'name' => 'アカウント管理'
            ]
        ];
    }

    /*
     * list Acount admin
     */
    public function index()
    {

        $view_data = [
            'menu_active' => 'li_admin_user'
        ];

        $pagination = [
            'page' => (int) $this->input->get_or_default('p', 1),
            'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
        ];

        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

        // Call api to get list of admins
        $res = $this->_api('admin')->get_list($pagination);

        if (isset($res['result'])) {
            $view_data['admins']  =  $res['result']['items'];
        }

        $view_data['pagination'] = $pagination;

        $this->_render($view_data);

    }
    /*
     * Change Password for acount admin
     */
    public function change_password()
    {
        $view_data = [
            'form_errors' => [],
            'menu_active' => 'li_admin_user'
        ];
        if ($this->input->is_post()) {
            $res = $this->_api('admin')->change_password([
                'password'=> $this->input->post('password'),
                'confirm_password'=> $this->input->post('confirm_password')
            ]);

            if ($res['invalid_fields']) {
                $view_data['form_errors'] = $res['invalid_fields'];
            }
            else {
                $this->_flash_message('パスワードを変更しました');
                redirect('/admin');
            }
        }

        $this->_breadcrumb[] = [
            'name' => 'パスワード変更'
        ];
        $this->_render($view_data);
    }

    /**
     * Add account page
     */
    public function add()
    {

        if (!$this->current_user->has_permission('ADMIN_CREATE_ACCOUNT')) {
            return redirect('admin');
        }
        $role_name = $this->_api('admin')->get_role([]);

        $view_data = [
            'role' => $role_name,
            'post' => [],
            'form_errors' => [],
            'menu_active' => 'li_admin_user'
        ];

        if ($this->input->is_post()) {

            $res = $this->_api('admin')->create([
                'role_id' => $this->input->post('role_id'),
                'name' => $this->input->post('name'),
                'login_id' => $this->input->post('login_id'),
                'password' =>  $this->input->post('password'),
                'confirm_password' =>  $this->input->post('confirm_password')
            ]);

            if(isset($res['result'])) {

                $this->_flash_message('アカウントを作成しました');
                return redirect('admin');
            }

            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();
        }

        $this->_breadcrumb[] = [
            'name' => 'アカウント作成'
        ];
        $this->_render($view_data);
    }

    /**
     * Edit account admin page
     * @param int $id
     * @throws APP_Api_internal_call_exception
     */
    public function edit($id = null)
    {
        if (!$this->current_user->has_permission('ADMIN_EDIT_ACCOUNT') || !$id) {
            return redirect('admin');
        }
        $role_name = $this->_api('admin')->get_role([]);
        $view_data = [
            'post' => [],
            'role' => $role_name,
            'form_errors' => [],
            'menu_active' => 'li_admin_user'
        ];

        $admin = $this->_internal_api('admin', 'get_detail', [
            'id' => $id
        ]);

        if (empty($admin)) {
            return $this->_redirect('admin');
        }

        if ($this->input->is_post()) {
            $res = $this->_api('admin')->update([
                'id' => $id,
                'role_id' => $this->input->post('role_id'),
                'name' => $this->input->post('name') ? $this->input->post('name') : null
            ]);

            if (isset($res['result'])) {
                $this->_flash_message('アカウントを編集しました');
                return redirect('admin');
            }

            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();
        }

        $view_data['admin'] = $admin;


        $this->_breadcrumb[] = [
            'name' => 'アカウント編集'
        ];
        $this->_render($view_data);
    }

    /**
     * Delete admin account page
     */
    public function delete()
    {
        if (!$this->current_user->has_permission('ADMIN_DELETE_ACCOUNT')) {
            return redirect('admin');
        }

        if ($this->input->is_post()) {
            $res = $this->_api('admin')->delete([
                'id' => $this->input->post('id')
            ]);

            $this->_flash_message('アカウントを削除しました');
            redirect('admin');
        }
    }

}
