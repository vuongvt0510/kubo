<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "/controllers/modules/APP_OAuth_authenticatable.php";
require_once SHAREDPATH . "/third_party/tmhOAuth/tmhOAuth.php";


/**
 * Twitter認証用モジュール
 *
 * Twitter認証制御を入れ込んでくれるモジュール
 *
 * @method void _render_500(String $title, String $msg)
 * @method void _redirect(String $url)
 *
 * @package APP\Controller
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interset-marketing.net>
 */
trait APP_Twitter_authenticatable
{
    use APP_OAuth_authenticatable;

    /**
     * @var null
     */
    private $twitter_consumer_key = '';

    /**
     * @var null
     */
    private $twitter_consumer_secret = '';

    /**
     * Twitter認証
     *
     * @access public
     * @return void
     */
    public function authorize()
    {
        $twitter = new tmhOAuth(array(
            'consumer_key' => $this->twitter_consumer_key,
            'consumer_secret' => $this->twitter_consumer_secret
        ));

        $code = $twitter->apponly_request(array(
            'without_bearer' => TRUE,
            'method' => 'POST',
            'url' => $twitter->url('oauth/request_token', ''),
            'params' => array(
                'oauth_callback' => $this->_generate_authorization_callback_url()
            )
        ));

        if ($code != 200) {
            log_message("WARN", "Twitter communicate failed. {$twitter->response['response']}");
            $this->_render_500(
                'Twitter認証エラー',
                'Twitterへの接続が現在できません。しばらくしたら再度ご確認ください。'
            );
            return;
        }

        $result = $twitter->extract_params($twitter->response['response']);

        if ($result['oauth_callback_confirmed'] !== 'true') {
            log_message("WARN", "Twitter callback was not confirmed. {$twitter->response['response']}");
            $this->_render_500(
                'Twitter認証エラー',
                'Twitterへの接続が現在できません。しばらくしたら再度ご確認ください。'
            );
            return;
        }

        $this->_store_oauth_session('twitter', $result);

        $url = $twitter->url('oauth/authorize', '') . "?" . http_build_query(array('oauth_token' => $result['oauth_token']));

        $this->_redirect($url);
    }

    /**
     * Twitter認証コールバック
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

        try {

            if (FALSE != $this->input->get('denied')) {
                // ログインキャンセルしたなど失敗した場合の処理
     
                // セッションを破棄
                $this->_spend_oauth_session('twitter');

                throw new APP_Twitter_authenticatable_exception(
                    'twitter authorization failed. response is denied.',
                    APP_Twitter_authenticatable_exception::DENIED
                );
            }

            $oauth_token = $this->input->get('oauth_token');
            $oauth_verifier = $this->input->get('oauth_verifier');
            $session = $this->_spend_oauth_session('twitter');

            if (empty($oauth_token)) {
                throw new APP_Twitter_authenticatable_exception(
                    'twitter authorization failed. parameter `oauth_token` is not found.',
                    APP_Twitter_authenticatable_exception::INVALID_PARAMS
                );
            }

            if (empty($oauth_verifier)) {
                throw new APP_Twitter_authenticatable_exception(
                    'twitter authorization failed. parameter `oauth_verifier` is not found.',
                    APP_Twitter_authenticatable_exception::INVALID_PARAMS
                );
            }

            if (FALSE === $session) {
                throw new APP_Twitter_authenticatable_exception(
                    'twitter authorization failed. oauth session is not found.',
                    APP_Twitter_authenticatable_exception::INVALID_SESSION
                );
            }

            if ($oauth_token !== $session['oauth_token']) {
                throw new APP_Twitter_authenticatable_exception(
                    'twitter authorization failed. oauth token is not identical.',
                    APP_Twitter_authenticatable_exception::INVALID_TOKEN
                );
            }

            $twitter = new tmhOAuth(array(
                'consumer_key' => $this->twitter_consumer_key,
                'consumer_secret' => $this->twitter_consumer_secret,
                'token' => $session['oauth_token'],
                'secret' => $session['oauth_token_secret']
            ));

            $code = $twitter->user_request(array(
                'method' => 'POST',
                'url' => $twitter->url('oauth/access_token', ''),
                'params' => array(
                    'oauth_verifier' => trim($oauth_verifier)
                )
            ));

            if ($code !== 200) {
                throw new APP_Twitter_authenticatable_exception(
                    "twitter authorization failed. access_token request is failed. {$twitter->response['response']}",
                    APP_Twitter_authenticatable_exception::ACCESS_TOKEN_REQUEST_FAILED,
                    $twitter->response['response']
                );
            }

            $oauth_creds = $twitter->extract_params($twitter->response['response']);

            $twitter->reconfigure(array_merge($twitter->config, array(
                'token' => $oauth_creds['oauth_token'],
                'secret' => $oauth_creds['oauth_token_secret']
            )));

            $code = $twitter->user_request(array(
                'url' => $twitter->url('1.1/account/verify_credentials')
            ));

            if ($code !== 200) {
                throw new APP_Twitter_authenticatable_exception(
                    "twitter authorization failed. verify_credentials request is failed. {$twitter->response['response']}",
                    APP_Twitter_authenticatable_exception::ACCESS_TOKEN_REQUEST_FAILED,
                    $twitter->response['response']
                );
            }

            $verify_credentials = json_decode($twitter->response['response'], TRUE);

            return $this->_authorization_succeed(
                $verify_credentials['id_str'],
                $oauth_creds['oauth_token'],
                $oauth_creds['oauth_token_secret'],
                $verify_credentials
            );

        } catch (APP_Twitter_authenticatable_exception $e) {
            return $this->_authorization_denied($e);
        }
    }
}


/**
 * Twitter認証用モジュール例外クラス
 *
 * @package APP\Controller
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interset-marketing.net>
 */
class APP_Twitter_authenticatable_exception extends APP_Exception
{
    const DENIED = 1001;
    const INVALID_PARAMS = 2001;
    const INVALID_SESSION = 2002;
    const INVALID_TOKEN = 3001;
    const ACCESS_TOKEN_REQUEST_FAILED = 4001;
    const VERIFY_CREDENTIAL_REQUEST_FAILED = 5001;

    protected $log_level = 'warn';
    protected $response = NULL;

    public function __construct($message = null, $code = 0, $response = NULL)
    {
        $this->response = @json_decode($response);
        parent::__construct($message, $code);
    }
}

