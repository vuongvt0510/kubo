<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class Action_history_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Action_history_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'action_history';
    public $primary_key = 'id';

}