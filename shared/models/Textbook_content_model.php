<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Textbook_content_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Textbook_content_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'textbook_content';
    public $primary_key = 'id';
}
