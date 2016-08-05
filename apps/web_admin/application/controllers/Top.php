<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Top extends Application_controller
{
    public function index()
    {
        return redirect('news');

        $this->_render([
            'menu_active' => 'li_home',
            'breadcrumb' => []
        ]);
    }
}