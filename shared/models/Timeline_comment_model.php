<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Timeline_comment_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Timeline_comment_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'timeline_comment';
    public $primary_key = 'id';

    /**
     * fetch with profile info
     *
     * @access public
     * @return User_model
     */
    public function with_profile()
    {
        return $this->join('user_profile', 'timeline_comment.user_id = user_profile.user_id', 'left');
    }

    /**
     * fetch with user info
     *
     * @access public
     * @return User_model
     */
    public function with_user()
    {
        return $this->join('user', 'timeline_comment.user_id = user.id', 'left');
    }
}