<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Video_question_timeline_model
 */
class Video_question_timeline_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'video_question_timeline';
    public $primary_key = ['video_id', 'question_id'];

    /**
     * @return Video_question_timeline_model
     */
    public function with_question()
    {
        return $this
            ->select('video_question_timeline.video_id, video_question_timeline.second, question.id, question.type, question.data')
            ->join('question', 'question.id = video_question_timeline.question_id');
    }
}
