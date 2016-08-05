<?php

/**
 * エラー通知ドライバ - Email
 *
 * @author Yoshikazu Ozawa
 */
class APP_Error_notifier_driver_email {

    /**
     * エラーメール送信元メールアドレス
     * @ver string
     */
    public $from = NULL;

    /**
     * エラーメール送信先
     * @ver string
     */
    public $to = NULL;

    public function __construct($params)
    {
        foreach ($params as $key => $value) {
            if (FALSE !== $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * 送信
     *
     * @access public
     * @param string $subject
     * @param string $contents
     * @param array $options
     * @return bool
     */
    public function send($subject, $contents, $options = array())
    {
        $CI =& get_instance();
        $CI->load->library('email');

        $CI->email->clear();

        call_user_func(array($CI->email, 'subject'), $subject);
        call_user_func_array(array($CI->email, 'from'), $this->from);
        foreach ($this->to as $t) {
            $CI->email->to($t, NULL, TRUE);
        }

        $CI->email->message($contents);
        $CI->email->send();

        log_message('debug', "[Error notifier email]\n" . $CI->email->print_debugger());
    }
}

