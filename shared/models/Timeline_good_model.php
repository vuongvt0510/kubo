<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Timeline_good_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Timeline_good_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'timeline_good';
    public $primary_key = ['timeline_id', 'user_id'];

    /**
     * fetch with user info
     *
     * @access public
     * @return User_model
     */
    public function with_user()
    {
        return $this->join('user', 'timeline_good.user_id = user.id', 'left');
    }
}