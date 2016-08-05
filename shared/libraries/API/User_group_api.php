<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_group_api
 *
 * @property User_model user_model
 * @property User_friend_model user_friend_model
 * @property User_group_model user_group_model
 * @property User_rabipoint_model user_rabipoint_model
 * @property User_group_playing_model user_group_playing_model
 * @property Group_model group_model
 * @property Group_invite_model group_invite_model
 * @property Room_user_model Room_user_model
 * @property Room_model room__model
 * @property Message_model message_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_group_api extends Base_api
{

    /**
     * Get full info of all group which user join in SPEC UG-010
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param string $group_type (family|friend)
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'グループID', 'required|integer|valid_user_id');
        $v->set_rules('group_type', 'グループタイプ', 'required|valid_primary_type');
        $v->require_login();

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');

        $list_groups = $this->group_model->get_user_group($params);

        // Return
        return $this->true_json([
            'total' => count($list_groups),
            'items' => $list_groups
        ]);
    }

    /**
     * Invite user by email Spec UG-020
     *
     * @param array $params
     *
     * @internal param int $group_id
     * @internal param string $email
     * @internal param string $uri
     *
     * @return array
     */
    public function invite($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'グループID', 'required|valid_group_id');
        $v->set_rules('uri', 'リダイレクトURI', 'required');
        $v->set_rules('email', 'メールアドレス', 'required|max_length[255]|valid_vague_email');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load helper
        $this->load->helper('string_helper');

        // Load model
        $this->load->model('group_invite_model');
        $this->load->model('user_model');
        $this->load->model('group_model');

        // Get user info
        $user = $this->user_model->where(['email' => $params['email'] ])->all(['master' => TRUE]);

        // Create token
        $token = random_string('alnum', 32);

        // Clean up old token
        $this->group_invite_model
            ->where('user_id', $this->operator()->id)
            ->where('expired_at <', business_date('Y-m-d H:i:s'))->destroy_all();

        // Create invite token
        $this->group_invite_model->create([
            'user_id' => $this->operator()->id,
            'token' => $token,
            'email' => $params['email'],
            'group_id' => $params['group_id'],
            'expired_at' => business_date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ]);

        // Get group name from group_id
        $group = $this->group_model->get_group_name($params['group_id']);
        // Send mail
        $this->send_mail('mails/group_invite', [
            'to' => $params['email'],
            'subject' =>  $this->subject_email['group_invite']
        ], [
            'invite_url' => sprintf("%s/login?redirect=",$this->config->item('site_url')).
                urlencode(sprintf("uri=%s?token=%s", $params['uri'], $token)),
            'confirm' => empty($user),
            'user_name' => $this->operator()->nickname,
            'group_name' => isset($group->name) ? $group->name : null
        ]);

        // Return
        return $this->true_json();
    }

    /**
     * Check the invitation API Spec UG-022
     *
     * @param array $params
     * @internal param string $token of user
     * @internal param int $group_id of group
     * @internal param int $user_id of user
     * @internal param string $role in user group
     *
     * @return array
     */
    public function verify_invitation($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('token', '認証トークン', 'required|valid_token[group_invite]');
        $v->set_rules('group_id', 'グループID', 'required|valid_group_id|valid_group_id_invite_token['. $params['token'] .']');
        $v->set_rules('role', '役割', 'required|valid_group_role');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_invite_model');
        $this->load->model('user_group_model');
        $this->load->model('user_model');

        // Get user info
        $user = $this->user_model->available(TRUE)->find($this->operator()->id);

        // Return error if user is not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Create user group
        $this->user_group_model->create([
            'group_id' => $params['group_id'],
            'user_id' => $this->operator()->id,
            'role' => $params['role']
        ], [
            'mode' => 'replace'
        ]);

        // Remove token
        $this->group_invite_model->where('token', $params['token'])->destroy_all();

        // Return
        return $this->true_json();
    }

    /**
     * Verify the email API Spec UG-021
     *
     * @param array $params
     * @internal param token of user
     * @internal param group_id of group
     * @internal param user_id of user
     * @internal param role in user group
     *
     * @return array
     */
    public function check_invitation($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('token', '認証トークン', 'required|valid_token[group_invite]');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_invite_model');


        /** @var object $res Get user info */
        $res = $this->group_invite_model
            ->where('group_invite.token', $params['token'])
            ->first();

        // Return
        return $this->true_json(empty($res)? [] : [
            'id' => isset($res->group_id) ? $res->group_id : NULL,
            'user_id' => isset($res->user_id) ? $res->user_id : NULL,
            'name'  => isset($res->name) ? $res->name : NULL,
            'email'  => isset($res->email) ? $res->email : NULL,
            'users' => $this->build_user_response($res)
        ]) ;
    }

    /**
     * Add user to group API Spec UG-050
     *
     * @param array $params
     * @internal param int $group_id of group
     * @internal param int $user_id of user
     * @internal param string $role in user group
     *
     * @return array
     */
    public function add_member($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_id', 'グループID', 'required|valid_group_id');
        $v->set_rules('user_id', 'ユーザーID', 'required|valid_user_id');
        $v->set_rules('role', '役割', 'required|valid_group_role');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_group_model');
        $this->load->model('room_user_model');
        $this->load->model('room_model');
        $this->load->model('group_model');

        $this->load->model('user_group_model');
        $check_group = $this->user_group_model
            ->join('group', 'group.id = user_group.group_id')
            ->join('user', 'user.id = user_group.user_id')
            ->where('user.primary_type', 'student')
            ->where('group.primary_type', 'family')
            ->where('user_group.user_id', $params['user_id'])
            ->all();
        // Check group type
        $group = $this->group_model->select('id, primary_type')
            ->where('id', $params['group_id'])
            ->first();

        if (!empty($check_group) && $group->primary_type == 'family') {
            return $this->false_json(self::BAD_REQUEST, 'この子どもアカウントは既に家族グループに所属しています');
        }

        // Check the number of members in the group
        $res = $this->user_group_model->select('user_id')
            ->where('group_id', $params['group_id'])
            ->all();

        if ($group->primary_type == 'family') {
            if(count($res) >= DEFAULT_MAX_USER_IN_GROUP) {
                return $this->false_json(self::BAD_REQUEST, 'この家族グループはすでに上限'.DEFAULT_MAX_USER_IN_GROUP.'人に達しています');
            }
        } else {
            if(count($res) >= DEFAULT_MAX_USER_IN_TEAM) {
                return $this->false_json(self::BAD_REQUEST, 'このチームはすでに上限'.DEFAULT_MAX_USER_IN_TEAM.'人に達しています');
            }

            // Get number groups user joined
            $groups = $this->user_group_model
                ->select('user_group.group_id, user_group.user_id, group.primary_type')
                ->join('group', 'group.id = user_group.group_id')
                ->where('group.primary_type', 'friend')
                ->where('user_id', $params['user_id'])
                ->all();

            // Check number groups user joined
            if (count($groups) >= DEFAULT_MAX_TEAM ) {
                return $this->false_json(self::BAD_REQUEST, DEFAULT_MAX_TEAM.'チームに参加しています。');
            }
        }

        // Create user group
        $this->user_group_model->create([
            'group_id' => $params['group_id'],
            'user_id' => $params['user_id'],
            'role' => $params['role']
        ], ['mode' => 'replace'] );

        // Get room message by group_id
        $room = $this->room_model
            ->select('id, type, name, group_id')
            ->where('group_id',$params['group_id'])
            ->first();

        // Check room to add user into group message
        if (!empty($room)) {
            // Add user to group message
            $this->room_user_model->add_member_group($room->id, $params['user_id']);
        }

        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);

        $res_rabipoint = FALSE;
        // Create rabipoint for joining group
        if ($user->primary_type == 'student') {
            $this->load->model('user_rabipoint_model');
            if ($group->primary_type == 'family') {
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $params['user_id'],
                    'target_id' => $params['group_id'],
                    'case' => 'join_family'
                ]);
            } else {

                $owners = $this->user_group_model->get_user_owner($params['group_id']);

                $is_owner = FALSE;
                foreach ($owners as $owner) {
                    if ($owner->id == $params['user_id']) {
                        $is_owner = TRUE;
                        break;
                    }
                }

                if ($is_owner == FALSE) {
                    $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                        'user_id' => $params['user_id'],
                        'target_id' => $params['group_id'],
                        'case' => 'join_team'
                    ]);
                }
            }
        }

        $this->load->model('user_group_playing_model');
        $this->user_group_playing_model->add_member($params['user_id'], $params['group_id']);

        // Return
        return $this->true_json(['point' => $res_rabipoint]);
    }

    /**
     * Remove user from group API Spec UG-051
     *
     * @param array $params
     * @internal param int $group_id of group
     * @internal param int $user_id of user
     *
     * @return array
     */
    public function remove_member($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'グループID', 'required|valid_group_id');
        $v->set_rules('user_id', 'ユーザーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_group_model');
        $this->load->model('user_model');
        $this->load->model('room_user_model');

        // Get user info
        $user = $this->user_model->available(TRUE)->find($params['user_id']);

        // Return error if user is not exist
        if (!$user) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Remove user group
        $res = $this->user_group_model->where([
            'user_id' => $params['user_id'],
            'group_id' => $params['group_id']
        ])->destroy_all(['return' => TRUE]);

        // Return error if cannot delete
        if(!$res) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Remove user room message
        $this->room_user_model->remove_member_room($params['group_id'], $params['user_id']);

        // Return
        return $this->true_json();
    }

    /**
     *
     * Get list members in the group API Spec UG-052
     *
     * @param array $params
     * @internal param $group_id
     *
     * @return array
     */
    public function get_list_members($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'グループID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');

        // Get group info
        $res = $this->group_model->get_member($params['group_id']);

        // Return result
        return $this->true_json(['users' => $this->build_responses($res, ['group', 'user_info'] )]);
    }

    /**
     *
     * Get owner user in the group API Spec UG-053
     *
     * @param array $params
     * @internal param $group_id
     *
     * @return array
     */
    public function get_user_owner($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'グループID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_group_model');

        // Get group info
        $res = $this->user_group_model->get_user_owner($params['group_id']);

        // Return result
        return $this->true_json(['users' => $res]);
    }

    /**
     * Get all user in family group or friend
     *
     * MS40
     *
     * @param array $params
     * @internal param string $group_type (family|friend)
     *
     * @return array
     */
    public function get_member_message($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_type', 'グループタイプ', 'valid_primary_type');
        $v->require_login();

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

        // Load model
        $this->load->model('user_friend_model');
        $this->load->model('user_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        // get list family of current user
        $this->user_model
            ->calc_found_rows()
            ->select('user.id as user_id, user.login_id, user.nickname, user.primary_type, user_profile.avatar_id')
            ->with_profile()
            ->with_group()
            ->fetch_group_id($current_user_id)
            ->where('user.id !=', $current_user_id)
            ->group_by('user.id')
            ->order_by('user.'.$params['sort_by'], $params['sort_position']);

        // check group_type
        if ($params['group_type'] == 'family') {
            return $this->true_json([
                'items' => $this->build_responses($this->user_model->all()),
                'total' => (int)$this->user_model->found_rows()
            ]);
        } else {
            $family = $this->user_model->all();

            $list_family = [];
            foreach ($family as $record) {
                $list_family[] = $record->user_id;
            }

            // check list members family
            if (empty($list_family)){
                $list_family = 0;
            }

            // get list friend of current user
            $this->user_friend_model
                ->calc_found_rows()
                ->select('user.id as user_id, user.login_id, user.nickname, user.primary_type, user_profile.avatar_id')
                ->with_profile()
                ->with_user()
                ->where('user_friend.user_id', $current_user_id)
                ->where('user_friend.status', 'active')
                ->where('user.status', 'active')
                ->where('user.email_verified', 1)
                ->where('user.primary_type', 'student')
                ->where_not_in('user_friend.target_id', $list_family)
                ->order_by('user.'.$params['sort_by'], $params['sort_position'])
                ->group_by('user.id');

            return $this->true_json([
                'items' => $this->build_responses($this->user_friend_model->all()),
                'total' => (int)$this->user_friend_model->found_rows()
            ]);
        }
    }

    /**
     * Invite user by id Spec TM-015
     *
     * @param array $params
     *
     * @internal param int $group_id
     * @internal param int $user_id
     *
     * @return array
     */
    public function invite_friend($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'グループID', 'required|valid_group_id');
        $v->set_rules('user_id', 'グループID', 'required|integer|valid_user_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('room_user_model');
        $this->load->model('message_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        // Get private room if exist
        $room = $this->room_user_model->get_private_room($current_user_id, $params['user_id']);

        if (empty($room)) {
            // Create new room
            $room = $this->room_user_model->create_private_room($current_user_id, $params['user_id']);
        }

        // Send message invite
        $res = $this->message_model->create([
            'user_id' => $current_user_id,
            'room_id' => $room->id,
            'message' => 'チームへの招待 <br> チームで一緒に勉強しよう！ [team]'.$params['group_id'].'[/team]'
        ], [
            'return' => TRUE
        ]);

        // Update unread message to read
        $this->room_user_model
            ->where('room_id', $room->id)
            ->where('user_id', $current_user_id)
            ->update_all([
                'last_time' => business_date('Y-m-d H:i:s')
            ]);

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res)
        ]);
    }

    /**
     * Invite new user by email Spec UG-020
     *
     * @param array $params
     *
     * @internal param int $group_id
     * @internal param string $email
     * @internal param string $uri
     *
     * @return array
     */
    public function invite_new_user($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'グループID', 'required|valid_group_id');
        $v->set_rules('uri', 'リダイレクトURI', 'required');
        $v->set_rules('email', 'メールアドレス', 'required|max_length[255]|valid_vague_email');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load helper
        $this->load->helper('string_helper');

        // Load model
        $this->load->model('group_invite_model');
        $this->load->model('group_model');

        // Create token
        $token = random_string('alnum', 32);

        // Clean up old token
        $this->group_invite_model
            ->where('user_id', $this->operator()->id)
            ->where('expired_at <', business_date('Y-m-d H:i:s'))
            ->destroy_all();

        // Create invite token
        $this->group_invite_model->create([
            'user_id' => $this->operator()->id,
            'token' => $token,
            'email' => $params['email'],
            'group_id' => $params['group_id'],
            'expired_at' => business_date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ]);

        // Get group name from group_id
        $group = $this->group_model->get_group_name($params['group_id']);

        // Send mail
        $this->send_mail('mails/invite_new_user', [
            'to' => $params['email'],
            'subject' =>  $this->subject_email['invite_new_user']
        ], [
            'invite_url' => sprintf("%s/register/student?redirect=",$this->config->item('site_url')).
                urlencode(sprintf("uri=%s?token=%s", $params['uri'], $token)),
            'user_name' => $this->operator()->nickname,
            'group_name' => isset($group->name) ? $group->name : null
        ]);

        // Return
        return $this->true_json();
    }

    /**
     * get list group message function - MS41
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param string $group_type (family|friend)
     *
     * @return array
     */
    public function get_list_group_message($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'グループID', 'required|integer|valid_user_id');
        $v->set_rules('group_type', 'グループタイプ', 'required|valid_primary_type');
        $v->require_login();

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');

        $res = $this->group_model
            ->calc_found_rows()
            ->select('group.id as group_id, group.name, group.avatar_id as group_avatar, group.created_at, user_group.role, group.primary_type as primary_type_group')
            ->select('room.id as room_id')
            ->join('user_group', 'user_group.group_id = group.id')
            ->join('room', 'room.group_id = group.id', 'left')
            ->where('group.id IN (SELECT group_id FROM user_group WHERE user_id = '.$params['user_id'].')')
            ->where('group.primary_type', $params['group_type'])
            ->group_by('group.id')
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => $this->group_model->found_rows()
        ]);
    }

    /**
     * Build the user information response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = []){

        if (empty($res)) {
            return [];
        }

        $result = get_object_vars($res);

        // Build user info
        if(in_array('user_info', $options)) {
            $result = $this->build_user_response($res, $options);
        }

        // Build user info
        if(in_array('group', $options)) {
            $result = array_merge($result, [
                'role' => $res->role,
                'group_id' => (int) $res->group_id
            ]);
        }

        return $result;
    }
}
