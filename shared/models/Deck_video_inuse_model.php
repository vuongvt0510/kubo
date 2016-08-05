<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Deck_video_inuse_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Deck_video_inuse_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'deck_video_inuse';
    public $primary_key = ['deck_id', 'video_id'];

    /**
     * fetch with with_video
     *
     * @access public
     * @return Deck_video_inuse_model
     */
    public function with_video()
    {
        return $this->join('video', 'deck_video_inuse.video_id = video.id', 'left');
    }

    /**
     * fetch with with_question
     *
     * @access public
     * @return Deck_video_inuse_model
     */
    public function with_question()
    {
        return $this
            ->select('question.id, question.type, question.data, deck_video_inuse.deck_id')
            ->join('question', 'question.deck_id = deck_video_inuse.deck_id');
    }
}
