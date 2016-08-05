<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Team Controller
 *
 * @author DiepHQ
 */
class Team extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_is_student');
    }

    /**
     * Index Spec TM-010
     *
     * Get list group friend
     */
    public function index()
    {
        $res = $this->_internal_api('user_group', 'get_list', [
            'user_id' => (int) $this->current_user->id,
            'group_type' => 'friend'
        ]);

        $view_data = [
            'list_groups' => $res['items'],
            'operator_primary_type' => $this->current_user->primary_type,
        ];

        if ($res['total'] >= DEFAULT_MAX_TEAM) {
            $view_data['total_team'] = TRUE;
        }

        $this->_render($view_data);
    }

    /**
     * Index Spec TM-015
     *
     * Create new group friend
     */
    public function create()
    {
        $view_data = [];

        $res = $this->_internal_api('user_group', 'get_list', [
            'user_id' => (int) $this->current_user->id,
            'group_type' => 'friend'
        ]);

        if ($res['total'] >= DEFAULT_MAX_TEAM) {
            return redirect('team');
        }

        if($this->input->is_post()) {

            $res = $this->_api('group')->create([
                'group_name' => $this->input->post('group_name'),
                'primary_type' => 'friend'
            ]);

            if(isset($res['result'])) {
                $this->_api('user_group')->add_member([
                    'group_id' => $res['result']['group_id'],
                    'user_id' => (int) $this->current_user->id,
                    'role' => 'owner'
                ]);

                return redirect('/team/'.$res['result']['group_id'].'/invite_friend');

            } else {
                $view_data = [
                    'form_errors' => isset($res['invalid_fields']) ? 'チーム名欄は必須入力です' : [],
                    'post' => $this->input->post()
                ];
            }
        }

        $this->_render($view_data);
    }

    /**
     * Index Spec TM-050
     *
     * Menu management
     *
     * @param int $group_id
     */
    public function menu($group_id)
    {
        $view_data = [
            'maximum' => FALSE,
            'not_delete' => FALSE
        ];

        // Get list members in group
        $res =  $this->_api('user_group')->get_list_members([
            'group_id' => $group_id
        ]);

        // Check current user in group
        $status = FALSE;

        foreach ($res['result']['users'] as $key => $value) {
            if ($value['id'] == $this->current_user->id) {
                $status = TRUE;
            }
        }

        if (!$status) {
            return redirect('team');
        }

        $students = $this->_api('user_group')->get_list_members([
            'group_id' => $group_id
        ]);

        if(count($students['result']['users']) >= DEFAULT_MAX_USER_IN_TEAM) {
            $view_data['maximum'] = TRUE;
        }

        // Get group detail
        $group_detail = $this->_api('group')->get_detail(['group_id' => $group_id]);

        $this->_render(array_merge($view_data, [
            'group_id' => $group_id,
            'group_name' => $group_detail['result']['group_name']
        ]));
    }

    /**
     * Index Spec TM-020
     *
     * Choose friend to invite
     *
     * @param int $group_id
     */
    public function invite_friend($group_id)
    {
        // get group by id
        $res = $this->_api('group')->get_detail([
            'group_id' => $group_id
        ]);

        if (isset($res['result'])) {
            $view_data['group'] = $res['result'];
            $view_data['group_id'] = $group_id;
        } else {
            return redirect('team');
        }

        // Get list friends of current user
        $res = $this->_api('user_friend')->get_list([
            'user_id'   => $this->current_user->id,
            'group_id'  => $group_id
        ]);

        if (!empty($this->input->is_post())) {

            if ($this->input->post('user_id')) {

                // Invite user friend
                foreach ($this->input->post('user_id') as $user_id) {

                    $this->_api('user_group')->invite_friend([
                        'group_id' => $group_id,
                        'user_id' => $user_id
                    ]);
                }

                $this->_flash_message('チームへの招待メッセージを送信しました');
                return redirect('team');
                
            } else {
                $view_data ['errmsg'] = '友達を選択してください';
            }
        }

        $view_data['list_friends'] = $res['result']['items'];

        $this->_render($view_data);
    }

    /**
     * Index Spec TM-030
     *
     * Check the team invitation
     *
     * @param int $group_id
     */
    public function add_member($group_id)
    {

        if ($this->session->userdata('add_team') && $this->session->userdata['add_team']['user_id'] != $this->current_user->id) {

            $view_data = [];

            // Get user detail
            $invite_user = $this->_api('user')->get_detail(['id' => $this->session->userdata['add_team']['user_id']]);

            $view_data['invite_user'] = $invite_user['result'];

            $students = $this->_api('user_group')->get_list_members([
                'group_id' => $group_id
            ]);

            if($students['result']) {
                $view_data['students'] = $students['result']['users'];

                // check current user have in groups
                $joined = FALSE;
                foreach ($students['result']['users'] as $user) {
                    if ($user['id'] == $this->current_user->id) {
                        $joined = TRUE;
                        $this->_flash_message('すでにこのチームに参加しています');
                    }
                }
                $view_data['joined'] = $joined;

                // check total members in a group
                $full = FALSE;
                if(count($students['result']['users']) >= DEFAULT_MAX_USER_IN_TEAM) {
                    $full = TRUE;
                }
                $view_data['full'] = $full;
            }

            $group_detail = $this->_api('group')->get_detail(['group_id' => $group_id]);

            // Join to team
            if($this->input->is_post()) {

                // join to group
                $res = $this->_api('user_group')->add_member([
                    'group_id' => $this->session->userdata['add_team']['group_id'],
                    'user_id' => $this->current_user->_operator_id(),
                    'role' => 'member'
                ]);

                if($res['submit']) {

                    $this->_api('user_rabipoint')->create_rp([
                        'user_id' => $this->session->userdata['add_team']['user_id'],
                        'type' => 'invite_team'
                    ]);

                    $owners = $this->_api('user_group')->get_user_owner([
                        'group_id' => $this->session->userdata['add_team']['group_id']
                    ]);

                    foreach ($owners['result']['users'] as $owner) {
                        $this->_api('user_rabipoint')->create_rp([
                            'user_id' => $owner->id,
                            'type' => 'create_team'
                        ]);
                    }

                    $this->session->unset_userdata('add_team');
                    $this->_flash_message('チームに参加しました');
                    $this->session->set_flashdata('get_point', $res['result']['point']);
                    return redirect('/profile/detail');
                }

                $view_data ['errmsg'] = $res['errmsg'];
            }

            $view_data['group_id'] = $group_id;
            $view_data['group_name'] = $group_detail['result']['group_name'];
            $view_data['created_at'] = $group_detail['result']['created_at'];

            $this->_render($view_data);

        } else {
            return redirect('/team');
        }

    }

    /**
     * Index Spec TM-060
     *
     * Update team name
     *
     * @param int
     */
    public function update($group_id)
    {
        $view_data = [];

        // Get list members in group
        $res =  $this->_api('user_group')->get_list_members([
            'group_id' => $group_id
        ]);

        // Check current user in group
        $status = FALSE;

        foreach ($res['result']['users'] as $key => $value) {
            if ($value['id'] == $this->current_user->id) {
                $status = TRUE;
            }
        }

        if (!$status) {
            return redirect('team');
        }

        if($this->input->is_post()) {

            $res = $this->_api('group')->update([
                'group_id' => $group_id,
                'group_name' => $this->input->post('group_name')
            ]);
            if(isset($res['result'])) {
                $this->_flash_message('チーム名を更新しました');
                return redirect('team');
            } else {
                $view_data = [
                    'form_errors' => isset($res['invalid_fields']) ? $res['invalid_fields'] : []
                ];
            }
        }

        // Get group detail
        $group = $this->_api('group')->get_detail([
            'group_id' => $group_id
        ]);

        $view_data['group_id'] = $group_id;
        $view_data['group_name'] = $group['result']['group_name'];

        $this->_render($view_data);
    }

    /**
     * Index Spec TM-040
     *
     * Invite new user to team
     *
     * @param int $group_id
     */
    public function invite_new_user($group_id)
    {
        $view_data = [];

        if($this->input->is_post()) {

            $res = $this->_api('user_group')->invite_new_user( [
                'group_id' => $group_id,
                'email' => $this->input->post('email'),
                'uri' => 'friend/add_confirm&from=TM12'
            ]);

            // Check error form
            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];

            if($res['submit']) {
                $this->_flash_message('招待メールを送信しました');
            } else {
                $view_data['err_msg'] = $res['errmsg'];
            }
        }

        // Get group detail
        $group = $this->_api('group')->get_detail([
            'group_id' => $group_id
        ]);

        $view_data['group_name'] = isset($group['result']) ? $group['result']['group_name'] : null;
        $view_data['group_id'] = $group_id;

        $this->_render($view_data);
    }

    /**
     * Index Spec TM-070
     *
     * Leave from team
     *
     * @param int $group_id
     */
    public function leave($group_id)
    {
        $view_data = [];

        // Get list members in group
        $res =  $this->_api('user_group')->get_list_members([
            'group_id' => $group_id
        ]);

        // Check current user in group
        $status = FALSE;

        foreach ($res['result']['users'] as $key => $value) {
            if ($value['id'] == $this->current_user->id) {
                $status = TRUE;
            }
        }

        if (!$status) {
            return redirect('team');
        }

        if ($this->input->is_post()) {
            $res = $this->_api('user_group')->remove_member([
                'group_id' => $group_id,
                'user_id'  => $this->current_user->id
            ]);

            if ($res['submit']) {
                $this->_flash_message('チームから退会しました');
                return redirect('team/'.$group_id);
            } else {
                $view_data['err_msg'] = $res['errmsg'];
            }
        }
        $this->_render($view_data);
    }
}