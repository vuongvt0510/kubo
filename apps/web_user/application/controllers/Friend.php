<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Friend controller
 */
class Friend extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_is_student', [
            'except' => ['index']
        ]);
    }

    /**
     * Grade list page Spec MK10
     */
    public function index($user_id = null)
    {
        $view_data = [];

        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $user_id = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;
        } else {
            $user_id = $user_id == null ? $this->current_user->id : $user_id;
        }

        $res = $this->_api('user_friend')->get_list([
            'user_id' => $user_id
        ]);

        $user_info = $this->_api('user')->get_detail([
            'id' => $user_id
        ]);

        $view_data['list_friends'] = $res['result']['items'];
        $view_data['user_info'] = $user_info['result'];

        if(isset($res['result'])) {
            $view_data['list_friends'] = $res['result']['items'];
        }

        if($user_id != $this->current_user->id) {
            $view_data['friend_view'] = TRUE;
        }

        $this->_render($view_data);
    }

    /**
     * Add friend page Spec MK20
     */
    public function add() {

        $view_data = [
            'form_errors' => []
        ];

        if($this->input->is_post()) {

            $number = implode('', $this->input->post('number'));
            $res = $this->_api('user_friend')->find_user_by_number([
                'friend_number' => $number
            ]);

            if (isset($res['result'])) {
                // Move to confirm page
                $this->session->set_userdata('friend_user_id', $res['result']);
                return redirect('friend/add_confirm');
            }

            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];
            $view_data['errmsg'] = isset($res['errmsg']) ? $res['errmsg'] : null;
            $view_data['post'] = $this->input->post('number');

        }

        $this->_render($view_data);
    }

    /**
     * Add friend confirm page Spec MK30
     */
    public function add_confirm()
    {
        if (!$this->session->userdata('friend_user_id') && !$this->session->userdata('add_team')) {
            return redirect('friend/add');
        }

        if ($this->session->userdata('add_team')) {
            $view_data['add_friend'] = TRUE;
            $check_friend = $this->_api('user_friend')->check_friend([
                'user_id' => $this->current_user->_operator_id(),
                'target_id' => $this->session->userdata('add_team')['user_id']
            ]);
            if ($check_friend['result'] == TRUE) {
                // Get detail user invite
                $user = $this->_api('user')->get_detail(['id' => $this->session->userdata('add_team')['user_id']]);
                $view_data['group_id'] = $this->session->userdata('add_team')['group_id'];
                $this->_flash_message($user['result']['nickname'].'と友達になりました');
            }
        } else {
            $view_data['add_friend'] = FALSE;
        }

        $target_id = $this->session->userdata('friend_user_id') ? $this->session->userdata('friend_user_id') : $this->session->userdata('add_team')['user_id'];

        if($this->input->is_post()) {
            $res = $this->_api('user_friend')->create([
                'user_id' => $this->current_user->_operator_id(),
                'target_id' => $target_id
            ]);

            if($res['result']) {
                if ($this->session->userdata('friend_user_id')) {
                    $this->session->unset_userdata('friend_user_id');
                    $redirect = 'friend';
                }

                if ($this->session->userdata('add_team')) {
                    $redirect = 'team/'.$this->session->userdata('add_team')['group_id'].'/add_member';
                }

                $this->session->set_flashdata('get_trophy', $res['result']['trophy']);
                $this->session->set_flashdata('get_point', $res['result']['point']);
                $this->_flash_message('友達に追加しました');
                return redirect($redirect);
            }
        }

        $view_data['user'] = $this->_internal_api('user', 'get_detail', [
            'id' => $target_id
        ]);

        $this->_render($view_data);
    }

    /**
     * Get my friend number page Spec MK40
     */
    public function my_number()
    {
        $view_data = [];

        $reset = $this->input->post('reset') == 'reset' ? TRUE : FALSE;

        $res = $this->_internal_api('user_friend', 'get_number', [
            'user_id' => (int) $this->current_user->id,
            'reset' => $reset
        ]);

        $view_data['expired_at'] = $res['expired_at'];
        $view_data['number'] = str_split($res['number'], 1);

        $this->_render($view_data);
    }

    /**
     * Delete friend page Spec MK50
     * @param string|int $user_id
     */
    public function delete()
    {
        $view_data = [];

        if($this->input->is_post()) {

            $res = $this->_api('user_friend')->delete([
                'user_id' => $this->current_user->_operator_id(),
                'target_id' => $this->input->post('user_id')
            ]);

            if($res['submit']) {
                $this->_flash_message('友達から削除しました');
                return redirect('friend');
            }
        }

        $this->_render($view_data);
    }

}