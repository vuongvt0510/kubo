<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(SHAREDPATH . 'core/APP_Cli_controller.php');

/**
 * Coin batch
 *
 * @property User_model user_model
 * @property Purchase_model purchase_model
 * @property User_buying_model user_buying_model
 * @property Action_history_model action_history_model
 * @property User_group_model user_group_model
 * @property Schooltv_email schooltv_email
 *
 * @package Controller
 *
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Coin extends APP_Cli_controller
{

    /**
     * expire current coin
     */
    public function expire_current_coin()
    {
        // Load Model
        $this->load->model('user_model');
        $this->load->model('purchase_model');
        $this->load->model('user_buying_model');
        $this->load->model('action_history_model');
        $this->load->model('user_group_model');
        $this->load->library('schooltv_email');

        $acquire = $this->purchase_model
            ->select('max(purchase.created_at) as created_at, purchase.target_id as user_id, user.email, user.login_id, user.nickname, user.current_coin, user.primary_type')
            ->join('user', 'user.id = purchase.target_id', 'left')
            ->where('purchase.status', Purchase_model::PURCHASE_STATUS_SUCCESS)
            ->where('purchase.type', Purchase_model::PURCHASE_TYPE_COIN)
            ->where('user.current_coin > ', 0)
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->group_by('purchase.target_id')
            ->all();

        $pay = $this->user_buying_model
            ->select('max(user_buying.created_at) as created_at, user_buying.user_id, user.email, user.login_id, user.nickname, user.current_coin, user.primary_type')
            ->join('user', 'user.id = user_buying.user_id', 'left')
            ->where('user.current_coin > ', 0)
            ->where('user_buying.type != ', 'expired')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->group_by('user_buying.user_id')
            ->all();

        $history = [];

        foreach ($acquire AS $item) {

            if (!isset($history[$item->user_id])) {
                $history[$item->user_id] = $item;
            } else if (strtotime($item->created_at) > strtotime($history[$item->user_id]->created_at)) {
                $history[$item->user_id] = $item;
            }
        }

        foreach ($pay AS $item) {
            if (!isset($history[$item->user_id])) {
                $history[$item->user_id] = $item;
            } else if (strtotime($item->created_at) > strtotime($history[$item->user_id]->created_at)) {
                $history[$item->user_id] = $item;
            }
        }

        // Check to update current coin to 0
        $last_year_timestamp = strtotime('-1 year', business_time());

        foreach ($history AS $record) {

            if($record->current_coin > 0 && strtotime($record->created_at) < $last_year_timestamp) {
                log_message('info', sprintf('[ExpiredCoin] User: %s (%s)', $record->user_id, $record->login_id));

                $this->user_model->update($record->user_id, [
                    'current_coin' => 0
                ]);

                // History for expired coin
                $this->user_buying_model->create([
                    'type' => 'expired',
                    'user_id' => $record->user_id,
                    'target_id' => 0,
                    'coin' => $record->current_coin
                ]);

                // Send mail to all parents and user
                $mail_data = [
                    'login_id' => $record->login_id,
                    'nickname' => $record->nickname,
                    'current_coin' => $record->current_coin
                ];

                // List email of user and user parent
                $list_emails = [$record->email];

                // Find all parents in all groups that student join in
                $email_parents = $this->user_group_model->get_all_parent_emails($record->user_id);

                foreach ($email_parents AS $email_parent) {
                    if (!empty($email_parent->email) && !in_array($email_parent->email, $list_emails)) {
                        $list_emails[] = $email_parent->email;
                    }
                }

                foreach ($list_emails AS $email) {
                    $this->schooltv_email->send('expire_current_coin', $email, $mail_data, ['queuing' => TRUE]);
                }

            }
        }

        // Send all emails
        try {
            $this->schooltv_email->send_from_all_queue();
        } catch (Exception $e) {
            sleep(10);
            // Try to send email again
            $this->schooltv_email->send_from_all_queue();
        }
    }
}
