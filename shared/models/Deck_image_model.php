<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Deck_image_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Deck_image_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'deck_image';
    public $primary_key = 'id';

}
