<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Coin controller
 *
 * @author duytt <duytt@nal.vn>
 */
class Coin extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_is_parent',[
            'except' => ['index', 'ask_parent']
        ]);
    }

    /**
     * Check coin of Spec CC-10
     *
     * @param int|string $user_id
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

        $view_data = [
            'list_purchases' => $this->_internal_api('coin', 'get_user_purchase_history', [
                'user_id' => $user_id
            ]),

            'list_buyings' => $this->_internal_api('coin', 'get_user_buying_history', [
                'user_id' => $user_id
            ]),

            'user_coin' => $this->_internal_api('coin', 'get_user_coin', [
                'user_id' => $user_id
            ]),
            'operator_primary_type' => $this->current_user->primary_type,
            'user_id' => $this->current_user->id
        ];

        // Check exist parent
        $view_data['has_parent'] = $this->_api('user')->check_user_has_parent(['user_id' => $user_id]);

        // Set session asking when it transited from DK10
        if ($this->input->get('asking') == TRUE) {
            $this->session->set_userdata('asking', TRUE);
        }

        $this->_render($view_data);
    }

    /**
     * Ask for coin - Spec PT 10
     */
    public function ask_parent()
    {
        // Check request ask_parent from DK10 or PT10
        if ($this->input->post()) {
            $params = $this->input->param();
        } else if ($this->input->get('deck_id')) {
            $params['user_id'] = $this->current_user->id;
        }

        // Check exist parent
        $has_parent = $this->_api('user')->check_user_has_parent(['user_id' => $params['user_id']]);

        if (! $has_parent) {
            redirect('coin');
        }

        // send mail and notify to parent
        $res = $this->_api('coin')->ask_parent($params);

        if ($res['result']) {
            $this->_flash_message('保護者の方におねだりしました');
            $this->session->set_userdata('__get_trophy', $res['result']['trophy']);
            $this->session->set_userdata('__get_point', $res['result']['point']);
        }

        // Change redirect to DK10
        if ($this->input->get('deck_id')) {
            return redirect('deck/'. $this->input->get('deck_id'));
        }

        return redirect('coin');
    }

    /**
     * Password page Spec CC-20
     *
     * @param int|string $user_id
     */
    public function password($user_id = '')
    {
        if(isset($this->students[$user_id])) {
            $this->session->set_userdata('switch_student_id', $user_id);
        } else {
            redirect('dashboard/'.$user_id);
            return;
        }

        $view_data = [];

        $view_data['user_id'] = $user_id;

        $parse_url = [];
        if($this->input->get('redirect')) {
            parse_str(urldecode($this->input->get('redirect')), $parse_url);
        }


        if ($this->input->is_post()) {

            try {
                $res = $this->_api('credit_card')->get_detail([
                    'user_id' => (int) $this->current_user->id,
                    'password' => $this->input->post('password')
                ]);

                if (isset($res['errcode']) && $res['errcode'] == APP_Response::TYPE_API_ERROR) {
                    throw new GMO_Exception_api('[Payment][LoginByPassword] ' . $res['errmsg']);
                }

            } catch (GMO_Exception_api $e) {
                $res['errmsg'] = strpos($res['errmsg'], 'GMO_EXCEPTION') !== FALSE ? 'GMO_EXCEPTION: 決済システムへの接続時に、問題が発生しました' : str_replace("\n", "<br>", $res['errmsg']);
            }


            if (isset($res['result'])) {

                $this->session->set_userdata('credit_card_password', $this->input->post('password'));

                $redirect = 'coin/'.$user_id.'/purchase';

                if (!empty($parse_url['from'])) {
                    switch ($parse_url['from']) {
                        case 'MC20':
                            $redirect = 'pay_service/'.$user_id.'/purchase';
                            break;
                        case 'MC10':
                            $redirect = 'pay_service/'.$user_id;
                            break;
                    }
                }

                return redirect($redirect);
            }

            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];
            $view_data['errmsg'] = isset($res['errmsg']) ? $res['errmsg'] : null ;
        }

        $view_data['params_register_url'] = $this->input->get('redirect') ? urlencode($this->input->get('redirect')) : urlencode('from=CC20&id='.$user_id);
        $view_data['back_link'] = '/coin/'.$user_id;

        $view_data['from_page'] = 'CC10';

        if (!empty($parse_url['from']) && in_array($parse_url['from'], ['MC10', 'MC20'])) {
            $view_data['back_link'] = '/pay_service/'.$user_id;
            $view_data['from_page'] = $parse_url['from'];
        }

        $check_card = $this->_internal_api('credit_card', 'check_user', [
            'user_id' => (int) $this->current_user->id
        ]);

        if (!$check_card['has_credit_card'] && empty($view_data['errmsg'])) {
            $view_data['errmsg'] = 'クレジットカードが登録されていません';
        }

        $this->_render($view_data);
    }

    /**
     * Purchase page Spec CC-30
     *
     * @param int|string $user_id
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
            return redirect('coin/' . $user_id . '/password');
        }

        $view_data = [
            'form_errors' => [],
            'list_coins' => $this->_internal_api('coin', 'get_list_price'),
            'user' => $this->_internal_api('user', 'get_detail', [
                'id' => $user_id
            ])
        ];

        if ($this->input->is_post()) {

            try {
                $res = $this->_api('coin')->purchase([
                    'user_id' => (int) $user_id,
                    'password' => $this->session->userdata('credit_card_password'),
                    'coin' => (int) $this->input->post('coin'),
                ]);

                if (isset($res['errcode']) && $res['errcode'] == APP_Response::TYPE_API_ERROR) {
                    throw new GMO_Exception_api('[Payment][Purchase]' . $res['errmsg']);
                }

            } catch (GMO_Exception_api $e) {
                // just ignore
                $res['errmsg'] = strpos($res['errmsg'], 'GMO_EXCEPTION') !== FALSE ? 'GMO_EXCEPTION: 決済システムへの接続時に、問題が発生しました' : str_replace("\n", "<br>", $res['errmsg']);
            }


            if (isset($res['result'])) {

                if (isset($res['result']['data']['acs']) && !empty($res['result']['data']['acs'])) {

                    $this->load->library('gmo_payment');

                    $acs = $res['result']['data']['acs'];
                    $acs['term_url'] = site_url('coin/purchase_verify/' . $user_id);

                    $this->session->set_userdata('order_id', $res['result']['order_id']);

                    echo $this->gmo_payment->create_redirect_page($acs);

                    return;

                } else {
                    // The purchase success and don't need to verify 3d security
                    $this->session->unset_userdata('credit_card_password');
                    $this->_flash_message('コインを購入しました');
                    return redirect('coin/' . $res['result']['target_id']);
                }
            }

            // If error occurs from purchase, show error page CC-30

            if (!isset($res['invalid_fields'])) {
                return $this->_render([
                    'errmsg' => $res['errmsg'],
                    'user_id' => $user_id
                ], 'coin/purchase_fail');
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
        $view_data['params_register_url'] = urlencode('from=CC20&id='.$user_id);

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
            $res = $this->_api('coin')->verify_purchase([
                'order_id' => $this->session->userdata('order_id'),
                'md' => $this->input->post('MD'),
                'pa_res' => $this->input->post('PaRes')
            ]);

            if (isset($res['errcode']) && $res['errcode'] == APP_Response::TYPE_API_ERROR) {
                throw new GMO_Exception_api('[Payment][VerifyPurchase] ' . $res['errmsg']);
            }

        } catch (GMO_Exception_api $e) {
            $res['errmsg'] = strpos($res['errmsg'], 'GMO_EXCEPTION') !== FALSE ? 'GMO_EXCEPTION: 決済システムへの接続時に、問題が発生しました' : str_replace("\n", "<br>", $res['errmsg']);
        }

        if ($res['result']) {
            $this->session->unset_userdata('credit_card_password');
            $this->_flash_message('コインを購入しました');
            return redirect('coin/' . $res['result']['target_id']);
        }

        return $this->_render([
            'errmsg' => $res['errmsg'],
            'user_id' => $user_id
        ], 'coin/purchase_fail');
    }
}
