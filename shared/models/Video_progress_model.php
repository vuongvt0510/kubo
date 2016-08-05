<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';
/**
 * Class Video_progress_model
 */
class Video_progress_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'video_progress';
    public $primary_key = 'id';

    
    /**
     * fetch with video done_flag
     *
     * @access public
     * @return Video_progress_model
     */
    public function is_done($flag = TRUE)
    {
        return $this->where('video_progress.done_flag', (int) $flag);
    }
}
