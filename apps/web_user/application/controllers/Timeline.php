<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Timeline controller
 */
class Timeline extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * Timeline list TL-10
     *
     * @param int|null $user_id
     */
    public function index($user_id = null)
    {
        if($this->current_user->primary_type == 'parent') {

            if(isset($this->students[$user_id])) {
                // Change switch student id
                $this->session->set_userdata('switch_student_id', $user_id);
            }

            $user_id = empty($user_id) ? $this->session->userdata['switch_student_id'] : $user_id;

        } else {
            $user_id = empty($user_id) ? $this->current_user->id : $user_id;
        }

        $view_data = [];

        $timeline_list = $this->_api('timeline')->get_list([
            'user_id' => $user_id,
            'limit' => 20,
            'offset' => 0
        ]);

        if ($timeline_list['result']['total'] > 20) {
            $view_data['show_more'] = TRUE;
        }

        $view_data['timeline_list'] = $timeline_list['result']['items'];
        $view_data['user_id'] = $user_id;
        $view_data['get_friend'] = 0;

        $this->_render($view_data);
    }

    /**
     * Timeline list TL-20
     *
     * @param int|null $user_id
     */
    public function friend($user_id = null)
    {
        if($this->current_user->primary_type == 'parent') {

            if(isset($this->students[$user_id])) {
                // Change switch student id
                $this->session->set_userdata('switch_student_id', $user_id);
            }

            $user_id = empty($user_id) ? $this->session->userdata['switch_student_id'] : $user_id;

        } else {
            $user_id = empty($user_id) ? $this->current_user->id : $user_id;
        }

        $view_data = [];

        $timeline_list = $this->_api('timeline')->get_list([
            'get_friend' => TRUE,
            'user_id' => $user_id,
            'type' => 'trophy',
            'limit' => 20,
            'offset' => 0
        ]);

        if ($timeline_list['result']['total'] > 20) {
            $view_data['show_more'] = TRUE;
        }
        $view_data['timeline_list'] = $timeline_list['result']['items'];
        $view_data['user_id'] = $user_id;
        $view_data['get_friend'] = 1;

        $this->_render($view_data, 'timeline/index');
    }

    /**
     * Timeline list TL-30
     *
     * @param int|null $timeline_id
     */
    public function detail($timeline_id = null)
    {
        $view_data = [];

        $view_data['timeline'] = $this->_internal_api('timeline', 'get_detail', [
            'timeline_id' => $timeline_id
        ]);

        $goods = $this->_api('timeline_good')->get_list([
            'timeline_id' => $timeline_id,
            'limit' => 3,
            'offset' => 0
        ]);

        $view_data['goods'] = isset($goods['result']) ? $goods['result']['items'] : [];
        $view_data['goods_total'] = isset($goods['result']) ? $goods['result']['total'] : 0;


        $view_data['show_more'] = FALSE;
        $view_data['comments'] = [];

        $comments = $this->_api('timeline_comment')->get_list([
            'timeline_id' => $timeline_id
        ]);

        if (isset($comments['result'])) {
            $view_data['comments'] = array_reverse($comments['result']['items']);
            $view_data['show_more'] = $comments['result']['total'] > 20 ? 'true' : 'false';
        }

        // Last time request
        $view_data['last_time'] = business_date('Y-m-d H:i:s');
        $view_data['oldest_time'] = empty($view_data['comments']) ? business_date('Y-m-d H:i:s') : $view_data['comments'][0]['created_at'];

        $this->_render($view_data);
    }

    /**
     * Post comment
     */
    public function post_comment() {

        if ($this->input->is_post()) {
            $res = $this->_api('timeline_comment')->create([
                'type' => 'comment',
                'timeline_id' => $this->input->post('timeline_id'),
                'user_id' => $this->current_user->id,
                'target_id' => $this->input->post('target_id'),
                'content' => $this->input->post('content')
            ]);

            if (!isset($res['result'])) {
                return $this->_false_json(APP_Response::BAD_REQUEST);
            }

            return $this->_build_json($res['result']);
        }

        return $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Add good
     */
    public function add_good() {

        if ($this->input->is_post()) {
            $res = $this->_api('timeline_good')->create([
                'type' => 'good',
                'timeline_id' => $this->input->post('timeline_id'),
                'user_id' => $this->current_user->id,
                'target_id' => $this->input->post('target_id')
            ]);

            if (!isset($res['result'])) {
                return $this->_false_json(APP_Response::BAD_REQUEST);
            }

            return $this->_build_json($res['result']);

        }

        return $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Ajax get list new comments
     */
    public function get_list_new_comments() {

        if ($this->input->is_post()) {

            $res = $this->_api('timeline_comment')->get_list([
                'timeline_id' => $this->input->post('timeline_id'),
                'time_request' => $this->input->post('time_request'),
                'type' => 'new'
            ]);

            if (isset($res['result'])) {
                return $this->_build_json([
                    'items' => $res['result']['items'],
                    'last_time' => business_date('Y-m-d H:i:s')
                ]);
            }
        }

        $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Ajax get list old comments
     */
    public function get_list_old_comments() {
        if ($this->input->is_post()) {

            $res = $this->_api('timeline_comment')->get_list([
                'timeline_id' => $this->input->post('timeline_id'),
                'time_request' => $this->input->post('time_request'),
                'type' => 'old'
            ]);

            if (isset($res['result'])) {
                return $this->_build_json($res['result']['items']);
            }
        }

        $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Ajax get list new timelines
     */
    public function get_list_new_timelines() {

        if ($this->input->is_post()) {
            $res = $this->_api('timeline')->get_list([
                'user_id' => $this->input->post('user_id'),
                'get_friend' => $this->input->post('get_friend') == 1 ? TRUE : null,
                'created_at' => $this->input->post('latest_timeline'),
                'type_get_list' => 'new',
                'limit' => 20,
                'offset' => 0
            ]);

            if (isset($res['result'])) {
                return $this->_build_json($res['result']['items']);
            }
        }

        return $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Ajax get list old timelines
     */
    public function get_list_old_timelines() {

        if ($this->input->is_post()) {

            $res = $this->_api('timeline')->get_list([
                'user_id' => $this->input->post('user_id'),
                'get_friend' => $this->input->post('get_friend') == 1 ? TRUE : null,
                'created_at' => $this->input->post('latest_timeline'),
                'type_get_list' => 'old',
                'offset' => $this->input->post('offset'),
                'limit' => 20
            ]);

            if (isset($res['result'])) {
                return $this->_build_json($res['result']['items']);
            }
        }

        $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Ajax count good comment
     */
    public function count_good_comment() {

        if ($this->input->is_post()) {

            $res = $this->_api('timeline')->get_list([
                'timeline_id' => $this->input->post('timeline_id')
            ]);

            if (isset($res['result'])) {
                return $this->_build_json($res['result']['items'][0]);
            }
        }

        $this->_false_json(APP_Response::BAD_REQUEST);
    }
 }
