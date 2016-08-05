<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Room_user_api
 *

 * @property Room_user_model room_user_model
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 *
 * @author DiepHQ
 * @author IMVN Team
 */
class Room_user_api extends Base_api
{

    /**
     * Get members of group message
     *
     * @param array $params
     * @internal param int $room_id
     *
     * @return array
     */
    public function get_list_members($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('room_id', 'Room ID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if(!isset($params['sort_by']) || !in_array($params['sort_by'], ['id'])) {
            $params['sort_by'] = 'id';
        }

        // Set default for param sort position
        if(!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'asc';
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('room_user_model');

        $res = $this->room_user_model
            ->calc_found_rows()
            ->select('room_user.room_id')
            ->select('room.type, room.name')
            ->select('user.id as user_id, user.login_id, user.nickname, user.primary_type')
            ->select('user_profile.avatar_id')
            ->join('room', 'room.id = room_user.room_id')
            ->join('user', 'user.id = room_user.user_id')
            ->join('user_profile', 'user_profile.user_id = room_user.user_id', 'left')
            ->where('room_user.room_id', $params['room_id'])
            ->where('user.deleted_at IS NULL')
            ->order_by('user.'.$params['sort_by'], $params['sort_position'])
            ->all();

        $result = [];
        foreach ($res as $record) {
            if (isset($res)) {
                $result = [
                    'room_id' => (int) $record->room_id,
                    'room_name' => $record->name,
                    'type' => $record->type,
                    'members' => []
                ];
            }

            $users[] = [
                'user_id' => (int) $record->user_id,
                'login_id' => $record->login_id,
                'nickname' => $record->nickname,
                'primary_type' => $record->primary_type,
                'avatar_id' => (int) $record->avatar_id
            ];
        }

        $result['members'] = $users;

        // Return
        return $this->true_json($result);
    }

}