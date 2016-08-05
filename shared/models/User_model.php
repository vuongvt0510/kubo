<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';
require_once SHAREDPATH . 'core/APP_Operator.php';

/**
 * Class User_model
 *
 * @property APP_Loader load
 * @property User_school_history_model user_school_history_model
 * @property User_grade_history_model user_grade_history_model
 * @property User_promotion_code_model user_promotion_code_model
 * @property User_profile_model user_profile_model
 * @property User_login_token_model user_login_token_model
 * @property User_oauth_model user_oauth_model
 * @property User_power_model user_power_model
 * @property User_contract_model user_contract_model
 */
class User_model extends APP_Paranoid_model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user';
    public $primary_key = 'id';

    public $record_class = 'User_record';

    /**
     * User register
     *
     * @param array $attributes
     * @param array $options
     *
     * @return bool|object
     */
    public function register($attributes, $options = [])
    {
        $this->transaction(function() use($attributes, &$user) {
            // Load model
            $this->load->model('user_profile_model');
            $this->load->model('user_grade_history_model');
            $this->load->model('user_promotion_code_model');
            $this->load->model('user_oauth_model');
            $this->load->model('user_power_model');
            $this->load->model('user_contract_model');

            /** @var object $user Create user*/
            $user = parent::create([
                'login_id' =>  $attributes['id'],
                'email' =>  $attributes['email'],
                'password' =>  $this->encrypt_password($attributes['password']),
                'primary_type' =>  $attributes['type'],
                'invited_from_id' => isset($attributes['invited_from_id'])? $attributes['invited_from_id'] : NULL,
                'nickname' =>  isset($attributes['nickname'])? $attributes['nickname'] : NULL
            ], [
                'return' => TRUE
            ]);

            // Create invite code
            if (!empty($attributes['promotion_code'])) {
                $promotion_code = str_replace('-', '', $attributes['promotion_code']);
                $this->user_promotion_code_model->create([
                    'user_id' => $user->id,
                    'code' => $promotion_code,
                    'type' => User_promotion_code_model::TYPE_FORCECLUB
                ]);
            }

            // Create campaign code
            if (!empty($attributes['campaign_code'])) {
                $this->user_promotion_code_model->create([
                    'user_id' => $user->id,
                    'code' => $attributes['campaign_code'],
                    'type' => User_promotion_code_model::TYPE_CAMPAIGN
                ]);
            }

            // Create user profile
            $this->user_profile_model->create([
                'user_id' => $user->id,
                'avatar_id' => 0
            ]);

            // Create user grade history
            if (isset($attributes['grade_id'])) {

                /** @var object $u_grade */
                $u_grade = $this->user_grade_history_model->create([
                    'user_id' => $user->id,
                    'grade_id' => $attributes['grade_id'],
                    'registered_at' => business_date('Y-m-d H:i:s')
                ], ['return' => TRUE] );

                // Update again user grade
                $this->update($user->id, [
                    'current_grade' => $u_grade->id
                ], [
                    'return' => TRUE,
                    'master' => TRUE
                ]);
            }

            // Create power and free fee base plan for user
            if ($attributes['type'] == 'student') {
                if (isset($attributes['parent_id'])) {
                    $this->load->model('action_history_model');
                    $this->action_history_model->create([
                        'user_id' => $attributes['parent_id'],
                        'object' => 'register',
                        'action' => 'same_time',
                        'target_id' => $user->id
                    ]);

                    $this->action_history_model->create([
                        'user_id' => $user->id,
                        'object' => 'register',
                        'action' => 'same_time',
                        'target_id' => $attributes['parent_id']
                    ]);
                }

                $this->user_power_model->create([
                    'user_id' => $user->id,
                    'max_power' => DEFAULT_MAX_USER_POWER,
                    'current_power' => DEFAULT_MAX_USER_POWER
                ], [
                    'master' => TRUE
                ]);

                $this->user_contract_model->create([
                    'user_id' => $user->id,
                    'status' => 'free',
                    'expired_time' => business_date('Y-m-d H:i:s', strtotime('+30 days', business_time()))
                ]);
            }

            // Set relation user with oauth

            if(!empty($attributes['oauth_facebook_id'])) {
                $this->user_oauth_model->create([
                    'user_id' => $user->id,
                    'type' => 'facebook',
                    'oauth_id' => $attributes['oauth_facebook_id'],
                    'access_token' => isset($attributes['oauth_facebook_access_token']) ? $attributes['oauth_facebook_access_token'] : ''
                ], [
                    'master' => TRUE
                ]);
            }

            if(!empty($attributes['oauth_twitter_id'])) {
                $this->user_oauth_model->create([
                    'user_id' => $user->id,
                    'type' => 'twitter',
                    'oauth_id' => $attributes['oauth_twitter_id'],
                    'access_token' => isset($attributes['oauth_twitter_access_token']) ? $attributes['oauth_twitter_access_token'] : ''
                ], [
                    'master' => TRUE
                ]);
            }

            return TRUE;
        });

        return $user;
    }

    /**
     * @param int $user_id of user
     * @param int $school_id of master_school
     *
     * @return bool|object
     */
    public function update_school($user_id, $school_id)
    {
        $this->transaction(function() use($user_id, $school_id, &$res) {

            // Load model
            $this->load->model('user_school_history_model');

            // Create user school history
            $school = $this->user_school_history_model->create([
                'user_id' =>  $user_id,
                'school_id' =>  $school_id
            ], [
                'return' => TRUE
            ]);

            /** @var object $res Update user school */
            $res = $this->update($user_id, [
                'current_school' => $school->id
            ], [
                'return' => TRUE
            ]);

            return TRUE;
        });

        return $res;
    }

    /**
     * @param array $user_id of user
     * @param array $grade_id of master_grade
     *
     * @return bool|object
     */
    public function update_grade($user_id, $grade_id)
    {
        $this->transaction(function() use($user_id, $grade_id, &$res) {

            // Load model
            $this->load->model('user_grade_history_model');

            // Create user school history
            $res = $this->user_grade_history_model->create([
                'user_id' =>  $user_id,
                'grade_id' =>  $grade_id,
                'registered_at' => business_date('Y-m-d H:i:s')
            ], ['return' => TRUE] );

            /** @var object $res Update user grade */
            $res = $this->update($user_id, [
                'current_grade' => $res->id
            ], ['return' => TRUE] );

            return TRUE;
        });

        return $res;
    }

    /**
     * fetch with profile info
     *
     * @access public
     * @return User_model
     */
    public function with_profile()
    {
       return $this->join('user_profile', 'user.id = user_profile.user_id', 'left');
    }

    /**
     * fetch with profile info
     *
     * @access public
     * @return User_model
     */
    public function with_oauth()
    {
        return $this->join('user_oauth', 'user.id = user_oauth.user_id', 'left');
    }

    /**
     * fetch with school info
     *
     * @access public
     * @return User_model
     */
    public function with_school()
    {
        return $this->join('master_school', 'user_school_history.school_id = master_school.id', 'left');
    }

    /**
     * fetch with grade info
     *
     * @access public
     * @return User_model
     */
    public function with_grade()
    {
        return $this->join('master_grade', 'user_grade_history.grade_id = master_grade.id', 'left');
    }

    /**
     * fetch with user_school_history
     *
     * @access public
     * @return User_model
     */
    public function with_school_history()
    {
        return $this->join('user_school_history', 'user.current_school = user_school_history.id', 'left');
    }

    /**
     * fetch with user_grade_history
     *
     * @access public
     * @return User_model
     */
    public function with_grade_history()
    {
        return $this->join('user_grade_history', 'user.current_grade = user_grade_history.id', 'left');
    }

    /**
     * fetch with user invite
     *
     * @access public
     * @return User_model
     */
    public function with_user_invite()
    {
        return $this->join('user as user_invite', 'user_invite.id = user.invited_from_id', 'left');
    }

    /**
     * fetch with promotion code
     *
     * @access public
     * @return User_model
     */
    public function with_promotion_code()
    {
        return $this->join('user_promotion_code', 'user_promotion_code.user_id = user.id', 'left');
    }

    /**
     * fetch with user group
     * 
     * @access public
     * @return User_model
     */
    public function with_group()
    {
        return $this->join('user_group', 'user_group.user_id = user.id');
    }

    /**
     * fetch user is active|inactive
     *
     * @param bool $available
     *
     * @access public
     * @return User_model
     */
    public function available($available = TRUE)
    {
        return $this->where('user.status', ($available) ? 'active' : 'inactive');
    }

    /**
     * Verify email token by fetching user_email_verify table
     *
     * @access public
     */
    public function verify_email_token()
    {

    }

    /**
     * Fetch with user oauth
     *
     * @access public
     */
    public function with_user_oauth()
    {
        return $this->join('user_oauth', 'user_oauth.user_id = user.id', 'left');
    }

    /**
     * Encrypt password
     *
     * @access public
     *
     * @param string $password
     *
     * @return string
     */
    public function encrypt_password($password)
    {
        return sha1($password);
    }

    /**
     * Set user password
     *
     * @access public
     *
     * @param array $attributes
     * @return array
     */
    public function set_encrypted_password(& $attributes)
    {
        if (isset($attributes['password'])) {
            $attributes['password'] = $this->encrypt_password($attributes['password']);
        }

        return $attributes;
    }

    /**
     * Get user information
     *
     * @access public
     *
     * @param int $user_id
     * @return object
     */
    public function get_user_info($user_id){

        // Fetch user info
        return $this
            ->select('user.id, user.login_id, user.nickname, user.email, user.primary_type, user.status, user.email_verified')
            ->select('user_profile.birthday, user_profile.avatar_id, user_profile.gender, user_profile.postalcode, user_profile.address, user_profile.phone')
            ->select('master_grade.name as grade_name, master_grade.id as grade_id')
            ->select('master_school.name as school_name, master_school.id as school_id, master_school.address as school_address, master_school.type as school_type, user_oauth.type as oauth_type')
            ->with_school_history()
            ->with_grade_history()
            ->with_user_oauth()
            ->with_profile()
            ->with_school()
            ->with_grade()
            ->where('user.id', $user_id)
            ->first([
                'master' => TRUE
            ]);
    }

    /**
     * Get auto login info
     * @param $token
     *
     * @return Object
     */
    public function get_autologin($token)
    {
        $this->load->model('user_login_token_model');
        return $this->user_login_token_model
            ->where('token', $token)
            ->first();
    }

    /**
     * @param $token
     *
     * @return bool
     */
    public function delete_autologin($token)
    {
        $this->load->model('user_login_token_model');
        $this->user_login_token_model
            ->where('token', $token)
            ->real_destroy_all();

        return TRUE;
    }

    /**
     * Check login user
     *
     * @access public
     *
     * @param string $login_id
     * @param string $password
     * @param array $options
     *
     * @return bool|object
     */
    public function authenticate($login_id, $password, $options = array())
    {
        return $this->find_by([
            'login_id' => $login_id,
            'password' => $this->encrypt_password($password)
        ], $options);
    }

    /**
     * Save auto login information
     *
     * @param int $id
     * @return array
     */
    public function set_autologin($id)
    {
        // Load helper
        $this->load->helper('string_helper');

        // Load model
        $this->load->model('user_login_token_model');

        $res = $this->user_login_token_model->create([
            'user_id' => $id,
            'token' => random_string('alnum', 32),
            'user_agent' => $this->input->user_agent(),
            'remote_ip' => $this->input->ip_address()
        ], [
            'mode' => 'replace',
            'return' => TRUE
        ]);

        return $res;
    }

    /**
     * Save highest_score of user
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $score
     * 
     * @return boolean
     */
    public function update_highest_score($params)
    {
        // Filter type play
        if ($params['type'] != 'battle') {
            return FALSE;
        }

        // Select user highest_score
        $user = $this
            ->select('highest_score')
            ->where('id', $params['user_id'])
            ->first();

        // if score > highest score of user, it will update highest score user
        if ($params['score'] > $user->highest_score) {
            $this->update($params['user_id'], [
                'highest_score' => $params['score']
            ]);

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Get position of user in ranking
     */
    public function get_ranking_position($ranking_type, $user_id)
    {

        $personal_rank = $ranking_type == 'personal' ?
            "JOIN (SELECT target_id
                FROM user_friend
                WHERE status = 'active' AND user_id = $user_id
                UNION SELECT $user_id
            ) AS friend ON friend.target_id = user_rank.id" : '';

        $rank_sql = "FIND_IN_SET(user.highest_score, (
            SELECT GROUP_CONCAT(user_rank.highest_score ORDER BY user_rank.highest_score DESC)
            FROM user AS user_rank $personal_rank WHERE user_rank.primary_type = 'student' AND user_rank.status = 'active')) AS rank";

        $res = $this->user_model
            ->select('user.id as user_id, user.highest_score, user.nickname, user.primary_type, user_profile.avatar_id')
            ->select($rank_sql)
            ->where('user.id', $user_id)
            ->with_profile()
            ->first();

        return $res;
    }

    /**
     * @param $user_id
     * @return User_model
     */
    public function fetch_group_id($user_id)
    {
        return $this->where("user_group.group_id IN (SELECT group_id	FROM user_group
                JOIN `group` ON `group`.`id` = `user_group`.`group_id` AND `group`.`primary_type` = 'family'
                WHERE user_id = $user_id)");
    }
}

