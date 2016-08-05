<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Credit_card_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Credit_card_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Credit_card_api_validator';

    /**
     * Create a new credit card or Update Spec CC-010
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param string $card_number
     * @internal param string $expire time (yyyy-dd)
     * @internal param string $holder_name
     * @internal param string $password
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required|integer');

        if (ENVIRONMENT == 'production') {
            $v->set_rules('card_number', 'カード番号', 'required|valid_credit_card_number');
        } else {
            $v->set_rules('card_number', 'カード番号', 'required');
        }

        $v->set_rules('expire', '有効期限', 'required|valid_credit_card_expired');
        $v->set_rules('password', 'パスワード', 'required|alpha_numeric|min_length[8]|max_length[16]');
        $v->set_rules('holder_name', 'カード名義人', 'valid_alpha_space|max_length[30]');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $this->load->model('credit_card_model');

        $user = $this->user_model->find($params['user_id']);

        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        if ($user->primary_type != 'parent') {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load GMO Payment
        $this->load->library('gmo_payment');

        // Get CC of user if exist
        $credit_card = $this->credit_card_model
            ->find_by([
                'user_id' => $user->id
            ]);

        $gmo_member_id = $this->credit_card_model->generate_gmo_member_id($user->id);

        //Check gmo member is exist
        $gmo_member = $this->gmo_payment->search_member([
            'id' => $gmo_member_id
        ]);

        if (empty($gmo_member)) {
            $gmo_member = $this->gmo_payment->create_member([
                'id' => $gmo_member_id,
                'name' => $user->login_id
            ]);
        }

        if (empty($gmo_member)) {
            $gmo_errors = $this->gmo_payment->get_error();
            $error_code = $gmo_errors['has_internal_error'] ? self::TYPE_API_ERROR : self::BAD_REQUEST;
            return $this->false_json($error_code, $gmo_errors['message']);
        }

        // If credit card of user is exist on GMO => remove it
        if (!empty($credit_card)) {
            $this->gmo_payment->delete_credit_card([
                'member_id' => $gmo_member_id
            ]);
        }

        // Add new credit card
        $card_info = $this->gmo_payment->add_credit_card([
            'member_id' => $gmo_member_id,
            'card_name' => $this->credit_card_model->detect_credit_card($params['card_number']),
            'card_no' => $params['card_number'],
            'expire' => date('ym', strtotime($params['expire'])),
            'holder_name' => isset($params['holder_name']) ? $params['holder_name'] : null,
            'default_flag' => 1
        ]);

        if (empty($card_info)) {
            $gmo_errors = $this->gmo_payment->get_error();
            $error_code = $gmo_errors['has_internal_error'] ? self::TYPE_API_ERROR : self::BAD_REQUEST;
            return $this->false_json($error_code, $gmo_errors['message']);
        }

        $data = [
            'user_id' => $params['user_id'],
            'password' => $this->credit_card_model->encrypt_password($params['password']),
            'card_number' => str_repeat('*', strlen($params['card_number']) - 4) . substr($params['card_number'], -4),
            'card_seq' => $card_info['seq']
        ];

        if (!empty($credit_card)) {
            $data['id'] = $credit_card->id;
        }

        $credit_card = $this->credit_card_model->create($data, [
            'mode' => 'replace',
            'return' => TRUE
        ]);

        return $this->true_json([
            'user_id' => (int) $params['user_id'],
            'card_number' => $credit_card->card_number
        ]);
    }

    /**
     * Delete credit card Spec CC-020
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $password
     *
     * @return array
     */
    public function delete($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required');
        $v->set_rules('password', '暗証番号', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $this->load->model('credit_card_model');

        $user = $this->user_model->find($params['user_id']);

        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $res = $this->credit_card_model
            ->find_by([
                'user_id' => $params['user_id'],
                'password' => $this->credit_card_model->encrypt_password($params['password'])
            ]);

        if (empty($res)) {
            return $this->false_json(self::BAD_REQUEST, 'クレジットカードが登録されていません');
        }

        $this->load->library('gmo_payment');

        $credit_card = $this->gmo_payment->search_credit_card([
            'member_id' => $this->credit_card_model->generate_gmo_member_id($res->user_id),
            'card_seq' => $res->card_seq
        ]);

        if (empty($credit_card)) {
            return $this->false_json(self::TYPE_API_ERROR, '決済システムへの接続時に、問題が発生しました');
        }

        $gmo_member_id = $this->credit_card_model->generate_gmo_member_id($res->user_id);

        $this->gmo_payment->delete_credit_card([
            'member_id' => $gmo_member_id
        ]);

        $this->gmo_payment->delete_member([
            'id' => $gmo_member_id
        ]);

        // Check password for credit card
        $this->credit_card_model->destroy($res->id);

        return $this->true_json();
    }

    /**
     * Update credit card information Spec CC-040
     *
     * @param array $params
     *
     * @internal param int $id
     * @internal param int $user_id
     * @internal param string $card_number
     * @internal param string $cvv_code
     * @internal param string $expire
     * @internal param string $password
     *
     * @return array
     */
    public function update($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'クレジットカードID', 'required|integer');
        $v->set_rules('user_id', 'アカウントID', 'required|integer');
        $v->set_rules('password', 'パスワード', 'required');
        $v->set_rules('card_number', 'カード番号', 'min_length[14]');
        $v->set_rules('cvv_code', 'CVVコード', 'min_length[3]');
        $v->set_rules('expire', '有効期限', 'min_length[4]');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $this->load->model('credit_card_model');

        // Check password for credit card
        $check_password = $this->credit_card_model->check_password($params['id'], $params['user_id'], $params['password']);

        if(isset($params['card_number'])) {
            $data['card_number'] = $params['card_number'];
        }

        if(isset($params['cvv_code'])) {
            $data['cvv_code'] = $params['cvv_code'];
        }

        if(isset($params['expire'])) {
            $data['expire'] = $params['expire'];
        }

        // Update credit information in database
        if($check_password) {
            $this->credit_card_model->update($params['id'], $data);
        }

        // Update in GP system (not done yet)

        return $this->true_json();
    }

    /**
     * Check user registered credit card or not Spec CC-031
     *
     * @param array $params
     *
     * @internal param int $user_id
     *
     * @return array
     */
    public function check_user($params = [])
    {
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required|integer|valid_user_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('credit_card_model');
        $res = $this->credit_card_model
            ->find_by([
                'user_id' => $params['user_id']
            ]);

        return $this->true_json([
            'user_id' => (int) $params['user_id'],
            'has_credit_card' => !empty($res)
        ]);
    }

    /**
     * Get credit card detail Spec CC-030
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param string $password
     * @param array $options
     *
     * @return array
     */
    public function get_detail($params = [], $options = [])
    {
        $options = array_merge([
            'require_password' => TRUE
        ], $options);

        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required|integer|valid_user_id');

        if ($options['require_password']) {
            $v->set_rules('password', 'パスワード', 'required');
        }

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if ($this->operator()->_operator_id() != $params['user_id']) {
            return $this->false_json(self::FORBIDDEN);
        }

        $this->load->model('credit_card_model');

        $res = $this->credit_card_model->find_by([
            'user_id' => $params['user_id']
        ]);

        if (empty($res)) {
            return $this->false_json(self::BAD_REQUEST, 'クレジットカードが登録されていません');
        }

        // Check password
        if ($options['require_password']) {
            if ($res->password != $this->credit_card_model->encrypt_password($params['password'])) {
                return $this->false_json(self::BAD_REQUEST, '保存パスワードが違います');
            }
        }

        $this->load->library('gmo_payment');

        $credit_card = $this->gmo_payment->search_credit_card([
            'member_id' => $this->credit_card_model->generate_gmo_member_id($res->user_id),
            'card_seq' => $res->card_seq
        ]);

        if (empty($credit_card)) {
            $gmo_errors = $this->gmo_payment->get_error();
            $error_code = $gmo_errors['has_internal_error'] ? self::TYPE_API_ERROR : self::BAD_REQUEST;
            return $this->false_json($error_code, $gmo_errors['message']);
        }

        $expire_date = '';

        if (!empty($credit_card['expire']) && strlen($credit_card['expire']) == 4) {
            $expire_date = substr($credit_card['expire'], -2) . '/20' . substr($credit_card['expire'], 0, 2);
        }

        return $this->true_json([
            'user_id' => (int) $res->user_id,
            'card_type' => !empty($credit_card['card_type']) ? $credit_card['card_type'] : null,
            'card_number' => $res->card_number,
            'expire' => $expire_date,
            'holder_name' => !empty($credit_card['holder_name']) ? $credit_card['holder_name'] : null
        ]);
    }

}

