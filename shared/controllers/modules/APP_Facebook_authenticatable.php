<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . '/controllers/modules/APP_OAuth_authenticatable.php';
require_once SHAREDPATH . '/third_party/facebook-php-sdk-v4/autoload.php';

/**
 * Facebook認証用モジュール
 *
 * Facebook認証制御を入れ込んでくれるモジュール
 *
 * @method void _redirect(String $url)
 *
 * @package APP\Controller
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interset-marketing.net>
 */
trait APP_Facebook_authenticatable
{

    use APP_OAuth_authenticatable;

    /**
     * @var string
     */
    private $facebook_app_id = '';

    /**
     * @var string
     */
    private $facebook_app_secret = '';

    /**
     * @var string
     */
    private $facebook_default_graph = '';

    /**
     * @var array
     */
    private $facebook_permission = [];

    /**
     * @var object
     */
    private $facebook = null;

    /**
     * Init facebook helper
     *
     * @return void
     */
    private function init_facebook() {

        if($this->facebook !== NULL) {
            return;
        }

        $this->facebook = new \Facebook\Facebook([
            'app_id' => $this->facebook_app_id,
            'app_secret' => $this->facebook_app_secret,
            'default_graph_version' => $this->facebook_default_graph
        ]);
    }

    /**
     * Facebook認証
     *
     * @access public
     * @return void
     */
    public function authorize()
    {
        $this->init_facebook();

        $helper = $this->facebook->getRedirectLoginHelper();

        $url = $helper->getLoginUrl($this->_generate_authorization_callback_url(), $this->facebook_permission);

        $this->_redirect($url);
    }

    /**
     * Facebook認証コールバック
     *
     * @access public
     * @return void
     */
    public function authorization_callback()
    {
        if (!method_exists($this, '_authorization_denied')) {
            throw new LogicException(get_class($this).'::_authorization_denied() is not found.');
        }

        if (!method_exists($this, '_authorization_succeed')) {
            throw new LogicException(get_class($this).'::_authorization_succeed() is not found.');
        }

        $this->init_facebook();

        $helper = $this->facebook->getRedirectLoginHelper();

        try {

            $access_token = $helper->getAccessToken();

            if(!$access_token) {
                if ($helper->getError()) {
                    throw new Exception($helper->getError());
                } else {
                    throw new Exception('Bad request');
                }
                return;
            }

        } catch(Exception $e) {
            log_exception('ERROR', $e);
            $this->_authorization_denied($e);
            return;
        }

        try {
            // TODO: 有効期限を取得する方法を検討する
            // $session->validate();

            $response = $this->facebook->get('/me?fields=id,name,email', $access_token);

            $user = $response->getGraphUser();

            $this->_authorization_succeed(
                $user['id'],
                $access_token->getValue(),
                $user
            );

        } catch (Exception $e) {
            log_exception('ERROR', $e);
            $this->_authorization_denied($e);
            return;
        }
    }
}
