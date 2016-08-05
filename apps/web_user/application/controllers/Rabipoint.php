<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Rabipoint controller
 *
 * @author trannguyen <nguyentc@nal.vn>
 */
class Rabipoint extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_is_parent',[
            'except' => ['index', 'history', 'expire',  'exchange']
        ]);
    }

    /**
     * Spec RP10
     * @param int $user_id
     */
    public function index($user_id = null)
    {
        // Check user primary type
        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            } else {
                redirect('dashboard/'.$user_id);
                return;
            }
        } else {
            $user_id = $this->current_user->id;
        }

        $view_data = [
            'user' => $this->_internal_api('user_rabipoint', 'get_detail', [
                'user_id' => $user_id
            ]),
            'is_contract' => FALSE
        ];

        // params pagination
        $page = !empty($_GET['p']) && is_numeric($_GET['p']) ? $_GET['p'] : 1;
        $limit = PAGINATION_DEFAULT_LIMIT;
        $offset = ($page - 1) * $limit;

        // Get list rabipoint of user
        $list_rabipoint = $this->_api('user_rabipoint')->get_list([
            'limit' => $limit,
            'offset' => $offset,
            'user_id' => $user_id,
            'explanation' => TRUE
        ]);

        // response list rabipoint of user
        if (isset($list_rabipoint['result'])) {
            $res = $list_rabipoint['result'];
            $view_data['list_rabipoints'] = $res;
            $view_data['pagination'] = [
                'page' => (int) $page,
                'items_per_page' => (int) $limit,
                'total' => (int) $res['total'],
                'offset' => (int) $offset + 1,
                'limit' => $offset + $limit > $res['total'] ? $res['total'] : (int) $offset + $limit
            ];
        }

        // Get contract monthly payment of this student
        $contract = $this->_api('user_contract')->get_detail([
            'user_id' => $user_id
        ]);

        if (isset($contract['result']) && $contract['result']['status'] == 'under_contract') {
            $view_data['is_contract'] = TRUE;
        }

        $view_data['user_id'] = $user_id;
        return $this->_render($view_data);
    }

    /**
     * View list history rabipoint Spec RP10
     *
     * @param int $user_id
     *
     * @throws APP_Api_internal_call_exception
     */
    public function history($user_id = null)
    {
        // Check user primary type
        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            } else {
                redirect('dashboard/'.$user_id);
                return;
            }
        } else {
            $user_id = $this->current_user->id;
        }

        $view_data = [
            'user' => $this->_internal_api('user_rabipoint', 'get_detail', [
                'user_id' => $user_id
            ]),
            'is_contract' => FALSE
        ];

        // params pagination
        $page = !empty($_GET['p']) && is_numeric($_GET['p']) ? $_GET['p'] : 1;
        $limit = PAGINATION_DEFAULT_LIMIT;
        $offset = ($page - 1) * $limit;

        // get list rabipoint of user
        $list_points = $this->_api('point_exchange')->get_list([
            'limit' => $limit,
            'offset' => $offset,
            'user_id' => $user_id
        ]);

        // response list rabipoint of user
        if (isset($list_points['result'])) {
            $res = $list_points['result'];
            $view_data['list_points'] = $res;
            $view_data['pagination'] = [
                'page' => (int) $page,
                'items_per_page' => (int) $limit,
                'total' => (int) $res['total'],
                'offset' => (int) $offset + 1,
                'limit' => $offset + $limit > $res['total'] ? $res['total'] : (int) $offset + $limit
            ];
        }

        // Get contract monthly payment of this student
        $contract = $this->_api('user_contract')->get_detail([
            'user_id' => $user_id
        ]);

        if (isset($contract['result']) && $contract['result']['status'] == 'under_contract') {
            $view_data['is_contract'] = TRUE;
        }

        $view_data['user_id'] = $user_id;
        return $this->_render($view_data);
    }

    /**
     * Exchange rabipoint page Spec RP20
     *
     * @param int $user_id
     */
    public function exchange($user_id = null)
    {
        $view_data = [];

        // Check user primary type
        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            } else {
                redirect('dashboard/'.$user_id);
                return;
            }
        } else {
            $user_id = $this->current_user->id;
        }

        // Get contract monthly payment of this student
        $contract = $this->_api('user_contract')->get_detail([
            'user_id' => $user_id
        ]);

        if (isset($contract['result']) && $contract['result']['status'] != 'under_contract') {
            return redirect('rabipoint/'.$user_id);
        }

        // Get current point of this user
        $res =  $this->_api('user_rabipoint')->get_detail([
            'user_id' => $user_id
        ]);

        if (isset($res['result'])) {
            $view_data['user'] = $res['result'];
        }

        $this->load->model('price_model');

        $mile = $this->price_model->get_min_mile_exchange();
        if (!empty($mile)) {
            $view_data['min_point'] = $mile->min_point;
            $view_data['min_mile'] = $mile->min_mile;
        }

        $view_data['user_id'] = $user_id;
        $view_data['is_parent'] = $this->current_user->primary_type == 'parent' ? TRUE : FALSE;
        return $this->_render($view_data);
    }

    /**
     * Check limit exchange in day
     */
    public function check_limit()
    {
        // Check ajax
        if (!$this->input->is_ajax_request() || !$this->session->userdata('switch_student_id')) {
            return $this->_render_404();
        }

        $user_id = $this->session->userdata('switch_student_id');

        if (!$user_id) {
            return $this->_render_404();
        }

        // Get contract monthly payment of this student
        $contract = $this->_api('user_contract')->get_detail([
            'user_id' => $user_id
        ]);

        if (isset($contract['result']) && $contract['result']['status'] != 'under_contract') {
            return $this->_render_404();
        }

        $res = $this->_api('point_exchange')->check_limit_times_exchange([
            'user_id' => $this->current_user->id
        ]);

        return $this->_build_json($res);
    }

    /**
     * Spec RP40
     */
    public function execute($target_user_id = null)
    {
        // Check user primary type
        if(isset($this->students[$target_user_id])) {
            $this->session->set_userdata('switch_student_id', $target_user_id);
        } else {
            redirect('dashboard/'.$target_user_id);
            return;
        }

        // Get contract monthly payment of this student
        $contract = $this->_api('user_contract')->get_detail([
            'user_id' => $target_user_id
        ]);

        if (isset($contract['result']) && $contract['result']['status'] != 'under_contract') {
            return redirect('rabipoint/'. $target_user_id);
        }

        // Check limit times in day
        $limit = $this->_api('point_exchange')->check_limit_times_exchange([
            'user_id' => $this->current_user->id
        ]);

        if ($limit['result']['is_limited']) {
            $this->_flash_message('1日の交換上限回数は10回です');
            return redirect('rabipoint/'. $target_user_id);
        }

        // Load model
        $this->load->model('point_exchange_model');
        $this->load->model('price_model');

        // Add rate exchange
        $view_data = [
            'list_package' => $this->point_exchange_model->get_packs(),
        ];

        $mile = $this->price_model->get_min_mile_exchange();
        if (!empty($mile)) {
            $view_data['min_point'] = $mile->min_point;
            $view_data['min_mile'] = $mile->min_mile;
        }

        $user = $this->_api('user_rabipoint')->get_detail([
            'user_id' => $target_user_id
        ]);

        if (isset($user['result'])) {
            $view_data['user'] = $user['result'];
        }

        // Save uid of user after redirect from Netmile
        if ($this->input->get('uid') && $this->input->get('token') && $this->session->userdata('request_netmile_token')) {
            // API to save uid of user
            $this->_api('user_netmile')->update([
                'user_id' => $this->current_user->id,
                'enc_user_id' => $this->input->get('uid'),
                'token' => $this->input->get('token')
            ]);

        } else {
            // Call API to get enc uid from netmile
            $res = $this->_api('point_exchange')->get_redirect_link([
                'url_redirect' => '/rabipoint/execute/'. $target_user_id
            ]);

            if (isset($res['result'])) {
                return redirect($res['result']['url']);
            }
        }

        return $this->_render($view_data);
    }

    /**
     * Check enough point to exchange
     */
    public function check_enough_point()
    {
        // Check ajax
        if (!$this->input->post() || !$this->input->is_ajax_request() || !$this->session->userdata('switch_student_id') || !$this->input->post('pack')) {
            return $this->_render_404();
        }

        $target_id = $this->session->userdata('switch_student_id');

        // Get contract monthly payment of this student
        $contract = $this->_api('user_contract')->get_detail([
            'user_id' => $target_id
        ]);

        if (isset($contract['result']) && $contract['result']['status'] != 'under_contract') {
            return $this->_render_404();
        }

        // Call API check enough point to exchange
        $res = $this->_api('point_exchange')->confirm_exchange([
            'user_id' => $this->current_user->id,
            'target_id' => $target_id,
            'pack' => $this->input->post('pack'),
            'ip_address' => $this->input->ip_address()
        ]);

        // Return
        if (isset($res['result'])) {
            return $this->_true_json();
        }

        return $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Confirm exchange point
     */
    public function confirm_exchange()
    {
        // Check exist student
        if (!$this->session->userdata('switch_student_id')) {
            return redirect('dashboard');
        }

        $view_data['user_id'] = $this->session->userdata('switch_student_id');;
        return $this->_render($view_data);
    }
}
