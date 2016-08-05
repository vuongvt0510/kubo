<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_buying_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_buying_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_buying';
    public $primary_key = ['id'];

    const TYPE_OF_DECK = 'deck';
    const TYPE_OF_EXPIRED = 'expired';
}
