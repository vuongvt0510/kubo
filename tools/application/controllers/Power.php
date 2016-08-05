<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Batch use for User power
 *
 * @package Controller
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */

require_once SHAREDPATH . "core/APP_Cli_controller.php";

/**
 * Class Power
 *
 * @property User_power_model user_power_model
 */
class Power extends APP_Cli_controller
{
    function __construct()
    {
        parent::__construct();

        set_time_limit(-1);
    }

    /**
     * Auto refund user power everyday
     */
    public function refund_user_power()
    {
        $this->load->model('user_power_model');

        $query = "UPDATE {$this->user_power_model->database_name}.{$this->user_power_model->table_name} SET current_power = max_power" ;

        $this->user_power_model->master->query($query);
    }

    /**
     * This function is used for testing server
     */
    public function reinsert_user_power_record()
    {
        $this->load->model('user_model');
        $this->load->model('user_power_model');

        $res = $this->user_model
            ->select('user.id, user_power.max_power')
            ->join('user_power', 'user_power.user_id = user.id', 'LEFT')
            ->where('user.primary_type', 'student')
            ->all([
                'master' => TRUE
            ]);


        foreach ($res AS $user) {

            if (empty($user->max_power)) {
                $this->user_power_model->create([
                    'user_id' => $user->id,
                    'max_power' => 40,
                    'current_power' => 40
                ], [
                    'master' => TRUE
                ]);
            }

        }

    }
}
