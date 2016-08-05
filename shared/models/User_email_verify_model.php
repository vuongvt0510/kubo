<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';


/**
 * User_email_verify_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_email_verify_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_email_verify';
    public $primary_key = 'id';

    /**
     * @param int $login_id of user
     *
     * @return bool|object
     */
    public function check_login_id($login_id = null)
    {
        // Load model
        $this->load->model('user_model');
        $this->load->helper('string_helper');

        if (!empty($login_id)) {
            $this->user_model->where('login_id', $login_id);
        }

        $users = $this->user_model
            ->select('user.id, user.login_id, user.primary_type, user_email_verify.id as token_id')
            ->join('user_email_verify', 'user_email_verify.user_id = user.id')
            ->where('user_email_verify.expired_at < ', business_date('Y-m-d H:i:s'))
            ->where('user.status is null')
            ->where('user.email_verified', 0)
            ->all();

        foreach ($users as $user) {
            $this->destroy($user->token_id);
            $this->user_model
                ->update($user->id, [
                    'login_id' => $user->login_id.':not_verify'.random_string('alnum', 15),
                    'user.status' => 'unauth',
                    'user.deleted_at' => business_date('Y-m-d H:i:s'),
                    'user.deleted_by' => 'system'
                ]);
        }
        return TRUE;
    }
}
