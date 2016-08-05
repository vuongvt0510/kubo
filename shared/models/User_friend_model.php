<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_friend_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_friend_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_friend';

    /**
     * fetch with profile info
     *
     * @access public
     * @return User_model
     */
    public function with_profile()
    {
        return $this->join('user_profile', 'user_friend.target_id = user_profile.user_id', 'left');
    }

    /**
     * fetch with user info
     *
     * @access public
     * @return User_model
     */
    public function with_user()
    {
        return $this->join('user', 'user_friend.target_id = user.id', 'left');
    }
}