<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(SHAREDPATH . 'core/APP_Cli_controller.php');

/**
 * Register batch
 *
 * @property User_email_verify_model User_email_verify_model
 * @package Controller
 *
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Register extends APP_Cli_controller
{

    /**
     * Delete user not verified with expired token
     */
    public function expire_current_coin()
    {
        // Load Model
        $this->load->model('user_email_verify_model');

        $this ->user_email_verify_model->check_login_id();
    }
}
