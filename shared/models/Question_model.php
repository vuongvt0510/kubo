<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class Question_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Question_model extends APP_Paranoid_model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'question';
    public $primary_key = 'id';

    /**
     * @param int $video_id
     *
     * @return Question_model
     */
    public function with_video_id($video_id)
    {
        return $this
            ->join('video_question_timeline', 'question.id = video_question_timeline.question_id')
            ->where('video_question_timeline.video_id', $video_id);
    }

    /**
     * with stage question inuse
     *
     * @return Question_model
     */
    public function with_stage_question_inuse()
    {
        return $this->join('stage_question_inuse', 'question.id = stage_question_inuse.question_id');
    }
}
