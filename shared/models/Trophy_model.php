<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Trophy_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Trophy_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'trophy';
    public $primary_key = 'id';

}