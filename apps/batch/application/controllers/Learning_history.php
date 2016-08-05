<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(SHAREDPATH . 'core/APP_Cli_controller.php');

/**
 * Learning_history batch
 *
 * @property Learning_history_model
 * @package Controller
 *
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Learning_history extends APP_Cli_controller
{

    /**
     * Learning_history constructor
     */
    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        // Load Model
        $this->load->model('user_model');
    }

    /**
     * Send report of study situation to parent
     */
    public function send_students_report()
    {
        $parents = $this->user_model
            ->select('id, email')
            ->where('status', 'active')
            ->where('primary_type', 'parent')
            ->all();

        foreach ($parents as $parent) {

            $this->_api('learning_history')->send_students_situation([
                'parent_id' => $parent->id,
                'parent_email' => $parent->email
            ]);
        }
    }
}
