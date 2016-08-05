<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_textbook_inuse_model
 */
class User_textbook_inuse_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_textbook_inuse';
    public $primary_key = 'id';

    /**
     * Return all user text book by search key
     *
     * @access public
     *
     * @param array $params
     *
     * @return object
     */
    public function get_list($params = [])
    {
        return $this
            ->select('user_textbook_inuse.id as id')
            ->with_textbook()
            ->with_publisher()
            ->with_master_subject()
            ->with_master_grade()
            ->where('user_textbook_inuse.user_id', $params['user_id'])
            ->order_by('master_subject.name' , 'ASC')
            ->limit($params['limit'])
            ->offset($params['offset'])
            ->all();
    }

    /**
     * fetch with with_master_grade
     *
     * @access public
     */
    public function with_master_grade(){
        return $this
            ->select('master_grade.id as grade_id, master_grade.name as grade_name')
            ->join('master_grade', 'master_subject.grade_id = master_grade.id');
    }

    /**
     * fetch with with_master_subject
     *
     * @access public
     */
    public function with_master_subject(){
        return $this
            ->select('master_subject.id as subject_id, master_subject.name as subject_name')
            ->select('master_subject.type, master_subject.color, master_subject.short_name as subject_short_name')
            ->join('master_subject', 'textbook.subject_id = master_subject.id')
            ->where('master_subject.display_flag', 1); // always get the subject with display_flag = 1
    }

    /**
     * fetch with with_publisher
     *
     * @access public
     */
    public function with_publisher(){
        return $this
            ->select('publisher.id as publisher_id, publisher.name as publisher_name')
            ->join('publisher', 'textbook.publisher_id = publisher.id');
    }

    /**
     * fetch with with_user_textbook_inuse
     *
     * @access public
     */
    public function with_textbook(){
        return $this
            ->select('textbook.id as textbook_id, textbook.name as textbook_name')
            ->join('textbook', 'textbook.id = user_textbook_inuse.textbook_id');
    }

}
