<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Message Control API
 *
 * @property Message_model message_model
 * @property Room_user_model room_user_model
 * @property Room_model room_model
 * @property Deck_model deck_model
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 *
 * @author DiepHQ
 * @author IMVN Team
 */
class Message_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Message_api_validator';

    /**
     * Get list receive message filter by family or friend API Spec MS-010 and MS-020
     *
     * @param array $params
     * @internal param string $group_type (family|friend)
     * @internal param int $offset of query Default:0
     * @internal param int $limit of query Default:20
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_type', 'グループタイプ', 'required|valid_group_type');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if(!isset($params['sort_by']) || !in_array($params['sort_by'], ['id'])) {
            $params['sort_by'] = 'created_at';
        }

        // Set default for param sort position
        if(!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'desc';
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('message_model');
        $this->load->model('user_friend_model');
        $this->load->model('deck_model');
        $this->load->model('room_user_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        // Get list message
        $this->room_user_model
            ->calc_found_rows()
            ->select('room_user.room_id, room_user.last_time')
            ->select('user.id as user_id, user.login_id, user.nickname, user.primary_type')
            ->select('user_profile.avatar_id')
            ->select('room.type, room.group_id')
            ->select('group.name as name, group.avatar_id as group_avatar')
            ->select('message.id, message.user_id as user_send, message.message, message.created_at')
            ->join('room', 'room.id = room_user.room_id')
            ->join_room_user()
            ->join_message()
            ->join_user()
            ->join_user_profile()
            ->join('group', 'group.id = room.group_id', 'left')
            ->where('room_user.user_id', $current_user_id)
            ->lastest_message_in_room($current_user_id)
            ->order_by('message.'.$params['sort_by'], $params['sort_position'])
            ->group_by('message.id')
            ->limit($params['limit'], $params['offset']);

        // Set query condition
        $group_type = $params['group_type'];
        switch ($group_type) {
            case 'friend':
                $this->room_user_model
                    ->where("(room.type = 'private' OR room.type = 'friend')",null)
                    ->filter_friend($current_user_id);
                break;

            case 'family':
                $this->room_user_model
                    ->where("(room.type = 'private' OR room.type = 'family')",null)
                    ->filter_family($current_user_id);
                break;
        }

        $res = $this->room_user_model->all();

        foreach ($res as $key => $message) {
            // regex
            if (preg_match("/\[link\]([a-zA-Z0-9\/]*?)\[\/link\]/", $message->message, $match)){
                $text_replace = '';
                $message->message = str_replace($match[0], $text_replace, $message->message);

                // Get number
                $number = explode('/', $match[1]);
                $deck_id = $number[1];

                $deck_infor = $this->deck_model->get_infor($deck_id);
                $res[$key]->deck_infor = $deck_infor;
            }

            //regex message invited
            if (preg_match("/\[team\]([a-zA-Z0-9\/]*?)\[\/team\]/", $message->message, $match)) {
                $text_replace = 'チームへの招待';
                $message->message = str_replace($message->message, $text_replace, $message->message);
            }
        }

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['user_info']),
            'total' => (int) $this->message_model->found_rows()
        ]);
    }

    /**
     * Create message of area API Spec MS-040
     *
     * @param array $params
     * @internal param int $user_id of User receive
     * @internal param string $content of message
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('room_id', 'Room ID', 'required');
        $v->set_rules('content', 'メッセージ内容', 'required|strip_tags');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('message_model');
        $this->load->model('room_user_model');
        $this->load->model('deck_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        // Create message
        $res = $this->message_model->create([
            'user_id' => $current_user_id,
            'room_id' => $params['room_id'],
            'message' => $params['content']
        ], [
            'return' => TRUE
        ]);

        // regex
        if (preg_match("/\[link\]([a-zA-Z0-9\/]*?)\[\/link\]/", $params['content'], $match)){
            $text_replace = '';
            $res->message = str_replace($match[0], $text_replace, $params['content']);

            // Get number
            $number = explode('/', $match[1]);
            $deck_id = $number[1];

            $deck_infor = $this->deck_model->get_infor($deck_id);
            $res->deck_infor = $deck_infor;
        }

        $res = $this->build_responses($res);

        // Add trophy
        $this->load->model('timeline_model');
        $trophy = $this->timeline_model->create_timeline('message', 'trophy');

        $res_rabipoint = FALSE;
        if ($this->operator()->primary_type == 'student') {
            $this->load->model('user_rabipoint_model');
            $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $current_user_id,
                'case' => 'send_message'
            ]);
        }

        $res['trophy'] = $trophy;
        $res['point'] = $res_rabipoint;

        return $this->true_json($res);
    }

    /**
     * Get list chat messages of current operator with other user API Spec MS-030
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param int $offset of query Default:0
     * @internal param int $limit of query Default:20
     * @internal param string $last_time
     *
     * @return array
     */
    public function get_chat_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('room_id', 'Room ID', 'required');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if(!isset($params['sort_by']) || !in_array($params['sort_by'], ['id'])) {
            $params['sort_by'] = 'created_at';
        }

        // Set default for param sort position
        if(!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'desc';
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('message_model');
        $this->load->model('room_model');
        $this->load->model('room_user_model');
        $this->load->model('deck_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        // Get list message
        $this->message_model
            ->calc_found_rows()
            ->select('message.id, message.user_id, message.room_id, message.message, message.created_at')
            ->select('user.nickname, user.login_id, user.primary_type, user_profile.avatar_id')
            ->with_user()
            ->with_user_profile()
            ->where('message.room_id', $params['room_id'])
            ->order_by('message.'.$params['sort_by'], $params['sort_position'])
            ->group_by('message.id')
            ->limit($params['limit'], $params['offset']);

        // Check param last_time
        if (!empty($params['last_time'])) {
            $this->message_model->where('message.created_at <', $params['last_time']);
        }

        $res = $this->message_model->all();

        // Get list ids of unread message
        foreach ($res as $key => $message) {
            // regex
            if (preg_match("/\[link\]([a-zA-Z0-9\/]*?)\[\/link\]/", $message->message, $match)){
                $text_replace = "";
                $message->message = str_replace($match[0], $text_replace, $message->message);

                // Get number
                $number = explode('/', $match[1]);

                $deck_id = (int) end($number);

                if ($deck_id) {
                    $deck_infor = $this->deck_model->get_infor($deck_id);
                    $res[$key]->deck_infor = $deck_infor;
                }
            }

            //regex message invited
            if (preg_match("/\[team\]([a-zA-Z0-9\/]*?)\[\/team\]/", $message->message, $match)) {
                $text_replace = '<a href="/message/team_invite/'.$message->user_id.'/'.$match[1].'" class="btn btn-green btn-block">チームを見る</a>';
                $message->message = str_replace($match[0], $text_replace, $message->message);
            }
        }

        // Update unread message to readed
        $this->room_user_model
            ->where('room_id', $params['room_id'])
            ->where('user_id', $current_user_id)
            ->update_all([
                'last_time' => business_date('Y-m-d H:i:s')
            ]);

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res,['user_info']),
            'total' => (int) $this->message_model->found_rows()
        ]);
    }

    /**
     * Get list chat new messages of current operator with other user API Spec MS-050
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param string $last_time
     * @internal param int $limit of query Default:20
     *
     * @return array
     */
    public function get_chat_list_new($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('room_id', 'Room ID', 'required');
        $v->set_rules('last_time', 'Last time', 'required|datetime_format');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if(!isset($params['sort_by']) || !in_array($params['sort_by'], ['id'])) {
            $params['sort_by'] = 'created_at';
        }

        // Set default for param sort position
        if(!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'asc';
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('message_model');
        $this->load->model('room_user_model');
        $this->load->model('deck_model');
        $this->load->model('user_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        // Get list new message
        $res = $this->message_model
            ->calc_found_rows()
            ->select('message.id, message.user_id, message.room_id, message.message, message.created_at')
            ->select('user.login_id, user.nickname, user.primary_type')
            ->select('user_profile.avatar_id')
            ->with_user()
            ->with_user_profile()
            ->where('message.room_id', $params['room_id'])
            ->where('message.created_at >', $params['last_time'])
            ->order_by('message.'.$params['sort_by'], $params['sort_position'])
            ->all();

        foreach ($res AS $key => $message) {
            // regex
            if (preg_match("/\[link\]([a-zA-Z0-9\/]*?)\[\/link\]/", $message->message, $match)){
                $text_replace = "";
                $message->message = str_replace($match[0], $text_replace, $message->message);

                // Get number
                $number = explode('/', $match[1]);

                $deck_id = (int) end($number);

                if ($deck_id) {
                    $deck_infor = $this->deck_model->get_infor($deck_id);
                    $res[$key]->deck_infor = $deck_infor;
                }
            }

            //regex message invited
            if (preg_match("/\[team\]([a-zA-Z0-9\/]*?)\[\/team\]/", $message->message, $match)) {
                $text_replace = '<a href="/message/team_invite/'.$message->user_id.'/'.$match[1].'" class="btn btn-green btn-block">チームを見る</a>';
                $message->message = str_replace($match[0], $text_replace, $message->message);
            }
        }

        // Update unread message to readed
        $this->room_user_model
            ->where('room_id', $params['room_id'])
            ->where('user_id', $current_user_id)
            ->update_all([
                'last_time' => business_date('Y-m-d H:i:s')
            ]);

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['user_info']),
            'total' => (int) $this->message_model->found_rows()
        ]);
    }

    /**
     * Get total  new messages of current operator API Spec MS-070
     *
     * @param array $params
     *
     * @return array
     */
    public function get_total_new_message($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('message_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        // Get list new message
        $res = $this->message_model
            ->select('count(message.id) as total')
            ->join('room_user', 'room_user.room_id = message.room_id AND room_user.user_id != message.user_id')
            ->where('room_user.user_id', $current_user_id)
            ->where('message.user_id !=', $current_user_id)
            ->where('message.created_at > room_user.last_time')
            ->first();

        // Return
        return $this->true_json([
            'total' => (int) $res->total
        ]);
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

        if (!empty($res->deck_infor)) {
            $deck['id'] = $res->deck_infor->id;
            $deck['name'] = $res->deck_infor->name;
            $deck['image_key'] = $res->deck_infor->image_key;
            $deck['description'] = $res->deck_infor->description;
            $deck['coin'] = (int) $res->deck_infor->coin;
            $deck['category'] = [
                'id' => $res->deck_infor->category_id,
                'title' => $res->deck_infor->category_title,
            ];
            $deck['subject'] = [
                'short_name' => $res->deck_infor->short_name,
                'color' => $res->deck_infor->color,
                'type' => $res->deck_infor->type
            ];
        } else {
            $deck = [];
        }

        if (isset($res->type)) {
            $room['room_type'] = $res->type;
            $room['room_name'] = $res->name;
            $room['group_id'] = $res->group_id;
            $room['group_avatar'] = $res->group_avatar ? $res->group_avatar : null;
        } else {
            $room = [];
        }

        $return = [
            'id'  => (int) $res->id,
            'user_id'  => (int) $res->user_id,
            'room_id' => (int) $res->room_id,
            'message' => $res->message,
            'created_at' => $res->created_at,
            'deck' => $deck,
            'room' => $room
        ];

        if (in_array('user_info', $options)) {
            $return = array_merge($return, [
                'user_send' => !empty($res->user_send) ? $res->user_send : null,
                'login_id' => !empty($res->login_id) ? $res->login_id : null,
                'nickname' => !empty($res->nickname) ? $res->nickname : null,
                'primary_type' => !empty($res->primary_type) ? $res->primary_type : null,
                'avatar_id' => !empty($res->avatar_id) ? (int) $res->avatar_id : 0,
                'last_time' => !empty($res->last_time) ? $res->last_time : null
            ]);
        }

        return $return;
    }

}

/**
 * Class Message_api_validator
 *
 * @property Message_api $base
 */
class Message_api_validator extends Base_api_validation
{
    /**
     * Valid group type
     *
     * @param string $type of group
     * @return bool
     */
    function valid_group_type($type)
    {
        if(!in_array($type, ['family', 'friend'])) {
            $this->set_message('valid_group_type', 'グループタイプは家族か友達のいずれかです');
            return FALSE;
        }

        return TRUE;
    }

}
