<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';
/**
 * Class Message_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 * @author DiepHQ
 */
class Message_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'message';
    public $primary_key = 'id';

    /**
     * fetch with user 
     *
     * @access public
     * @return Message_model
     */
    public function with_user()
    {
    	return $this->join('user', 'message.user_id = user.id');
    }

    /**
     * fetch with user profile
     *
     * @access public
     * @return Message_model
     */
    public function with_user_profile()
    {
    	return $this->join('user_profile', 'user.id = user_profile.user_id', 'left');
    }

    /**
     * fetch with room
     *
     * @access public
     * @return Message_model
     */
    public  function  join_room()
    {
        return $this->join('room', 'room.id = message.room_id');
    }

    /**
     * fetch with room_user
     *
     * @access public
     * @return Message_model
     */
    public function join_room_user()
    {
        return $this->join('room_user', 'room_user.user_id = message.user_id AND room_user.room_id = message.room_id');
    }
}
