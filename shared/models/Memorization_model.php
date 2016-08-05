<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class Question_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Memorization_model extends APP_Paranoid_model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'memorization';
    public $primary_key = 'id';
}
