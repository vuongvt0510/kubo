<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH.'controllers/modules/APP_Facebook_authenticatable.php';
require_once APPPATH . 'controllers/Application_controller.php';

/**
 * User register controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class Register extends Application_controller
{
    /**
     * @var string Layout file
     */
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['re_send_email', 'index', 'parent', 'student', 'verify_email', 're_send_email_complete', 'complete']
        ]);

        $this->_before_filter('_logged_in', [
            'only' => ['index']
        ]);

        $this->_before_filter('_check_grade', [
            'except' => ['update_nickname']
        ]);

        $this->_before_filter('_is_parent',[
            'only' => ['student']
        ]);

        $this->_before_filter('_is_student',[
            'only' => ['parent']
        ]);
    }

    /**
     * Index Spec RG-10
     */
    public function index()
    {
        // If user is invited from other user, store token to session use for register
        $invitation_token = $this->input->get('token');
        if($invitation_token) {
            $this->session->set_userdata('invitation_token', $invitation_token);
        }

        // Remove user nick name
        $this->session->unset_userdata('user_nickname');

        // Add user nickname for student
        if(!empty($this->input->get('nickname'))) {
            $this->session->set_userdata('user_nickname', $this->input->get('nickname'));
        }

        $this->_render([
            'register_page' => TRUE,
            'meta' => [
                'title' => "スクールTVに無料会員登録",
                'description' => "無料会員登録すると、教科書を設定できます。全国の小学校・中学校の教科書に対応した動画で、授業の予習・復習をしよう！"
            ]
        ]);
    }

    /**
     * Parent register page Spec RG-20
     */
    public function parent()
    {
        $view_data = [
            'form_errors' => [],
            'parent_only' => $this->input->get('parent_only') == 1 ? 1 : 0
        ];

        $invitation_token = $this->session->userdata('invitation_token');
        $promotion_code = $this->session->userdata('promotion_code');
        $campaign_code = $this->session->userdata('campaign_code');

        // Check input data
        if ($this->input->is_post() || $this->session->userdata('oauth_facebook_id') || $this->session->userdata('oauth_twitter_id')) {

            $res = [];

            if (!$this->input->is_post()) {

                $oauth = [];
                if ($this->session->userdata('oauth_facebook_id')) {
                    $oauth = [
                        'oauth_type' => 'facebook',
                        'oauth_id' => $this->session->userdata('oauth_facebook_id')
                    ];
                }

                if ($this->session->userdata('oauth_twitter_id')) {
                    $oauth = [
                        'oauth_type' => 'twitter',
                        'oauth_id' => $this->session->userdata('oauth_twitter_id')
                    ];
                }

                $res = $this->_api('user')->oauth($oauth);

                if (isset($res['errcode']) && $res['errcode'] == Base_api::USER_IS_INACTIVE) {
                    $view_data['errmsg'] = sprintf('この%sアカウントはすでに使用されています', $oauth['oauth_type']);

                    $this->session->unset_userdata([
                        'oauth_'.$oauth['oauth_type'].'_id',
                        'oauth_'.$oauth['oauth_type'].'_access_token',
                        'oauth_'.$oauth['oauth_type'].'_email'
                    ]);

                }

            } else {
                // If is post
                // Call API to register for parent
                $user_data = [
                    'id' => $this->input->post('id'),
                    'password' => $this->input->post('password'),
                    'email' => $this->input->post('email'),
                    'promotion_code' => $this->input->post('promotion_code'),
                    'campaign_code' => $this->input->post('campaign_code'),
                    'type' => 'parent',
                    'invitation_token' => $invitation_token,
                    'force_remove_invite' => $this->input->get('parent_only') == 1
                        ? TRUE : FALSE
                ];

                // merge data with oauth facebook and twitter if user has connect
                if($this->session->userdata('oauth_facebook_id')) {
                    $user_data = array_merge($user_data, [
                        'oauth_facebook_id' => $this->session->userdata('oauth_facebook_id'),
                        'oauth_facebook_access_token' => $this->session->userdata('oauth_facebook_access_token'),
                    ]);
                }

                if($this->session->userdata('oauth_twitter_id')) {
                    $user_data = array_merge($user_data, [
                        'oauth_twitter_id' => $this->session->userdata('oauth_twitter_id'),
                        'oauth_twitter_access_token' => $this->session->userdata('oauth_twitter_access_token'),
                    ]);
                }

                $res = $this->_api('user')->register($user_data);
                //var_dump($res); die;
            }

            // Add relation with facebook or twitter account

            if (isset($res['result'])) {
                // Remove session unnecessary;

                $this->session->unset_userdata([
                    'invitation_token',
                    'promotion_code',
                    'campaign_code',
                    'oauth_facebook_id',
                    'oauth_twitter_id',
                    'oauth_facebook_access_token',
                    'oauth_twitter_access_token',
                    'oauth_facebook_email',
                    'oauth_twitter_email',
                ]);

                if (!$this->input->get('redirect')) {
                    // Send verify email to parent
                    $this->_api('user')->send_verify_email([
                        'user_id' => $res['result']['id'],
                        'email' => $res['result']['email'],
                        'email_type' => 'parent'
                    ]);
                }

                /** @var string $redirect url */
                $redirect = '';

                // Process redirect
                if($this->input->get('redirect')) {

                    $url_params = explode('?', urldecode($this->input->get('redirect')));
                    $redirect_params = explode('&', $url_params[0]);

                    $redirect = isset($redirect_params[0]) ? explode('=', $redirect_params[0])[1] : null;
                    /** @var string $from from key page */
                    $from = isset($redirect_params[1]) ? explode('=', $redirect_params[1])[1] : null;

                    $token = isset($url_params[1]) ? explode('=', $url_params[1])[1] : null;

                    if (!empty($token)) {
                        // Send verify email to parent
                        $this->_api('user')->send_verify_email([
                            'user_id' => $res['result']['id'],
                            'email' => $res['result']['email'],
                            'email_type' => 'parent',
                            'redirect_URL' => urlencode($this->input->get('redirect'))
                        ]);
                    }
                    $redirect = 'register/complete';

                } else {
                    // If is not exist 'redirect' GET key, follow redirect register page
                    $this->session->set_userdata('register_parent', [
                        'id' => $res['result']['id'],
                        'email' => $res['result']['email'],
                        'login_id' => $res['result']['login_id'],
                        'promotion_code' => $this->input->post('promotion_code'),
                        'campaign_code' => $this->input->post('campaign_code')
                    ]);

                    // If register together, set the invitation token for student
                    if($this->input->get('parent_only') != 1) {
                        $this->session->set_userdata('promotion_code', $promotion_code);
                        $this->session->set_userdata('campaign_code', $campaign_code);
                        $this->session->set_userdata('invitation_token', $invitation_token);
                    }

                    $redirect = $this->input->get('parent_only') == 1 ? 'register/complete' : 'register/student';
                }

                redirect($redirect);
                return;
            }

            $view_data['form_errors'] = !empty($res['invalid_fields']) ? $res['invalid_fields'] : [] ;
            $view_data['post'] = $this->input->post();
        }

        // Proccess view form
        $view_data['meta'] = [
            'title' => "スクールTVに無料会員登録",
            'description' => "保護者の方も登録してお子さまと一緒に学習できます。全国の小学校・中学校の教科書に対応した動画で無料で学習できます！"
        ];

        // Set session url redirect if user register by facebook or twitter
        $params = [];
        if ($this->input->get('parent_only') == 1) {
            $params[] = 'parent_only=1';
        }
        if ($this->input->get('redirect')) {
            $params[] = 'redirect=' . $this->input->get('redirect');
        }

        $oauth_redirect = sprintf('register/parent%s', !empty($params) ? '?'. implode('&', $params) : '' );

        $this->session->set_userdata('oauth_redirect_page', $oauth_redirect);

        // Set email from oauth
        $view_data['oauth_email'] = $this->session->userdata('oauth_facebook_email');
        if(!$view_data['oauth_email']) {
            $view_data['oauth_email'] = $this->session->userdata('oauth_twitter_email');
        }

        $view_data['oauth_email'] = $view_data['oauth_email'] ? $view_data['oauth_email'] : '';

        $view_data['register_page'] = TRUE;
        $view_data['promotion_code'] = $this->session->userdata('promotion_code');
        $view_data['campaign_code'] = $this->session->userdata('campaign_code');
        $view_data['invitation_token'] = $this->session->userdata('invitation_token');

        $this->_render($view_data);
    }

    /**
     * Student register page Spec RG-30
     */
    public function student()
    {
        $view_data = [
            'form_errors' => []
        ];

        // Check input data
        if ($this->input->is_post()) {

            $params_student = [
                'id' => $this->input->post('id'),
                'password' => $this->input->post('password'),
                'email' => $this->input->post('email'),
                'promotion_code' => $this->input->post('promotion_code'),
                'campaign_code' => $this->input->post('campaign_code'),
                'type' => 'student',
                'grade_id' => $this->input->post('grade_id'),
                'invitation_token' => $this->session->userdata('invitation_token'),
                'force_remove_invite' => TRUE
            ];

            if (isset($this->session->userdata['register_parent']['id'])) {
                $params_student['parent_id'] = $this->session->userdata['register_parent']['id'];
            }

            if(!empty($this->session->userdata('user_nickname')) && !empty($this->session->userdata('user_score'))) {
                $params_student = array_merge($params_student, ['nickname' => $this->session->userdata('user_nickname')]);
            }

            // Call API to register for student
            $student = $this->_api('user')->register($params_student);

            if (isset($student['result'])) {

                if ($this->input->cookie('user_video_cookie')) {

                    // Give rabipoint when student already watches introduction video
                    $this->_api('user_rabipoint')->create_rp([
                        'user_id' => $student['result']['id'],
                        'type' => 'watch_tutorial'
                    ]);
                }

                // Process redirect
                if($this->input->get('redirect')) {

                    $url_params = explode('?', urldecode($this->input->get('redirect')));
                    $redirect_params = explode('&', $url_params[0]);

                    $redirect = isset($redirect_params[0]) ? explode('=', $redirect_params[0])[1] : null;
                    /** @var string $from from key page */
                    $from = isset($redirect_params[1]) ? explode('=', $redirect_params[1])[1] : null;

                    $token = isset($url_params[1]) ? explode('=', $url_params[1])[1] : null;

                    if (!empty($token)) {
                        // Send verify email to parent
                        $this->_api('user')->send_verify_email([
                            'user_id' => $student['result']['id'],
                            'email' => $student['result']['email'],
                            'email_type' => 'student',
                            'redirect_URL' => urlencode($this->input->get('redirect'))
                        ]);
                    }
                    return redirect('register/complete');

                } else {

                    // Send verify email to student
                    $this->_api('user')->send_verify_email([
                        'user_id' => $student['result']['id'],
                        'email' => $student['result']['email'],
                        'email_type' => 'student'
                    ]);
                }

                // Add a new student to the group of existing parent (from CA-20)
                if($this->session->userdata['add_student_by_register']) {
                    $group_id = $this->session->userdata['add_student_by_register'];
                    // Join student in group with the role of member
                    $res = $this->_api('user_group')->add_member([
                        'group_id' => $group_id,
                        'user_id' => $student['result']['id'],
                        'role' => 'member'
                    ]);
                    $this->session->unset_userdata('add_student');


                    if($res['submit']) {

                        // Students become friend automatically
                        $members = $res = $this->_api('user_group')->get_list_members([
                            'group_id' => $group_id
                        ]);

                        foreach ($members['result']['users'] as $member) {
                            if ($member['primary_type'] == 'student') {

                                $this->_api('user_friend')->create([
                                    'user_id' => $student['result']['id'],
                                    'target_id' => $member['id']
                                ]);
                            }
                        }

                        $student['result']['password'] = $this->input->post('password');
                        $this->session->set_userdata('add_student_by_register', $student['result']);
                        redirect('group_setting/'.$group_id.'/add_student/complete/'.$student['result']['id']);
                        return;
                    }
                }

                $this->session->set_userdata('register_student', ['email' => $student['result']['email']]);

                // Create group if both student and parent register in the same time
                if (isset($this->session->userdata['register_parent']['id'])) {

                    $group = $this->_api('group')->create([
                        'primary_type' => 'family',
                        'group_name' => '家族グループ名未設定'
                    ]);

                    if ($group['result']) {

                        // Join parent in group with the role of owner
                        $this->_api('user_group')->add_member([
                            'group_id' => $group['result']['group_id'],
                            'user_id' => $this->session->userdata['register_parent']['id'],
                            'role' => 'owner'
                        ]);

                        // Join student in group with the role of member
                        $this->_api('user_group')->add_member([
                            'group_id' => $group['result']['group_id'],
                            'user_id' => $student['result']['id'],
                            'role' => 'member'
                        ]);
                    }
                }

                // Remove invitation token in session
                $this->session->unset_userdata('invitation_token');
                $this->session->unset_userdata('promotion_code');
                $this->session->unset_userdata('campaign_code');

                // Create user score
                if(!empty($this->session->userdata('user_score'))) {

                    $this->_api('ranking')->create([
                        'target_id' => $this->session->userdata('user_score')['target_id'],
                        'score' => (int) $this->session->userdata('user_score')['score'],
                        'type' => 'score',
                        'user_id' => $student['result']['id']
                    ]);

                    // Remove session
                    $this->session->unset_userdata('user_score');
                }

                // Remove user nick name
                $this->session->unset_userdata('user_nickname');

                // Redirect page
                redirect('register/complete');
                return;
            }
            $view_data['form_errors'] = $student['invalid_fields'];
            $view_data['post'] = $this->input->post();
        }

        // Get list grades
        $res = $this->_api('grade')->get_list();
        $view_data['grades'] = $res['result']['items'];

        // If registering student page is come from parent page
        if ($this->session->userdata('register_parent')) {

            $parent_info = $this->session->userdata('register_parent');

            $view_data['post']['email'] = $this->input->post_or_default('email', $parent_info['email']);
            // Default student login_id which is registered by parent is string concat of parent login_id and 'student'
            $view_data['post']['id'] = $this->input->post_or_default('id', '');
            $view_data['post']['promotion_code'] = $this->input->post_or_default('promotion_code', $parent_info['promotion_code']);
            $view_data['post']['campaign_code'] = $this->input->post_or_default('campaign_code', $parent_info['campaign_code']);
        }

        // Set default for current grade id
        $view_data['post']['grade_id'] = $this->input->post_or_default('grade_id',
            !empty($this->session->userdata('current_grade_id')) ? $this->session->userdata('current_grade_id') : 1
        );


        $view_data['meta'] = [
            'title' => "スクールTVに無料会員登録",
            'description' => "無料会員登録すると、教科書を設定できます。全国の小学校・中学校の教科書に対応した動画で、授業の予習・復習をしよう！"
        ];

        $view_data['register_page'] = TRUE;
        $view_data['promotion_code'] = $this->session->userdata('promotion_code');
        $view_data['campaign_code'] = $this->session->userdata('campaign_code');
        $view_data['invitation_token'] = $this->session->userdata('invitation_token');

        $this->_render($view_data);
    }

    /**
     * Re-send mail page Spec RG-40
     */
    public function re_send_email()
    {
        // Check session
        if(isset($this->session->userdata['register_student']['email'])
            || isset($this->session->userdata['register_parent']['email'])
            || $this->current_user->is_login() ) {
            if($this->input->is_post()) {

                // Check session to resend to parent
                if (isset($this->session->userdata['register_student']['email'])) {
                    $this->_api('user')->send_verify_email([
                        'email' => $this->session->userdata['register_student']['email'],
                        'email_type' => 'resend_mail'
                    ]);
                }

                // Check session to resend to student
                if (isset($this->session->userdata['register_parent']['email'])) {
                    $this->_api('user')->send_verify_email([
                        'email' => $this->session->userdata['register_parent']['email'],
                        'email_type' => 'resend_mail'
                    ]);
                }
                $this->session->sess_destroy();
                redirect('register/re_send_email_complete');
                return;
            }
            $this->_render();
        } else {
            redirect('login');
            return;
        }
    }

    /**
     * Re-send mail complete
     */
    public function re_send_email_complete()
    {
        $this->_render();
    }

    /**
     * Register complete
     */
    public function complete()
    {
        $this->_render([
            'meta' => [
                'title' => '仮会員登録完了｜【スクールTV】無料の動画で授業の予習・復習をするならスクールTV',
                'description' => '仮会員登録が完了しました。'
            ]
        ]);
    }

    /**
     * Verify email
     */
    public function verify_email() {

        if($this->input->get('token')) {

            // Check token
            $res = $this->_api('user')->verify_email($this->input->get());
            if($res['submit']) {
                if($this->input->get('email')) {

                    $redirect = !$this->current_user->is_login() ? 'setting/change_email' . urlencode('?email=' . $this->input->get('email'))
                        : 'setting/change_email?email=' . $this->input->get('email');
                    redirect($redirect);
                    return;
                }
                if ($this->input->get('redirect')) {
                    redirect('login?redirect='.urlencode($this->input->get('redirect')));
                    return;
                }
                redirect('login');
                return;
            }
        }
        redirect('login');
        return;
    }


    /**
     * Update user nickname
     *
     * @param string $nickname
     *
     * @return JSON
     */
    public function update_nickname() {

        if (!$this->input->post('nickname') || !$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        // Update nickname
        $res = $this->_api('user')->update([
            'id' => $this->current_user->id,
            'nickname' => $this->input->post('nickname')
        ]);

        // return
        if($res['result']) {
            return $this->_true_json();
        }
    }
}