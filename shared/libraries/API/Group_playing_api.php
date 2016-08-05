<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Group_playing_api
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Group_playing_api extends Base_api
{
    /**
     * Get all user's team - Spec TB-020
     *
     * @param array $params
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_group_user($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーヤーID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }
        
        // Load model
        $this->load->model('user_group_model');

        // Request minimum member in group
        $minimum_member = 2;

        // Set query
        $res = $this->user_group_model
            ->calc_found_rows()
            ->select('group.id, group.name, group.avatar_id, group.highest_score, user_group.user_id')
            ->join("(
                    SELECT group_id
                    FROM user_group
                    WHERE user_group.user_id = ".$params['user_id']."
                ) AS ug", "ug.group_id = user_group.group_id"
                )
            ->join('group', 'group.id = user_group.group_id')
            ->where('group.primary_type', 'friend')
            ->group_by('user_group.group_id')
            ->having('COUNT(user_group.user_id) >= '. $minimum_member)
            ->all();

        // Return
        return $this->true_json([
            'total' => (int) $this->user_group_model->found_rows(),
            'items' => $this->build_responses($res)
        ]);
    }

    /**
     * Get all user's team - Spec TB-022
     *
     * @param array $params
     * @internal param int $user_id
     * 
     * @return array
     */
    public function get_target_group($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_id', 'グループID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default get limit target group
        $default_limit = 6;

        // Request minimum member in group
        $minimum_member = 2;

        // Load model
        $this->load->model('group_model');
        $this->load->model('user_group_model');

        // Set query
        $res = $this->group_model
            ->calc_found_rows()
            ->select('group.id, group.name, group.avatar_id, group.highest_score')
            ->join('user_group', 'user_group.group_id = group.id')
            ->where("group.id NOT IN (
                    SELECT `group`.id
                    FROM `group`
                    JOIN user_group ON user_group.group_id = `group`.id
                    WHERE user_group.user_id IN (
                            SELECT user_group.user_id
                            FROM user_group
                            WHERE user_group.group_id = ".$params['group_id'] ."
                        )
                        AND `group`.primary_type = 'friend'
                    GROUP BY `group`.id
                )")
            ->where('group.primary_type', Group_model::GROUP_FRIEND_TYPE)
            ->where('group.highest_score >= 0')
            ->group_by('group.id')
            ->having('COUNT(user_group.user_id) >= '.$minimum_member)
            ->limit($default_limit)
            ->order_by('RAND()')
            ->all();

        // Return
        return $this->true_json([
            'total' => (int) $this->group_model->found_rows(),
            'items' => $this->build_responses($res)
        ]);
    }

    public function search_group($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_param', 'group param', 'required');
        $v->set_rules('group_id', 'group ID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');
        $this->load->model('group_playing_model');

        // Set query
        $res = $this->group_model
            ->select('group.id, group.name, group.avatar_id, group.highest_score')
            ->where('group.id !=', $params['group_id']);

        // Filter input
        if (is_numeric($params['group_param'])) {
            $res = $res->where('group.id', $params['group_param']);
        } else if (is_string($params['group_param'])) {
            $res = $res->like("group.name", $params['group_param']);
        }

        $res = $res->all();

        // Return
        return $this->true_json([
            'total' => (int) $this->group_model->found_rows(),
            'items' => $this->build_responses($res)
        ]);
    }

    public function check_target_group($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_id', 'グループID', 'required|integer');
        $v->set_rules('target_group_id', '相手チームID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');
        $this->load->model('group_playing_model');

        // Set query
        $group_users = $this->group_model
            ->select('user_group.user_id')
            ->join('user_group', 'user_group.group_id = group.id')
            ->where('group.id', $params['group_id'])
            ->all();

        $target_groups = $this->group_model
            ->select('user_group.user_id, group.highest_score')
            ->join('user_group', 'user_group.group_id = group.id')
            ->where('group.id', $params['target_group_id'])
            ->all();

        $is_duplicated_member = FALSE;
        // Check duplicated member
        foreach ($group_users as $key => $group) {
            foreach ($target_groups as $key => $target_group) {
                if ($group->user_id == $target_group->user_id) {
                    $is_duplicated_member = TRUE;
                    break;
                }
            }
        }

        $is_not_highScore = TRUE;
        // Check is have not high score
        foreach ($target_groups as $key => $target_group) {
            if ($target_group->highest_score) {
                $is_not_highScore = FALSE;
            }
        }

        // Return
        return $this->true_json([
            'is_duplicated_member' => $is_duplicated_member,
            'is_not_highScore' => $is_not_highScore
        ]);
    }

    public function check_duplicate_stage($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('battle_room_id', '部屋の戦いID', 'required|integer');
        $v->set_rules('user_id', 'ユーザーD', 'required|integer');
        $v->set_rules('stage_id', 'ステージID', 'required|integer');
        $v->set_rules('group_id', 'グループID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_group_playing_model');

        // Set query
        // Check user play more than one times
        $res = $this->user_group_playing_model
            ->select('user_group_playing.id')
            ->join('group_playing', 'group_playing.id = user_group_playing.group_playing_id')
            ->where('user_group_playing.user_id', $params['user_id'])
            ->where('group_playing.target_id', $params['battle_room_id'])
            ->first();

        $is_duplicated_stage = !empty($res) ? TRUE : FALSE;

        // Check duplicate stage for one team battle
        if (!$is_duplicated_stage) {
            $res = $this->user_group_playing_model
                ->select('user_group_playing.id')
                ->join('group_playing', 'group_playing.id = user_group_playing.group_playing_id')
                ->where('user_group_playing.target_id', $params['stage_id'])
                ->where('group_playing.group_id', $params['group_id'])
                ->where('group_playing.target_id', $params['battle_room_id'])
                ->first();

            // One more check
            $is_duplicated_stage = !empty($res) ? TRUE : FALSE;
        }

        // Return
        return $this->true_json([
            'is_duplicated_stage' => $is_duplicated_stage
        ]);
    }

    /**
     * Get list history user's team battle - Spec TB-013
     *
     * @param array $params
     * @internal param int $group_id
     * 
     * @return array
     */
    public function get_infor_current_group($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_id', 'グループID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }
        
        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('group_model');

        // Set query
        $res = $this->group_model
            ->select('id, name, avatar_id, highest_score')
            ->where('id', $params['group_id'])
            ->first();

        // Return
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Check exist room
     * @param  array  $params
     * @internal param int $group_id
     * @internal param int $target_group_id
     * 
     * @return array
     */
    public function check_exist_room($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_id', 'グループID', 'required|integer');
        $v->set_rules('target_group_id', '相手チームID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('battle_room_model');
        $this->load->model('group_playing_model');

        // Set query
        // Check exist battle_room
        $room = $this->battle_room_model
            ->select('id')
            ->where('group_id', $params['group_id'])
            ->where('target_group_id', $params['target_group_id'])
            ->where('status', Battle_room_model::BATTLE_ROOM_OPEN_STATUS)
            ->first();

        // Return if exist room
        if (!empty($room)) {
            return $this->true_json(['id' => $room->id]);
        }

        // Create room if not exist
        // Create battle room
        $room = $this->battle_room_model->create([
            'group_id' => $params['group_id'],
            'target_group_id' => $params['target_group_id'],
            'time_limit' => Battle_room_model::BATTLE_ROOM_TIME_LIMIT, // Simulation 30 minutes
            'status' => Battle_room_model::BATTLE_ROOM_OPEN_STATUS
        ], [
            'return' => TRUE
        ]);

        // Check create room
        if (empty($room)) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Create room group user
        $this->group_playing_model->create([
            'group_id' => $params['group_id'],
            'type' => Battle_room_model::GROUP_BATTLE_TYPE,
            'target_id' => $room->id,
            'status' => Battle_room_model::BATTLE_ROOM_OPEN_STATUS
        ]);

        // Create room target group
        $this->group_playing_model->create([
            'group_id' => $params['target_group_id'],
            'type' => Battle_room_model::GROUP_BATTLE_TYPE,
            'target_id' => $room->id,
            'status' => Battle_room_model::BATTLE_ROOM_OPEN_STATUS
        ]);

        // Return
        return $this->true_json(['id' => $room->id]);
    }

    /**
     * Get ranking of battle - Spec TB-050
     *
     * @param array $params
     * @internal param int $offset Default: 0
     * @internal param int $limit Default: 20
     * 
     * @return array
     */
    public function get_ranking($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }
        
        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        // Set query
        // Return
        // Virtual data
        $virtual = [];
        return;
    }
}