<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Master_postalcode_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Master_postalcode_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'master_postalcode';
    public $primary_key = 'id';
}
