<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_oauth_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_oauth_model extends APP_Paranoid_model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_oauth';
    public $primary_key = 'id';

}