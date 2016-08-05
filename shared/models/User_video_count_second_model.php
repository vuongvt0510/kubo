<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_video_count_second_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_video_count_second_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_video_count_second';
    public $primary_key = 'user_id';

}