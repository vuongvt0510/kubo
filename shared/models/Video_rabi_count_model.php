<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';
require_once SHAREDPATH . 'core/APP_Operator.php';

/**
 * Class Video_rabi_count_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Video_rabi_count_model extends APP_Paranoid_model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'video_rabi_count';
    public $primary_key = 'id';
}
