<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Pay_service controller
 *
 * @author Duy Phan <yoshikawa@interest-marketing.net>
 */
class Pay_service extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        
        $this->_before_filter('_require_login', [
            'only' => ['cancel']
        ]);
    }

    /**
     * Pay service (parent) index screen MC10, MP10
     * @param string $user_id
     */
    public function index($user_id = '')
    {
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

        // Process action ask parent of student
        if ($this->input->is_post() && $this->input->post('action') == 'ask_parent' && $this->current_user->primary_type == 'student') {
            $res = $this->_api('user_contract')->ask_parent([
                'user_id' => $this->current_user->id
            ]);

            if ($res['success'] && $res['submit']) {
                $this->session->set_flashdata('is_ask_pay_service', TRUE);
                return redirect('pay_service/' . $user_id);
            }
        }

        // Prepare data for view
        $view_data = [];

        $contract = $this->_internal_api('user_contract', 'get_detail', [
            'user_id' => $user_id
        ]);

        $view_data['contract'] = $contract;
        $view_data['param_url_request'] = urlencode('from=MC20&id='.$user_id);

        if (isset($contract['user_purchase_id']) && $contract['user_purchase_id'] == $this->current_user->id) {
            $credit_card = $this->_api('credit_card')->get_detail([
                'user_id' => $contract['user_purchase_id']
            ], [
                'require_password' => FALSE
            ]);

            if (isset($credit_card['result'])) {
                $view_data['credit_card'] = $credit_card['result'];
            }

            $view_data['param_url_request_update_cc'] = urlencode('from=MC10&id='.$user_id);
        }

        $template_path = null;

        if ($this->current_user->primary_type == 'student') {

            $template_path = 'pay_service/index_student';

            $view_data['has_parent'] = $this->check_user_has_parent($this->current_user->id);

            $view_data['is_ask_pay_service'] = $this->session->flashdata('is_ask_pay_service');
        }

        $view_data['amount'] = DEFAULT_MONTHLY_PAYMENT_AMOUNT;

        $view_data['is_expired'] = strtotime($contract['expired_time']) < mktime(23, 59, 59, business_date('m'), business_date('d'), business_date('Y'));

        $this->_render($view_data, $template_path);
    }

    /**
     * Pay service purchase screen MC30
     * @param string $user_id
     */
    public function purchase($user_id = '')
    {
        if(isset($this->students[$user_id])) {
            $this->session->set_userdata('switch_student_id', $user_id);
        } else {
            redirect('dashboard/'.$user_id);
            return;
        }

        if (!$this->session->userdata('credit_card_password')) {
            return redirect('coin/' . $user_id . '/password?redirect=' . $this->input->get('redirect'));
        }

        $view_data = [
            'form_errors' => [],
            'user' => $this->_internal_api('user', 'get_detail', [
                'id' => $user_id
            ]),
            'load_form_js' => FALSE
        ];

        if ($this->input->is_post()) {

            try {
                $res = $this->_api('user_contract')->create([
                    'user_id' => (int) $user_id,
                    'password' => $this->session->userdata('credit_card_password')
                ]);

                if (isset($res['errcode']) && $res['errcode'] == APP_Response::TYPE_API_ERROR) {
                    throw new GMO_Exception_api('[Payment][RegisterCreditCard] ' . $res['errmsg']);
                }

            } catch (GMO_Exception_api $e) {
                // just ignore
                $res['errmsg'] = strpos($res['errmsg'], 'GMO_EXCEPTION') !== FALSE ? 'GMO_EXCEPTION: 決済システムへの接続時に、問題が発生しました' : str_replace("\n", "<br>", $res['errmsg']);
            }


            if (isset($res['result'])) {

                if (isset($res['result']['data']['acs']) && !empty($res['result']['data']['acs'])) {

                    $this->load->library('gmo_payment');

                    $acs = $res['result']['data']['acs'];
                    $acs['term_url'] = site_url('pay_service/purchase_verify/' . $user_id);

                    $this->session->set_userdata('pay_service_order_id', $res['result']['order_id']);

                    echo $this->gmo_payment->create_redirect_page($acs);

                    return;

                } else {
                    // The purchase success and don't need to verify 3d security
                    $this->session->unset_userdata('credit_card_password');
                    $this->session->set_flashdata('get_point', $res['result']['point']);
                    $this->_flash_message('スクールTV Plusを申込みました');
                    return redirect('pay_service/' . $res['result']['target_id']);
                }
            }

            // If error occurs from purchase, show error page CC-50
            if (!isset($res['invalid_fields'])) {
                return $this->_render([
                    'errmsg' => $res['errmsg'],
                    'user_id' => $user_id
                ], 'pay_service/purchase_fail');
            }

            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];
            $view_data['errmsg'] = !empty($res['errmsg']) ? $res['errmsg'] : null;
            $view_data['post'] = $this->input->post();
        }

        $credit_card = $this->_internal_api('credit_card', 'get_detail', [
            'user_id' => $this->current_user->id,
            'password' => $this->session->userdata('credit_card_password')
        ]);

        $view_data['credit_card'] = $credit_card;

        // Use for creditcard register page if user want to update creditcard
        $view_data['params_register_url'] = urlencode('from=MC30&id='.$user_id);

        $view_data['amount'] = DEFAULT_MONTHLY_PAYMENT_AMOUNT;

        $this->_render($view_data);
    }

    /**
     * Verify 3d security for purchase
     * @param string $user_id
     */
    public function purchase_verify($user_id = '')
    {
        if(isset($this->students[$user_id])) {
            $this->session->set_userdata('switch_student_id', $user_id);
        } else {
            redirect('dashboard/'.$user_id);
            return;
        }

        try {
            $res = $this->_api('user_contract')->verify_purchase([
                'order_id' => $this->session->userdata('pay_service_order_id'),
                'md' => $this->input->post('MD'),
                'pa_res' => $this->input->post('PaRes')
            ]);

            if (isset($res['errcode']) && $res['errcode'] == APP_Response::TYPE_API_ERROR) {
                throw new GMO_Exception_api('[Payment][RegisterCreditCard] ' . $res['errmsg']);
            }

        } catch (GMO_Exception_api $e) {
            // just ignore
            $res['errmsg'] = strpos($res['errmsg'], 'GMO_EXCEPTION') !== FALSE ? 'GMO_EXCEPTION: 決済システムへの接続時に、問題が発生しました' : str_replace("\n", "<br>", $res['errmsg']);
        }

        if ($res['result']) {
            $this->session->unset_userdata('credit_card_password');
            $this->_flash_message('スクールTV Plusを申込みました');
            return redirect('pay_service/' . $res['result']['target_id']);
        }

        return $this->_render([
            'errmsg' => $res['errmsg'],
            'user_id' => $user_id
        ], 'pay_service/purchase_fail');
    }

    /**
     * Pay service cancel screen MC50
     * @param string $user_id
     */
    public function cancel($user_id = '')
    {
        // Check parent
        if ($this->current_user->primary_type !== 'parent') {
            $this->_redirect('profile/detail');
        }

        // Check student belong to parent
        if(isset($this->students[$user_id])) {
            $this->session->set_userdata('switch_student_id', $user_id);
        } else {
            redirect('dashboard/'.$user_id);
            return;
        }

        $contract = $this->_internal_api('user_contract', 'get_detail', [
            'user_id' => $user_id
        ]);

        if (!in_array($contract['status'], ['under_contract', 'pending'])) {
            return redirect('pay_service/'.$user_id);
        }

        if ($this->input->post('action') == 'cancel') {
            $res = $this->_api('user_contract')->cancel([
                'user_id' => $user_id
            ]);

            if (isset($res['result'])) {
                $this->_flash_message('スクールTV Plusを解約しました');
                return redirect('pay_service/' . $user_id);
            }
        }

        $view_data = [];

        $view_data['contract'] = $contract;

        $this->_render($view_data);
    }

    /**
     * Pay service about screen MC70
     */
    public function about()
    {
        $view_data = [
            'user_id' => $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : ($this->current_user->is_login() ? $this->current_user->id : '')
        ];

        $this->_render($view_data);
    }

    /**
     * Check student has parent
     *
     * @param int $user_id
     * @return bool
     */
    private function check_user_has_parent($user_id)
    {
        $groups = $this->_api('user_group')->get_list([
            'user_id' => $user_id,
            'group_type' => 'family'
        ]);

        if ($groups['result']) {
            foreach ($groups['result']['items'] AS $group) {

                if ( !empty($group['owner']) && $group['owner']['primary_type'] == 'parent' && $group['owner']['email_verified']) {
                    return TRUE;
                }

                foreach ($group['members'] AS $member) {
                    if (!$member['email_verified']) {
                        continue;
                    }

                    if ($member['primary_type'] == 'parent') {
                        return TRUE;
                    }
                }
            }
        }

        return FALSE;
    }

}
