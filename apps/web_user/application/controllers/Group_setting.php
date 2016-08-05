<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Group setting controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class Group_setting extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_group_permission', ['except' => ['family', 'add_family_group', 'recommend_student_confirm']]);
        $this->_before_filter('_is_parent', ['except' => ['family']]);
    }

    /**
     * Add student page Spec CA-10
     */
    public function add_student($group_id)
    {
        $view_data = [];

        // Add a new student by register
        if($this->input->get('register')) {
            $this->session->set_userdata('add_student_by_register', $group_id);
            redirect('register/student');
            return;
        }

        // Search student
        if($this->input->is_post()) {
            $res = $this->_api('user')->search( [
                'id' => $this->input->post('id'),
                'search_type' => 'group'
            ]);
            // Check error form
            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];

            if(!empty($res['result'])) {
                $this->session->set_userdata('add_student', $res['result']);
                redirect('group_setting/'.$group_id.'/add_student/confirm/'.$res['result']['id']);
                return;
            }

            // Show error message
            $view_data['post'] = $this->input->post();

            if(!empty($res['errmsg'])) {
                $view_data['err_msg'] = $res['errmsg'];
            }
        }
        $view_data['group_id'] = $group_id;
        $view_data['group_name'] = $this->get_group_detail($group_id)['group_name'];

        $this->_render($view_data);
    }

    /**
     * Add student confirm page Spec CA-20
     */
    public function add_student_confirm($group_id, $user_id)
    {
        if($this->session->userdata['add_student']) {

            if($this->input->is_post()) {
                // Join student in group with the role of member
                $res = $this->_api('user_group')->add_member([
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'role' => 'member'
                ]);

                if ($this->session->userdata['add_student']['email_verified'] == 1 && $this->session->userdata['add_student']['status'] == 'active') {
                    $this->session->set_userdata('switch_student_id', $user_id);
                }

                if($res['submit']) {

                    // Students become friend automatically
                    $members = $res = $this->_api('user_group')->get_list_members([
                        'group_id' => $group_id
                    ]);

                    foreach ($members['result']['users'] as $member) {
                        if ($member['primary_type'] == 'student') {

                            $this->_api('user_friend')->create([
                                'user_id' => $user_id,
                                'target_id' => $member['id']
                            ]);
                        }
                    }
                    $this->session->unset_userdata('add_student_by_register');
                    redirect('group_setting/'.$group_id.'/add_student/complete/'.$user_id);
                    return;
                }
            }
            $view_data['student'] = $this->session->userdata['add_student'];
            $view_data['group_name'] = $this->get_group_detail($group_id)['group_name'];
            $view_data['group_id'] = $group_id;
            $this->_render($view_data);
        } else {
            redirect('group_setting/family');
            return;
        }

    }

    /**
     * Add student complete page Spec CA-30
     */
    public function add_student_complete($group_id, $user_id)
    {
        $view_data = [];
        if($this->input->get('clearsession') == TRUE) {
            $this->session->unset_userdata('add_student_by_register');
            $this->session->unset_userdata('add_student');
            redirect('group_setting/'.$group_id.'/student');
            return;
        }

        if(isset($this->session->userdata['add_student_by_register']) || isset($this->session->userdata['add_student'])) {
            $view_data ['student'] = isset($this->session->userdata['add_student']) ? $this->session->userdata['add_student'] : $this->session->userdata['add_student_by_register'];
            $view_data['group_name'] = $this->get_group_detail($group_id)['group_name'];
            $view_data['show'] = isset($this->session->userdata['add_student_by_register']) ? 1 : 0;
            $this->_render($view_data);
        } else {
            redirect('group_setting/family');
            return;
        }
    }

    /**
     * Delete student page Spec CD-10
     */
    public function delete_student($group_id)
    {

        $view_data = [];
        if($this->input->is_post()) {
            $this->session->set_userdata('remove_member', $this->input->post());
            redirect('group_setting/'.$group_id.'/delete_student/confirm/'.$this->input->post('member_id'));
            return;
        }

        if($group_id) {
            $res = $this->_api('user_group')->get_list_members([
                'group_id' => $group_id
            ]);

            if($res['result']) {
                $view_data['members'] = $res['result']['users'];
            }
            $view_data['group_name'] = $this->get_group_detail($group_id)['group_name'];
            $view_data['group_id'] = $group_id;
        }

        $this->_render($view_data);
    }

    /**
     * Delete student confirm page Spec CD-20
     */
    public function delete_student_confirm($group_id, $user_id)
    {
        if($this->session->userdata['remove_member']) {
            $view_data = [];
            if($this->input->is_post()) {
                $res = $this->_api('user')->suspend_user( [
                    'user_id' => $user_id
                ]);
                if($res['submit']) {
                    if ($this->session->userdata['switch_student_id'] == $user_id) {

                        if (!empty($this->students)) {
                            foreach ($this->students as $student) {
                                if ($student['id'] != $user_id && $student['primary_type'] == 'student' && $student['email_verified'] == 1 && $student['status'] == 'active') {
                                    $this->session->set_userdata('switch_student_id', $student['id']);
                                } else {
                                    $this->session->unset_userdata('switch_student_id');
                                }
                            }
                        }

                    }
                    $this->session->unset_userdata('remove_member');
                    $this->_flash_message('アカウントを削除しました');
                    redirect('group_setting/'.$group_id.'/delete_student');
                    return;
                }
            }
            $this->_render([
                'remove_member' =>$this->session->userdata['remove_member'],
                'group_name' => $this->get_group_detail($group_id)['group_name'],
                'group_id' => $group_id
            ]);
        } else {
            redirect('group_setting/family');
        }
    }

    /**
     * Student manage page Spec CH-10
     */
    public function student($group_id = null)
    {

        $view_data = [
            'maximum' => FALSE,
            'not_delete' => FALSE
        ];

        $students = $this->_api('user_group')->get_list_members([
            'group_id' => $group_id
        ]);

        if(count($students['result']['users']) >= DEFAULT_MAX_USER_IN_GROUP) {
            $view_data['maximum'] = TRUE;
        }

        if (count($students['result']['users']) <= 1) {
            $view_data['not_delete'] = TRUE;
        }

        $this->_render(array_merge($view_data, [
            'group_id' => $group_id,
            'group_name' => $this->get_group_detail($group_id)['group_name']
        ]));
    }

    /**
     * Recommend student page Spec CR-10
     */
    public function recommend_student($group_id)
    {
        $view_data = [];
        if($this->input->is_post()) {

            $res = $this->_api('user_group')->invite( [
                'group_id' => $group_id,
                'email' => $this->input->post('email'),
                'uri' => 'group_setting/recommend_student_confirm&from=CR10'
            ]);

            // Check error form
            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];

            if($res['submit']) {
                $this->_flash_message('招待メールを送信しました');
                redirect('group_setting/'.$group_id.'/student');
                return;
            }
        }
        $view_data['group_name'] = $this->get_group_detail($group_id)['group_name'];
        $view_data['group_id'] = $group_id;
        $this->_render($view_data);
    }

    /**
     * Recommend student sent page Spec CR-20
     */
    public function recommend_student_sent($group_id)
    {
        $view_data['group_name'] = $this->get_group_detail($group_id)['group_name'];
        $this->_render($view_data);
    }

    /**
     * Recommend student confirm page Spec CR-30
     */
    public function recommend_student_confirm($group_id)
    {
        // Confirm by parent who was invited in CR-20
        if(isset($this->session->userdata['recommend_parent']['group_id'])) {

            $view_data = [];

            $students = $this->_api('user_group')->get_list_members([
                'group_id' => $this->session->userdata['recommend_parent']['group_id']
            ]);

            if($students['result']) {
                $view_data['students'] = $students['result']['users'];
            }

            $invite_user = $this->_api('user')->get_detail([
                'id' => $this->session->userdata['recommend_parent']['user_id']
            ]);

            $view_data['invite_user'] = $invite_user['result'];

            $view_data['already_added'] = FALSE;
            foreach($students['result']['users'] as $student) {
                if(isset($student['id']) && $student['id'] == $this->current_user->_operator_id()) {
                    $view_data['already_added'] = TRUE;
                }
            }

            if($this->input->is_post()) {

                $res = $this->_api('user_group')->add_member([
                    'group_id' => $this->session->userdata['recommend_parent']['group_id'],
                    'user_id' => $this->current_user->_operator_id(),
                    'role' => 'member'
                ]);

                foreach($students['result']['users'] as $student) {
                    if ($student['primary_type'] == 'student' && $student['email_verified'] == 1 && $student['status'] == 'active') {
                        $this->session->set_userdata('switch_student_id', $student['id']);
                    }
                }

                if($res['submit']) {
                    $group_id_redirect = $this->session->userdata['recommend_parent']['group_id'];
                    $this->session->unset_userdata('recommend_parent');
                    $this->_flash_message('家族グループに参加しました');
                    redirect('group_setting/'.$group_id_redirect.'/student');
                    return;
                }
                $view_data ['errmsg'] = $res['errmsg'];
            }

            $group_detail = $this->get_group_detail($group_id);

            $view_data['group_id'] = $group_id;
            $view_data['group_name'] = $group_detail['group_name'];
            $view_data['created_at'] = $group_detail['created_at'];
            $this->_render($view_data);
        } else {
            redirect('group_setting/family');
        }

    }

    /**
     * List family group Spec CF-10
     */
    public function family()
    {

        $res = $this->_internal_api('user_group', 'get_list', [
            'user_id' => (int) $this->current_user->id,
            'group_type' => 'family'
        ]);

        $view_data = [
            'list_groups' => $res['items'],
            'operator_primary_type' => $this->current_user->primary_type,
        ];

        $this->_render($view_data);
    }

    /**
     * Create new family group Spec CF-20
     */
    public function add_family_group() {

        $view_data = [];

        if($this->input->is_post()) {

            $res = $this->_api('group')->create([
                'group_name' => $this->input->post('group_name'),
                'primary_type' => 'family'
            ]);

            if(isset($res['result'])) {

                $this->_api('user_group')->add_member([
                    'group_id' => $res['result']['group_id'],
                    'user_id' => (int) $this->current_user->id,
                    'role' => 'owner'
                ]);

                $redirect = $this->current_user->primary_type == 'parent' ? 'group_setting/'.$res['result']['group_id'].'/student/' : 'group_setting/'.$res['result']['group_id'].'/add_parent';
                redirect($redirect);
            } else {
                $view_data = [
                    'form_errors' => isset($res['invalid_fields']) ? $res['invalid_fields'] : [],
                    'post' => $this->input->post()
                ];
            }
        }
        $this->_render($view_data);

    }

    /**
     * Update family group name Spec CF-20
     */
    public function update_name($group_id) {

        $view_data = [];

        if($this->input->is_post()) {

            $res = $this->_api('group')->update([
                'group_id' => $group_id,
                'group_name' => $this->input->post('group_name')
            ]);
            if(isset($res['result'])) {
                redirect('group_setting/'.$group_id.'/student');
            } else {
                $view_data = [
                    'form_errors' => isset($res['invalid_fields']) ? $res['invalid_fields'] : []
                ];
            }
        }
        $view_data['group_id'] = $group_id;

        $group = $this->_api('group')->get_detail([
            'group_id' => $group_id
        ]);

        $view_data['group_name'] = $group['result']['group_name'];
        $this->_render($view_data);

    }

    public function add_team_group() {
        $this->_render();
    }

    public function add_team_member() {
        $this->_render();
    }

    private function get_group_detail($group_id) {

        $res = $this->_api('group')->get_detail([
            'group_id' => $group_id
        ]);

        return $res['result'];
    }
}