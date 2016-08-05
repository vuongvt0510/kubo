<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_invite_code
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_invite_code_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_invitation_code';
    public $primary_key = 'id';
}
