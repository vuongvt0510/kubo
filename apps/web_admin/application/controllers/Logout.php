<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Logout extends Application_controller
{
    /**
     * Logout index page
     */
    public function index()
    {
        if ($this->input->is_post()) {

            $this->_api('admin')->logout();
            $this->_redirect('login');

        }

        $this->_render([
            'menu_active' => 'li_logout'
        ]);
    }
}
