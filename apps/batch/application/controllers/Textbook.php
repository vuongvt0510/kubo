<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(SHAREDPATH . 'core/APP_Cli_controller.php');

/**
 * Textbook batch
 *
 * @property Master_textbook_inuse_model master_textbook_inuse_model
 * @property Textbook_cache_count_model textbook_cache_count_model
 * @package Controller
 *
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Textbook extends APP_Cli_controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function execute(){
        log_message('INFO', 'execute'); return TRUE;
    }
    /**
     * Update textbook share
     */
    public function update_cache_share()
    {
        // Load Model
        $this->load->model('master_textbook_inuse_model');
        $this->load->model('textbook_cache_count_model');

        $offset = 0;
        while(true) {

            try {

                $res = $this->master_textbook_inuse_model
                    ->select('textbook_id, count(textbook_id) as count')
                    ->group_by('textbook_id')
                    ->order_by('count', 'DESC')
                    ->limit(100)
                    ->offset($offset)
                    ->all(['master' => TRUE]);

                // Return if done
                if(empty($res)) {
                    log_message('INFO', 'Updated textbook cache count.');
                    break;
                }

                foreach ($res as $k) {
                    try {
                        // Create cache count
                        $this->textbook_cache_count_model->create(
                            [
                                'textbook_id' => (int) $k->textbook_id,
                                'count' => (int) $k->count
                            ],
                            [
                                'mode' => 'replace'
                            ]

                        );
                    }
                    catch (APP_Exception $e) {
                        // Throw message when create error
                        log_message(
                            'WARNING',
                            sprintf(
                                "Textbook cache count %s() has warning: %s",
                                __METHOD__,
                                $e->getMessage()
                            )
                        );
                        continue;
                    }
                }

            } catch (APP_Exception $e) {

                log_message(
                    'ERROR',
                    sprintf(
                        "Textbook cache count %s() has error: %s",
                        __METHOD__,
                        $e->getMessage()
                    )
                );

                return FALSE;
            }

            $offset += 100;
        }

        return TRUE;
    }


}
