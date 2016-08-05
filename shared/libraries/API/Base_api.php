<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/APP_Api.php';

/**
 * Base API
 *
 * @property User_model user_model
 * @property User_email_verify_model user_email_verify_model
 * @property User_profile_model user_profile_model
 * @property User_password_verify_model user_password_verify_model
 * @property User_group_model user_group_model
 * @property User_invite_code_model user_invite_code_model
 * @property Group_model group_model
 * @property Textbook_model textbook_model
 * @property Master_area_model master_area_model
 * @property Master_area_pref_model master_area_pref_model
 * @property News_model news_model
 * @property Textbook_content_model textbook_content_model
 *
 * @property APP_Loader load
 * @property CI_Session session
 * @property CI_Lang lang
 * @property APP_Config config
 * @property APP_Smarty smarty
 * @property APP_Email email
 * @property APP_Input input
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class Base_api extends APP_Api
{
    const USER_NOT_FOUND = 41010;
    const TOKEN_NOT_FOUND = 41020;
    const USER_IS_INACTIVE = 41030;

    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Base_api_validation';

    /**
     * @var Base_api_validation
     */
    public $validator = NULL;

    /**
     * @var array subject japan email
     */
    public $subject_email = [
        'verify_email_parent' => '【スクールTV】メールアドレス認証を行ってください（保護者の方）',
        'verify_email_student' => '【スクールTV】メールアドレス認証を行ってください（お子様）',
        'resend_email' => '【スクールTV】（再送）メールアドレス認証を行ってください',
        'change_email' => '【スクールTV】（メールアドレス変更）メールアドレスを認証してください',
        'user_invite' => 'いっしょに学習しよう！【スクールTV】学校の教科書で学べる動画サービス',
        'resend_id' => '【スクールTV】ご利用中のIDをお知らせします',
        'reset_password' => '【スクールTV】パスワードを再設定してください',
        'group_invite' => '【スクールTV】家族グループへの招待が届いています（保護者の方）',
        'contact_us' => '【スクールTV】ユーザーからお問合せが届きました',
        'contact_user' => '【スクールTV】お問い合わせを受け付けました',
        'purchase_coin_success' => '【スクールTV】コインを購入しました',
        'purchase_contract_success' => '【スクールTV】スクールTV Plusのお申し込みありがとうございます',
        'purchase_manually_pending_success' => '【スクールTV】スクールTV Plus決済完了のお知らせ',
        'cancel_contract' => '【スクールTV】スクールTV Plusの解約を受付けました',
        'withdraw' => '【スクールTV】退会手続きが完了しました',
        'delete_student' => '【スクールTV】退会手続きが完了しました',
        'invite_new_user' => '【スクールTV】チームへ招待されています',
        'students_learning' => '【スクールTV】お子さまの学習状況をお知らせします'
    ];

    /**
     * Send mail
     *
     * @param string $path
     * @param array $config Config to send email
     * @param array $data Data to create body
     *
     * @internal param string $to Email to
     * @internal param string $to_name Email to name
     * @internal param string $subject Subject of sending mail
     *
     * return void
     */
    public function send_mail($path, $config = [], $data = [])
    {
        // Load the library
        $this->load->library('smarty');

        $res = $this->smarty->view(SHAREDPATH . 'views/' . $path, array_merge($data, [
            'service_name' => $this->config->item('service_name')
        ]), TRUE);

        // Remove un-use resource
        unset($this->smarty);

        // Send
        $this->load->library('email');
        $this->email
            ->from($this->config->item('mail_from'), $this->config->item('mail_from_name'))
            ->to($config['to'], !empty($config['to_name']) ? $config['to_name'] : null)
            ->subject($config['subject'])
            ->message($res)
            ->send();
    }

    /**
     * Set params default
     *
     * @param array $params
     * @return array
     */
    public function _set_default(&$params)
    {
        // Set the value for Offset : default 0
        $params['offset'] = isset ($params['offset']) && !empty($params['offset'])
            ? (int)$params['offset'] : 0;

        // Set the value for Limit : default 20
        $params['limit'] = isset ($params['limit']) && !empty($params['limit'])
            ? (int)$params['limit'] : 20;

        // Set maximum limit for limit params for security
        if ($params['limit'] > 200) {
            $params['limit'] = 200;
        }

        return $params;
    }

    /**
     * Build the user information response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_user_response($res, $options = [])
    {

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_group_model');

        // Get user info
        $user = $this->user_model->get_user_info(isset($res->user_id) ? $res->user_id : $res->id);

        // Build user info
        $user_info = [];
        if (in_array('user_detail', $options)) {
            $user_info = [
                'email' => isset($user->email) ? $user->email : NULL,
                'authorized_email' => isset($user->email_verified) ? to_bool($user->email_verified) : NULL,
                'in_group' => $this->user_group_model->get_user_group_id(isset($user->id) ? $user->id : NULL),
            ];
        }

        $current_school = isset($user->school_id) ? [
            'id' => (int)$user->school_id,
            'name' => isset($user->school_name) ? $user->school_name : NULL,
            'address' => isset($user->school_address) ? $user->school_address : NULL,
            'type' => !empty($user->school_type) ? $user->school_type : NULL
        ] : [];

        $current_grade = isset($user->grade_id) ? [
            'id' => (int)$user->grade_id,
            'name' => isset($user->grade_name) ? $user->grade_name : NULL
        ] : [];

        return array_merge([
            'id' => isset($user->id) ? (int)$user->id : NULL,
            'login_id' => isset($user->login_id) ? $user->login_id : NULL,
            'primary_type' => isset($user->primary_type) ? $user->primary_type : NULL,
            'email_verified' => isset($user->email_verified) ? $user->email_verified : NULL,
            'oauth_type' => isset($user->oauth_type) ? $user->oauth_type : NULL,
            'nickname' => isset($user->nickname) ? $user->nickname : NULL,
            'current_school' => $current_school,
            'current_grade' => $current_grade,
            'status' => isset($user->status) ? $user->status : NULL,
            'avatar' => isset($user->avatar_id) ? (int)$user->avatar_id : NULL,
            'gender' => isset($user->gender) ? $user->gender : NULL,
            'birthday' => isset($user->birthday) ? $user->birthday : NULL,
            'postalcode' => isset($user->postalcode) ? $user->postalcode : NULL,
            'address' => isset($user->address) ? $user->address : NULL,
            'phone' => isset($user->phone) ? $user->phone : NULL
        ], $user_info);
    }

    /**
     * Build the video information response
     *
     * @param object $res
     * @return array
     */
    public function build_video_response($res)
    {
        return [
            'id' => isset($res->id) ? (int)$res->id : NULL,
            'brightcove_id' => isset($res->brightcove_id) ? $res->brightcove_id : NULL,
            'name' => isset($res->name) ? $res->name : NULL,
            'type' => isset($res->type) ? $res->type : NULL,
            'description' => isset($res->description) ? $res->description : NULL,
            'created_at' => isset($res->created_at) ? $res->created_at : NULL
        ];
    }

    /**
     * Build the chapter information response
     *
     * @param object $res
     * @return array
     */
    public function build_chapter_response($res)
    {
        return [
            'id' => isset($res->id) ? (int)$res->id : null,
            'name' => isset($res->name) ? $res->name : null,
            'chapter_name' => isset($res->chapter_name) ? $res->chapter_name : null,
            'description' => isset($res->description) ? $res->description : null,
            'order' => isset($res->order) ? (int)$res->order : null,
            'deck_id' => isset($res->deck_id) ? (int)$res->deck_id : null,
            'video_id' => isset($res->video_id) ? (int)$res->video_id : null,
            'subject_short_name' => isset($res->subject_short_name) ? $res->subject_short_name : null,
            'subject_color' => isset($res->color) ? $res->color : null,
            'subject_type' => isset($res->type) ? $res->type : null,
        ];
    }

    /**
     * Build the user information response
     *
     * @param object $res
     * @return array
     */
    public function build_user_textbook_response($res)
    {
        return [
            'textbook' => [
                'id' => (int)$res->textbook_id, 'name' => $res->textbook_name
            ],
            'subject' => [
                'id' => (int)$res->subject_id, 'name' => $res->subject_name,
                'short_name' => $res->subject_short_name,
                'type' => $res->type, 'color' => $res->color
            ],
            'grade' => [
                'id' => (int)$res->grade_id, 'name' => $res->grade_name
            ],
            'publisher' => [
                'id' => (int)$res->publisher_id, 'name' => $res->publisher_name
            ]
        ];
    }

}

