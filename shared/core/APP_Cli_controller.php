<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('APP_Controller')) {
    require_once dirname(__FILE__) . "/APP_Controller.php";
}

/**
 * CLIコントローラ
 *
 * @author Yoshikazu Ozawa
 */
class APP_Cli_controller extends APP_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->output->enable_profiler(FALSE);
        $this->_before_filter('_accept_cli_request');

        set_time_limit(0);
    }

    /**
     * コマンドラインリクエストのみ通す
     *
     * @access public
     * @return void
     */
    public function _accept_cli_request()
    {
        if (TRUE !== $this->input->is_cli_request()) {
            $this->_render_404();
        }
    }

    /**
     * コマンドラインリクエスト用に書きかえ
     *
     * @access public
     *
     * @param string $method
     * @param array $params
     *
     * @throws APP_Exception
     * @throws Exception
     */
    public function _remap($method, $params = array())
    {
        if ($this->_skipped) return;

        if ( ! method_exists($this, $method)) {
            return $this->_render_404();
        }

        try {
            log_message("info", "start.");

            $result = call_user_func_array(array($this, $method), $params);

            if (FALSE === $result) {
                log_message("error", "aborted.");
            } else {
                log_message("info", "completed.");
            }

        } catch (Exception $e) {

            if (isset($this->_error_notifier)) {
                $this->_error_notifier->send_exception($e);
            }

            // 全てのコネクションを切断
            if (class_exists("APP_DB_active_record")) {
                APP_DB_active_record::close_all();
            }

            if (! $e instanceof APP_Exception) {
                log_exception("error", $e);
            }

            log_message("info", "aborted.");

            if (ENVIRONMENT === 'development') {
                throw $e;
            }
        }
    }

}
