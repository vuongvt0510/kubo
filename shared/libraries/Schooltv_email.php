<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once BASEPATH . 'libraries/Email.php';
require_once SHAREDPATH . 'libraries/APP_Email.php';
require_once SHAREDPATH . 'libraries/APP_Queuing_email.php';

/**
 * SchoolTV Email
 *
 * @author Duy Ton
 */
class Schooltv_email extends APP_Queuing_email
{
    /** @var null|object $CI instance */
    protected $CI = null;

    public $subject_email = [
        // Mail ID 320
        'batch_monthly_payment_success' => '【スクールTV】スクールTV Plus決済完了のお知らせ',
        //Mail ID 330
        'batch_monthly_payment_pending' => '【スクールTV】スクールTV Plus決済失敗のお知らせ',
        // Mail ID 390
        'batch_monthly_payment_canceling_to_not_contract' => '【スクールTV】スクールTV Plusの解約を受付けました',
        // Mail ID 360
        'batch_monthly_payment_10th_pending_mail' => '【スクールTV】スクールTV Plusの決済が完了しておりません',
        // Mail ID 300
        'batch_monthly_payment_free_plan_expired' => '【スクールTV】スクールTV Plus無料期間終了のお知らせ',
        // Mail ID 380
        'batch_monthly_payment_turn_off_pending_contract' => '【スクールTV】スクールTV Plus契約終了のお知らせ',

        // Mail ID 430
        'expire_current_coin' => '【スクールTV】コインの利用期限切れのお知らせ',

        // Notice email for rabipoint will be expired next month
        'expire_rabipoint' => '【スクールTV】ラビポイント有効期限のお知らせ'
    ];

    /**
     * Constructor
     *
     * @param array $config
     *
     * @throws APP_Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->CI =& get_instance();
    }

    /**
     * Send email
     *
     * @param string $path
     * @param string $to_email
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function send($path = '', $to_email = '', $attributes = [], $options = [])
    {

        // Load the library
        $this->CI->load->library('smarty');

        $res = $this->CI->smarty->view(SHAREDPATH . 'views/mails/' . $path, array_merge($attributes, [
            'service_name' => $this->CI->config->item('service_name')
        ]), TRUE);

        $this->subject($this->subject_email[$path]);

        $this->message($res);

        $this->from($this->CI->config->item('mail_from'), $this->CI->config->item('mail_from_name'));

        $this->to($to_email);

        return parent::send(array_merge(['queuing' => FALSE], $options));
    }

}

