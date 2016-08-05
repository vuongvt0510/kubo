<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class User_group extends Application_controller
{
    /**
     * Spec GA10
     *
     * Search user to add to a group
     *
     * @param int $group_id
     */
    public function group_detail($group_id = null)
    {
        if (empty($group_id)) {
            return redirect();
        }

        if (!$this->current_user->has_permission('USER_UPDATE_GROUP')) {
            return redirect();
        }

        $view_data = [
            'menu_active' => 'li_user'
        ];

        // Get group detail
        $group = $this->_api('group')->get_detail([
            'group_id' => $group_id
        ]);

        if (!isset($group['result'])) {
            return $this->_render_404();
        }

        // Get list group members
        $members = $this->_api('user_group')->get_list_members([
            'group_id' => $group_id
        ]);

        $pagination = [
            'page' => (int) $this->input->get_or_default('p', 1),
            'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
        ];

        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];
        
        $view_data['search'] = $pagination['search'] = $this->input->get();

        // Get list users
        $users = $this->_api('user')->search_list([
            'id' => $this->input->get_or_default('user_id', ''),
            'email' => $this->input->get_or_default('email', ''),
            'nickname' => $this->input->get_or_default('nickname', ''),
            'login_id' => $this->input->get_or_default('login_id', ''),
            'group_id' => $group_id,
            'group_type' => $group['result']['type'],
            'sort_position' => 'asc',
            'limit' => $pagination['limit'],
            'offset' => $pagination['offset']
        ]);

        if (isset($users['result'])) {
            $view_data['users'] = $users['result']['items'];
            $pagination['total'] = $users['result']['total'];
        }

        $view_data['group'] = $group['result'];
        $view_data['group_id'] = $group_id;
        $view_data['members'] = isset($members['result']) ? $members['result']['users'] : [];
        $view_data['rowspan'] = isset($members['result']) ? count($members['result']['users']) + 1 : 0;
        $view_data['pagination'] = $pagination;

        $this->_breadcrumb = [
            [
                'link' => '/user_group/group_detail',
                'name' => 'グループにユーザーを追加'
            ]
        ];

        return $this->_render($view_data);
    }

    /**
     * Spec GA20
     *
     * View detail user
     *
     * @param int $group_id
     * @param int $user_id
     */
    public function user_detail($group_id = null, $user_id = null)
    {
        if (!$this->current_user->has_permission('USER_UPDATE_GROUP')) {
            return redirect();
        }

        $prefecture = $this->_api('prefecture')->get_list();

        // Get info user
        $user = $this->_api('user')->get_detail([
            'id' => $user_id,
            'get_all' => TRUE
        ]);

        // Get promotion code
        $code = $this->_api('promotion')->get_detail([
            'user_id' => $user_id
        ]);

        // get list group family
        $family = $this->_api('user_group')->get_list([
            'user_id' => $user_id,
            'group_type' => 'family'
        ]);

        // get list group friend
        $friend = $this->_api('user_group')->get_list([
            'user_id' => $user_id,
            'group_type' => 'friend'
        ]);

        // Get group detail
        $group = $this->_api('group')->get_detail([
            'group_id' => $group_id
        ]);

        // Get list group members
        $members = $this->_api('user_group')->get_list_members([
            'group_id' => $group_id
        ]);

        if ($group['result']['type'] == 'friend' && $user['result']['primary_type'] == 'parent') {
           $add_team = TRUE;
        }

        // limit members
        if ($group['result']['type'] == 'friend') {
            // limit member group friend
            if(count($members['result']['users']) >= DEFAULT_MAX_USER_IN_TEAM) {
                $maximum = TRUE;
            }
        } else {
            // limit member group family
            if(count($members['result']['users']) >= DEFAULT_MAX_USER_IN_GROUP) {
                $maximum = TRUE;
            }
        }

        // Check current user in group
        foreach ($members['result']['users'] as $key => $value) {
            if ($value['id'] == $user_id) {
                $status = TRUE;
            }
        }

        $view_data = [
            'menu_active' => 'li_user',
            'promotion_code' => isset($code['result']) && isset($code['result']['promotion_code']) ? $code['result']['promotion_code'] : null,
            'user_id' => $user_id,
            'user_detail' => isset($user['result']) ? $user['result'] : [],
            'prefectures' =>  $prefecture['result']['items'],
            'family' => isset($family['result']) ? $family['result']['items'] : [],
            'friend' => isset($friend['result']) ? $friend['result']['items'] : [],
            'group' => isset($group['result']) ? $group['result'] : null,
            'members' => isset($members['result']) ? $members['result']['users'] : [],
            'rowspan' => isset($members['result']) ? count($members['result']['users']) + 1 : 0,
            'group_id' => $group_id,
            'status' => isset($status) ? $status : FALSE,
            'maximum' => isset($maximum) ? $maximum : FALSE,
            'add_team' => isset($add_team) ? $add_team : FALSE
        ];

        $this->_breadcrumb = [
            [
                'link' => '/user_group/user_detail',
                'name' => 'ユーザーの詳細'
            ]
        ];

        return $this->_render($view_data);
    }

    /**
     * Spec GA30
     *
     * View detail user
     */
    public function add_user_group()
    {
        if (!$this->current_user->has_permission('USER_UPDATE_GROUP')) {
            return redirect();
        }

        if ($this->input->is_post()) {
            $this->_api('user_group')->add_member([
                'group_id' => $this->input->post('group_id'),
                'user_id' => $this->input->post('user_id'),
                'role' => 'member'
            ]);
        }

        $user_id =  $this->session->userdata('add_user_group');
        $this->session->unset_userdata('add_user_group');

        $this->_flash_message('ユーザーをグループに追加しました');

        return redirect('user/detail/'.$user_id);
    }

    /**
     * Spec GD10
     *
     * Remove user from group
     */
    public function remove_user_group()
    {
        if (!$this->current_user->has_permission('USER_UPDATE_GROUP')) {
            return redirect();
        }

        if ($this->input->is_post()) {
            $this->_api('user_group')->remove_member([
                'group_id' => $this->input->post('group_id'),
                'user_id' => $this->input->post('user_id')
            ]);
        }

        $this->_flash_message('ユーザーをグループから削除しました');
        return redirect('user/detail/'.$this->input->post('user_site'));
    }

    /**
     * View detail user
     */
    public function user_group_remove()
    {
        if (!$this->current_user->has_permission('USER_UPDATE_GROUP')) {
            return redirect();
        }

        // Get user detail
        $user = $this->_api('user')->get_detail([
            'id' => (int) $this->input->post('user_id'),
            'get_all' => TRUE
        ]);

        // Get group detail
        $group = $this->_api('group')->get_detail([
            'group_id' => (int) $this->input->post('group_id')
        ]);

        // Get members of group
        $members = $this->_api('user_group')->get_list_members([
            'group_id' => (int) $this->input->post('group_id')
        ]);

        $view_data = [
            'user' => isset($user['result']) ? $user['result'] : null,
            'group_name' => isset($group['result']) ? $group['result']['group_name'] : null,
            'group_type' => isset($group['result']) ? $group['result']['type'] : null,
            'group_id' => $this->input->post('group_id'),
            'members' => isset($members['result']) ? $members['result']['users'] : []
        ];

        return $this->_true_json($view_data);
    }

}
