<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Room api
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 *
 * @author DiepHQ
 * @author IMVN Team
 */
class Room_api extends Base_api
{
    /**
     * @param array $params
     * @internal param int $room_id
     * @return array
     */
    public function room_info($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('room_id', 'Room ID', 'required');
        $v->set_rules('room_type', 'Room Type', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('room_model');

        // Get current user_id
        $current_user_id = $this->operator()->id;

        $this->room_model
            ->select('room.id, room.type')
            ->where('room.id', $params['room_id']);

        switch ($params['room_type']) {
            case 'private':
                $this->room_model
                    ->select('user.nickname, user.login_id, user.status')
                    ->join('room_user', 'room_user.room_id = room.id', 'left')
                    ->join('user', 'user.id = room_user.user_id', 'left')
                    ->where('room_user.user_id !=', $current_user_id);

                return $this->true_json($this->build_responses($this->room_model->first(), ['target_user']));
                break;

            case ($params['room_type'] == 'friend' || $params['room_type'] == 'family'):
                $this->room_model
                    ->select('group.id as group_id, group.name as group_name')
                    ->join('group', 'group.id = room.group_id', 'left');

                return $this->true_json($this->build_responses($this->room_model->first()));
                break;
        }
    }

    /**
     * @param array $params
     * @internal param int $user_id
     * @return array
     */
    public function get_room_private($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('room_user_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        if ($current_user_id == $params['user_id']) {
            return $this->false_json(self::BAD_REQUEST);
        }

        $res = $this->room_user_model->get_private_room($current_user_id, $params['user_id']);

        if (empty($res)) {
            // Create private room
            $res = $this->room_user_model->create_private_room($current_user_id, $params['user_id']);
        }

        // Return
        return $this->true_json($this->build_response($res));
    }

    /**
     * @param array $params
     * @internal param int $group_id
     * @return array
     */
    public function get_room_group($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'Group ID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('room_model');

        $res = $this->room_model
            ->select('id, type, name, group_id')
            ->where('room.group_id', $params['group_id'])
            ->first();

        if (empty($res)) {
            // Get room type
            // Load model
            $this->load->model('group_model');
            $this->load->model('room_user_model');

            $group = $this->group_model
                ->select('id, primary_type, name')
                ->where('id', $params['group_id'])
                ->first();

            // Get group info
            $members = $this->group_model->get_member($params['group_id']);

            // Create new room message
            $res = $this->room_model->create([
                'type' => $group->primary_type,
                'group_id' => $params['group_id']
            ], [
                'return' => TRUE
            ]);

            // Add member to room message
            foreach ($members as $key => $value) {
                $this->room_user_model->create([
                    'room_id' => $res->id,
                    'user_id' => $value->id,
                    'last_time' => business_date('Y-m-d H:i:s')
                ]);
            }
        }

        // Return
        return $this->true_json($this->build_response($res));
    }

    public function get_detail( $params = [] )
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('room_id', 'Room ID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('room_model');

        $res = $this->room_model
            ->select('room.id, room.type ')
            ->where('room.id', $params['room_id'])
            ->first();

        // Return
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = [])
    {
        $return = [];

        if (empty($res)) {
            return $return;
        }

        $return = [
            'id' => (int)$res->id,
            'type' => isset($res->type) ? $res->type : null,
            'name' => isset($res->group_name) ? $res->group_name : null,
            'group_id' => isset($res->group_id) ? (int) $res->group_id : null,
            'nickname' => isset($res->nickname) ? $res->nickname : null,
            'login_id' => isset($res->login_id) ? $res->login_id : null
        ];

        // Build admin response
        if (in_array('target_user', $options)) {
            $return['status'] = $res->status ? $res->status : null;
        }

        // Return
        return $return;
    }

}