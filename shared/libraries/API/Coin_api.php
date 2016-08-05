<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Coin_api
 *
 * @property Purchase_model purchase_model
 * @property User_model user_model
 * @property User_group_model user_group_model
 * @property Notification_model notification_model
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Coin_api extends Base_api
{

    const PURCHASE_STATUS_PENDING = 'pending';
    const PURCHASE_STATUS_SUCCESS = 'success';

    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Coin_api_validator';

    /**
     * Get current coin of user Spec C-010
     *
     * @param array $params
     *
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_user_coin($params = [])
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
        $user = $this->user_model
            ->with_profile()
            ->find($params['user_id']);

        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        return $this->true_json([
            'id' => (int) $user->id,
            'login_id' => isset($user->login_id) ? $user->login_id : NULL,
            'nickname' => isset($user->nickname) ? $user->nickname : NULL,
            'current_coin' => (int) $user->current_coin
        ]);
    }

    /**
     * Ask parent for coin C-040
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

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        if ($user->primary_type != 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        $this->load->model('user_group_model');
        $this->load->model('notification_model');

        $res = $this->user_group_model
            ->select('DISTINCT(user.id), user.login_id, user.nickname, user.primary_type, user.email')
            ->join('user', 'user.id = user_group.user_id')
            ->where('group_id IN (SELECT group_id FROM user_group WHERE user_id = '.$params['user_id'].')')
            ->where('user.primary_type', 'parent')
            ->where('user.deleted_at IS NULL')
            ->all();

        $list_emails = [];

        foreach($res as $record) {
            // Create notification to parents (not done yet)
            $params_notify = [
                'type' => Notification_model::NT_ASK,
                'user_id' => $params['user_id'],
                'target_id' => $record->id
            ];

            // Create notification
            $notify = $this->notification_model->create_notification($params_notify);

            if (!in_array($record->email, $list_emails)) {
                $list_emails[] = $record->email;
            }
        }

        foreach ($list_emails AS $email) {
            // Send mail
            $this->send_mail('mails/ask_parent', [
                'to' => $email,
                'subject' => "【スクールTV】お子さまからおねだりが届きました"
            ], [
                'name' => !empty($user->nickname) ? $user->nickname : $user->login_id,
                'station_link' => sprintf('%s/station', $this->config->item('site_url')),
                'coin_link' => sprintf('%s/coin/%s', $this->config->item('site_url'), $user->id),
            ]);
        }

        $this->load->model('timeline_model');
        $trophy = $this->timeline_model->create_timeline('ask_coin', 'trophy');

        $this->load->model('user_rabipoint_model');
        $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
            'user_id' => $params['user_id'],
            'case' => 'ask_coin',
            'modal_shown' => 1
        ]);

        return $this->true_json(['trophy' => $trophy, 'point' => $res_rabipoint]);
    }

    /**
     * Get purchase history of user who is receive coin Spec C-050
     *
     * @param array $params
     *
     * @internal param int $user_id of student who is receive coin
     *
     * @return array
     */
    public function get_user_purchase_history($params = [])
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

        if (empty($user) || $user->primary_type != 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Load model
        $this->load->model('purchase_model');

        // Get coin-in history
        $res = $this->purchase_model
            ->select('purchase.coin, purchase.created_at')
            ->select('user.nickname, user.primary_type, user.login_id, user_profile.avatar_id')
            ->with_user_purchase()
            ->where('purchase.target_id', $params['user_id'])
            ->where('purchase.status', self::PURCHASE_STATUS_SUCCESS)
            ->where('purchase.type', Purchase_model::PURCHASE_TYPE_COIN)
            ->order_by('purchase.created_at', 'DESC')
            ->all();

        return $this->true_json($this->build_responses($res, ['purchase']));
    }

    /**
     * Get using coin history of user Spec C-051
     *
     * @param array $params
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_user_buying_history($params = [])
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

        if (empty($user) || $user->primary_type != 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Load model
        $this->load->model('user_buying_model');

        // Get buying history
        $res = $this->user_buying_model
            ->select('user_buying.type, user_buying.created_at, user_buying.target_id, user_buying.coin, deck.name, deck.image_key')
            ->join('schooltv_content.deck', 'user_buying.target_id = deck.id AND user_buying.type = "deck"')
            ->where('user_id', $params['user_id'])
            ->order_by('user_buying.created_at', 'DESC')
            ->all();

        return $this->true_json($this->build_responses($res, ['buying']));
    }

    /**
     * Purchase coin to student user Spec C-020
     *
     * @param array $params
     * @internal param int $user_id of student
     * @internal param int $password access credit card of parent
     * @internal param int $coin
     *
     * @return array
     */
    public function purchase($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required');
        $v->set_rules('password', 'パスワード', 'required');
        $v->set_rules('coin', 'コイン', 'required|valid_coin');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('price_model');

        $price = $this->price_model->find_by([
            'type' => 'coin',
            'number' => $params['coin']
        ]);

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);

        if (empty($user) || $user->primary_type !== 'student') {
            return $this->false_json(self::BAD_REQUEST);
        }

        $this->load->model('credit_card_model');

        $credit_card = $this->credit_card_model
            ->find_by([
                'user_id' => $this->operator()->id,
                'password' => $this->credit_card_model->encrypt_password($params['password'])
            ]);

        if (empty($credit_card)) {
            return $this->false_json(self::BAD_REQUEST, 'このクレジットカードは存在しません');
        }

        // Auto generation order id for this tran
        $order_id = $this->operator()->id.'-'.$params['user_id'].'-';
        // 27 is limit length of order_id in GMO
        $order_id .= generate_unique_key(27 - strlen($order_id));

        $this->load->library('gmo_payment');
        // Create tran
        $tran = $this->gmo_payment->exec_credit_card([
            'job_cd' => 'CAPTURE',
            'order_id' => $order_id,
            'amount' => $price->price,
            'member_id' => $this->credit_card_model->generate_gmo_member_id($this->operator()->id),
            'card_seq' => $credit_card->card_seq,
            'method' => 1
        ]);

        if (!$tran) {
            $gmo_errors = $this->gmo_payment->get_error();
            $error_code = $gmo_errors['has_internal_error'] ? self::TYPE_API_ERROR : self::BAD_REQUEST;
            return $this->false_json($error_code, $gmo_errors['message']);
        }

        // Save to DB
        $this->load->model('purchase_model');

        $purchase_data = [
            'order_id' => $order_id,
            'user_id' => $this->operator()->id,
            'target_id' => $params['user_id'],
            'amount' => $price->price, // Amount money
            'coin' => $price->number,
            'status' => isset($tran['acs']) ? self::PURCHASE_STATUS_PENDING : self::PURCHASE_STATUS_SUCCESS,
            'data' => json_encode($tran)
        ];

        $res = $this->purchase_model->create($purchase_data, [
            'return' => TRUE
        ]);

        if ($purchase_data['status'] == self::PURCHASE_STATUS_SUCCESS) {
            // Add coin to target student user
            $this->load->model('user_model');

            $target = $this->user_model
                ->where('id', $res->target_id)
                ->for_update()
                ->first([
                    'master' => TRUE
                ]);

            $this->user_model->update($res->target_id, [
                'current_coin' => (int) $target->current_coin + (int) $res->coin
            ], [
                'return' => TRUE,
                'master' => TRUE
            ]);

            // Send email if purchase needn't to verify 3d security to operator who purchase
            $this->send_mail('mails/purchase_coin_success', [
                'to' => $this->operator()->email,
                'subject' => $this->subject_email['purchase_coin_success']
            ], [
                'user_id' => $user->login_id,
                'user_name' => !empty($user->nickname) ? $user->nickname : DEFAULT_NICKNAME ,
                'station_url' => site_url('station'),
                'amount' => $price->price,
                'coin' => $price->number,
                'payment_type' => 'クレジットカード'
            ]);
        }

        return $this->true_json($this->build_responses($res, ['purchase_detail']));
    }

    /**
     * Verify purchase by 3d security Spec C-030
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

        if ($purchase->status == self::PURCHASE_STATUS_SUCCESS) {
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

        $res = $this->purchase_model->update($purchase->id, [
            'status' => 'success',
            'data' => json_encode($tran)
        ], [
            'return' => TRUE
        ]);

        // Add coin to target student user
        $this->load->model('user_model');

        $target = $this->user_model
            ->where('id', $purchase->target_id)
            ->for_update()
            ->first([
                'master' => TRUE
            ]);

        $this->user_model->update($purchase->target_id, [
            'current_coin' => (int) $target->current_coin + (int) $purchase->coin
        ], [
            'return' => TRUE,
            'master' => TRUE
        ]);

        // Send mail to operator who purchase
        $this->send_mail('mails/purchase_coin_success', [
            'to' => $this->operator()->email,
            'subject' => $this->subject_email['purchase_coin_success']
        ], [
            'user_id' => $target->login_id,
            'user_name' => !empty($target->nickname) ? $target->nickname : DEFAULT_NICKNAME,
            'station_url' => site_url('station'),
            'amount' => $purchase->amount,
            'coin' => $purchase->coin,
            'payment_type' => 'クレジットカード'
        ]);

        return $this->true_json($this->build_responses($res, ['purchase_detail']));
    }

    /**
     * Get list coin and price
     *
     * @param array $params
     *
     * @return array
     */
    public function get_list_price($params = [])
    {
        $v = $this->validator($params);
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('price_model');

        $res = $this->price_model
            ->where('type', 'coin')
            ->all();

        return $this->true_json($this->build_responses($res, ['coin_price']));

    }

    /**
     * Search purchase coin history Spec C-100
     *
     * @param array $params
     *
     * @internal param string $purchase_id
     * @internal param int $user_id
     * @internal param string $login_id
     * @internal param string $nickname
     * @internal param string $order_id
     * @internal param string $from_date
     * @internal param string $to_date
     * @internal param int $limit
     * @internal param int $offset
     * @internal param string $sort_by
     * @internal param string $sort_position
     *
     * @return array
     */
    public function search_purchase($params = [])
    {
        // Set params default
        $params = array_merge([
            'from_date' => null,
            'to_date' => null
        ], $params);

        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('from_date', '掲載開始日', 'date_format');
        $v->set_rules('to_date', '検索終了日', 'date_format|date_larger['.$params['from_date'].']');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('purchase_model');
        $this->load->model('credit_card_model');

        // Set default for params
        if (!isset($params['sort_by']) || !in_array($params['sort_by'], ['created_at'])) {
            $params['sort_by'] = 'created_at';
        }

        // Set default for param sort position
        if (!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'asc';
        }

        // Prepare query
        $this->purchase_model
            ->calc_found_rows()
            ->select('purchase.id AS purchase_id, purchase.created_at AS purchase_at, purchase.amount, purchase.coin, purchase.order_id')
            ->select('parent.id AS parent_id, parent.login_id AS parent_login_id, parent.nickname AS parent_nickname, parent_promotion.code AS parent_invitation_code, parent_inviter.id AS parent_inviter_id, parent_inviter.login_id AS parent_inviter_login_id')
            ->select('student.id AS student_id, student.login_id AS student_login_id, student.nickname AS student_nickname, student_promotion.`code` AS student_invitation_code, student_inviter.id AS student_inviter_id, student_inviter.login_id AS student_inviter_login_id')
            ->join('user AS parent', 'parent.id = purchase.user_id')
            ->join('user AS student', 'student.id = target_id')
            ->join('user AS parent_inviter', 'parent.invited_from_id = parent_inviter.id', 'LEFT')
            ->join('user AS student_inviter', 'student.invited_from_id = student_inviter.id', 'LEFT')
            ->join('user_promotion_code AS parent_promotion', 'parent_promotion.user_id = parent.id AND parent_promotion.type = "forceclub"', 'LEFT')
            ->join('user_promotion_code AS student_promotion', 'student_promotion.user_id = student.id AND student_promotion.type = "forceclub"', 'LEFT')
            ->order_by('purchase.' . $params['sort_by'], $params['sort_position'])
            ->where('purchase.status', Purchase_model::PURCHASE_STATUS_SUCCESS)
            ->where('purchase.type', Purchase_model::PURCHASE_TYPE_COIN);

        if (!empty($params['purchase_id'])) {
            $this->purchase_model->where('purchase.id', $params['purchase_id']);
        }

        if (!empty($params['order_id'])) {
            $this->purchase_model->where('purchase.order_id', $params['order_id']);
        }

        if (!empty($params['user_id'])) {
            $this->purchase_model->where('parent.id', $params['user_id']);
        }

        if (!empty($params['login_id'])) {
            $this->purchase_model->where('parent.login_id', $params['login_id']);
        }

        if (!empty($params['nickname'])) {
            $this->purchase_model->where('parent.nickname', $params['nickname']);
        }

        if(!empty($params['from_date'])) {
            $this->purchase_model->where('purchase.created_at >=', business_date('Y-m-d H:i:s', strtotime($params['from_date'] . ' 00:00:00')) );
        }

        if(!empty($params['to_date'])) {
            $this->purchase_model->where('purchase.created_at <=', business_date('Y-m-d H:i:s', strtotime($params['to_date'] . ' 23:59:59')) );
        }

        if (!empty($params['limit'])) {
            $this->purchase_model->limit($params['limit'], isset($params['offset']) ? (int) $params['offset'] : 0);
        }

        $res = $this->purchase_model->all();

        // Build response
        $items = [];
        foreach ($res AS $record) {
            $items[] = [
                'id' => (int) $record->purchase_id,
                'order_id' => $record->order_id,
                'created_at' => $record->purchase_at,
                'amount' => (int) $record->amount,
                'coin' => (int) $record->coin,
                'user' => [
                    'id' => (int) $record->parent_id,
                    'login_id' => $record->parent_login_id,
                    'nickname' => $record->parent_nickname,
                    'invitation_code' => !empty($record->parent_invitation_code) ? $record->parent_invitation_code : null,
                    'inviter_id' => !empty($record->parent_inviter_id) ? (int) $record->parent_inviter_id : null,
                    'inviter_login_id' => !empty($record->parent_inviter_login_id) ? $record->parent_inviter_login_id : null,
                ],
                'target_user' => [
                    'id' => (int) $record->student_id,
                    'login_id' => $record->student_login_id,
                    'nickname' => $record->student_nickname,
                    'invitation_code' => !empty($record->student_invitation_code) ? $record->student_invitation_code : null,
                    'inviter_id' => !empty($record->student_inviter_id) ? (int) $record->student_inviter_id : null,
                    'inviter_login_id' => !empty($record->student_inviter_login_id) ? $record->student_inviter_login_id : null,
                ]
            ];
        }

        // Return
        return $this->true_json([
            'total' => $this->purchase_model->found_rows(),
            'items' => $items
        ]);
    }

    /**
     * Search coin using history Spec C-200
     *
     * @param array $params
     *
     * @internal param int $buying_id
     * @internal param int $user_id
     * @internal param string $login_id
     * @internal param string $nickname
     * @internal param string $from_date
     * @internal param string $to_date
     * @internal param int $limit
     * @internal param int $offset
     * @internal param string $sort_by
     * @internal param string $sort_position
     *
     * @return array
     */
    public function search_buying($params)
    {
        // Set params default
        $params = array_merge([
            'from_date' => null,
            'to_date' => null
        ], $params);

        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('from_date', '掲載開始日', 'date_format');
        $v->set_rules('to_date', '検索終了日', 'date_format|date_larger['.$params['from_date'].']');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default for params
        if (!isset($params['sort_by']) || !in_array($params['sort_by'], ['created_at'])) {
            $params['sort_by'] = 'created_at';
        }

        // Set default for param sort position
        if (!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'asc';
        }

        // Load model
        $this->load->model('user_buying_model');

        // Prepare query
        $this->user_buying_model
            ->calc_found_rows()
            ->select('user_buying.id, user_buying.created_at, user_buying.coin AS coin')
            ->select('user.id AS user_id, user.login_id, user.nickname')
            ->select('user_promotion_code.code AS invitation_code, user_inviter.id AS inviter_id, user_inviter.login_id AS inviter_login_id')
            ->select('deck.id AS deck_id, deck.name AS deck_name')
            ->join('user', 'user.id = user_buying.user_id')
            ->join('user AS user_inviter', 'user.invited_from_id = user_inviter.id', 'LEFT')
            ->join('user_promotion_code', 'user_promotion_code.user_id = user.id AND user_promotion_code.type = "forceclub"', 'LEFT')
            ->join('schooltv_content.deck', 'deck.id = user_buying.target_id', 'LEFT')
            ->order_by('user_buying.' . $params['sort_by'], $params['sort_position'])
            ->where('user_buying.type', User_buying_model::TYPE_OF_DECK);

        if (!empty($params['buying_id'])) {
            $this->user_buying_model->where('user_buying.id', $params['buying_id']);
        }

        if (!empty($params['user_id'])) {
            $this->user_buying_model->where('user.id', $params['user_id']);
        }

        if (!empty($params['login_id'])) {
            $this->user_buying_model->where('user.login_id', $params['login_id']);
        }

        if (!empty($params['nickname'])) {
            $this->user_buying_model->where('user.nickname', $params['nickname']);
        }

        if(!empty($params['from_date'])) {
            $this->user_buying_model->where('user_buying.created_at >=', business_date('Y-m-d H:i:s', strtotime($params['from_date'] . ' 00:00:00')) );
        }

        if(!empty($params['to_date'])) {
            $this->user_buying_model->where('user_buying.created_at <=', business_date('Y-m-d H:i:s', strtotime($params['to_date'] . ' 23:59:59')) );
        }

        if (!empty($params['limit'])) {
            $this->user_buying_model->limit($params['limit'], isset($params['offset']) ? (int) $params['offset'] : 0);
        }

        $res = $this->user_buying_model->all();

        // Build response
        $items = [];
        foreach ($res AS $record) {
            $items[] = [
                'id' => (int) $record->id,
                'created_at' => $record->created_at,
                'coin' => (int) $record->coin,
                'user' => [
                    'id' => (int) $record->user_id,
                    'login_id' => $record->login_id,
                    'nickname' => !empty($record->nickname) ? $record->nickname : null,
                    'invitation_code' => !empty($record->parent_invitation_code) ? $record->parent_invitation_code : null,
                    'inviter_id' => !empty($record->parent_inviter_id) ? (int) $record->parent_inviter_id : null,
                    'inviter_login_id' => !empty($record->parent_inviter_login_id) ? $record->parent_inviter_login_id : null,
                ],
                'deck' => [
                    'id' => !empty($record->deck_id) ? (int) $record->deck_id : null,
                    'name' => !empty($record->deck_name) ? $record->deck_name : null
                ]
            ];
        }

        // Return
        return $this->true_json([
            'total' => $this->user_buying_model->found_rows(),
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

        if (in_array('purchase', $options)) {
            $result = [
                'coin' => (int) $res->coin,
                'created_at' => $res->created_at,
                'nickname' => $res->nickname,
                'primary_type' => $res->primary_type,
                'login_id' => $res->login_id,
                'avatar_id' => (int) $res->avatar_id
            ];
        }

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

        if (in_array('buying', $options)) {
            $result = [
                'coin' => (int) $res->coin,
                'created_at' => $res->created_at,
                'target' => [
                    'type' => $res->type,
                    'id' => (int) $res->target_id,
                    'name' => $res->name,
                    'image_key' => isset($res->image_key) ? $res->image_key : null
                ]

            ];
        }

        if (in_array('coin_price', $options)) {
            $result = [
                'price' => (int) $res->price,
                'coin' => (int) $res->number
            ];
        }

        return $result;
    }
}

/**
 * Class Coin_api_validator
 */
class Coin_api_validator extends Base_api_validation
{
    /**
     * Validates coin for purchase
     *
     * @param int $coin
     *
     * @return bool
     */
    public function valid_coin($coin)
    {
        // Load model
        $this->base->load->model('price_model');

        // Get price by coin
        $price = $this->base->price_model->find_by([
            'type' => 'coin',
            'number' => $coin
        ]);

        if (empty($price)) {
            $this->set_message('valid_coin', 'コインが無効です');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate ended_date must to be larger than started_date
     *
     * @param string $ended_date
     * @param string $started_date
     *
     * @return bool
     */
    function date_larger($ended_date = NULL, $started_date) {

        // Ended date value is not required , only check when $ended_date has value
        if($ended_date && strtotime($ended_date) < strtotime($started_date)) {
            $this->set_message('date_larger', '終了日時は開始日時より後になるように設定してください。');
            return FALSE;
        }

        return TRUE;
    }
}