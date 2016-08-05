<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Credit card controller
 *
 * @author duytt <duytt@nal.vn>
 */
class Credit_card extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_is_parent');

    }

    /**
     * Register credit card of Spec CC-40
     */
    public function register()
    {
        $view_data = [
            'form_errors' => [],
            'back_link' => ''
        ];

        $redirect = '';

        // Process redirect
        if($this->input->get('redirect')) {

            parse_str(urldecode($this->input->get('redirect')), $parse_url);

            $parse_url['from'] = isset($parse_url['from']) ? $parse_url['from'] : 'CC20';

            $user_id = isset($parse_url['id']) ? $parse_url['id'] : null;

            if ($user_id) {
                switch ($parse_url['from']) {
                    case 'MC10':
                        $redirect = 'pay_service/'.$user_id;
                        $view_data['back_link'] = '/pay_service/'.$user_id;
                        break;

                    case 'MC20':
                        $redirect = 'pay_service/'.$user_id.'/purchase';
                        $view_data['back_link'] = '/coin/'.$user_id.'/password?redirect=' . urlencode($this->input->get('redirect'));
                        break;

                    case 'MC30':
                        $redirect = 'pay_service/'.$user_id.'/purchase';
                        $view_data['back_link'] = '/coin/'.$user_id.'/password?redirect=' . urlencode($this->input->get('redirect'));
                        break;
                    default: // Go to CC20
                        $redirect = 'coin/'.$user_id.'/purchase';
                        $view_data['back_link'] = '/coin/'.$user_id.'/password';
                        break;
                }
            }
        }

        if($this->input->is_post()) {

            try {
                $res = $this->_api('credit_card')->create([
                    'user_id' => $this->current_user->id,
                    'card_number' => $this->input->post('card_number'),
                    'cvv_code' => $this->input->post('cvv_code'),
                    'holder_name' => $this->input->post('holder_name'),
                    'expire' => sprintf("%d-%02d", $this->input->post('expired_year'), $this->input->post('expired_month')),
                    'password' => $this->input->post('password'),
                ]);

                if (isset($res['errcode']) && $res['errcode'] == APP_Response::TYPE_API_ERROR) {
                    throw new GMO_Exception_api('[Payment][RegisterCreditCard] ' . $res['errmsg']);
                }

            } catch (GMO_Exception_api $e) {
                // just ignore
                $res['errmsg'] = strpos($res['errmsg'], 'GMO_EXCEPTION') !== FALSE ? 'GMO_EXCEPTION: 決済システムへの接続時に、問題が発生しました' : str_replace("\n", "<br>", $res['errmsg']);
            }

            if (isset($res['result'])) {
                // Set flag parent login creditcard
                $this->session->set_userdata('credit_card_password', $this->input->post('password'));

                if (isset($parse_url['from']) && $parse_url['from'] == 'MC10') {
                    $this->_flash_message('クレジットカード情報を更新しました');
                }

                redirect($redirect);
            }

            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];

            if (isset($view_data['form_errors']['expire'])) {
                $view_data['form_errors']['expired_year'] = $view_data['form_errors']['expire'];
            }

            $view_data['errmsg'] = !empty($res['errmsg']) ? $res['errmsg'] : null;
            $view_data['post'] = $this->input->post();
        }

        $this->_render($view_data);
    }
}
