<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';
/**
 * Class Page_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 * @author DiepHQ
 */
class Page_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'page';
    public $primary_key = 'id';
}