<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH.'controllers/modules/APP_Facebook_authenticatable.php';
require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Facebook authenticatable controller
 *
 */
class Facebook extends Application_controller
{
    use APP_Facebook_authenticatable;

    /**
     * @var string Layout file
     */
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['authorize', 'authorization_callback']
        ]);

        $facebook = [];
        // Load config file
        $files = array(
            SHAREDPATH . "config/facebook.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/facebook.php",
            APPPATH . "config/facebook.php",
            APPPATH . "config/" . ENVIRONMENT . "/facebook.php"
        );

        foreach ($files as $f) {
            if (is_file($f)) {
                include $f;
            }
        }

        /** @var array $facebook_config */
        $facebook_config = $facebook;

        foreach($facebook_config as $key => $val) {
            if(isset($this->{$key})) {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * @param Exception $e
     */
    private function _authorization_denied($e)
    {
        $redirect_url = $this->session->userdata('oauth_redirect_page');
        redirect($redirect_url);
    }

    /**
     * Process function when authorization facebook is successful
     *
     * @param int $id of facebook account
     * @param string $token access token
     * @param array $user account facebook information
     *
     */
    private function _authorization_succeed($id, $token, $user)
    {
        $this->session->set_userdata([
            'oauth_facebook_id' => $user['id'],
            'oauth_facebook_email' => $user['email'],
            'oauth_facebook_access_token' => $token
        ]);

        //
        $redirect_url = $this->session->userdata('oauth_redirect_page');

        redirect($redirect_url);
    }

}