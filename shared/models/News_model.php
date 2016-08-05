<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';
require_once SHAREDPATH . 'core/APP_Operator.php';

/**
 * Class News_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class News_model extends APP_Paranoid_model
{
    public $database_name = DB_MAIN;
    public $table_name = 'news';
    public $primary_key = 'id';

    /**
     * fetch with public status
     *
     * @access public
     * @return News_model
     */
    public function with_public()
    {
        return $this->where('news.status', 'public');
    }

    /**
     * fetch with private status
     *
     * @access public
     * @return News_model
     */
    public function with_private()
    {
        return $this->where('news.status', 'private');
    }

    /**
     * fetch news is available
     *
     * @access public
     * @return News_model
     */
    public function with_available()
    {
        return $this->where("started_at <= '".business_date('Y-m-d H:i:s')."' and (ended_at >= '".business_date('Y-m-d H:i:s')."' or ended_at IS NULL)");
    }

}
