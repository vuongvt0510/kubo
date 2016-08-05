<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Import function use for release production
 *
 * @package Controller
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */

require_once SHAREDPATH . "core/APP_Cli_controller.php";

/**
 * Class Release
 *
 * @property User_model user_model
 */
class Release extends APP_Cli_controller
{
    /**
     * Release constructor.
     */
    function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '2048M');
        set_time_limit(-1);
    }

    /**
     * Batch to update user created at field, use for release 2.1
     */
    public function update_user_created_at_field()
    {
        $this->load->model('user_model');

        $query = "UPDATE {$this->user_model->database_name}.{$this->user_model->table_name} SET registered_at = created_at WHERE registered_at IS NULL AND `status` IS NOT NULL " ;

        log_message('info', 'Run query update user created at field sql');

        $this->user_model->master->query($query);
    }
}
