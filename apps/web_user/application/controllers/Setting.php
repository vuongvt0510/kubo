<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Setting controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class Setting extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['withdraw_complete']
        ]);
    }

    /**
     * User setting page Spec ST-10
     */
    public function index()
    {
        $groups = $this->_api('user_group')->get_list([
            'user_id' => $this->current_user->id,
            'group_type' => 'family'
        ]);

        $students_not_verified =[];
        if ($groups['result']) {
            foreach ($groups['result']['items'] AS $group) {

                foreach ($group['members'] AS $member) {

                    if (!$member['email_verified'] && $member['primary_type'] == 'student') {

                        $students_not_verified[$member['user_id']] = $member;
                    }
                }
            }
        }
        $res = $this->_api('user')->get_detail([
            'id' => $this->current_user->id
        ]);

        $this->_render([
            'user' => $res['result'],
            'students_not_verified' => $students_not_verified
        ]);
    }

    /**
     * User setting avatar page Spec ST-20
     */
    public function avatar()
    {
        $view_data = [
            'form_errors' => []
        ];

        $this->load->helper('directory');

        $files = directory_map(APPPATH .'../public_html/images/avatar');

        foreach ($files AS $key => $file) {

            $file_part = explode('.', $file);

            if (!is_numeric($file_part[0])) {
                unset($files[$key]);
            }
        }

        $view_data['number_avatar'] = count($files);
        
        // Check input data
        if ($this->input->is_post()) {

            // Call API to register for parent
            $res = $this->_api('user')->update([
                'id' => $this->current_user->id,
                'avatar_id' => $this->input->post('avatar')
            ]);

            if (isset($res['result'])) {
                $this->session->set_flashdata('get_trophy', $res['result']['trophy']);
                $this->session->set_flashdata('get_point', $res['result']['point']);
                redirect('setting');
                return;
            }

            $view_data['form_errors'] = $res['invalid_fields'];
        }

        $res = $this->get_current_user_detail();

        $view_data['current_avatar'] = $res['result']['avatar'];

        $this->_render($view_data);
    }

    /**
     * Updating user profile page Spec ST-40
     */
    public function profile()
    {
        $view_data = [
            'form_errors' => []
        ];
        // Check input data
        if ($this->input->is_post()) {
            $gender = '';

            if (!empty($this->input->post('sex'))) {
                $gender = $this->input->post('sex');
            }

            if(mb_strlen($this->input->post('nickname')) <= 10) {
                // Call API to register for parent
                $res = $this->_api('user')->update_profile([
                    'id' => $this->current_user->id,
                    'nickname' => $this->input->post('nickname'),
                    'sex' => $gender,
                ]);
            } else {
                $view_data['form_errors'] = ['nickname' => 'ニックネーム欄は10文字以内で入力してください'];
            }

            if (isset($res['result'])) {
                $this->session->set_flashdata('get_trophy', $res['result']['trophy']);
                $this->session->set_flashdata('get_point', $res['result']['point']);
                redirect('setting');
                return;
            } elseif (isset($res)) {
                // Show error if form is incorrect
                $view_data['form_errors'] = $res['invalid_fields'];
            }
            $view_data['post'] = $this->input->post();

        }

        $user_res = $this->get_current_user_detail();

        $view_data['user'] = $user_res['result'];

        $this->_render($view_data);
    }

    public function withdraw()
    {
        $view_data = [
            'form_errors' => []
        ];

        $remaining_coin = $this->_api('coin')->get_user_coin([
            'user_id' => $this->current_user->id
        ]);

        $view_data['current_coin'] = $remaining_coin['result']['current_coin'];
        if ($this->input->is_post()) {
            foreach ($this->current_user->in_group as $group) {
                $group_members = $this->_api('user_group')->get_list_members(['group_id' => $group]);
                $change_owner = FALSE;
                $parent_in_group = FALSE;
                foreach ($group_members['result']['users'] as $user) {
                    if ($user['role'] == 'owner' && $user['id'] == $this->current_user->id) {
                        $change_owner = TRUE;
                    }

                    if ($user['primary_type'] == 'parent' && $user['id'] != $this->current_user->id) {
                        $parent_in_group = TRUE;
                    }
                }
                if (count($group_members['result']['users']) > 1 && $change_owner == TRUE) {
                    $this->_api('user_group')->add_member([
                        'group_id' => $group,
                        'user_id' => $this->current_user->id,
                        'role' =>'member'
                    ]);
                    foreach ($group_members['result']['users'] as $user) {
                        if ($parent_in_group == TRUE && $user['primary_type'] == 'parent' && $user['id'] != $this->current_user->id) {
                            $res = $this->_api('user_group')->add_member([
                                'group_id' => $group,
                                'user_id' => $user['id'],
                                'role' =>'owner'
                            ]);
                            break 1;
                        }

                        if ($parent_in_group == FALSE && $user['id'] != $this->current_user->id) {
                            $res = $this->_api('user_group')->add_member([
                                'group_id' => $group,
                                'user_id' => $user['id'],
                                'role' =>'owner'
                            ]);
                            break 1;
                        }
                    }
                }
            }
            $res = $this->_api('user')->withdraw($this->input->post());

            if($res['submit'] == TRUE) {
                redirect('/setting/withdraw_complete');
                return;
            }
            $view_data['form_errors'] = $res['invalid_fields'];
        }
        $this->_render($view_data);
    }


    public function withdraw_complete()
    {
        $this->_render();
    }

    /**
     * Change user email page Spec ML-10
     */
    public function change_email()
    {
        if ($this->input->get('email')) {
            $res = $this->_api('user')->update_email([
                'id' => $this->current_user->_operator_id(),
                'email' => $this->input->get('email')
            ]);
            if ($res['submit']) {
                $this->_flash_message('メールアドレスを変更しました');
                redirect('setting');
                return;
            }
        }

        $view_data = [
            'form_errors' => []
        ];


        // Check input data
        if ($this->input->is_post()) {
            // Call API to send verify email
            $res = $this->_api('user')->send_verify_email([
                'user_id' => $this->current_user->id,
                'email_type' => 'change_email',
                'email' => $this->input->post('email'),
            ]);
            if ($res['submit']) {
                redirect('setting/change_email_sent');
            }

            // Show error if form is incorrect
            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();
        }
        $user_res = $this->get_current_user_detail();
        $view_data['post'] = $user_res['result'];

        $this->_render($view_data);
    }

    /**
     * Change user email page Spec ML-10
     */
    public function change_email_sent()
    {
        $this->_render();
    }

    /**
     * Change user password page Spec PS-10
     */
    public function change_password()
    {
        $view_data = [
            'form_errors' => []
        ];
        // Check input data
        if ($this->input->is_post()) {

            // Call API to register for parent
            $res = $this->_api('user')->change_password($this->input->post());

            if ($res['success'] && !isset($res['invalid_fields'])) {
                $this->_flash_message('パスワードを変更しました');
                redirect('setting');
                return;
            }
            // Show error if form is incorrect
            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();
        }

        $this->_render($view_data);
    }

//    /**
//     * Change user password page Spec PS-10
//     */
//    public function change_password_sent()
//    {
//        $this->_render();
//    }

    /**
     * Get current user detail
     */
    public function get_current_user_detail()
    {
        $user_res = $this->_api('user')->get_detail([
            'id' => $this->current_user->id
        ]);
        return $user_res;
    }
}