class Base_api_validation extends APP_Api_validation
{

    /**
     * パーミッション
     * @var array
     */
    private $_has_permission = TRUE;

    /**
     * パーミッションチェック
     *
     * @access public
     * @return void
     */
    public function require_permissions()
    {
        $permissions = func_get_args();
        $this->_has_permission = (!$this->base->operator()->is_anonymous() && $this->base->operator()->has_all_permissions($permissions));
    }

    /**
     * 一部のパーミッションを取得しているか
     *
     * @access public
     * @return void
     */
    public function require_either_permissions()
    {
        $permissions = func_get_args();
        $this->_has_permission = (!$this->base->operator()->is_anonymous() && $this->base->operator()->has_either_permissions($permissions));
    }

    /**
     * Check email existing in system
     *
     * @var string
     * @return bool
     */
    function valid_email_exist($email)
    {

        // Load model
        $this->base->load->model('user_model');
        // Get the email
        $email = $this->base->user_model->find_by(['email' => $email]);

        // If existing return error
        if ($email) {
            $this->set_message('valid_email_exist', 'このメールアドレスは使用されています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check ID existing in system
     *
     * @var string
     * @return bool
     */
    function valid_id($id)
    {

        // Load model
        $this->base->load->model('user_model');
        // Get the user ID
        $email = $this->base->user_model->find_by(['nickname' => $id]);

        // If existing return error
        if ($email) {
            $this->set_message('valid_id', 'このログインIDはすでに使用されています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check User ID existing in system
     *
     * @var string
     * @return bool
     */
    function valid_user_id($user_id)
    {

        // Load model
        $this->base->load->model('user_model');

        // Get the user ID
        $user = $this->base->user_model
            ->find($user_id);

        // If existing return error
        if (!$user) {
            $this->set_message('valid_user_id', '該当のユーザーが存在しません');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check News ID existing in system
     *
     * @var string
     * @return bool
     */
    function valid_news_id($news_id)
    {

        // Load model
        $this->base->load->model('news_model');

        // Get the user ID
        $news = $this->base->news_model
            ->find($news_id);

        // If existing return error
        if (!$news) {
            $this->set_message('valid_news_id', '該当のユーザーが存在しません');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check Token existing in system
     *
     * @param string $token
     * @param string $type
     * @return bool
     */
    function valid_token($token, $type)
    {

        $model = $type . '_model';
        // Load model
        $this->base->load->model($model);
        // Get the user ID
        $token = $this->base->{$model}->find_by([
            'token' => $token,
            'expired_at > ' => business_date('Y-m-d H:i:s')
        ]);

        // If existing return error
        if (!$token) {
            $this->set_message('valid_token', '認証トークンが間違っているか、有効期限が過ぎています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check Login id is incorrect
     *
     * @param string $login_id
     *
     * @return bool
     */
    function valid_login_id($login_id)
    {
        // If existing return error
        if (preg_match('/\s/', $login_id) > 0 || preg_match('/[^A-Za-z0-9@._-]/i', $login_id) > 0) {
            $this->set_message('valid_login_id', '半角英数字（a-z,0-9）、記号は半角の「@（アットマーク）」「_（アンダーバー）」「.（ピリオド）」「-（ハイフン）」のみ使用できます');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check format password is incorrect
     *
     * @param string $password
     *
     * @return bool
     */
    function valid_format_password($password)
    {
        // If existing return error
        if (preg_match('/\s/', $password) > 0 || preg_match('/[^a-z0-9]/i', $password) > 0) {
            $this->set_message('valid_format_password', '半角英数字（a-z,0-9）のみ使用できます。記号は使用できません。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check Grade ID is not exist
     *
     * @param string $grade_id
     *
     * @return bool
     */
    function valid_grade_id($grade_id)
    {

        $this->base->load->model('master_grade_model');

        // If existing return error
        if (!$this->base->master_grade_model->find($grade_id)) {
            $this->set_message('valid_grade_id', '学年の設定が間違っています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check Group ID is not exist
     *
     * @param string $group_id
     *
     * @return bool
     */
    function valid_group_id($group_id)
    {

        $this->base->load->model('group_model');

        // If existing return error
        if (!$this->base->group_model->find($group_id)) {
            $this->set_message('valid_group_id', 'グループの設定が間違っています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check School is is not exist
     *
     * @param int $school_id
     *
     * @return bool
     */
    function valid_school_id($school_id)
    {

        $this->base->load->model('master_school_model');

        // If existing return error
        if (!$this->base->master_school_model->find($school_id)) {
            $this->set_message('valid_school_id', '学校の設定が間違っています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check Video is is not exist
     *
     * @param int $video_id
     *
     * @return bool
     */
    function valid_video_id($video_id)
    {

        $this->base->load->model('video_model');

        // If existing return error
        if (!$this->base->video_model->find($video_id)) {
            $this->set_message('valid_video_id', 'ビデオIDが間違っています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check School is is not exist
     *
     * @param int $tb_id
     *
     * @return bool
     */
    function valid_textbook_id($tb_id)
    {

        $this->base->load->model('textbook_model');

        // If existing return error
        if (!$this->base->textbook_model->find($tb_id)) {
            $this->set_message('valid_textbook_id', '教科書の設定が間違っています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Valid Group role
     *
     * @param string $role
     *
     * @return bool
     */
    function valid_group_role($role)
    {

        // If existing return error
        if (!in_array($role, ['owner', 'admin', 'member'])) {
            $this->set_message('valid_group_role', '設定値は、オーナー・管理者・メンバーである必要があります');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate type
     *
     * @param String $type
     *
     * @return bool
     */
    function valid_primary_type($type)
    {

        if (!in_array($type, ['family', 'friend'])) {
            $this->set_message('valid_primary_type', '設定値は、家族か友達である必要があります');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Token and group
     *
     * @param String $group_id
     * @param String $token
     *
     * @return bool
     */
    function valid_group_id_invite_token($group_id, $token)
    {

        // Load model
        $this->base->load->model('group_invite_model');

        // Check the group token
        $g_token = $this->base->group_invite_model->find_by([
            'group_id' => $group_id,
            'token' => $token
        ]);

        if (!$g_token) {
            $this->set_message('valid_group_id_invite_token', '認証トークンが間違っているか、有効期限が過ぎています');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate multiple id
     *
     * @param int $id
     *
     * @return bool
     */
    function valid_multiple_id($id)
    {

        $is_type = TRUE;

        $id = explode(',', $id);

        foreach ($id as $k) {
            if (!is_numeric($k)) {
                $is_type = FALSE;
            }
        }

        if (!$is_type) {
            $this->set_message('valid_multiple_id', 'IDが間違っています。IDはコンマで区切られた数字です。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check Deck ID is not exist
     *
     * @param int $deck_id
     *
     * @return bool
     */
    function valid_deck_id($deck_id)
    {
        $this->base->load->model('deck_model');

        // If existing return error
        if (!$this->base->deck_model->find($deck_id)) {
            $this->set_message('valid_deck_id', 'デッキが存在していません。');
            return FALSE;
        }

        return TRUE;
    }

}
