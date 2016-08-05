<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Login Controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class Login extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_check_grade', [
            'except' => ['logout']
        ]);
        $this->_before_filter('_require_login', [
            'only' => ['logout']
        ]);
        $this->_before_filter('_logged_in',[
            'except' => ['logout', 'index']
        ]);
    }

    /**
     * Login page Spec LO-10
     */
    public function index()
    {
        $view_data = [
            'form_errors' => [],
            'load_form_js' => FALSE
        ];
        $redirect = null;

        // remove outh_facebook or twitter id if user go to from register page

        if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], site_url('register')) !== FALSE) {
            $this->session->unset_userdata([
                'oauth_facebook_id',
                'oauth_twitter_id',
                'oauth_facebook_email',
                'oauth_twitter_email'
            ]);
        }
        // If oauth login successfully, return res['result']

        // Check input data
        if ($this->input->is_post() || $this->session->userdata('oauth_facebook_id') || $this->session->userdata('oauth_twitter_id')) {

            $res = [];

            // Validate if use oauth facebook
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
                    $view_data['errmsg'] = $res['errmsg'];

                    $this->session->unset_userdata([
                        'oauth_'.$oauth['oauth_type'].'_id',
                        'oauth_'.$oauth['oauth_type'].'_access_token',
                        'oauth_'.$oauth['oauth_type'].'_email'
                    ]);

                } else if (!isset($res['result'])) {
                    // This oauth is not register, just redirect to register parent page
                    $auto_redirect = 'register/parent';
                    if ($this->input->get('redirect')) {
                        $auto_redirect .= '?redirect=' . $this->input->get('redirect');
                    } else {
                        $auto_redirect .= '?parent_only=1';
                    }

                    return redirect($auto_redirect);
                }
            } else {
                // Call login api to authenticate with post data
                $res = $this->_api('user')->auth($this->input->post());
            }

            if(!isset($res['result'])) {

                $view_data = [
                    'form_errors' => isset($res['invalid_fields']) ? $res['invalid_fields'] : [],
                    'errmsg' => isset($res['errmsg']) ? $res['errmsg'] : null,
                    'post' => $this->input->post()
                ];

            } else {
                $this->_find_current_user();
                // Set default textbook for user first login
                $user_textbook = $this->_api('user_textbook')->get_list([
                    'user_id' => $this->current_user->id,
                    'grade_id' => $this->current_user->grade_id
                ]);

                if(empty($user_textbook['result']['items'])) {

                    $subject_list = $this->_api('subject')->get_list([
                        'grade_id' => $this->current_user->grade_id
                    ]);

                    $s_list = [];
                    if (!empty($subject_list['result']['items'])) {
                        foreach ($subject_list['result']['items'] as $k) {
                            $s_list[] = $k['id'];
                        }
                    }

                    // Get the most popular subject
                    $most_popular_subject = $this->_api('video_textbook')->get_most_popular([
                        'subject_id' => implode(',', $s_list)
                    ]);

                    if(!empty($most_popular_subject['result']['items'])) {
                        $most_popular_subject = array_slice($most_popular_subject['result']['items'], 0, 7);

                        foreach($most_popular_subject as $tb) {
                            $this->_api('user_textbook')->create([
                                'user_id' => $this->current_user->id,
                                'textbook_id' => $tb['textbook']['id'],
                                'no_rabipoint' =>TRUE
                            ]);
                        }
                    }
                }
            }
        }

        // Get param r to redirect after login
        if($this->input->get('r')) {
            $redirect = urldecode($this->input->get('r'));

            if ($this->input->get('user_id')) {
                $redirect .= '?user_id='.$this->input->get('user_id');
            }
        }

        // Set default redirect page for login by facebook, twitter and normal login
        if (isset($res['result'])) {
            $redirect_default = $res['result']['primary_type'] == 'parent' ? '/' : 'profile/detail';
        }

        // Set default redirect page for user  already login
        if ($this->current_user->is_login()) {
            $redirect_default = $this->current_user->primary_type == 'parent' ? '/' : 'profile/detail';
        }

        // Handle URL
        if($this->input->get('redirect')) {
            $url_params = explode('?', urldecode($this->input->get('redirect')));
            $redirect_params = explode('&', $url_params[0]);

            $redirect = isset($redirect_params[0]) ? explode('=', $redirect_params[0])[1] : null;
            $from = isset($redirect_params[1]) ? explode('=', $redirect_params[1])[1] : null;
            $student_id = isset($redirect_params[2]) ? explode('=', $redirect_params[2])[1] : null;
            $token = isset($url_params[1]) ? explode('=', $url_params[1])[1] : null;

            if (isset($token) && !isset($student_id)) {
                $view_data['register_url'] = '/parent?redirect=' . urlencode($this->input->get('redirect'));

                if ($from == 'TM12') {
                    $view_data['register_url'] = '/student?redirect=' . urlencode($this->input->get('redirect'));
                }
            }
        }

        // Login with group invite token
        if ($this->current_user->is_login() || isset($res['result'])) {

            if(isset($token)) {
                $this->session->set_userdata('__get_point', $res['result']['point']);

                $token_info = $this->_api('user_group')->check_invitation(['token' => $token]);
                if(isset($token_info['result'])) {
                    switch($from) {

                        // Parent login to confirm recommendation (from CR-10)
                        case 'CR10': {
                            $this->session->set_userdata('recommend_parent', [
                                'group_id' => $token_info['result']['id'],
                                'user_id' => $token_info['result']['user_id']
                            ]);
                            $redirect = 'group_setting/'.$token_info['result']['id'].'/recommend_student/confirm';
                            break;
                        }

                        // Parent login to confirm student request (from PR-10)
                        case 'PR10': {
                            $this->session->set_userdata('add_parent', [
                                'group_id' => $token_info['result']['id'],
                                'user_id' => $token_info['result']['user_id']
                            ]);
                            $redirect = 'group_setting/'.$token_info['result']['id'].'/add_parent/confirm';
                            break;
                        }

                        // Student login to confirm team invitation (from TM-12)
                        case 'TM12': {
                            $this->session->set_userdata('add_team', [
                                'group_id' => $token_info['result']['id'],
                                'user_id' => $token_info['result']['user_id']
                            ]);

                            // Add friend
                            $this->_api('user_friend')->create([
                                'user_id' => $this->current_user->id,
                                'target_id' => $token_info['result']['user_id']
                            ]);

                            $redirect = 'friend/add_confirm';
                            break;
                        }
                    }
                    isset($redirect) ? redirect($redirect) : redirect($redirect_default);
                    return;
                }
                redirect($redirect_default);
                return;
            }
        }

        if($this->current_user->is_login() || isset($res['result'])) {

            $this->session->set_userdata('__get_point', $res['result']['point']);
            isset($redirect) ? redirect($redirect) : redirect($redirect_default);
            return;
        }

        $url_oauth_login = 'login';
        if($this->input->get('redirect')) {
            $url_oauth_login .= '?redirect='.$this->input->get('redirect');
        } elseif($this->input->get('r')) {
            $url_oauth_login .= '?r='.$this->input->get('r');
        }
        // Set session oauth redirect page
        $this->session->set_userdata('oauth_redirect_page', $url_oauth_login);

        $view_data['login_page'] = TRUE;

        $this->_render(array_merge([
            'meta' => [
                'title' => "スクールTVにログイン",
                'description' => "無料会員登録して、教科書を設定しよう。全国の小学校・中学校の教科書に対応した動画で無料で学習できます！"
            ]
        ], $view_data));
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->_api('user')->logout();

        $this->_redirect('login');
    }

    /**
     * Forget id page Spec ID-10
     */
    public function forget_id()
    {
        $view_data = [
            'form_errors' => []
        ];

        if ($this->input->is_post()) {
            $res = $this->_api('user')->resend_id($this->input->post());

            if($res['submit']) {
                redirect('login/forget_id_complete');
                return;
            }
            $view_data = [
                'form_errors' => isset($res['invalid_fields']) ? $res['invalid_fields'] : [],
                'errmsg' => isset($res['errmsg']) ? $res['errmsg'] : null,
                'post' => $this->input->post()
            ];
        }

        $this->_render($view_data);
    }

    /**
     * Forget id complete page Spec ID-20
     */
    public function forget_id_complete()
    {
        $this->_render([
            'meta' => [
                'title' => 'ログインIDの再通知完了｜【スクールTV】無料の動画で授業の予習・復習をするならスクールTV',
                'description' => 'ログインIDの再通知メールを送信しました。'
            ]
        ]);
    }

    /**
     * Forget password page Spec PW-10
     */
    public function forget_password()
    {
        $view_data = [
            'form_errors' => []
        ];

        if ($this->input->is_post()) {
            $res = $this->_api('user')->reset_password($this->input->post());

            if($res['submit']) {
                redirect('/login/forget_password_complete');
                return;
            }
            $view_data = [
                'form_errors' => isset($res['invalid_fields']) ? $res['invalid_fields'] : [],
                'errmsg' => isset($res['errmsg']) ? $res['errmsg'] : null,
                'post' => $this->input->post()
            ];
        }

        $this->_render($view_data);
    }

    /**
     * Login page Spec PW-20
     */
    public function forget_password_complete()
    {
        $this->_render([
            'meta' => [
                'title' => 'ログインパスワードの再設定メール送信完了｜【スクールTV】無料の動画で授業の予習・復習をするならスクールTV',
                'description' => 'ログインパスワードの再設定メールの送信が完了しました。'
            ]
        ]);
    }

    /**
     * Reset password page Spec PW-30
     */
    public function reset_password()
    {
        $view_data = [
            'form_errors' => []
        ];

        if ($this->input->is_post()) {
            $res = $this->_api('user')->update_password( [
                'password' => $this->input->post('password'),
                'confirm_password' => $this->input->post('confirm_password'),
                'token' => $this->input->get('token'),
                'id' => $this->input->get('id')
            ]);

            // Redirect if reset password successful
            if($res['submit']) {
                redirect('/login/reset_password_complete');
                return;
            }

            $view_data = [
                'form_errors' => isset($res['invalid_fields']) ? $res['invalid_fields'] : [],
                'errmsg' => isset($res['errmsg']) ? $res['errmsg'] : null,
                'post' => $this->input->post()
            ];
        }

        $this->_render($view_data);
    }

    /**
     * Reset password complete page Spec PW-40
     */
    public function reset_password_complete()
    {
        $this->_render();
    }
}