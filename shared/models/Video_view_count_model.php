<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Group_model
 */
class Video_view_count_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'video_view_count';
    public $primary_key = 'video_id';
}
