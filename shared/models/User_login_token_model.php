<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_login_token_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_login_token_model extends APP_Paranoid_model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_login_token';
    public $primary_key = ['user_id', 'token'];
}
