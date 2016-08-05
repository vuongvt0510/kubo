<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Page Controller
 *
 * @author DiepHQ
 */
class Page extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'only' => ['logout']
        ]);

        $this->_before_filter('_logged_in',[
            'except' => ['detail']
        ]);
    }

    /**
     * Index Spec OT-020
     *
     * @param string $key
     */
    public function detail( $key = '' )
    {   
        $view_data = [];
        $filter['key'] = $key;     

        $res = $this->_api('page')->get_detail($filter);

        if(isset($res['result'])) {
            $view_data['page'] = $res['result'];
        }
        $this->_render($view_data);
    }
}