/**
 * User record
 *
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class User_record implements APP_Operator
{

    /**
     * @var null ID
     */
    public $id = null;

    /**
     * @var null Name
     */
    public $nickname = null;

    /**
     * @var null Gender
     */
    public $gender = null;

    /**
     * @var null Primary type (parent|student)
     */
    public $primary_type = null;

    /**
     * 未ログインユーザーかどうか
     *
     * @access public
     * @return bool
     */
    public function is_anonymous()
    {
        return FALSE;
    }

    /**
     * ログインしているかどうか
     *
     * @access public
     * @return bool
     */
    public function is_login()
    {
        return TRUE;
    }

    /**
     * 管理者かどうか
     *
     * @access public
     * @return bool
     */
    public function is_administrator()
    {
        return FALSE;
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function has_permission($permission) { return FALSE; }

    /**
     * @param array $permissions
     * @return bool
     */
    public function has_all_permissions($permissions) { return FALSE; }

    /**
     * 操作者IDを返す
     *
     * @access public
     * @return mixed
     */
    public function _operator_id()
    {
        return $this->id;
    }

    /**
     * 操作者名を返す
     *
     * @access public
     * @return string
     */
    public function _operator_name()
    {
        return $this->nickname;
    }

    /**
     * 操作者識別子を返す
     *
     * @access public
     * @return string
     */
    public function _operator_identifier()
    {
        return "user:" . $this->id;
    }

    /**
     * Get user gender to string
     *
     * @access public
     * @return string
     */
    public function _gender()
    {
        return ($this->gender == 0) ? 'Male' : 'Female' ;
    }

    /**
     * Set user operator by
     *
     * @return string
     */
    public function _operated_by()
    {
        return "user:" . $this->id;
    }
}