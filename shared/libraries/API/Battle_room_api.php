<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Battle_room_api
 *
 * @property Battle_room_model battle_room_model
 * @property Group_playing_model group_playing_model
 * @property User_group_model user_group_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Battle_room_api extends Base_api
{
     /**
     * Get room which team'user playing - Spec TB-010
     *
     * @param array $params
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_room_playing($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーヤーID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('battle_room_model');

        // Set query
        $res = $this->battle_room_model
            ->calc_found_rows()
            ->select('battle_room.id, group_user.score as group_score, group_user.name as group_name,
                target_group.score as target_group_score, target_group.name as target_group_name')
            ->with_group_user()
            ->with_target_group()
            ->join('group_playing', 'group_playing.target_id = battle_room.id')
            ->join('user_group', 'user_group.group_id = group_playing.group_id')
            ->where('battle_room.status', Battle_room_model::BATTLE_ROOM_OPEN_STATUS)
            ->where('user_group.user_id', $params['user_id'])
            ->all();

        // Return
        return $this->true_json([
            'total' => (int) $this->battle_room_model->found_rows(),
            'items' => $this->build_responses($res)
        ]);
    }

    /**
     * Get room team battle detail - Spec TB-011
     *
     * @param array $params
     * @internal param int $room_id
     * 
     * @return array
     */
    public function get_room_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('room_id', 'ルームID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Filter by sort
        if(empty($params['sort_by']) || !in_array($params['sort_by'], ['id', 'created_at'])) {
            $params['sort_by'] = 'user_group_playing.created_at';
        }

        // Filter by sort position
        if(empty($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'desc';
        }

        // Load model
        $this->load->model('battle_room_model');
        $this->load->model('group_playing_model');
        $this->load->model('user_group_model');

        // Set query
        $room = $this->battle_room_model
            ->select('battle_room.id, battle_room.time_limit, battle_room.created_at,
                group_user.name AS group_name, group_user.score AS group_score, group_user.group_id,
                target_group.name AS target_group_name, target_group.score AS target_group_score, target_group.group_id AS target_group_id')
            ->with_group_user() // join group_id
            ->with_target_group() // join target_group_id
            ->join('group_playing', 'group_playing.target_id = battle_room.id')
            ->join('user_group', 'user_group.group_id = group_playing.group_id')
            ->where('battle_room.id', $params['room_id'])
            ->where('user_group.user_id', $params['user_id'])
            ->group_by('battle_room.id')
            ->first();

        $members = [];
        if (!empty($room)) {
            // Check group of user
            $groups = $this->user_group_model
                ->select('group_id')
                ->where('user_id', $params['user_id'])
                ->all();

            // build to array
            foreach ($groups as $key => $group) {
                $groups[] = $group->group_id;
            }

            // Check group has user
            if (in_array($room->group_id, $groups)) {
                $room_id = $room->group_id;
            } else {
                $room_id = $room->target_group_id;
            }

            // Set query member played
            $members = $this->group_playing_model
                ->calc_found_rows()
                ->select('group_playing.target_id AS room_id, user_group_playing.score, user_group_playing.created_at,
                    user_profile.avatar_id, user.nickname, stage.name AS stage_name')
                ->join('user_group_playing', 'user_group_playing.group_playing_id = group_playing.id')
                ->join('user_profile', 'user_profile.user_id = user_group_playing.user_id')
                ->join('user', 'user.id = user_group_playing.user_id')
                ->join(DB_CONTENT. '.stage', DB_CONTENT. '.stage.id = user_group_playing.target_id')
                ->where('group_playing.group_id', $room_id)
                ->where('group_playing.type', Battle_room_model::GROUP_BATTLE_TYPE)
                ->where('group_playing.target_id', $params['room_id'])
                ->where('user.deleted_at IS NULL')
                ->order_by($params['sort_by'], $params['sort_position'])
                ->all();
        }

        // Add end_time
        if (empty($room->end_time)) {
            $room->end_time = date('Y-m-d H:i:s', strtotime($room->created_at) + $room->time_limit);
        }

        // Return
        return $this->true_json([
            'room' => $this->build_responses($room),
            'members' => [
                'total' => (int) $this->group_playing_model->found_rows(),
                'items' => $this->build_responses($members)
            ]
        ]);
    }

    /**
     * Get room which team'user playing - Spec TB-010
     *
     * @param array $params
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_history($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーヤーID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('battle_room_model');

        // Set query
        $res = $this->battle_room_model
            ->calc_found_rows()
            ->select('battle_room.id, battle_room.time_limit, battle_room.created_at,
                group_user.score as group_score, group_user.name as group_name,
                target_group.score as target_group_score, target_group.name as target_group_name')
            ->with_group_user()
            ->with_target_group()
            ->join('group_playing', 'group_playing.target_id = battle_room.id')
            ->join('user_group', 'user_group.group_id = group_playing.group_id')
            ->where('battle_room.status', Battle_room_model::BATTLE_ROOM_CLOSE_STATUS)
            ->where('user_group.user_id', $params['user_id'])
            ->all();

        // build response
        foreach ($res as $key => $room) {
            // Add end_time
            if (empty($room->end_time)) {
                $room->end_time = date('Y-m-d H:i:s', strtotime($room->created_at) + $room->time_limit);
            }

            // check result of match
            if ((int) $room->group_score > (int) $room->target_group_score) {
                $room->result = Battle_room_model::RESULT_WIN;
            } else if ((int) $room->group_score < (int) $room->target_group_score) {
               $room->result = Battle_room_model::RESULT_LOSE;
            } else {
               $room->result = Battle_room_model::RESULT_DRAW;
            }
        }

        // Return
        return $this->true_json([
            'total' => (int) $this->battle_room_model->found_rows(),
            'items' => $this->build_responses($res)
        ]);
    }
}
