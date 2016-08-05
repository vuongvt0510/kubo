<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Grade controller
 */
class Grade extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['detail']
        ]);
    }

    /**
     * Grade list page Spec TP15
     */
    public function detail()
    {
        $this->_render();
    }

}