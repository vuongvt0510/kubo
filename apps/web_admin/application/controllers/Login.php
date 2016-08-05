<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Login Controller
 *
 * @property object uri
 */
class Login extends Application_controller
{

    public $layout = "layouts/base_login";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['index'],
        ]);
    }

    /**
     * Index
     */
    public function index()
    {

        // Check auto-login
        if($this->current_user->is_login() && $this->current_user->is_administrator()) {
            $this->_redirect(site_url('news'));
        }

        // Check input data
        if ($this->input->is_post()) {

            // Call login api to authenticate
            $res = $this->_api('admin')->auth( [
                'id' => $this->input->post('id'),
                'password' => $this->input->post('password'),
                'auto_login' => $this->input->post('remember')
            ]);

            if(!isset($res['result'])) {

                $view_data = [
                    'form_errors' => isset($res['invalid_fields']) ? $res['invalid_fields'] : [],
                    'errmsg' => isset($res['errmsg']) ? $res['errmsg'] : null,
                    'post' => $this->input->post()
                ];
                $this->_render($view_data);

            } else {
                $this->_redirect(site_url('news'));
            }
        } else {
            $this->_render();
        }
    }

}
