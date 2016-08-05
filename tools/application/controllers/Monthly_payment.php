<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "core/APP_Cli_controller.php";

/**
 * Class Monthly_payment
 *
 * @property Gmo_payment gmo_payment
 * @property Schooltv_email schooltv_email
 * @property User_model user_model
 * @property Credit_card_model credit_card_model
 * @property Purchase_model purchase_model
 * @property User_contract_model user_contract_model
 * @property User_group_model user_group_model
 * @property User_contract_history_model user_contract_history_model
 *
 * @package Controller
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class Monthly_payment extends APP_Cli_controller
{

    var $list_contract_retries = [];

    /**
     * Monthly_payment constructor.
     */
    function __construct()
    {
        parent::__construct();

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        //Load library and model
        $this->load->library('gmo_payment');
        $this->load->library('schooltv_email');

        $this->load->model('user_model');
        $this->load->model('credit_card_model');
        $this->load->model('user_contract_model');
        $this->load->model('purchase_model');
        $this->load->model('user_contract_history_model');

        $this->load->helper('url');
    }

    /**
     * Reinsert contract for student when release v 2.1
     */
    public function reinsert_contract()
    {
        $this->load->model('user_model');
        $this->load->model('user_contract_model');

        $res = $this->user_model
            ->select('user.id, user.created_at, user_contract.expired_time')
            ->join('user_contract', 'user_contract.user_id = user.id', 'LEFT')
            ->where('user.primary_type', 'student')
            ->where('user_contract.user_id IS NULL')
            ->all([
                'master' => TRUE
            ]);

        foreach ($res AS $user) {

            if (empty($user->expired_time)) {
                $this->user_contract_model->create([
                    'user_id' => $user->id,
                    'status' => 'free',
                    'expired_time' => business_date('Y-m-d H:i:s', strtotime('+30 days', strtotime($user->created_at))),
                ], [
                    'master' => TRUE,
                    'mode' => 'replace'
                ]);
            }

        }
    }

    /**
     * Batch to auto purchase and update contract status
     * The batch will be run daily at 0h00
     *
     */
    public function auto_update()
    {
        /** @var array $this_day */
        $this_day = getdate(business_time());

        switch ($this_day['mday']) {
            case '26':
            case '27':
            case '28':
            case '29':
            case '30':
            case '31':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
            case '10':

                $end_day_timestamp = mktime(23, 59, 59, $this_day['mon'], $this_day['mday'], $this_day['year']);

                // Find all contract are purchased today
                $user_contract_res = $this->user_contract_model
                    ->select('user.id AS user_id, user.login_id, user.nickname, user.email')
                    ->select('user_contract.expired_time, user_contract.next_purchase_time, user_contract.status')
                    ->select('purchase.user_id AS parent_id')
                    ->with_user()
                    ->with_purchase()
                    ->where('purchase.user_id IS NOT NULL')
                    ->where('next_purchase_time <=', business_date('Y-m-d H:i:s', $end_day_timestamp))
                    ->where_in('user_contract.status', ['under_contract', 'pending'])
                    ->where('user.status', 'active')
                    ->all();

                $this->auto_purchase($user_contract_res);

                // Just try to purchase contract once again
                if ($this->list_contract_retries) {
                    $user_contract_res = $this->list_contract_retries;
                    $this->list_contract_retries = [];

                    //
                    sleep(5);

                    $this->auto_purchase($user_contract_res, TRUE);
                }
                break;
        }

        // After auto update purchase, send all email from queue
        try {
            $this->schooltv_email->send_from_all_queue();
        } catch (Exception $e) {
            sleep(10);
            // Try to send email again
            $this->schooltv_email->send_from_all_queue();
        }
    }

    /**
     * Auto purchase function
     *
     * @param array $user_contract_res
     * @param bool $is_try
     *
     * @throws Exception
     */
    private function auto_purchase($user_contract_res, $is_try = FALSE)
    {
        foreach ($user_contract_res AS $contract) {

            log_message('info', sprintf('[MC_Batch] Start to purchasing contract of user %s : %s ', $contract->user_id, $contract->login_id));

            $parent = $this->user_model->find($contract->parent_id);

            if (empty($parent)) {
                log_message('info', sprintf('Cancel this user contract because parent %s (%s) is deleted', $parent->login_id, $parent->id));
                // Turn of contract this user
                // Need to change status to not_contract for another parent can be purchase to this user
                $this->user_contract_model->update($contract->user_id, [
                    'status' => 'canceling'
                ]);

                continue;
            }

            if (business_date('d') == 26 && $contract->status == 'pending' && strtotime($contract->expired_time) < business_time()) {
                log_message('info', sprintf('This contract is pending from last month, need to change contract status to not_contract'));
                // Check if this contract is pending from 26th last_month => change this contract from pending to not_contract
                $this->turn_off_pending_contract_at_25th($contract, $parent->email);

                continue;
            }

            //Get credit card
            $credit_card = $this->credit_card_model
                ->find_by([
                    'user_id' => $contract->parent_id
                ]);

            if (empty($credit_card)) {

                log_message('info', sprintf('Pending this user contract because credit card of parent %s (%s) is not exist in DB', $parent->login_id, $parent->id));

                $this->pending_contract($contract);

                continue;
            }

            // Starting to purchase
            $order_id = $this->purchase_model->generate_order_id($contract->parent_id, $contract->user_id, Purchase_model::PURCHASE_TYPE_CONTRACT_BATCH);

            $this->load->library('gmo_payment');
            // Create tran
            $tran = $this->gmo_payment->exec_credit_card([
                'job_cd' => 'CAPTURE',
                'order_id' => $order_id,
                'amount' => DEFAULT_MONTHLY_PAYMENT_AMOUNT,
                'member_id' => $this->credit_card_model->generate_gmo_member_id($contract->parent_id),
                'card_seq' => $credit_card->card_seq,
                'method' => 1
            ]);

            // If transaction was error
            if (!$tran) {
                $gmo_errors = $this->gmo_payment->get_error();

                if ($gmo_errors['has_internal_error']) {
                    // If the error occur by GMO or internal error, show retry to purchase contract again
                    log_message('info', sprintf('[MC_Batch] GMO error when purchase %s', $gmo_errors['message']));

                    if (!$is_try && business_date('d') != 26) {
                        // If batch is run at 26 => try to run again, so another day no need to try because it really pending
                        log_message('info', sprintf('The batch will be retry to purchase once again for this user', $gmo_errors['message']));
                        $this->list_contract_retries[] = $contract;
                    } else {
                        log_message('info', sprintf('This purchase still error when trying, pending for this user', $gmo_errors['message']));
                        // If this turn is try to process to GMO, but it is not success, so pending this
                        $this->pending_contract($contract, $parent->email, $gmo_errors['message']);
                    }


                } else {
                    // If this error occur by user credit card, need to pending this contract
                    log_message('info', sprintf('[MC_Batch] GMO error when purchase %s', $gmo_errors['message']));
                    $this->pending_contract($contract, $parent->email, $gmo_errors['message']);
                }

                continue;
            }

            // Purchase success
            // Store purchase information into DB
            $purchase_res = $this->purchase_model->create([
                'order_id' => $order_id,
                'user_id' => $contract->parent_id,
                'target_id' => $contract->user_id,
                'amount' => DEFAULT_MONTHLY_PAYMENT_AMOUNT, // Amount money
                'type' => 'contract',
                'status' => isset($tran['acs']) ? Purchase_model::PURCHASE_STATUS_PENDING : Purchase_model::PURCHASE_STATUS_SUCCESS,
                'data' => json_encode($tran)
            ], [
                'return' => TRUE
            ]);

            if ($purchase_res->status == Purchase_model::PURCHASE_STATUS_SUCCESS) {
                log_message('info', sprintf('Successfully purchase', $contract->login_id, $contract->user_id));

                // If transaction is ok and don't required 3d security => Purchase is ok
                // Process transaction
                // Calculator expired date
                $current_time = business_time();
                $last_expired = strtotime($contract->expired_time);

                // If user still in free time and register contract, current time will change to end of free time.
                $current_time = $current_time < $last_expired ? $last_expired : $current_time;

                // It will be apply bonus period in current month
                // Next purchase will be on 26 of nextmonth
                $next_month = strtotime('first day of next month', $current_time);
                $next_purchase_time = mktime(0, 0, 0, date('m', $next_month), 26, date('Y', $next_month));
                $new_expired = strtotime('last day of next month', $current_time);

                // Update
                $this->user_contract_model->create([
                    'user_id' => $contract->user_id,
                    'status' => 'under_contract',
                    'purchase_id' => $purchase_res->id,
                    'next_purchase_time' => business_date('Y-m-d H:i:s', $next_purchase_time),
                    'expired_time' => business_date('Y-m-d H:i:s', $new_expired)
                ], [
                    'mode' => 'replace',
                    'return' => TRUE
                ]);

                // Log history
                $this->user_contract_history_model->create([
                    'user_id' => $contract->user_id,
                    'parent_id' => $parent->id,
                    'purchase_id' => $purchase_res->id,
                    'type' => User_contract_history_model::TYPE_AUTO_PURCHASE_AT_26TH,
                    'status' => User_contract_history_model::STATUS_SUCCESS
                ]);

                // Send email to parent user when purchase is success

                $this->schooltv_email->send('batch_monthly_payment_success', $parent->email, [
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'amount' => DEFAULT_MONTHLY_PAYMENT_AMOUNT,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);

                if ($contract->email != $parent->email) {
                    // Also send email to student
                    $this->schooltv_email->send('batch_monthly_payment_success', $contract->email, [
                        'user_id' => $contract->login_id,
                        'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                        'amount' => DEFAULT_MONTHLY_PAYMENT_AMOUNT,
                        'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                    ], ['queuing' => TRUE]);
                }

            } else {
                // Pending
                log_message('info', sprintf('Unsuccessfully purchase because this parent credit card use 3d verify'));
                $this->pending_contract($contract, $parent->email, 'Your credit card use 3d security, the system can not auto purchase monthly payment');
            }
        }
    }

    /**
     * Pending contract
     * @param object $contract
     * @param string $parent_email
     * @param string $error_message
     */
    private function pending_contract($contract, $parent_email = '', $error_message = '')
    {
        if ($contract->status == 'under_contract') {
            // Change status of this contract to pending
            $next_month = strtotime('first day of next month', business_time());

            // Allow expired monthly payment pending to date 10 of next month
            $pending_to_date = mktime(23, 59, 59, date('m', $next_month), 10, date('Y', $next_month));

            // If this error occur by user credit card, need to pending this contract
            $this->user_contract_model->update($contract->user_id, [
                'expired_time' => business_date('Y-m-d H:i:s', $pending_to_date),
                'status' => 'pending'
            ]);

            if ($parent_email) {
                $this->schooltv_email->send('batch_monthly_payment_pending', $parent_email, [
                    'error' => $error_message,
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }

            if ($parent_email != $contract->email) {
                // Send email to student
                $this->schooltv_email->send('batch_monthly_payment_pending', $contract->email, [
                    'error' => $error_message,
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }
        }

        // Log history
        $this->user_contract_history_model->create([
            'user_id' => $contract->user_id,
            'parent_id' => $contract->parent_id,
            'type' => $contract->status == 'under_contract' ?
                User_contract_history_model::TYPE_AUTO_PURCHASE_AT_26TH : User_contract_history_model::TYPE_TRYING_AUTO_PURCHASE_26TH,
            'status' => User_contract_history_model::STATUS_FAIL,
            'content' => $error_message
        ]);
    }

    /**
     * Turn off the contract which pending from last month
     * @param object $contract
     * @param string $parent_email
     */
    private function turn_off_pending_contract_at_25th($contract, $parent_email = '')
    {
        if ($contract->status == 'pending') {

            $this->user_contract_model->update($contract->user_id, [
                'status' => 'not_contract',
                'purchase_id' => null
            ]);

            if ($parent_email) {
                $this->schooltv_email->send('batch_monthly_payment_turn_off_pending_contract', $parent_email, [
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }

            if ($parent_email != $contract->email) {
                // Send email to student
                $this->schooltv_email->send('batch_monthly_payment_turn_off_pending_contract', $contract->email, [
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }
        }

        // Log history
        $this->user_contract_history_model->create([
            'user_id' => $contract->user_id,
            'type' => User_contract_history_model::TYPE_TURN_OFF_PENDING_25TH,
            'status' => User_contract_history_model::STATUS_SUCCESS
        ]);

    }


    /**
     * Auto change contract to not_contract of pending contract and canceling contract
     * We need to create batch to run this function at first day on every month
     */
    public function auto_turn_off_contract()
    {
        /** @var array $this_day */
        $this_day = getdate(business_time());

        // This batch must be run at first day of month
        if ($this_day['mday'] != 1) {
            return FALSE;
        }

        $end_day_timestamp = mktime(23, 59, 59, $this_day['mon'], $this_day['mday'], $this_day['year']);

        // Find all contract are purchased today
        $user_contract_res = $this->user_contract_model
            ->select('user.id AS user_id, user.login_id, user.nickname')
            ->select('user_contract.expired_time, user_contract.next_purchase_time, user_contract.status')
            ->select('purchase.user_id AS parent_id')
            ->with_user()
            ->with_purchase()
            ->where('expired_time <=', business_date('Y-m-d H:i:s', $end_day_timestamp))
            ->where_in('user_contract.status', ['canceling', 'pending'])
            ->where('user.status', 'active')
            ->all();

        foreach ($user_contract_res AS $contract) {

            log_message('info', sprintf('[MC_Batch] Turn contract status to not_contract of user %s : %s ', $contract->user_id, $contract->login_id));

            $this->user_contract_model->update($contract->user_id, [
                'status' => 'not_contract',
                'purchase_id' => null
            ]);

            // Log history
            $this->user_contract_history_model->create([
                'user_id' => $contract->user_id,
                'type' => $contract->status == 'canceling' ? User_contract_history_model::TYPE_TURN_OFF_CANCEL : User_contract_history_model::TYPE_TURN_OFF_PENDING_25TH,
                'status' => User_contract_history_model::STATUS_SUCCESS
            ]);

            $parent = $this->user_model->find($contract->parent_id);

            if (!empty($parent)) {
                $this->schooltv_email->send('batch_monthly_payment_canceling_to_not_contract', $parent->email, [
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }

            if (empty($parent) || (!empty($parent) && $contract->email != $parent->email)) {
                $this->schooltv_email->send('batch_monthly_payment_canceling_to_not_contract', $contract->email, [
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }

        }

        // After auto update purchase, send all email from queue
        $this->send_email_from_queue();
    }

    /**
     * This batch use for send email notice that pending contract can not play video drill at day 11 each month
     * This batch must be run at 00:00:00 at day 11 every month
     * @return bool
     */
    public function auto_send_mail_at_10th_pending()
    {
        /** @var array $this_day */
        $this_day = getdate(business_time());

        // This batch must be run at first day of month
        if ($this_day['mday'] != 11) {
            return FALSE;
        }

        // Date of
        $day_10th_of_month_timestamp = mktime(23, 59, 59, $this_day['mon'], $this_day['mday'] - 1, $this_day['year']);

        // Find all contract are purchased today
        $user_contract_res = $this->user_contract_model
            ->select('user.id AS user_id, user.login_id, user.nickname, user.email')
            ->select('user_contract.expired_time, user_contract.next_purchase_time, user_contract.status')
            ->select('purchase.user_id AS parent_id')
            ->with_user()
            ->with_purchase()
            ->where('expired_time >=', business_date('Y-m-d 00:00:00', $day_10th_of_month_timestamp))
            ->where('expired_time <=', business_date('Y-m-d 23:59:59', $day_10th_of_month_timestamp))
            ->where_in('user_contract.status', ['pending'])
            ->where('user.status', 'active')
            ->all();

        foreach ($user_contract_res AS $contract) {

            $parent = $this->user_model->find($contract->parent_id);

            // Log history
            $this->user_contract_history_model->create([
                'user_id' => $contract->user_id,
                'type' => User_contract_history_model::TYPE_PENDING_AT_11TH,
                'status' => User_contract_history_model::STATUS_SUCCESS
            ]);

            log_message('info', sprintf('[MC_Batch] Send pending email to user %s: %s', $contract->user_id, $contract->login_id));

            if (!empty($parent)) {
                $this->schooltv_email->send('batch_monthly_payment_10th_pending_mail', $parent->email, [
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }

            if (empty($parent) || (!empty($parent) && $contract->email != $parent->email)) {
                $this->schooltv_email->send('batch_monthly_payment_10th_pending_mail', $contract->email, [
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }
        }

        // After auto update purchase, send all email from queue
        $this->send_email_from_queue();
    }

    /**
     * This batch use for auto send email inform that user is finish the 30 days free contract plan
     * This batch must be run every day to check and send email
     */
    public function auto_send_email_free_plan_expired()
    {
        $this->load->model('user_group_model');

        /** @var array $this_day */
        $yesterday_timestamp = strtotime('-1 day', business_time());

        // Find all of the free contract is limit on yesterday
        $user_contract_res = $this->user_contract_model
            ->select('user.id AS user_id, user.login_id, user.nickname, user.email')
            ->with_user()
            ->where('expired_time >=', business_date('Y-m-d 00:00:00', $yesterday_timestamp))
            ->where('expired_time <=', business_date('Y-m-d 23:59:59', $yesterday_timestamp))
            ->where('user_contract.status', 'free')
            ->where('user.status', 'active')
            ->all();

        foreach ($user_contract_res AS $contract) {
            log_message('info', sprintf('[Free_plan_expired] Send email to user %s | %s', $contract->user_id, $contract->login_id));

            // List email of user and user parent
            $list_emails = [$contract->email];

            // Find all parents in all groups that student join in
            $res = $this->user_group_model
                ->select('DISTINCT(user.email)')
                ->join('user', 'user.id = user_group.user_id')
                ->where('group_id IN (SELECT group_id FROM user_group WHERE user_id = '.$contract->user_id.')')
                ->where('user.primary_type', 'parent')
                ->where('user.status', 'active')
                ->all();

            foreach ($res AS $record) {
                if (!empty($record->email) && !in_array($record->email, $list_emails)) {
                    $list_emails = array_merge($list_emails, [$record->email]);
                }
            }

            foreach ($list_emails AS $email) {
                $this->schooltv_email->send('batch_monthly_payment_free_plan_expired', $email, [
                    'user_id' => $contract->login_id,
                    'user_name' => !empty($contract->nickname) ? $contract->nickname : DEFAULT_NICKNAME,
                    'url' => $this->config->item('site_url') . 'pay_service/' . $contract->user_id
                ], ['queuing' => TRUE]);
            }
        }

        $this->send_email_from_queue();
    }

    private function send_email_from_queue()
    {
        try {
            $this->schooltv_email->send_from_all_queue();
        } catch (Exception $e) {
            sleep(10);
            // Try to send email again
            $this->schooltv_email->send_from_all_queue();
        }
    }
}
