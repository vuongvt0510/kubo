<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_api
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 *
 * @author IMVN Team
 */
class User_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'User_api_validator';

    /**
     * User login API Spec U-001
     *
     * @param array $params
     *
     * @internal param string $id login id
     * @internal param string $password password to login
     * @internal param bool $auto check if auto login needed
     *
     * @return array
     */
    public function auth($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('id', 'アカウントID', 'required|max_length[255]');
        $v->set_rules('password', 'パスワード', 'required|max_length[255]');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load library
        $this->load->library('session');
        // Load model
        $this->load->model('user_model');

        $user = $this->user_model->find_by([
            'login_id' => $params['id']
        ], [
            'with_deleted' => TRUE
        ]);

        // Return error when record does not exist
        if ( empty($user) || $user->password != $this->user_model->encrypt_password($params['password'])) {
            return $this->false_json(APP_Response::BAD_REQUEST, 'IDかパスワードが間違っています');
        }

        // Return error if user is withdrawn
        if (!empty($user->deleted_at)) {
            return $this->false_json(APP_Response::BAD_REQUEST, '退会済みのユーザーです');
        }

        // Return error if the user does not verify yet
        if($user->email_verified != 1 && $user->status != 'active') {
            return $this->false_json(APP_Response::BAD_REQUEST, 'メールアドレスが認証されていません');
        }

        // Set user session
        $this->session->set_userdata('user_id', $user->id);

        // Save cookie
        if (isset($params['auto_login'])) {

            /** @var object $res Create auto_login record */
            $res = $this->user_model->set_autologin($user->id);

            if ($res) {
                // Save cookie
                $this->input->set_cookie([
                    'name' => 'STV_' . md5($res->token),
                    'value' => $res->token,
                    'expire' => 60 * 60 * 24 * 365,
                    'path' => '/'
                ]);
            }
        }

        if ($user->primary_type == 'student') {
            $this->load->model('user_rabipoint_model');
            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->id,
                'case' => 'first_login'
            ]);
        }

        // Return
        return $this->true_json($this->build_responses($user, ['user_info', 'user_detail']));
    }

    /**
     * User login API Spec U-020
     *
     * @param array $params
     *
     * @internal param string $oauth_type Type of oauth (facebook|twitter)
     * @internal param int $oauth_id Account id of oauth
     *
     * @return array
     */
    public function oauth($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('oauth_type', '契約タイプ', 'required|valid_oauth_type');
        $v->set_rules('oauth_id', '契約アカウントID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load library
        $this->load->library('session');
        // Load model
        $this->load->model('user_model');

        // Load user information with active user
        $user = $this->user_model
            ->with_user_oauth()
            ->where('type', $params['oauth_type'])
            ->where('oauth_id', $params['oauth_id'])
            ->first();

        // Return error when record does not exist
        if (empty($user)) {
            return $this->false_json(APP_Response::BAD_REQUEST, 'この契約アカウントは登録されていません');
        }

        if ($user->status != 'active') {
            return $this->false_json(self::USER_IS_INACTIVE, 'メールアドレスが認証されていません');
        }

        // Set user session
        $this->session->set_userdata('user_id', $user->user_id);

        // Return
        return $this->true_json($this->build_responses($user, ['user_info', 'user_detail']));
    }

    /**
     * User logout API Spec U-002
     *
     * @return array
     */
    public function logout()
    {
        $this->load->model('user_model');
        $this->load->helper('cookie');
        $this->load->library('session');

        if (is_array($_COOKIE)) {
            foreach (array_keys($_COOKIE) AS $k) {
                if (!preg_match('/^STV\_/', $k)) {
                    continue;
                }

                delete_cookie($k);
                $this->user_model->delete_autologin($this->input->cookie($k));
            }
        }

        $this->session->sess_destroy();

        return $this->true_json();
    }

    /**
     * User register API Spec U-010
     *
     * @param array $params
     *
     * @internal param string $id login id
     * @internal param string $password password to login
     * @internal param string $email user email
     * @internal param string $promotion_code when register
     * @internal param string $invitation_token
     * @internal param string $type user type parent|student
     * @internal param int $grade_id user Grade
     * @internal param int $oauth_facebook_id
     * @internal param string $oauth_facebook_access_token
     * @internal param int $oauth_twitter_id
     * @internal param string $oauth_twitter_access_token
     * @internal param int $parent_id
     *
     * @return array
     */
    public function register($params = [])
    {
        if (isset($params['id'])) {
            $this->load->model('user_email_verify_model');
            $this->user_email_verify_model->check_login_id($params['id']);
        }
        // Validate
        $v = $this->validator($params);
        $v->set_rules('id', 'アカウントID', 'required|min_length[8]|max_length[16]|valid_id|valid_login_id|valid_login_id_duplicate');
        $v->set_rules('password', 'パスワード', 'required|min_length[8]|max_length[16]|valid_format_password');
        $v->set_rules('email', 'メールアドレス', 'required|max_length[255]|valid_vague_email');
        $v->set_rules('type', 'アカウントID', 'required|valid_type');
        $v->set_rules('grade_id', '学年ID', 'valid_grade_id');
        $v->set_rules('oauth_facebook_id', 'integer|valid_facebook_id');
        $v->set_rules('oauth_twitter_id', 'integer|valid_twitter_id');
        $v->set_rules('promotion_code',  '紹介コード', 'valid_promotion_code');
        $v->set_rules('campaign_code',  'キャンペーンコード', 'alpha_numeric|valid_campaign_code');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_invite_code_model');


        // Process register with invitation code
        /** @var object $invite record of user_invite_code_model */
        $invite = NULL;
        if (isset($params['invitation_token']) && !empty($params['invitation_token'])) {
            // Get invite code info
            $invite = $this->user_invite_code_model
                ->find_by([
                    'code' => $params['invitation_token']
                ]);

            if (!empty($invite)) {
                $params['invited_from_id'] = $invite->user_id;
            }
        }

        // If type of user is not parent, remove relation params with facebook and twitter
        if ($params['type'] != 'parent') {
            unset(
                $params['oauth_facebook_id'],
                $params['oauth_facebook_access_token'],
                $params['oauth_twitter_id'],
                $params['oauth_twitter_access_token']
            );
        }

        // Create user
        $res = $this->user_model->register($params);

        if ($params['type'] == 'student') {
            $this->load->model('user_rabipoint_model');

            if (!empty($params['promotion_code'])) {
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $res->id,
                    'case' => 'new_registration_by_forceclub'
                ]);
            }

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $res->id,
                'case' => 'new_registration'
            ]);
        }

        // If exist invitation, remove invite token relation
        if (!empty($invite) && (isset($params['force_remove_invite']) && $params['force_remove_invite'] === TRUE)) {
            $this->user_invite_code_model->destroy($invite->id);
        }

        // Return
        return $this->true_json($this->build_responses($res, ['user_info', 'user_detail']));
    }

    /**
     * User update information API Spec U-060
     *
     * @param array $params
     *
     * @internal param int $id identity user id
     * @internal param string $login_id of user
     * @internal param string $nickname of user
     * @internal param string $postalcode of user
     * @internal param string $address of user
     * @internal param string $phone of user
     * @internal param string $sex user gender
     * @internal param int $avatar_id user avatar
     * @internal param string $birthday user birthday
     *
     * @return array
     */
    public function update($params = [], $option = [])
    {
        // Validate
        /** @var APP_param_validation $v */
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'ID', 'required|integer');
        $v->set_rules('login_id', 'アカウントID', 'valid_login_id|valid_login_id_exist[' . $params['id'] . ']');
        if (isset($option['admin_edit']) && $option['admin_edit'] == TRUE) {
            $v->set_rules('nickname', 'ニックネーム', 'required|max_length[50]');
        } else {
            $v->set_rules('nickname', 'ニックネーム', 'max_length[50]');
        }
        $v->set_rules('postalcode', '郵便番号', 'min_length[6]|max_length[7]');
        $v->set_rules('address', '住所', 'max_length[255]');
        $v->set_rules('phone', '電話', 'max_length[32]');
        $v->set_rules('sex', '性別', 'integer');
        $v->set_rules('avatar_id', 'アイコン', 'integer');
        $v->set_rules('birthday', 'お誕生日', 'date_format|birthday_older');
        $v->set_rules('promotion_code',  '紹介コード', 'valid_promotion_code');
        $v->set_rules('campaign_code',  'キャンペーンコード', 'alpha_numeric|valid_campaign_code');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_profile_model');

        // If operator isn't admin,
        // he can not update user detail who isn't available or other user.
        if (!$this->operator()->is_administrator()) {

            if ($this->operator()->id != $params['id']) {
                return $this->false_json(self::BAD_REQUEST);
            }

            $this->user_model->available(TRUE);
        }

        // Return error if user is not exist
        $user = $this->user_model->find($params['id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Set param for update
        $user_data = [];
        $user_profile = [];
        if (isset($params['login_id'])) {
            $user_data['login_id'] = $params['login_id'];
        }
        if (isset($params['nickname'])) {
            $user_data['nickname'] = $params['nickname'];
        }
        if (isset($params['postalcode'])) {
            $user_profile['postalcode'] = $params['postalcode'];
        }
        if (isset($params['address'])) {
            $user_profile['address'] = $params['address'];
        }
        if (isset($params['phone'])) {
            $user_profile['phone'] = $params['phone'];
        }
        if (isset($params['sex'])) {
            $user_profile['gender'] = $params['sex'] == 0 ? 'male' : 'female';
            if (!$this->operator()->is_administrator() && $this->operator()->primary_type == 'student') {
                $this->load->model('user_rabipoint_model');
                $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $this->operator()->id,
                    'case' => 'register_profile',
                    'modal_shown' => 1
                ]);
            }
        }
        if (isset($params['avatar_id'])) {
            $user_profile['avatar_id'] = $params['avatar_id'];

            if (!$this->operator()->is_administrator() && $this->operator()->primary_type == 'student') {
                $this->load->model('user_rabipoint_model');
                $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $this->operator()->id,
                    'case' => 'register_profile',
                    'modal_shown' => 1
                ]);
            }
        }
        if (isset($params['birthday'])) {
            $user_profile['birthday'] = $params['birthday'];
        }

        // Update user
        if ($user_data) {
            $this->user_model->update($user->id, $user_data);
        }

        // Update user profile
        if ($user_profile) {
            $this->user_profile_model->update($user->id, $user_profile);
            $this->load->model('timeline_model');
            $trophy = $this->timeline_model->create_timeline('profile', 'trophy');
        }

        // Update user promotion
        if (isset($params['promotion_code'])) {
            $this->load->model('user_promotion_code_model');

            // Get promotion code
            $promotion_code = $this->user_promotion_code_model->find_by([
                'user_id' => (int) $params['id'],
                'type' => 'forceclub'
            ]);

            if ($promotion_code) {
                // update
                $this->user_promotion_code_model->update($promotion_code->id, [
                    'code' => $params['promotion_code']
                ]);

            } else {
                // create
                $this->user_promotion_code_model->create([
                    'user_id' => $params['id'],
                    'code' => $params['promotion_code'],
                    'type' => 'forceclub'
                ]);
            }
        }

        // Update user campaign
        if (isset($params['campaign_code'])) {
            $this->load->model('user_promotion_code_model');

            // Get campaign code
            $campaign_code = $this->user_promotion_code_model->find_by([
                'user_id' => (int) $params['id'],
                'type' => 'campaign'
            ]);

            if ($campaign_code) {
                // update
                $this->user_promotion_code_model->update($campaign_code->id, [
                    'code' => $params['campaign_code']
                ]);

            } else {
                // create
                $this->user_promotion_code_model->create([
                    'user_id' => $params['id'],
                    'code' => $params['campaign_code'],
                    'type' => 'campaign'
                ]);
            }
        }

        $res = $this->build_responses($user, [
            'user_info', 'user_detail'
        ]);

        if (isset($trophy)) {
            $res['trophy'] = $trophy;
        }

        if (isset($res_rabipoint)) {
            $res['point'] = $res_rabipoint;
        }

        // Return
        return $this->true_json($res);
    }

    /**
     * User update profile API Spec U-070
     *
     * @param array $params
     *
     * @internal param int $id identity user id
     * @internal param string $login_id of user
     * @internal param string $nickname of user
     * @internal param string $sex user gender
     *
     * @return array
     */
    public function update_profile($params = [])
    {
        // Validate
        /** @var APP_param_validation $v */
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'ID', 'required|integer');
        $v->set_rules('login_id', 'ID', 'valid_login_id|valid_login_id_exist[' . $params['id'] . ']');
        $v->set_rules('nickname', 'ニックネーム', 'required|max_length[50]');
        $v->set_rules('sex', '性別', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_profile_model');

        // If operator isn't admin,
        // he can not update user detail who isn't available or other user.
        if (!$this->operator()->is_administrator()) {

            if ($this->operator()->id != $params['id']) {
                return $this->false_json(self::BAD_REQUEST);
            }

            $this->user_model->available(TRUE);
        }

        // Return error if user is not exist
        $user = $this->user_model->find($params['id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Set param for update
        $user_data = [];
        $user_profile = [];
        if (isset($params['login_id'])) {
            $user_data['login_id'] = $params['login_id'];
        }
        if (isset($params['nickname'])) {
            $user_data['nickname'] = $params['nickname'];
        }
        if (isset($params['sex'])) {
            $user_profile['gender'] = $params['sex'];
        }

        // Update user
        if ($user_data) {
            $this->user_model->update($user->id, $user_data);
        }

        // Update user profile
        if ($user_profile) {
            $this->user_profile_model->update($user->id, $user_profile);
            if (!$this->operator()->is_administrator() && $this->operator()->primary_type == 'student') {
                $this->load->model('user_rabipoint_model');
                $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $this->operator()->id,
                    'case' => 'register_profile',
                    'modal_shown' => 1
                ]);

                $this->load->model('timeline_model');
                $trophy = $this->timeline_model->create_timeline('profile', 'trophy');
            }
        }
        $res = $this->build_responses($user, [
            'user_info', 'user_detail'
        ]);

        if (isset($trophy)) {
            $res['trophy'] = $trophy;
        }

        if (isset($res_rabipoint)) {
            $res['point'] = $res_rabipoint;
        }
        // Return
        return $this->true_json($res);
    }

    /**
     * User delete API Spec U-100
     *
     * @param array $params
     *
     * @internal param int $id id of user
     *
     * @return array
     */
    public function delete($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'ID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Only administrator can delete user
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load model
        $this->load->model('user_model');
        $this->load->helper('string_helper');

        /** @var object $user */
        // Throws error when user is not in exist
        if (!($user = $this->user_model->available(TRUE)->find_by(['id' => $params['id']]))) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $data = [];
        $data['nickname'] = $user->nickname;
        $data['user_id'] = $user->login_id;

        // Remove user
        $this->user_model->update($user->id, [
            'login_id' => $params['id'].':delete'.random_string('alnum', 15),
            'status' => 'suspended',
            'deleted_at' => business_date('Y-m-d H:i:s'),
            'deleted_by' => 'user:'.$this->operator()->id
        ]);

        // Send mail for user
        $this->send_mail('mails/withdraw', [
            'to' => $user->email,
            'subject' => $this->subject_email['withdraw']
        ], $data);

        return $this->true_json();
    }

    /**
     * Send mail to user for verify email API Spec U-030
     *
     * @param array $params
     *
     * @internal param string $email email to find
     * @internal param string $from string to redirect
     * @internal param string $user_id
     *
     * @return array
     */
    public function send_verify_email($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('email', 'メールアドレス', 'required|max_length[255]|valid_vague_email');
        $v->set_rules('user_id', 'アカウントID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load helper
        $this->load->helper('string_helper');

        // Load model
        $this->load->model('user_email_verify_model');
        $this->load->model('user_model');

        // Get user info
        $user = $this->user_model->where(['id' => $params['user_id']])->all(['master' => TRUE]);;
        // If email is not exist return error
        if (!$user) {
            return $this->false_json(self::NOT_FOUND, '送信対象が見つかりませんでした。');
        }

        $token = random_string('alnum', 32);

        // Build token params
        $bulk_params[$params['user_id']] = [
            'user_id' => $params['user_id'],
            'token' => $token,
            'expired_at' => business_date('Y-m-d H:i:s', strtotime('+24 hours')) // 24 hours
        ];

        // Create verify mail token
        $this->user_email_verify_model->bulk_create($bulk_params, ['replace' => TRUE]);

        // Create verify email content
        $verify_url = sprintf('%s/verify_email?token=%s', $this->config->item('site_url'), $token);
        $verify_url .= isset($params['email_type']) && $params['email_type'] == 'change_email' ? '&email=' . $params['email'] : null;
        $verify_url .= isset($params['redirect_URL']) ? '&redirect=' . $params['redirect_URL'] : null;

        $config_mail = [
            'to' => $params['email'],
        ];

        // Kind user register
        $email_type = $params['email_type'] ? $params['email_type'] : "";
        if ($email_type == 'student') {
            $config_mail['subject'] = $this->subject_email['verify_email_student'];
            // Send mail student
            $this->send_mail('mails/verify_email_student', $config_mail, ['verify_url' => $verify_url]);
        } else if ($email_type == 'parent') {
            $config_mail['subject'] = $this->subject_email['verify_email_parent'];
            // Send mail parent
            $this->send_mail('mails/verify_email_parent', $config_mail, ['verify_url' => $verify_url]);
        } else if ($email_type == 'resend_mail') {
            $config_mail['subject'] = $this->subject_email['resend_email'];
            $this->send_mail('mails/resend_email', $config_mail, ['verify_url' => $verify_url]);
        }else if ($email_type == 'change_email') {
            $user_name = $this->operator()->nickname ? $this->operator()->nickname : "";
            $config_mail['subject'] = $this->subject_email['change_email'];
            $this->send_mail('mails/change_email', $config_mail, [
                'verify_url' => $verify_url,
                'user_name' => $user_name
            ]);
        }
        // Return
        return $this->true_json();
    }

    /**
     * Send the account for user API Spec U-040
     *
     * @param array $params
     *
     * @internal param string $email email to find
     *
     * @return array
     */
    public function resend_id($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('email', 'メールアドレス', 'required|max_length[255]|valid_vague_email');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_group_model');

        /** @var object $user Get user info */
        $user = $this->user_model->where(['email' => $params['email']])->all();

        // Return error if user is not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $ids = [];
        // Set the ID for send
        foreach($user as $k) {
            $ids[$k->login_id] = $k->nickname;
        }

        // Set the Email for send
        $emails[$params['email']] = [$params['email'], NULL];

        // Check user type
        if ($user->primary_type == 'student') {

            /** @var array $group_ids Get user group */
            $group_ids = $this->user_group_model->get_user_group_id($user->id);

            if ($group_ids) {
                /** @var object $res Get group owner */
                $res = $this->user_group_model->get_user_owner($group_ids);

                if ($res) {
                    foreach ($res as $k) {
                        $ids[$k->login_id] = $k->nickname;
                        $emails[$k->email] = [$k->email, NULL];
                    }
                }
            }
        }

        // Send mail for user
        $this->send_mail('mails/resend_id', [
            'to' => array_values($emails),
            'subject' => $this->subject_email['resend_id']
        ], [
            'users' => $ids,
            'url_login' => sprintf('%s/login', $this->config->item('site_url'))
        ]);

        // Return
        return $this->true_json();
    }

    /**
     * Send the invitation to user API Spec U-080
     *
     * @param array $params
     *
     * @internal param string $email email to find
     *
     * @return array
     */
    public function send_invite($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('email', '友達のメールアドレス', 'required|max_length[255]|valid_vague_email');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load helper
        $this->load->helper('string_helper');

        // Load model
        $this->load->model('user_invite_code_model');

        // Create random token
        $token = random_string('alnum', 32);

        // Create invite token
        $this->user_invite_code_model->create([
            'code' => $token,
            'user_id' => $this->operator()->id
        ]);

        // Send mail for user
        $this->send_mail('mails/user_invite', [
            'to' => $params['email'],
            'subject' => $this->subject_email['user_invite']
        ], [
            'user_name' => $this->operator()->nickname,
            'register_url' => sprintf('%s/register?token=%s', $this->config->item('site_url'), $token)
        ]);

        // Return
        return $this->true_json();
    }


    /**
     * Send email for forget password API Spec U-050
     *
     * @param array $params
     *
     * @internal param string $email email to find
     *
     * @return array
     */
    public function reset_password($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('email', 'メールアドレス', 'required|max_length[255]|valid_vague_email');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load helper
        $this->load->helper('string_helper');

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_group_model');
        $this->load->model('user_password_verify_model');

        /** @var object $user Get user info */
        $users = $this->user_model->where(['email' => $params['email']])->all();

        // Return error if user is not exist
        if (!$users) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Create token
        $token = random_string('alnum', 32);

        // Create expired time , 30 minutes
        $expire_time = business_date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $login_id = [];
        $ids = [];
        $bulk_params = [];
        $data = [];
        foreach($users as $user) {
            $login_id[$user->id] = $user->login_id;
            $ids[$user->id] = [$user->email, NULL];
            $bulk_params[$user->id] = [
                'user_id' => $user->id,
                'token' => $token,
                'expired_at' => $expire_time
            ];
            
            $data[]=  get_object_vars($user);
        }

        // Clean up old token
        $this->user_password_verify_model
            ->where_in('user_id', array_keys($ids))
            ->where('expired_at <', business_date('Y-m-d H:i:s'))->destroy_all();

        // Save token
        $this->user_password_verify_model->bulk_create($bulk_params);
        // Send mail for user
        $this->send_mail('mails/reset_password', [
            'to' => array_values($ids),
            'subject' => $this->subject_email['reset_password']
        ], [
            'reset_password_url' => sprintf('%s/update_password?token=%s', $this->config->item('site_url'), $token),
            'users' => $data
        ]);

        // Return
        return $this->true_json();
    }

    /**
     * Verify the email API Spec U-031
     *
     * @param array $params
     *
     * @internal param string $token of user
     *
     * @return array
     */
    public function verify_email($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('token', '認証トークン', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_email_verify_model');
        $this->load->model('user_model');

        /** @var array $token Get token info */
        $token = $this->user_email_verify_model
            ->where([
                'token' => $params['token'],
                'expired_at >=' => business_date('Y-m-d H:i:s')
            ])->first([
                'master' => TRUE
            ]);

        // Return error if token does not exist
        if (!$token) {
            return $this->false_json(self::TOKEN_NOT_FOUND);
        }


        // Set condition for update
        $update_condition = [
            'email_verified' => 1,
            'status' => 'active'
        ];

        // Get user info
        $user = $this->user_model->find($token->user_id);

        // Update registered time for user
        if(!empty($user) && empty($user->registered_at) && $user->status == null) {
            $update_condition['registered_at'] = business_date('Y-m-d H:i:s');
        }

        $this->load->model('action_history_model');
        $register_same_time = $this->action_history_model
            ->select('user_id, target_id')
            ->where([
                'user_id' => $user->id,
                'object' => 'register',
                'action' => 'same_time'
            ])->first();

        if (!empty($register_same_time)) {

            $partner = $this->user_model->find($register_same_time->target_id);

            if ($partner->email_verified == 1) {

                $student_id = $user->primary_type == 'student' ? $user->id : $user->user_id_same_time_register;
                $parent_id = $user->primary_type == 'parent' ? $user->id : $user->user_id_same_time_register;

                $this->load->model('user_rabipoint_model');
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $student_id,
                    'target_id' => $parent_id,
                    'case' => 'both_register'
                ]);

                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $student_id,
                    'case' => 'join_family'
                ]);
            }
        }

        $inviting_person = $this->user_model->find($user->invited_from_id);

        if ($inviting_person->primary_type == 'student' && $user->primary_type == 'student') {
            $this->load->model('user_rabipoint_model');
            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->invited_from_id,
                'case' => 'invite_friend'
            ]);
        }

        // Update email verify
        $this->user_model->update($token->user_id, $update_condition);

        $this->user_model->where('id', $token->user_id)->update_all($update_condition);

        // Remove token
        $this->user_email_verify_model->where('token', $params['token'])->destroy_all();

        // Return
        return $this->true_json();
    }

    /**
     * User update password API Spec U-051
     *
     * @param array $params
     *
     * @internal param string $token
     * @internal param string $password
     * @internal param string $confirm_password
     * @internal param string $token of user
     *
     * @return array
     */
    public function update_password($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('token', '認証トークン', 'required');
        $v->set_rules('id', 'ユーザーID', 'required');
        $v->set_rules('password', 'パスワード', 'required|min_length[8]|max_length[16]|valid_format_password');
        $v->set_rules('confirm_password', 'パスワード確認', 'required|valid_confirm_password[' . $params['password'] . ']');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_password_verify_model');
        $this->load->model('user_model');

        /** @var array $token Get token info */
        $token = $this->user_password_verify_model
            ->join('user', 'user.id = user_password_verify.user_id')
            ->where([
                'token' => $params['token'],
                'expired_at >=' => business_date('Y-m-d H:i:s'),
                'login_id' => $params['id']
            ])->all([
                'master' => TRUE
            ]);

        // Return error if token does not exist
        if (!$token) {
            return $this->false_json(self::TOKEN_NOT_FOUND);
        }

        /** @var object $user Get user info */
        $user = $this->user_model->find_by([
            'login_id' => $params['id']
        ]);

        // Return error if user does not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Update password
        $this->user_model->update($user->id, [
            'password' => $this->user_model->encrypt_password($params['password'])
        ]);

        // Remove token
        $this->user_password_verify_model
            ->where(['token' => $params['token'], 'user_id' => $user->id])->destroy_all();

        // Return
        return $this->true_json();
    }

    /**
     * User change password API Spec U-053
     *
     * @param array $params
     *
     * @internal param string $current_password
     * @internal param string $password
     * @internal param string $confirm_password
     * @internal param string $token of user
     *
     * @return array
     */
    public function change_password($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('current_password', '現在のパスワード', 'required|valid_current_password');
        $v->set_rules('password', '新しいパスワード', 'required|min_length[8]|max_length[16]|valid_format_password');
        $v->set_rules('confirm_password', '新しいパスワード（確認）', 'required|valid_confirm_password[' . $params['password'] . ']');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');

        /** @var object $user Get user info */
        $user = $this->user_model->available(TRUE)->find($this->operator()->id);

        // Return error if user does not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Update password
        $this->user_model->update($this->operator()->id, [
            'password' => $this->user_model->encrypt_password($params['password'])
        ]);

        // Return
        return $this->true_json();
    }

    /**
     * User update email API Spec U-052
     *
     * @param array $params
     *
     * @internal param int $id user ID
     * @internal param string $email
     *
     * @return array
     */
    public function update_email($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'ユーザーID', 'required|integer');
        $v->set_rules('email', 'メールアドレス', 'required|max_length[255]|valid_vague_email');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');

        // If operator isn't admin, he can not update user detail who isn't available or other user.
        if (!$this->operator()->is_administrator()) {

            if ($this->operator()->id != $params['id']) {
                return $this->false_json(self::BAD_REQUEST);
            }

            $this->user_model->available(TRUE);
        }

        /** @var object $user Get user info */
        $user = $this->user_model->find($params['id']);

        // Return error if user does not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Update email
        $this->user_model->update($user->id, [
            'email' => $params['email'],
            'email_verified' => 1
        ]);

        // Return
        return $this->true_json();
    }

    /**
     * User Update Relation with Oauth API Spec U-054
     *
     * @param array $params
     *
     * @internal param string $oauth_type Type of oauth (facebook|twitter)
     * @internal param int $oauth_id Account id of o
     * @internal param string $access_token of oauth
     *
     * @return array
     */
    public function update_oauth($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('oauth_type', '契約タイプ', 'required|valid_oauth_type');
        $v->set_rules('oauth_id', '契約アカウントID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_oauth_model');

        /** @var object $user Get user info */
        $user = $this->user_model->available(TRUE)->find($this->operator()->id);

        // Return error if user does not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        /** @var object $oauth info */
        $oauth = $this->user_oauth_model
            ->where('type', $params['oauth_type'])
            ->where('oauth_id', $params['oauth_id'])
            ->first();

        if (!empty($oauth) && $oauth->user_id != $user->id) {
            return $this->false_json(self::BAD_REQUEST, '契約アカウントが設定されました');
        }

        // Update or create oauth record by user_id and oauth_type
        $current_oauth = $this->user_oauth_model
            ->where('user_id', $user->id)
            ->where('type', $params['oauth_type'])
            ->first();

        if ($current_oauth) {
            $this->user_oauth_model->update($current_oauth->id, [
                'oauth_id' => $params['oauth_id'],
                'access_token' => $params['access_token']
            ]);
        } else {
            $this->user_oauth_model->create([
                'user_id' => $user->id,
                'oauth_type' => $params['oauth_type'],
                'oauth_id' => $params['oauth_id'],
                'access_token' => $params['access_token']
            ]);
        }

        // Return
        return $this->true_json();
    }

    /**
     * Get user detail API Spec U-071
     *
     * @param array $params
     *
     * @internal param int $id user ID
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'ID', 'required|integer');
        $v->set_rules('get_all', 'Get All', 'is_boolean');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }
        // Load model
        $this->load->model('user_profile_model');

        // If operator isn't admin, he can not get user detail who isn't available
        if (!$this->operator()->is_administrator() && !isset($params['get_all'])) {
            $this->user_profile_model->where('user.status', 'active');
        }

        // Get user info
        $user = $this->user_profile_model
            ->join('user', 'user.id = '.$params['id'], 'left')
            ->where('user_profile.user_id', $params['id'])
            ->first();

        // Return error if user does not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $options = isset($params['get_all']) ? ['user_get_all'] : ['user_info', 'user_detail'];

        // Return
        return $this->true_json($this->build_responses($user, $options));
    }

    /**
     * Search user API Spec U-090
     *
     * @param array $params
     *
     * @internal param int $id user ID
     * @internal param int $search_type Search Type
     *
     * @return array
     */
    public function search($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'ユーザーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');

        // Get user info
        $res = $this->user_model->available(TRUE)
            ->find_by([
                'login_id' => $params['id'],
                'id != ' => $this->operator()->id
            ]);

        if (!$res) {
            return $this->false_json(self::NOT_FOUND, 'アカウントが見つかりません。');
        }

        if (isset($params['search_type']) && $params['search_type'] == 'group') {

            if ($res->primary_type == 'parent') {
                return $this->false_json(self::BAD_REQUEST, 'このアカウントは親アカウントです。');
            }

            $this->load->model('user_group_model');
            $check_group = $this->user_group_model
                ->join('group', 'group.id = user_group.group_id')
                ->join('user', 'user.id = user_group.user_id')
                ->where('user.primary_type', 'student')
                ->where('group.primary_type', 'family')
                ->where('user_group.user_id', $res->id)
                ->all();
            if (!empty($check_group)) {
                return $this->false_json(self::BAD_REQUEST, 'この子どもアカウントは既に家族グループに所属しています');
            }
        }

        // Return
        return $this->true_json($this->build_responses($res, [
            'user_info'
        ]));
    }

    /**
     * Search List User API Spec U-095
     *
     * @param array $params
     *
     * @internal param string $login_id of user keyword
     * @internal param string $email of user keyword
     * @internal param string $group_id
     * @internal param string $group_type
     * @internal param string $sort_by (id|login_id|nickname|email|primary_type) Default login_id
     * @internal param string $sort_position (asc|desc) Default asc
     * @internal param int $offset of query Default:0
     * @internal param int $limit of query Default:20
     *
     * @param array $options
     *
     * @return array
     */
    public function search_list($params = [], $options = [])
    {
        if( !isset($params['from_date']) ) {
            $params['from_date'] = null;
        }

        if( !isset($params['to_date']) ) {
            $params['to_date'] = null;
        }

        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('USER_LIST');
        $v->set_rules('group_id', 'Group ID', 'integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default for params
        if (!isset($params['sort_by']) || !in_array($params['sort_by'], ['id', 'login_id', 'nickname', 'email', 'primary_type'])) {
            $params['sort_by'] = 'created_at';
        }

        // Set default for param sort position
        if (!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'desc';
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_rabipoint_model');

        // Prepare query
        $this->user_model
            ->calc_found_rows()
            ->select('user.id, user.login_id, user.email, user.primary_type, user.nickname, user.status,user.created_at, user.deleted_at, user.current_coin, user.registered_at')
            ->select('user_grade_history.grade_id AS user_grade')
            ->select('user_promotion_code.code AS invitation_code')
            ->select('user_campaign.code AS campaign_code')
            ->select('user_profile.avatar_id, user_profile.gender')
            ->select('user_contract.status  as contract_status')
            ->select('SUM(user_rabipoint.point_remain) as current_rabipoint, SUM(user_rabipoint.rabipoint) as total_rabipoint')
            ->select('inviter.id AS inviter_id, inviter.login_id AS inviter_login_id')
            ->join('user_promotion_code', 'user_promotion_code.user_id = user.id AND user_promotion_code.type = "forceclub"', 'LEFT')
            ->join('user_promotion_code as user_campaign', 'user_campaign.user_id = user.id AND user_campaign.type = "campaign"', 'LEFT')
            ->join('user as inviter', 'user.invited_from_id = inviter.id', 'LEFT')
            ->join('user_grade_history', 'user_grade_history.id = user.current_grade', 'LEFT')
            ->join('user_contract', 'user_contract.user_id = user.id', 'LEFT')
            ->join('user_rabipoint', 'user_rabipoint.user_id = user.id', 'LEFT')
            ->with_profile()
            ->order_by('user.' . $params['sort_by'], $params['sort_position'])
            ->group_by('user.id');

        if (isset($params['group_id']) && isset($params['group_type'])) {
            $this->user_model->where('user.status', 'active');

            if ($params['group_type'] == 'friend') {
                $this->user_model->where('user.primary_type', 'student');
            }
        }

        if (empty($params['csv'])) {
            $this->user_model->limit($params['limit'], $params['offset']);
        }

        if (!empty($params['login_id'])) {
            $this->user_model->where('user.login_id', $params['login_id']);
        }

        if (!empty($params['email'])) {
            $this->user_model->where('user.email', $params['email']);
        }

        // search by id
        if (!empty($params['id'])) {
            $this->user_model->where('user.id', $params['id']);
        }

        // search by nickname
        if (!empty($params['nickname'])) {
            $this->user_model->where('user.nickname', $params['nickname']);
        }

        // search by promotion code
        if (!empty($params['code'])) {
            $this->user_model->where('user_promotion_code.code', $params['code']);
        }

        // search by user invitation_login_id
        if (!empty($params['user_inviter_login_id'])) {
            $this->user_model->where('inviter.login_id', $params['user_inviter_login_id']);
        }

        // search by user invitation_id
        if (!empty($params['user_inviter_id'])) {
            $this->user_model->where('inviter.id', $params['user_inviter_id']);
        }

        if (!(empty($params['student']) == empty($params['parent']))) {
            $primary_type_filter = !empty($params['student']) ? 'student' : 'parent';
            $this->user_model->where('user.primary_type', $primary_type_filter);
        }

        // search user contract
        if (!empty($params['contract'])) {
            $this->user_model->where('user_contract.status', 'under_contract');
        }

        if (!empty($params['from_date'])) {
            $this->user_model->where('user.created_at >=', business_date('Y-m-d H:i:s', strtotime($params['from_date'] . ' 00:00:00')));
        }

        if (!empty($params['to_date'])) {
            $this->user_model->where('user.created_at <=', business_date('Y-m-d H:i:s', strtotime($params['to_date'] . ' 23:59:59')));
        }

        // Fetch all records
        $res = $this->user_model->all($options);

        // Return
        if (isset($params['group_id']) && isset($params['group_type'])) {
            return $this->true_json([
                'items' => $this->build_responses($res, [
                    'group_id' => $params['group_id'],
                    'group_type' => $params['group_type']
                ]),
                'total' => (int)$this->user_model->found_rows()
            ]);
        } else {
            return $this->true_json([
                'items' => $this->build_responses($res, ['point_remain_admin']),
                'total' => (int) $this->user_model->found_rows()
            ]);
        }
    }

    /**
     * Get list groups belong to member API Spec U-072
     *
     * @param array $params
     *
     * @internal param $user_id User ID
     *
     * @return array
     */
    public function get_list_groups($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|valid_user_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');

        // Create user group
        $res = $this->group_model
            ->calc_found_rows()
            ->select('group.id as group_id, group.name as group_name, group.primary_type as group_type, user_group.role as user_role')
            ->join('user_group', 'group.id = user_group.group_id')
            ->where(['user_group.user_id' => $params['user_id']])
            ->all();

        // Return
        return $this->true_json([
            'groups' => $this->build_responses($res),
            'total' => (int)$this->group_model->found_rows()
        ]);
    }

    /**
     * Get list players to play with API Spec U-120
     *
     * @param array $params
     * @internal param int $stage_id Stage ID
     * @internal param int $user_id User ID
     * 
     * @return array
     */
    public function get_players($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('stage_id', 'ステージID', 'required');
        $v->set_rules('user_id', 'ユーザーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('user_playing_stage_model');

        // Get list of friend players
        $friends = $this->user_playing_stage_model
            ->calc_found_rows()
            ->select('user_playing_stage.id as play_id, user_playing_stage.score, user_profile.avatar_id, user.nickname, user.login_id')
            ->with_profile()
            ->with_user()
            ->join("(SELECT target_id FROM user_friend WHERE user_id = ".$params['user_id']." AND status = 'active') AS friends", "friends.target_id = user_playing_stage.user_id")
            ->where('user_playing_stage.stage_id', $params['stage_id'])
            ->where('user.primary_type', 'student')
            ->where('user_playing_stage.type', 'battle') // implement to lift power
            ->limit(3, 0)
            ->order_by('RAND()')
            ->group_by('user_playing_stage.user_id')
            ->all();

        $limit = count($friends) < 3 ? 6-count($friends) : 3;

        // Get list of non-friend players
        $non_friends = $this->user_playing_stage_model
            ->calc_found_rows()
            ->select('user_playing_stage.score, user_playing_stage.id as play_id, user_profile.avatar_id, user.nickname, user.login_id')
            ->with_profile()
            ->with_user()
            ->join('(SELECT target_id, status FROM user_friend WHERE user_id = '.$params['user_id'].') AS friends', 'friends.target_id = user_playing_stage.user_id', 'left')
            ->where('friends.target_id is null')
            ->where('user_playing_stage.stage_id', $params['stage_id'])
            ->where('user.primary_type', 'student')
            ->where('user_playing_stage.user_id !=', $params['user_id'])
            ->where('user_playing_stage.type', 'battle') // implement to lift power
            ->limit($limit, 0)
            ->order_by('RAND()')
            ->group_by('user_playing_stage.user_id')
            ->all();

        $friends = $this->build_responses($friends, ['friends']);
        $non_friends = $this->build_responses($non_friends, ['non_friends']);

        // Return
        return $this->true_json(array_merge($friends, $non_friends));
    }

    /**
     * Get user information and score with stage Spec U-121
     *
     * @param array $params
     * @internal param int $user_stage_id school id
     *
     * @return array
     */

    public function get_play($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('play_id', 'プレーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_playing_stage_model');

        // Get playing detail
        $play = $this->user_playing_stage_model
            ->calc_found_rows()
            ->select('user_playing_stage.id as play_id, user_playing_stage.score, user_profile.avatar_id, user.nickname, user.login_id')
            ->with_profile()
            ->with_user()
            ->where(['user_playing_stage.id' => $params['play_id']])
            ->first();

        // Return error if play does not exist
        if (!$play) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Return
        return $this->true_json($this->build_responses($play));
    }

    /**
     * Inactivate user
     *
     * @param array $params
     * @internal param int $password
     *
     * @return array
     */
    public function withdraw($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('password', 'パスワード', 'required|valid_current_password');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->helper('string_helper');

        $this->user_model->update($this->operator()->id, [
            'login_id' => $this->operator()->login_id.':withdrawn'.random_string('alnum', 15),
            'status' => 'suspended',
            'deleted_at' => business_date('Y-m-d H:i:s'),
            'deleted_by' => 'user:'.$this->operator()->id
        ]);

        $data = [];
        $data['nickname'] = $this->operator()->nickname;
        $data['user_id'] = $this->operator()->login_id;

        // Send mail for user
        $this->send_mail('mails/withdraw', [
            'to' => $this->operator()->email,
            'subject' => $this->subject_email['withdraw']
        ], $data);

        // Return
        return $this->true_json();
    }

    /**
     * Inactivate user from parent account
     *
     * @param array $params
     * @internal param int $user_id
     *
     * @return array
     */
    public function suspend_user($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if ($this->operator()->primary_type != 'parent') {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Load model
        $this->load->model('user_model');
        $this->load->helper('string_helper');

        $user = $this->user_model->find_by([
            'id' => $params['user_id']
        ]);

        $this->user_model->update($params['user_id'], [
            'login_id' => $user->login_id.':withdrawn'.random_string('alnum', 15),
            'status' => 'suspended',
            'deleted_at' => business_date('Y-m-d H:i:s'),
            'deleted_by' => 'user:'.$this->operator()->id
        ]);

        $data = [];
        $data['nickname'] = $user->nickname;
        $data['user_id'] = $user->login_id;

        // Send mail for user
        $this->send_mail('mails/withdraw', [
            'to' => $user->email,
            'subject' => $this->subject_email['withdraw']
        ], $data);

        // Return
        return $this->true_json();
    }

    /**
     * Inser highest score - spec U-130
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param int $score
     *
     * @return array
     */
    public function update_highest_score($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('score', 'スコア', 'required|integer');
        $v->set_rules('type', 'タイプ', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Filter type
        if ($params['type'] != 'battle') {
            return $this->false_json(self::BAD_REQUEST);
        }

        $score = $params['score'];

        // Load model
        $this->load->model('user_model');

        // Select user highest_score
        $user = $this->user_model
            ->select('highest_score')
            ->where('id', $params['user_id'])
            ->first();

        // if score > highest score of user, it will update highest score user
        if (!($user->highest_score >= $score)) {
            $this->user_model
                ->update($params['user_id'], ['highest_score' => $score]);
        }

        // Return
        return $this->true_json();
    }

    /**
     * Get 1st score in ranking global API - Spec U-140
     * 
     * @param array $params
     * 
     * @return array
     */
    public function get_highest_score($params = [])
    {
        // Validate
        // Run validate
        // Load model
        $this->load->model('user_model');

        // Set query
        $res = $this->user_model
            ->select('login_id, nickname, MAX(highest_score) as highest_score, primary_type')
            ->where('primary_type', 'student')
            ->first();

        // Return
        return $this->build_responses($res);

    }

    /**
     * Send mail for coin expiration
     *
     * @param array $params
     * @internal param int $login_id
     * @internal param int $nickname
     * @internal param int $current_coin
     * @internal param int $email
     *
     * @return array
     */
    public function expire_send_mail($params) {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('login_id', 'Login ID', 'required');
        $v->set_rules('nickname', 'Nickname', 'required');
        $v->set_rules('current_coin', 'Current Coin', 'required');
        $v->set_rules('email', 'Email', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $mail_data = [
            'login_id' => $params['login_id'],
            'nickname' => $params['nickname'],
            'current_coin' => $params['current_coin']
        ];
        // Send mail to student
        $this->send_mail('mails/expire_current_coin', [
            'to' => $params['email'],
            'subject' => '【スクールTV】コインの利用期限切れのお知らせ'
        ], $mail_data);

        return $this->true_json();
    }

    /**
     * Check user has parent
     *
     * @param array $params
     * @internal param int $user_id
     *
     * @return array
     */
    public function check_user_has_parent($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_group_model');

        // Set query
        $groups = $this->user_group_model
            ->select('group_id')
            ->where('user_id', $params['user_id'])
            ->all();

        // Check exist parent
        $bool = FALSE;
        foreach ($groups AS $key => $group) {
            $res = $this->user_model
                ->select('user.id')
                ->with_group()
                ->where('user_group.group_id', $group->group_id)
                ->where('primary_type', 'parent')
                ->first();

            if (!empty($res)) {
                $bool = TRUE;
                continue;
            }
        }

        // Return
        return $bool;
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = [])
    {

        if (empty($res)) {
            return [];
        }

        $result = get_object_vars($res);

        // Build user list
        if (array_key_exists('group_id', $options) && array_key_exists('group_type', $options)) {

            $this->load->model('user_group_model');
            $group_ids = $this->user_group_model->get_user_group_id_by_type($res->id, $options['group_type']);

            if ($options['group_type'] == 'friend') {
                // Load model
                $this->load->model('group_model');
                $this->load->model('user_friend_model');

                // Get group info
                $members = $this->group_model->get_member($options['group_id']);

                $result['button_style'] = 3;

                foreach($members AS $member) {

                    $user_friend = $this->user_friend_model->find_by([
                        'user_id' => $member->id,
                        'target_id' => $res->id
                    ]);

                    if (!empty($user_friend)) {
                        $result['button_style'] = 1;
                        break;
                    }
                }

            } elseif ($res->primary_type == 'student' && !empty($group_ids) && $options['group_type'] == 'family') {
                $result['button_style'] = 3;
            } else{
                $result['button_style'] = 1;
            }

            if (in_array($options['group_id'], $group_ids)) {
                $result['button_style'] = 2;
            }
        }

        // Build deleted user
        if (in_array('user_get_all', $options)) {
            unset($result['user_id']);
            unset($result['password']);
        }

        // Build friend response
        if (in_array('friends', $options)) {
            $result['is_friend'] = TRUE;
        }

        // Build non-friend response
        if (in_array('non_friends', $options)) {
            $result['is_friend'] = FALSE;
        }

        // Build user info
        if (in_array('user_info', $options)) {
            $result = $this->build_user_response($res, $options);
        }

        if (isset($result['group_id'])) {
            $result['group_id'] = (int)$result['group_id'];
        }

        if (isset($result['id'])) {
            $result['id'] = (int) $result['id'];
        }
        // Build user info
        if (in_array('group', $options)) {
            $result = array_merge($result, [
                'role' => $res->role,
                'group_id' => (int) $res->group_id
            ]);
        }

        // Build admin response
        if (in_array('user_get_all', $options)) {

            $this->load->model('user_school_history_model');
            $this->load->model('user_grade_history_model');

            $school = $this->user_school_history_model
                ->select('master_school.name, master_school.id, master_school.address, master_school.type')
                ->join('master_school', 'user_school_history.school_id = master_school.id', 'left')
                ->where('user_school_history.id', $res->current_school)
                ->first();

            $grade = $this->user_grade_history_model
                ->select('master_grade.name, master_grade.id')
                ->join('master_grade', 'user_grade_history.grade_id = master_grade.id', 'left')
                ->where('user_grade_history.id', $res->current_grade)
                ->first();

            $current_school = isset($school->id) ? [
                'id' => (int)$school->id,
                'name' => isset($school->name) ? $school->name : NULL,
                'address' => isset($school->address) ? $school->address : NULL,
                'type' => !empty($school->type) ? $school->type : NULL
            ] : [];

            $current_grade = isset($grade->id) ? [
                'id' => (int)$grade->id,
                'name' => isset($grade->name) ? $grade->name : NULL
            ] : [];


            $result['invited_from_id'] = $res->invited_from_id ? $res->invited_from_id : null;
            $result['created_at'] = $res->created_at ? $res->created_at : null ;
            $result['registered_at'] = $res->registered_at ? $res->registered_at : null;
            $result['deleted_at'] = $res->deleted_at ? $res->deleted_at : null;
            $result['current_school'] = $current_school;
            $result['current_grade'] = $current_grade;
        }

        // Build list_admin
        if (in_array('point_remain_admin', $options)) {
            $this->load->model('user_rabipoint_model');

            $rabipoint = $this->user_rabipoint_model
                ->select('SUM(point_remain) AS current_rabipoint')
                ->where('user_id', $res->id)
                ->where('type !=', User_rabipoint_model::RP_EXPIRED_POINT)
                ->first();

            $result['current_rabipoint'] = !empty($rabipoint->current_rabipoint) ? (int) $rabipoint->current_rabipoint : 0;
        }

        return $result;
    }

}

/**
 * Class User_api_validator
 *
 * @property User_api $base
 */
class User_api_validator extends Base_api_validation
{

    /**
     * Validate type
     *
     * @param String $type
     *
     * @return bool
     */
    function valid_type($type)
    {

        if (!in_array($type, ['student', 'parent'])) {
            $this->set_message('valid_type', 'タイプは（子ども、親）です');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate type
     *
     * @param String $confirm_password
     * @param String $password
     *
     * @return bool
     */
    function valid_confirm_password($confirm_password, $password)
    {

        if ($confirm_password != $password) {
            $this->set_message('valid_confirm_password', '新しいパスワード（確認）が違います');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate type
     *
     * @param String $current_password
     *
     * @return bool
     */
    function valid_current_password($current_password)
    {

        // Load model
        $this->base->load->model('user_model');

        $user = $this->base->user_model->find_by([
            'id' => $this->base->operator()->id,
            'password' => $this->base->user_model->encrypt_password($current_password)
        ]);

        if (!$user) {
            $this->set_message('valid_current_password', 'パスワードが違います');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate login id exist
     *
     * @param string $login_id
     * @param int $id of user
     *
     * @return bool
     */
    function valid_login_id_exist($login_id, $id)
    {

        // Load model
        $this->base->load->model('user_model');

        $user = $this->base->user_model->where([
            'id !=' => $id,
            'login_id' => $login_id
        ])->all();

        if ($user) {
            $this->set_message('valid_login_id_exist', 'このログインIDはすでに使用されています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate login id is duplicate
     *
     * @param string $login_id
     *
     * @return bool
     */
    function valid_login_id_duplicate($login_id)
    {

        // Load model
        $this->base->load->model('user_model');

        $user = $this->base->user_model
            ->where('login_id', $login_id)
            ->first([
                'with_deleted' => TRUE
            ]);

        if ($user) {
            $this->set_message('valid_login_id_duplicate', 'このログインIDはすでに使用されています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate type
     *
     * @param string $email
     * @param int $id of user
     *
     * @return bool
     */
    function valid_email_duplicate($email, $id)
    {

        // Load model
        $this->base->load->model('user_model');

        $user = $this->base->user_model->where([
            'id !=' => $id,
            'email' => $email
        ])->all();

        if ($user) {
            $this->set_message('valid_email_duplicate', 'メールアドレスが存在しています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Valid Oauth type
     *
     * @param string $type of oauth
     *
     * @return bool
     */
    function valid_oauth_type($type)
    {

        if (!in_array($type, ['facebook', 'twitter'])) {
            $this->set_message('valid_oauth_type', '契約タイプはfacebookかtwitterのどちらかです。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Valid Facebook account id
     *
     * @param int $oauth_id - facebook user id
     *
     * @return bool
     */
    function valid_facebook_id($oauth_id)
    {

        // Load model
        $this->base->load->model('user_oauth_model');

        $oauth = $this->base->user_oauth_model->where([
            'type' => 'facebook',
            'oauth_id' => $oauth_id
        ])->first();

        if ($oauth) {
            $this->set_message('valid_facebook_id', 'facebookアカウントが存在します');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Valid Twitter account id
     *
     * @param int $oauth_id - Twitter account id
     *
     * @return bool
     */
    function valid_twitter_id($oauth_id)
    {

        // Load model
        $this->base->load->model('user_oauth_model');

        $oauth = $this->base->user_oauth_model->where([
            'type' => 'twitter',
            'oauth_id' => $oauth_id
        ])->first();

        if ($oauth) {
            $this->set_message('valid_twitter_id', 'Twitterアカウントが存在します');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Valid promotion_code
     *
     * @param string promotion_code
     *
     * @return bool
     */
    public function valid_promotion_code($promotion_code = '')
    {
        $promotion_code = str_replace('-', '', trim($promotion_code));
        // Check input 12 numbers
        if (strlen($promotion_code) != 12 || !is_numeric($promotion_code)) {
            $this->set_message('valid_promotion_code', '紹介コードは無効です。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Valid campaign_code
     *
     * @param string campaign_code
     *
     * @return bool
     */
    public function valid_campaign_code($campaign_code = '')
    {
        // Load model
        $this->base->load->model('campaign_model');

        $campaign = $this->base->campaign_model->where([
            'code' => $campaign_code
        ])->first();

        if (empty($campaign) || (strtotime($campaign->ended_at) < strtotime("now") && strtotime($campaign->started_at) > strtotime("now"))) {
            $this->set_message('valid_campaign_code', '無効なキャンペーンコードです。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate birthday must to be older than today
     *
     * @param string $birthday
     *
     * @return bool
     */
    function birthday_older($birthday = NULL) {

        if(!empty($birthday) && strtotime($birthday) > strtotime(business_date('Y-m-d H:i:s'))) {
            $this->set_message('birthday', '誕生日は今日以前の必要があります');
            return FALSE;
        }

        return TRUE;
    }
}
