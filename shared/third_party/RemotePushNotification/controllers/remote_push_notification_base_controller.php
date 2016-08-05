<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('APP_Batch_controller')) {
    require_once SHAREDPATH . "core/APP_Batch_controller.php";
}

/**
 * プッシュ通知バッチ
 *
 * @author Yoshikazu Ozawa
 */
class Remote_push_notification_base_controller extends APP_Batch_controller {

    /**
     * プッシュ通知
     *
     * @access public
     * @return bool
     */
    public function send($app_name, $sent_at = NULL)
    {
        $this->load->library("rpn_sender", array("app_name" => $app_name));
        $this->rpn_sender->send($sent_at);
    }

    /**
     * フィードバック
     *
     * @access public
     * @return bool
     */
    public function feedback($app_name)
    {
        $this->load->library("rpn_sender", array("app_name" => $app_name));
        $this->rpn_sender->feedback();
    }

    /**
     * キューをクリア
     *
     * @access public
     * @return bool
     */
    public function clean($app_name, $time = NULL)
    {
        $this->load->library("rpn_sender", array("app_name" => $app_name));
        $this->rpn_sender->clean($time);
    }
}

