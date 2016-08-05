<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Mail_queue_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Mail_queue_model extends APP_Model
{
    public $database_name = DB_MAIL;
    public $table_name = 'mail_queue';
    public $primary_key = 'id';
}
