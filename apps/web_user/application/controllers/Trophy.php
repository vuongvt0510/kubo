<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Trophy controller
 */
class Trophy extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * Trophy list BG-10
     */
    public function index($user_id = null)
    {
        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $user_id = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;
        } else {
            $user_id = $user_id == null ? $this->current_user->id : $user_id;
        }

        $view_data = [];
        $trophies = $this->_api('user_trophy')->get_list([
            'user_id' => $user_id
        ]);
        if (isset($trophies['result'])) {
            $view_data['trophy_total'] = $trophies['result']['total'];
            $view_data['trophy_items'] = $trophies['result']['items'];
        }
        $view_data['user_id'] = $user_id;
        $user_info = $this->_api('user')->get_detail([
            'id' => $user_id
        ]);

        $view_data['user_nickname'] = $user_info['result']['nickname'];

        $this->_render($view_data);
    }

    /**
     * Trophy detail
     */
    public function get_detail()
    {
        $trophy = $this->_api('user_trophy')->get_detail($this->input->post());

        return $this->_build_json($trophy['result']);
    }
}