/**
 * Class Credit_card_api_validator
 */
class Credit_card_api_validator extends Base_api_validation
{
    /**
     * Validates a credit card number using the Luhn algorithm.
     *
     * @param string $number
     *
     * @return bool
     */
    public function valid_credit_card_number($number = '')
    {
        // Ensure every character in the number is numeric.
        if (!ctype_digit($number)) {
            $this->set_message('valid_credit_card_number', 'カードナンバーが正確ではありません');
            return FALSE;
        }

        // Validate the number using the Luhn algorithm.
        $total = 0;

        for ($i = 0; $i < strlen($number); $i++) {
            $digit = substr($number, $i, 1);
            if ((strlen($number) - $i - 1) % 2) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $total += $digit;
        }

        if ($total % 10 != 0) {
            $this->set_message('valid_credit_card_number', 'カードナンバーが正確ではありません');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validation card expired
     *
     * @param string $expired time
     *
     * @return bool
     */
    public function valid_credit_card_expired($expired = '') {

        $is_valid = TRUE;

        if (preg_match("/[0-9]{4}-[0-1][0-9]/", $expired)) {

            list($year, $month) = explode('-', $expired);

            if ($month < 1 || $month > 12) {
                $is_valid = FALSE;
            }

            $current_year = business_date('Y');
            $current_month = business_date('n');

            if ($year < $current_year || $current_year > ($current_year + 20)  ) {
                $is_valid = FALSE;
            }

            if ( $year == $current_year && $month < $current_month ) {
                $is_valid = FALSE;
            }
        } else {
            $is_valid = FALSE;
        }

        if (!$is_valid) {
            $this->set_message('valid_credit_card_expired', '有効期限が正確ではありません');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate credit card code
     *
     * @param $code
     * @param $credit_card_number_field
     *
     * @return bool
     */
    function valid_credit_card_code($code, $credit_card_number_field) {

        $number = isset($this->_field_data[$credit_card_number_field], $this->_field_data[$credit_card_number_field]['postdata']) ?
            $this->_field_data[$credit_card_number_field]['postdata'] : FALSE;

        $is_valid = TRUE;

        if (ctype_digit($code) && $number) {
            switch (substr($number, 0, 1)) {
                case '3':
                    if (strlen($number) == 15) {
                        $is_valid = strlen($code) == 4;
                    }
                    else {
                        $is_valid = strlen($code) == 3;
                    }
                    break;
                case '4':
                case '5':
                case '6':
                    $is_valid = strlen($code) == 3;
                    break;
            }

        } else {
            $is_valid = FALSE;
        }

        if (!$is_valid) {
            $this->set_message('valid_credit_card_code', 'カードコードが正確ではありません');
            return FALSE;
        }

        return TRUE;

    }

    /**
     * Validate alpha space card holder
     *
     * @param $card_holder
     *
     * @return bool
     */
    function valid_alpha_space($card_holder = '')
    {
        if (!preg_match("/^([a-zA-Z ])+$/i", $card_holder)) {
            $this->set_message('valid_alpha_space', 'カード名義人が正確ではありません');
            return FALSE;
        }

        return TRUE;
    }
}