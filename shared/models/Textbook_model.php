<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Textbook_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Textbook_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'textbook';
    public $primary_key = 'id';

    /**
     * Return all text book by search key
     *
     * @param array $params
     * @return array
     */
    public function search($params = [])
    {
        $this->with_publisher()
            ->with_master_subject()
            ->with_master_grade()
            ->where("( publisher.name LIKE '%".$params['keyword']."%' OR textbook.name LIKE '".$params['keyword']."' )");

        // Set the grade for $keyword
        if (isset($params['grade_id']) && !empty($params['grade_id'])) {
            $this->where('master_grade.id', $params['grade_id']);
        }

        // Set the grade for $keyword
        if (isset($params['subject_id']) && !empty($params['subject_id'])) {
            $this->where('master_subject.id', $params['subject_id']);
        }

        return $this->limit($params['limit'])
            ->offset($params['offset'])
            ->order_by('publisher.id', 'ASC')
            ->all();
    }

    /**
     * fetch with with_master_grade
     *
     * @access public
     * @return Textbook_model
     */
    public function with_master_grade()
    {
        return $this
            ->select('textbook.id as textbook_id, textbook.name as textbook_name')
            ->select('master_grade.id as grade_id, master_grade.name as grade_name')
            ->join('master_grade', 'master_subject.grade_id = master_grade.id');
    }

    /**
     * fetch with with_master_subject
     *
     * @access public
     * @return Textbook_model
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
     * @return Textbook_model
     */
    public function with_publisher()
    {
        return $this
            ->select('textbook.id as textbook_id, textbook.name as textbook_name')
            ->select('publisher.id as publisher_id, publisher.name as publisher_name')
            ->join('publisher', 'textbook.publisher_id = publisher.id');
    }

    /**
     * fetch with with_textbook_cache
     *
     * @access public
     * @return Textbook_model
     */
    public function with_textbook_cache()
    {
        return $this
            ->join('cache_textbook_count', 'cache_textbook_count.textbook_id = textbook.id', 'left');
    }

    /**
     * fetch with year
     *
     * @access public
     * @return Textbook_model
     */
    public function with_master_year()
    {
        return $this
            ->join('master_year', 'textbook.year_id = master_year.id');
    }


    /**
     * fetch with textbook_content
     *
     * @access public
     * @return Textbook_model
     */
    public function with_textbook_content()
    {
        return $this
            ->select('textbook_content.id, textbook_content.deck_id, textbook_content.name')
            ->select('textbook_content.order, textbook_content.chapter_name, textbook_content.description')
            ->join('textbook_content', 'textbook_content.textbook_id = textbook.id');
    }
}
