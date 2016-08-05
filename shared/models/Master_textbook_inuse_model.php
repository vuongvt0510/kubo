<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Master_textbook_inuse_model
 */
class Master_textbook_inuse_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'master_textbook_inuse';
    public $primary_key = 'id';

    /**
     * Return all text book by search key
     *
     * @access public
     * @param array $params
     *
     * @return array
     */
    public function search($params = [])
    {
        $this
            ->with_textbook()
            ->with_publisher()
            ->with_master_subject()
            ->with_master_grade()
            ->where('master_textbook_inuse.school_id', $params['school_id'])
            ->limit($params['limit'])
            ->offset($params['offset']);

        // Set the grade for $keyword
        if (isset($params['grade_id']) && !empty($params['grade_id'])) {
            $this->where('master_grade.id', $params['grade_id']);
        }

        return $this->all();
    }

    /**
     * fetch with with_master_grade
     *
     * @access public
     * @return Master_textbook_inuse_model
     */
    public function with_master_grade()
    {
        return $this
            ->select('master_grade.id as grade_id, master_grade.name as grade_name')
            ->join('master_grade', 'master_subject.grade_id = master_grade.id');
    }

    /**
     * fetch with with_master_subject
     *
     * @access public
     * @return Master_textbook_inuse_model
     */
    public function with_master_subject()
    {
        return $this
            ->select('textbook.id as textbook_id, textbook.name as textbook_name')
            ->select('master_subject.id as subject_id, master_subject.name as subject_name')
            ->select('master_subject.type, master_subject.color, master_subject.short_name as subject_short_name')
            ->join('master_subject', 'textbook.subject_id = master_subject.id')
            ->where('master_subject.display_flag', 1); // always get the subject with display_flag = 1
    }

    /**
     * fetch with with_publisher
     *
     * @access public
     * @return Master_textbook_inuse_model
     */
    public function with_publisher()
    {
        return $this
            ->select('publisher.id as publisher_id, publisher.name as publisher_name')
            ->join('publisher', 'textbook.publisher_id = publisher.id');
    }

    /**
     * fetch with with_textbook
     *
     * @access public
     * @return Master_textbook_inuse_model
     */
    public function with_textbook()
    {
        return $this
            ->select('textbook.id as textbook_id, textbook.name as textbook_name')
            ->join('textbook', 'master_textbook_inuse.textbook_id = textbook.id');
    }
}
