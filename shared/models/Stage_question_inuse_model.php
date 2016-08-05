<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Stage_question_inuse_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Stage_question_inuse_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'stage_question_inuse';
    public $primary_key = ['stage_id', 'question_id'];

    /**
     * fetch with with_question
     *
     * @access public
     * @return Stage_question_inuse_model
     */
    public function with_question()
    {
        return $this->join('question', 'stage_question_inuse.question_id = question.id');
    }
}
