<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class Quest_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Quest_model extends APP_model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'quest';
    public $primary_key = 'id';
}
