<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH.'controllers/modules/APP_Twitter_authenticatable.php';
require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Twitter authenticatable controller
 *
 */
class Twitter extends Application_controller
{
    use APP_Twitter_authenticatable;

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

        $twitter = [];
        // Load config file
        $files = array(
            SHAREDPATH . "config/twitter.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/twitter.php",
            APPPATH . "config/twitter.php",
            APPPATH . "config/" . ENVIRONMENT . "/twitter.php"
        );

        foreach ($files as $f) {
            if (is_file($f)) {
                include $f;
            }
        }

        /** @var array $facebook_config */
        $twitter_config = $twitter;

        foreach($twitter_config as $key => $val) {
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
     * Process function when authorization twitter is successful
     *
     * @param string $id of twitter user
     * @param string $token of twitter
     * @param string $secret of twitter
     * @param array $credentials of twitter
     *
     */
    private function _authorization_succeed($id = '', $token = '', $secret = '', $credentials = null)
    {

        $this->session->set_userdata([
            'oauth_twitter_id' => $id,
            'oauth_twitter_email' => isset($credentials['email']) ? $credentials['email'] : '',
            'oauth_twitter_access_token' => $token
        ]);

        $redirect_url = $this->session->userdata('oauth_redirect_page');

        redirect($redirect_url);
    }

}