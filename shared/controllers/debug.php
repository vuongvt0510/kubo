<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * デバッグ用クラス
 *
 * @author Yoshikazu Ozawa
 */
class Debug extends APP_Controller {

    /**
     * Debug constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_before_filter('_accept_only_development');
    }

    /**
     *
     */
    public function request()
    {
        $this->output->enable_profiler(FALSE);

        $this->template_engine = "codeigniter";
        $this->_render();
    }

    /**
     *
     */
    public function _accept_only_development()
    {
        if (ENVIRONMENT != 'development') {
            return $this->_render_404();
        }
    }
}

