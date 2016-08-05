<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Ranking Controller
 *
 * @author duytt <duytt@nal.vn>
 */
class Ranking extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * List Ranking Spec RK-10
     *
     * @param string $ranking_type (global|personal)
     */
    public function index($ranking_type = 'global')
    {
        $current_user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id;
        $view_data = $this->_internal_api('ranking', 'get_list', [
            'ranking_type' => $ranking_type,
            'user_id' => $current_user_id,
            'limit' => 100
        ]);

        $view_data['ranking_type'] = $ranking_type;

        $this->_render($view_data);
    }

    /**
     * Team ranking - VS250
     */
    public function team()
    {
        $this->_render();
    }
}