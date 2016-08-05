<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';
require_once SHAREDPATH . 'core/APP_Operator.php';

/**
 * Class User_profile_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_profile_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_profile';
    public $primary_key = 'user_id';
}
