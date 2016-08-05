<?php

if ( ! class_exists('CI_Session')) {
    require  BASEPATH . "/libraries/Session/Session.php";
}

/**
 * セッションクラス
 *
 * IE対応のためにcookieに書き込むタイミングを調整している
 * この調整を行うために描画とリダイレクトのタイミングでのみflash_cookieをしているので注意すること
 */
class APP_Session extends CI_Session
{
    /**
     * 自動ログイン期間
     * @const
     */
    const MAX_EXPIRATION = 63072000; // 2年

    /**
     * 自動ログイン用キー名
     * @const
     */
    const AUTOLOAD = '__auto';

    /**
     * 全セッションクラスのインスタンス
     * @var array
     */
    static protected $instances = array();

    /**
     * クッキー情報
     * @var array
     */
    protected $cookie_data = array();

    /**
     * クッキー HTTP only属性
     * @var bool
     */
    public $cookie_http_only = FALSE;

    public $sess_default_expiration = 7200;
    public $sess_default_expire_on_close = FALSE;

    public function __construct($params = array())
    {
        $this->CI = get_instance();

        self::$instances[] = $this;

        $this->sess_default_expiration = (isset($params['sess_expiration'])) ? $params['sess_expiration'] : $this->CI->config->item('sess_expiration');

        $this->sess_default_expire_on_close = (isset($params['sess_expire_on_close'])) ? $params['sess_expire_on_close'] : $this->CI->config->item('sess_expire_on_close');

        foreach (array('cookie_http_only') as $key) {
            $this->{$key} = (isset($params[$key])) ? $params[$key] : $this->CI->config->item($key);
        }

        parent::__construct($params);
    }

    /**
     * Cookie情報を書き込む
     * 
     * @access public
     * @return void
     */
    static public function flash_cookie_all()
    {
        foreach (self::$instances as & $i) {
            $i->flash_cookie();
        }
    }

    /**
     * 自動ログインを許可する
     *
     * @access public
     * @param bool $enable
     * @return bool
     */
    public function auto_login($enable = NULL)
    {
        $set_userdata = TRUE;

        // 指定されていない場合はCookieの情報から受け取る
        if (is_null($enable)) {
            $enable = $this->userdata(self::AUTOLOAD);
            $set_userdata = FALSE;
        }

        if ($enable) {
            log_message('debug', "session [{$this->sess_cookie_name}] enable auto login.");
            $this->sess_expiration = self::MAX_EXPIRATION;
            $this->sess_expire_on_close = FALSE;
        } else {
            $this->sess_expiration = $this->sess_default_expiration;
            $this->sess_expire_on_close = $this->sess_default_expire_on_close;
        }

        if ($set_userdata) {
            $this->set_userdata(self::AUTOLOAD, $enable);
        }

        return $enable;
    }

    /**
     * Cookie情報を書き込む
     *
     * @access public
     * @return void
     */
    public function flash_cookie()
    {
        if (empty($this->cookie_data)) {
            log_message('debug', 'flash cookie, but cookie data is empty.');
            return;
        }

        log_message('debug', 'flash cookie is ' . json_encode($this->cookie_data));

        setcookie(
            $this->cookie_data['name'],
            $this->cookie_data['data'],
            $this->cookie_data['expire'],
            $this->cookie_data['path'],
            $this->cookie_data['domain'],
            $this->cookie_data['secure'],
            $this->cookie_data['http_only']
        );
    }

    /**
     * セッション読み込み
     * セッションを読み込んだ際に自動ログインがONの場合は、セッションの有効期限の延長、
     * ブラウザが閉じた場合のセッションの消去を無効にする
     *
     * @access public
     * @return bool
     */
    public function sess_read()
    {
        if (FALSE === parent::sess_read()) {
            return FALSE;
        }

        $this->auto_login();

        return TRUE;
    }

    /**
     * @param string $cookie_data
     */
    public function _set_cookie($cookie_data = NULL)
    {
        if (is_null($cookie_data)) {
            $cookie_data = $this->userdata;
        }

        // Serialize the userdata for the cookie
        $cookie_data = $this->_serialize($cookie_data);

        if ($this->sess_encrypt_cookie == TRUE) {
            $cookie_data = $this->CI->encrypt->encode($cookie_data);
        } else {
            // if encryption is not used, we provide an md5 hash to prevent userside tampering
            $cookie_data = $cookie_data . md5($cookie_data . $this->encryption_key);
        }

        $expire = ($this->sess_expire_on_close === TRUE) ? 0 : $this->sess_expiration + time();

        // Set the cookie
        $this->cookie_data = array(
            'name' => $this->sess_cookie_name,
            'data' => $cookie_data,
            'expire' => $expire,
            'path' => $this->cookie_path,
            'domain' => $this->cookie_domain,
            'secure' => $this->cookie_secure,
            'http_only' => $this->cookie_http_only
        );
    }
}

