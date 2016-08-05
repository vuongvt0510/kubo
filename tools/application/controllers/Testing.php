<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Batch to import something for testing
 *
 * @package Controller
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */

require_once SHAREDPATH . "core/APP_Cli_controller.php";

/**
 * Class Testing
 *
 * @property User_model user_model
 */
class Testing extends APP_Cli_controller
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
     * Set dummy highest score for random user
     */
    public function set_dummy_highest_score()
    {
        $this->load->model('user_model');

        // Reset all ranking

        $query = "UPDATE {$this->user_model->database_name}.{$this->user_model->table_name} SET highest_score = NULL" ;

        $this->user_model->master->query($query);

        $res = $this->user_model
            ->where('status', 'active')
            ->where('primary_type', 'student')
            ->order_by('id', 'ASC')
            ->limit(100)
            ->all();

        foreach ($res AS $key => $user) {

            $key += 1;
            // Update random highest score

            $score = 0;

            switch (TRUE) {
                case $key == 1:
                    $score = 35000;
                    break;

                case $key == 2:
                    $score = 30000;
                    break;

                case $key >= 3 && $key <= 8:
                    $score = 29999 - ($key - 3);
                    break;

                case $key == 9:
                    $score = 27000;
                    break;
                case $key == 10:
                    $score = 25000;
                    break;
                case $key == 11:
                    $score = 20000;
                    break;

                case $key >= 12 && $key <= 48:
                    $score = 19999 - ($key - 12);
                    break;

                case $key == 49:
                    $score = 17000;
                    break;

                case $key == 50:
                    $score = 15000;
                    break;

                case $key == 51:
                    $score = 10000;
                    break;

                case $key >= 52 && $key <= 98:
                    $score = 9999 - ($key - 52);
                    break;

                case $key == 99:
                    $score = 7000;
                    break;

                case $key == 100:
                    $score = 3000;
                    break;

            }

            $this->user_model->update($user->id, [
                'highest_score' => $score
            ]);
        }
    }
}
