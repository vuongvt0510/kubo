<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';
/**
 * Class Room_user_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 * @author DiepHQ
 */
class Room_user_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'room_user';
    public $primary_key = 'id';

    /**
     * get room message of two users
     *
     * @param int $receive_user_id
     * @param int $send_user_id
     *
     * @return array|bool
     */
    public function get_private_room($receive_user_id, $send_user_id)
    {
        if ($receive_user_id == $send_user_id) {
            return FALSE;
        }

        $res = $this->select('room_user.room_id as id, room.type')
            ->join('room', 'room.id = room_user.room_id AND room.type = "private"')
            ->where("room_user.user_id", $receive_user_id)
            ->where("room_user.room_id IN (SELECT d.room_id
                FROM room_user as d
                WHERE d.user_id = {$send_user_id})")
            ->first();

        return $res;
    }

    /**
     * create private room message between two users
     *
     * @param int $receive_user_id
     * @param int $send_user_id
     *
     * @return array
     */
    public function create_private_room($receive_user_id, $send_user_id)
    {
        if ($receive_user_id == $send_user_id) {
            return FALSE;
        }

        // Load model
        $this->load->model('room_model');

        // Create room
        $room = $this->room_model->create([
            'type' => 'private'
        ], [
            'return' => TRUE
        ]);

        // Create room_user
        $this->create([
            'room_id' => $room->id,
            'user_id' => $receive_user_id
        ]);

        $this->create([
            'room_id' => $room->id,
            'user_id' => $send_user_id
        ]);

        return $room;
    }

    /**
     * fetch with family
     *
     * @param int $current_user_id
     *
     * @return Room_user_model
     */
    public function filter_family($current_user_id)
    {
        $sql = "u2.user_id IN (
            SELECT user_group.user_id AS user_id
            FROM user_group
            JOIN (
                SELECT group_id
                FROM user_group
                JOIN `group` ON `group`.id = user_group.group_id AND `group`.`primary_type` = 'family'
                WHERE user_id = {$current_user_id}
            ) AS gr ON gr.group_id = user_group.group_id
            WHERE user_group.user_id != {$current_user_id})";

        return $this->where($sql);
    }

    /**
     * fetch with family
     *
     * @param int $current_user_id
     *
     * @return Room_user_model
     */
    public function filter_friend($current_user_id)
    {
        $sql = "u2.user_id NOT IN (
            SELECT user_group.user_id AS user_id
            FROM user_group
            JOIN (
                SELECT group_id
                FROM user_group
                JOIN `group` ON `group`.id = user_group.group_id AND `group`.`primary_type` = 'family'
                WHERE user_id = {$current_user_id}
            ) AS gr ON gr.group_id = user_group.group_id
            WHERE user_group.user_id != {$current_user_id})
            AND u2.user_id != {$current_user_id}";

        return $this->where($sql);
    }

    /**
     * get lastest message of each room message
     *
     * @param int $current_user_id
     *
     * @return Room_user_model
     */
    public function lastest_message_in_room($current_user_id)
    {
        $sql = "message.id IN (
            SELECT MAX(message.id) as max_id
            FROM message
            WHERE message.room_id IN (
            SELECT room_user.room_id
                FROM room_user
                WHERE user_id = {$current_user_id}
                )
            GROUP BY  message.room_id
            )";

        return $this->where($sql);
    }

    /**
     * fetch with room_user
     *
     * @access public
     *
     * @return Room_user_model
     */
    public function join_room_user()
    {
        return $this->join('room_user as u2', 'u2.room_id = room_user.room_id');
    }

    /**
     * fetch with message
     *
     * @access public
     *
     * @return Room_user_model
     */
    public function join_message()
    {
        return $this->join('message', 'message.room_id = u2.room_id');
    }

    /**
     * fetch with user
     *
     * @access public
     *
     * @return Room_user_model
     */
    public function join_user()
    {
        return $this->join('user', 'user.id = u2.user_id');
    }

    /**
     * fetch with user_profile
     *
     * @access public
     *
     * @return Room_user_model
     */
    public function join_user_profile()
    {
        return $this->join('user_profile', 'user_profile.user_id = u2.user_id');
    }

    /**
     * add member to group message
     *
     * @access public
     *
     * @param $room_id
     * @param $user_id
     *
     * @return Room_user_model
     */
    public function add_member_group($room_id, $user_id)
    {
        // Check member is in room
        $room_user = $this
            ->select('id', 'room_id', 'user_id')
            ->where('room_id', $room_id)
            ->where('user_id', $user_id)
            ->first();

        if (empty($room_user)) {
            // Add member to room
            $room_user = $this->create([
                'room_id' => $room_id,
                'user_id' => $user_id,
            ], [
                'return' => TRUE
            ]);
        }

        return $room_user;
    }

    /**
     * remove member to room message
     *
     * @access public
     *
     * @param $group_id
     * @param $user_id
     *
     * @return Room_user_model
     */
    public function remove_member_room($group_id, $user_id)
    {
        // load room model
        $this->load->model('room_model');

        // get room_id
        $room = $this->room_model
            ->select('id, group_id')
            ->where('group_id', $group_id)
            ->first();

        if (!$room) {
            return FALSE;
        }
        // remove from room
        $res = $this->where([
            'room_id' => $room->id,
            'user_id' => $user_id
        ])->destroy_all(['return' => TRUE]);

        // Return
        return $res;
    }
}