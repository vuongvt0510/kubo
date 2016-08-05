<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Sitemap extends Application_controller
{
    public function index()
    {
        $this->_render();
    }
}