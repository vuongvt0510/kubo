<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Deck_category_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Deck_category_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'deck_category';
    public $primary_key = 'id';
}
