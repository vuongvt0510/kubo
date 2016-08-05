<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Message Controller
 *
 * @author DiepHQ
 */
class Message extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['index', 'detail']
        ]);
    }

    /**
     * Index Spec MS-10
     */
    public function index()
    {
        // get family and friend to send message
        $res = [
            'family' => $this->_internal_api('user_group', 'get_member_message', [
                'group_type' => 'family',
                'limit'     => 25
            ]),
            'friend' => $this->_internal_api('user_group', 'get_member_message', [
                'group_type' => 'friend',
                'limit'     => 25
            ])
        ];

        // get list message of friend and family
        $view_data = [
            'message_friend' => $this->_internal_api('message', 'get_list', [
                'group_type' => 'friend'
            ]),
            'friend' => $res['friend']['total'],

            'message_family' => $this->_internal_api('message', 'get_list', [
                'group_type' => 'family'
            ]),
            'family' => $res['family']['total']
        ];

        $view_data['primary_type'] = $this->current_user->primary_type;

        $this->_render($view_data);
    }

    /**
     * Index Spec MS-30
     *
     * @param string|int $room_id
     */
    public function timeline($room_id = '')
    {
        // Get info current user login
        $view_data['current_user'] = $this->current_user;

        $view_data['room_id'] = $room_id;

        // Get room type
        $room_detail = $this->_api('room')->get_detail([
            'room_id' => $room_id,
        ]);

        // Get room info
        $room = $this->_api('room')->room_info([
            'room_id' => $room_id,
            'room_type' => $room_detail['result']['type']
        ]);

        if (!empty($room['result'])) {
            // Title on timeline
            if ($room['result']['type'] == 'family' || $room['result']['type'] == 'friend') {
                $view_data['room_name'] = $room['result']['name'];
                $view_data['is_group'] = TRUE;
            } else {
                $view_data['room_name'] = $room['result']['nickname'];
                $view_data['is_group'] = FALSE;
            }
        } else {
            return redirect('message');
        }

        if ($room['result']['type'] == 'private') {
            $view_data['status'] = $room['result']['status'] == 'active' ? TRUE : FALSE;
        } else {
            $view_data['status'] = TRUE;
        }

        $view_data['load_form_js'] = TRUE;

        // get list message
        $res = $this->_api('message')->get_chat_list([
            'room_id' => $room_id,
            'limit' => 20
        ]);

        $view_data['list_message'] = isset($res['result']) ? array_reverse($res['result']['items']) : [];

        $view_data['oldest_created_at'] = empty($view_data['list_message']) ? business_date('Y-m-d H:i:s') : $view_data['list_message'][0]['created_at'];

        $view_data['has_oldest_message'] = count($view_data['list_message']) == 20 ? 'true' : 'false';

        $view_data['last_time'] = empty($view_data['list_message']) ? business_date('Y-m-d H:i:s') : end($view_data['list_message'])['created_at'];

        if ($this->session->userdata('share_deck_id')) {

            $view_data['share_deck'] = $this->session->userdata('share_deck_id') ? 1 : null;

            $deck = $this->_api('deck')->get_infor([
                'deck_id' => $this->session->userdata('share_deck_id'),
            ]);

            if (isset($deck['result'])) {
                $view_data['deck'][] = $deck['result']['items'];
            }
        }

        $this->_render($view_data);
    }

    /**
     * Index Spec MS-30
     */
    public function get_list_new_message()
    {
        if ($this->input->is_ajax_request()) {

            $res = $this->_api('message')->get_chat_list_new($this->input->post());

            if (isset($res['result'])) {

                return $this->_true_json([
                    'items' => $res['result']['items'],
                    'last_time' => business_date('Y-m-d H:i:s')
                ]);
            }
        }

        return $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Index Spec MS-40
     */
    public function create()
    {
        $content = $this->input->post('content');

        if ($this->session->userdata('share_deck_id')){

            // add message to share deck
            $content = '[link]deck/'.$this->session->userdata('share_deck_id').'[/link]' . $content ;

            // unset session
            $this->session->unset_userdata('share_deck_id');
        }

        $res = $this->_api('message')->create([
            'room_id' => $this->input->post('room_id'),
            'content' => $content
        ]);

        return $this->_true_json($res['result']);
    }

    /**
     * Test for MS40
     */
    public function friend_list()
    {
        // share deck
        if ($this->input->get('deck_id')) {

            $deck = $this->_api('deck')->get_infor(['deck_id' => $this->input->get('deck_id')]);

            if (!empty($deck['result']['items'])) {
                // Deck is exist, store into session for sharing
                $this->session->set_userdata('share_deck_id', $this->input->get('deck_id'));
                $view_data['message_type'] = 'share';
            }

            return redirect('message/friend_list');
        }

        $family_group = $this->_api('user_group')->get_list_group_message([
            'group_type' => 'family',
            'user_id'     => $this->current_user->id
        ]);

        if (!empty($family_group['result']['items'])) {
            $family_group = $family_group['result'];
        }

        $team = $this->_api('user_group')->get_list_group_message([
            'group_type' => 'friend',
            'user_id'     => $this->current_user->id
        ]);

        if (!empty($team['result']['items'])) {
            $team = $team['result'];
        }

        // get family and friend to send message
        $view_data = [
            'family' => $this->_internal_api('user_group', 'get_member_message', [
                'group_type' => 'family'
            ]),

            'family_group' => !empty($family_group) ? $family_group : [],

            'friend' => $this->_internal_api('user_group', 'get_member_message', [
                'group_type' => 'friend'
            ]),

            'team' => !empty($team) ? $team : [],
        ];

        // render primary of current user
        $view_data['primary_type'] = $this->current_user->primary_type;

        $this->_render($view_data);
    }

    /**
     * Scroll list message
     */
    public  function list_message_old()
    {
        if ($this->input->is_ajax_request()) {

            $res = $this->_api('message')->get_chat_list($this->input->post());

            if (isset($res['result'])) {
                return $this->_true_json($res['result']['items']);
            }
        }

        return $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Invite user to team
     * @param string|int $user_id
     * @param string|int $group_id
     */
    public function team_invite($user_id = '', $group_id = '')
    {
        if (!empty($user_id) && !empty($group_id)) {

            $this->session->set_userdata('add_team', [
                'group_id' => $group_id,
                'user_id' => $user_id
            ]);

            return redirect('/team/'.$group_id.'/add_member');
        }
    }

    /**
     * MS31
     *
     * List members in group message
     *
     * @param string|int $room_id
     */
    public function group_members($room_id = '')
    {
        $view_data = [];

        // Get group info
        $room_detail = $this->_api('room')->get_detail([
            'room_id' => $room_id
        ]);

        $room = $this->_api('room')->room_info([
            'room_id' => $room_id,
            'room_type' => $room_detail['result']['type']
        ]);

        if ($room['result']['group_id']) {
            // Get list members in group
            $res =  $this->_api('user_group')->get_list_members([
                'group_id' => $room['result']['group_id']
            ]);
            $view_data['members'] = $res['result']['users'];
        }

        $view_data['room_id'] = $room_id;
        $view_data['group_name'] = $room['result']['name'];

        $this->_render($view_data);
    }

    /**
     * @param string|int $user_id
     */
    public function chat($user_id = '')
    {
        // Get room between 2 users
        $res = $this->_api('room')->get_room_private([
            'user_id' => $user_id
        ]);

        if (isset($res['result']['id'])) {
            return redirect('message/'.$res['result']['id']);
        }

        return redirect('message');
    }

    /**
     * @param string|int $group_id
     */
    public function chat_room($group_id = '')
    {
        // Get room message of group
        $res = $this->_api('room')->get_room_group([
            'group_id' => $group_id
        ]);

        if (isset($res['result']['id'])) {
            return redirect('message/'.$res['result']['id']);
        }

        return redirect('message');
    }
}
