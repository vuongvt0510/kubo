<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Master_school_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Master_school_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'master_school';
    public $primary_key = 'id';

    /**
     * @var array multi byte characters
     */
    public $comparison = [
        'ー' => '',
        '０' => '0',
        '１' => '1',
        '２' => '2',
        '３' => '3',
        '４' => '4',
        '５' => '5',
        '６' => '6',
        '７' => '7',
        '８' => '8',
        '９' => '9',
        '-' => '',
    ];

    /**
     * Convert multi byte characters
     *
     * @access public
     *
     * @param string $keyword
     * @return string
     */
    public function sanitize_word($keyword)
    {
        return str_replace(array_keys($this->comparison),
            array_values($this->comparison), mb_convert_kana($keyword, 'r'));
    }

    /**
     * fetch with with_master_area
     *
     * @access public
     * @return Master_school_model
     */
    public function with_master_postalcode(){
        return $this->join(DB_CONTENT . 'master_postalcode as pc', 'pc.id = master_school.postalcode_id');
    }

}
