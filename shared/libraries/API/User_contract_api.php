<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_contract_api
 *
 * @property User_model user_model
 * @property User_group_model user_group_model
 * @property Credit_card_model credit_card_model
 * @property Purchase_model purchase_model
 * @property User_contract_model user_contract_model
 * @property Notification_model notification_model
 * @property User_contract_history_model user_contract_history_model
 * @property APP_Config config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_contract_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'User_contract_api_validator';


    /**
     * Register contract Spec MC-010
     *
     * @param array $params
     * @internal param int $user_id of student
     * @internal param int $password access credit card of parent
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required');
        $v->set_rules('password', 'パスワード', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('credit_card_model');
        $this->load->model('purchase_model');
        $this->load->model('user_contract_model');
        $this->load->model('user_contract_history_model');

        // Return error if user is not exist
        $user = $this->user_model->find($params['user_id']);

        if (empty($user) || $user->primary_type !== 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Check parent credit card
        $credit_card = $this->credit_card_model
            ->find_by([
                'user_id' => $this->operator()->id,
                'password' => $this->credit_card_model->encrypt_password($params['password'])
            ]);

        if (empty($credit_card)) {
            return $this->false_json(self::BAD_REQUEST, 'クレジットカードが登録されていません');
        }

        // Check contract status
        $user_contract = $this->user_contract_model->find($user->id);

        if (in_array($user_contract->status, ['under_contract', 'canceling'])) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Generate order id for GMO purchasing
        $order_id = $this->purchase_model->generate_order_id($this->operator()->id, $user->id, Purchase_model::PURCHASE_TYPE_CONTRACT);

        $this->load->library('gmo_payment');

        // Create tran
        $tran = $this->gmo_payment->exec_credit_card([
            'job_cd' => 'CAPTURE',
            'order_id' => $order_id,
            'amount' => DEFAULT_MONTHLY_PAYMENT_AMOUNT,
            'member_id' => $this->credit_card_model->generate_gmo_member_id($this->operator()->id),
            'card_seq' => $credit_card->card_seq,
            'method' => 1
        ]);

        if (!$tran) {
            $gmo_errors = $this->gmo_payment->get_error();
            $error_code = $gmo_errors['has_internal_error'] ? self::TYPE_API_ERROR : self::BAD_REQUEST;
            // Log history
            $this->user_contract_history_model->create([
                'user_id' => $user->id,
                'parent_id' => $this->operator()->id,
                'type' => $user_contract->status == 'free' ? User_contract_history_model::TYPE_FIRST_REGISTER : User_contract_history_model::TYPE_MANUALLY_PURCHASE_PENDING,
                'status' => User_contract_history_model::STATUS_FAIL,
                'content' => $gmo_errors['message']
            ]);

            return $this->false_json($error_code, $gmo_errors['message']);
        }

        // Save to DB
        $purchase_data = [
            'order_id' => $order_id,
            'user_id' => $this->operator()->id,
            'target_id' => $params['user_id'],
            'amount' => DEFAULT_MONTHLY_PAYMENT_AMOUNT, // Amount money
            'type' => 'contract',
            'status' => isset($tran['acs']) ? Purchase_model::PURCHASE_STATUS_PENDING : Purchase_model::PURCHASE_STATUS_SUCCESS,
            'data' => json_encode($tran)
        ];

        $purchase_res = $this->purchase_model->create($purchase_data, [
            'return' => TRUE
        ]);

        if ($purchase_data['status'] == Purchase_model::PURCHASE_STATUS_SUCCESS) {

            $is_purchase_pending = $user_contract->status != User_contract_model::UC_STATUS_FREE;

            // Calculator expired date by current time
            $current_time = business_time();
            $last_expired = strtotime($user_contract->expired_time);

            switch ($user_contract->status) {
                case 'free':
                    // If user still in free time and register contract, current time will change to end of free time.
                    // Fixed: Don't care about accumulate free time to next_purchase_time
                    // $current_time = $current_time < $last_expired ? $last_expired : $current_time;
                    break;
                case 'not_contract':
                    // Just current time is business time
                    break;
                case 'pending':
                    // If this contract is pending => That mean next purchase time must be next month of current $contract->next_purchase_time
                    $current_time = strtotime($user_contract->next_purchase_time);
                    break;
            }

            // It will be apply bonus period in current month
            // Next purchase will be on 26 of nextmonth

            $next_month = strtotime('first day of next month', $current_time);
            $next_purchase_time = mktime(0, 0, 0, date('m', $next_month), 26, date('Y', $next_month));
            $new_expired = strtotime('last day of next month', $current_time);

            // Update the contract info
            $res = $this->user_contract_model->create([
                'user_id' => $user->id,
                'status' => User_contract_model::UC_STATUS_UNDER_CONTRACT,
                'purchase_id' => $purchase_res->id,
                'next_purchase_time' => business_date('Y-m-d H:i:s', $next_purchase_time),
                'expired_time' => business_date('Y-m-d 23:59:59', $new_expired)
            ], [
                'mode' => 'replace',
                'return' => TRUE
            ]);

            // Create rabipoint for user contract when change from free to under_contract
            if ($user_contract->status == User_contract_model::UC_STATUS_FREE) {
                $this->load->model('user_rabipoint_model');
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->id,
                    'case' => 'monthly_payment'
                ]);

                if ($last_expired + 7 * 86400 >= $current_time) {
                    $this->user_rabipoint_model->create_rabipoint([
                        'user_id' => $user->id,
                        'case' => 'monthly_payment_1week'
                    ]);
                }
            }

            // Log history
            $this->user_contract_history_model->create([
                'user_id' => $user->id,
                'parent_id' => $this->operator()->id,
                'purchase_id' => $purchase_res->id,
                'type' => $is_purchase_pending ? User_contract_history_model::TYPE_MANUALLY_PURCHASE_PENDING : User_contract_history_model::TYPE_FIRST_REGISTER,
                'status' => User_contract_history_model::STATUS_SUCCESS
            ]);

            // Send email if purchase needn't to verify 3d security to operator who purchase and student
            // If this purchase is for pending contract, so that use mail template like mail success of batch auto purchase

            $list_emails = array_unique([
                $this->operator()->email,
                $user->email
            ]);

            $mail_template = $is_purchase_pending ? 'mails/purchase_manually_pending_success' : 'mails/purchase_contract_success';
            $mail_subject = $is_purchase_pending ? $this->subject_email['purchase_manually_pending_success'] : $this->subject_email['purchase_contract_success'];

            foreach ($list_emails AS $email) {
                $this->send_mail($mail_template, [
                    'to' => $email,
                    'subject' => $mail_subject
                ], [
                    'user_id' => $user->login_id,
                    'user_name' => !empty($user->nickname) ? $user->nickname : DEFAULT_NICKNAME,
                    'amount' => DEFAULT_MONTHLY_PAYMENT_AMOUNT,
                    'url' => site_url('pay_service/'.$user->id)
                ]);
            }
        }

        return $this->true_json(array_merge($this->build_responses($purchase_res, ['purchase_detail'])));
    }

    /**
     * Verify purchase by 3d security Spec MC-040
     *
     * @param array $params
     * @internal param string $order_id
     * @internal param string $md use for verify
     * @internal param string $pa_res use for verify
     *
     * @return array
     */
    public function verify_purchase($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('order_id', 'オーダーID', 'required');
        $v->set_rules('md', 'Md', 'required');
        $v->set_rules('pa_res', 'パ レス', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('purchase_model');

        $purchase = $this->purchase_model
            ->find_by([
                'order_id' => $params['order_id']
            ]);

        if (empty($purchase)) {
            return $this->false_json(self::BAD_REQUEST, 'オーダーIDが存在しません');
        }

        if ($purchase->user_id != $this->operator()->id) {
            return $this->false_json(self::FORBIDDEN);
        }

        if ($purchase->status == Purchase_model::PURCHASE_STATUS_SUCCESS) {
            return $this->false_json(self::BAD_REQUEST, 'オーダーが終了しました');
        }

        $this->load->library('gmo_payment');

        $tran = $this->gmo_payment->verify_tran([
            'md' => $params['md'],
            'pa_res' => $params['pa_res']
        ]);

        if (!$tran) {
            $gmo_errors = $this->gmo_payment->get_error();
            $error_code = $gmo_errors['has_internal_error'] ? self::TYPE_API_ERROR : self::BAD_REQUEST;
            return $this->false_json($error_code, $gmo_errors['message']);
        }

        $purchase = $this->purchase_model->update($purchase->id, [
            'status' => Purchase_model::PURCHASE_STATUS_SUCCESS,
            'data' => json_encode($tran)
        ], [
            'return' => TRUE
        ]);


        // Calculator expired date

        $this->load->model('user_contract_model');
        $this->load->model('user_contract_history_model');
        $this->load->model('user_model');

        $user_contract = $this->user_contract_model->find($purchase->target_id);

        $user = $this->user_model->find($purchase->target_id);

        $is_purchase_pending = $user_contract->status != 'free';

        // Calculator expired date by current time
        $current_time = business_time();

        switch ($user_contract->status) {
            case 'free':
                // If user still in free time and register contract, current time will change to end of free time.
                // Fixed: Don't care about accumulate free time to next_purchase_time
                // $current_time = $current_time < $last_expired ? $last_expired : $current_time;
                break;
            case 'not_contract':
                // Just current time is business time
                break;
            case 'pending':
                // If this contract is pending => That mean next purchase time must be next month of current $contract->next_purchase_time
                $current_time = strtotime($user_contract->next_purchase_time);
                break;
        }

        // It will be apply bonus period in current month
        // Next purchase will be on 26 of nextmonth

        $next_month = strtotime('first day of next month', $current_time);
        $next_purchase_time = mktime(0, 0, 0, date('m', $next_month), 26, date('Y', $next_month));
        $new_expired = strtotime('last day of next month', $current_time);

        // Update
        $res = $this->user_contract_model->create([
            'user_id' => $user->id,
            'status' => 'under_contract',
            'purchase_id' => $purchase->id,
            'next_purchase_time' => business_date('Y-m-d H:i:s', $next_purchase_time),
            'expired_time' => business_date('Y-m-d 23:59:59', $new_expired)
        ], [
            'mode' => 'replace',
            'return' => TRUE
        ]);

        // Log history
        $this->user_contract_history_model->create([
            'user_id' => $user->id,
            'parent_id' => $this->operator()->id,
            'purchase_id' => $purchase->id,
            'type' => $is_purchase_pending ? User_contract_history_model::TYPE_MANUALLY_PURCHASE_PENDING : User_contract_history_model::TYPE_FIRST_REGISTER,
            'status' => User_contract_history_model::STATUS_SUCCESS
        ]);

        // Send email if purchase needn't to verify 3d security to operator who purchase and student
        $list_emails = array_unique([
            $this->operator()->email,
            $user->email
        ]);

        $mail_template = $is_purchase_pending ? 'mails/purchase_manually_pending_success' : 'mails/purchase_contract_success';
        $mail_subject = $is_purchase_pending ? $this->subject_email['purchase_manually_pending_success'] : $this->subject_email['purchase_contract_success'];

        foreach ($list_emails AS $email) {
            $this->send_mail($mail_template, [
                'to' => $email,
                'subject' => $mail_subject
            ], [
                'user_id' => $user->login_id,
                'user_name' => !empty($user->nickname) ? $user->nickname : DEFAULT_NICKNAME,
                'amount' => DEFAULT_MONTHLY_PAYMENT_AMOUNT,
                'url' => site_url('pay_service/'.$user->id)
            ]);
        }

        return $this->true_json($this->build_responses($purchase, ['purchase_detail']));
    }

    /**
     * Get detail Spec MC-020
     *
     * @param array $params
     *
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);

        if (empty($user) || $user->primary_type !== 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        $this->load->model('user_contract_model');

        $res = $this->user_contract_model
            ->select('user.id AS user_id, user.login_id, user.nickname')
            ->select('user_contract.expired_time, user_contract.next_purchase_time, user_contract.status')
            ->select('purchase.user_id AS user_purchase_id')
            ->with_user()
            ->with_purchase()
            ->find($user->id);

        return $this->true_json($this->build_responses($res, ['contract']));
    }

    /**
     * Cancel contract Spec MC-030
     *
     * @param array $params
     * @internal param int $user_id of student
     * @internal param int $password access credit card of parent
     *
     * @return array
     */
    public function cancel($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);

        if (empty($user) || $user->primary_type !== 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        $this->load->model('user_contract_model');
        $this->load->model('action_history_model');
        $this->load->model('purchase_model');
        $this->load->model('user_contract_history_model');

        $user_contract = $this->user_contract_model->find($user->id);

        if (empty($user_contract) || $user_contract->status != 'under_contract') {
            return $this->false_json(self::BAD_REQUEST, 'このユーザーは契約中ではありません');
        }

        $purchase = $this->purchase_model->find($user_contract->purchase_id);

        if ($purchase->user_id != $this->operator()->id) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Update
        $res = $this->user_contract_model->update($user_contract->user_id, [
            'status' => 'canceling'
        ], [
            'return' => TRUE
        ]);

        // Log history
        $this->user_contract_history_model->create([
            'user_id' => $user_contract->user_id,
            'parent_id' => $this->operator()->id,
            'type' => User_contract_history_model::TYPE_CANCELED,
            'status' => User_contract_history_model::STATUS_SUCCESS
        ]);

        // Send email if purchase needn't to verify 3d security to operator who purchase and his student
        $list_emails = array_unique([
            $this->operator()->email,
            $user->email
        ]);

        foreach ($list_emails AS $email) {
            $this->send_mail('mails/cancel_contract', [
                'to' => $email,
                'subject' => $this->subject_email['cancel_contract']
            ], [
                'user_id' => $user->login_id,
                'user_name' => !empty($user->nickname) ? $user->nickname : DEFAULT_NICKNAME
            ]);
        }

        return $this->true_json($this->build_responses($res, ['contract']));
    }

    /**
     * Ask parent for coin MC-090
     *
     * @param array $params
     *
     * @internal param int $user_id
     *
     * @return array
     */
    public function ask_parent($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        //Load models
        $this->load->model('user_model');
        $this->load->model('user_group_model');
        $this->load->model('notification_model');

        // Find and check valid user
        $user = $this->user_model->find($params['user_id']);

        if (empty($user)) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        if ($user->primary_type != 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Find all parents of this user
        $res = $this->user_group_model
            ->select('DISTINCT(user.id), user.login_id, user.nickname, user.primary_type, user.email')
            ->join('user', 'user.id = user_group.user_id')
            ->where('group_id IN (SELECT group_id FROM user_group WHERE user_id = '.$params['user_id'].')')
            ->where('user.primary_type', 'parent')
            ->where('user.deleted_at IS NULL')
            ->all();

        $list_emails = [];

        foreach($res AS $record) {
            // Create notification to parents (not done yet)
            $params_notify = [
                'type' => Notification_model::NT_FEE_BASE,
                'user_id' => $user->id,
                'target_id' => $record->id
            ];

            // Create notification
            $this->notification_model->create_notification($params_notify);

            if (!in_array($record->email, $list_emails)) {
                $list_emails[] = $record->email;
            }
        }

        foreach ($list_emails AS $email) {
            // Send mail
            $this->send_mail('mails/ask_parent_pay_service', [
                'to' => $email,
                'subject' => '【スクールTV】お子さまから「スクールTV Plus」への申込依頼が届きました'
            ], [
                'name' => !empty($user->nickname) ? $user->nickname : $user->login_id,
                'link' => sprintf('%s/pay_service/%s', rtrim($this->config->item('site_url'), '/'), $user->id)
            ]);
        }

        return $this->true_json();
    }

    /**
     * List contract history of user Spec MC-100
     *
     * @param array $params
     * @internal param int $user_id of student
     *
     * @return array
     */
    public function get_history_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');

        $user = $this->user_model->find($params['user_id']);

        if (empty($user) || $user->primary_type !== 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        $this->load->model('user_contract_model');
        $this->load->model('user_contract_history_model');

        // Prepare query
        $where_sql = sprintf('((`type` IN ("%s", "%s", "%s") AND status = "%s") OR (`type` IN ("%s", "%s", "%s")))',
            User_contract_history_model::TYPE_FIRST_REGISTER, User_contract_history_model::TYPE_TRYING_AUTO_PURCHASE_26TH, User_contract_history_model::TYPE_MANUALLY_PURCHASE_PENDING, User_contract_history_model::STATUS_SUCCESS,
            User_contract_history_model::TYPE_AUTO_PURCHASE_AT_26TH,
            User_contract_history_model::TYPE_PENDING_AT_11TH,
            User_contract_history_model::TYPE_TURN_OFF_PENDING_25TH
        );

        $this->user_contract_history_model
            ->calc_found_rows()
            ->select('id, user_id, type, status, created_at')
            ->where('user_id', $params['user_id'])
            ->where($where_sql)
            ->order_by('created_at', 'DESC');

        if (!empty($params['limit'])) {
            $this->user_model->limit($params['limit'], isset($params['offset']) ? (int) $params['offset'] : 0);
        }

        $res = $this->user_contract_history_model->all();

        $items = [];

        foreach ($res AS $record) {

            $content = '';

            switch ($record->type) {
                case User_contract_history_model::TYPE_FIRST_REGISTER:
                case User_contract_history_model::TYPE_TRYING_AUTO_PURCHASE_26TH:
                case User_contract_history_model::TYPE_MANUALLY_PURCHASE_PENDING:
                    $content = '決済完了';
                    break;

                case User_contract_history_model::TYPE_AUTO_PURCHASE_AT_26TH:
                    $content = $record->status == User_contract_history_model::STATUS_SUCCESS ? '決済完了' : '自動更新：決済NG';
                    break;

                case User_contract_history_model::TYPE_PENDING_AT_11TH:
                    $content = '10日まで決済NG';
                    break;

                case User_contract_history_model::TYPE_TURN_OFF_PENDING_25TH:
                    $content = '自動解約';
                    break;

                case User_contract_history_model::TYPE_CANCELED:
                    $content = '解約';
                    break;

            }

            $items[] = [
                'id' => (int) $record->id,
                'created_at' => $record->created_at,
                'content' => $content
            ];
        }
        // Return
        return $this->true_json([
            'total' => (int) $this->user_contract_history_model->found_rows(),
            'items' => $items
        ]);
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = []){

        if (empty($res)) {
            return [];
        }

        $result = get_object_vars($res);

        if (in_array('purchase_detail', $options)) {
            $result = [
                'id' => (int) $res->id,
                'order_id' => $res->order_id,
                'user_id' => (int) $res->user_id,
                'target_id' => (int) $res->target_id,
                'coin' => (int) $res->coin,
                'status' => $res->status,
                'data' => json_decode($res->data, TRUE)
            ];
        }

        if (in_array('contract', $options)) {
            $result = [
                'user_id' => (int) $res->user_id,
                'login_id' => $res->login_id,
                'nickname' => $res->nickname,
                'expired_time' => $res->expired_time,
                'next_purchase_time' => $res->next_purchase_time,
                'status' => $res->status,
                'user_purchase_id' => !empty($res->user_purchase_id) ? (int) $res->user_purchase_id : null
            ];
        }

        return $result;
    }
}

/**
 * Class User_contract_api_validator
 */
class User_contract_api_validator extends Base_api_validation
{

}