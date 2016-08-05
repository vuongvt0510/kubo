<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Cookie反映クラス
 *
 * IEの場合、set_cookieを乱発するとcookieが反映されない場合があるため、
 * 処理の最後にset_cookieを行うフックを追加
 *
 * @author Yoshikazu Ozawa
 */
class APP_Cookie_manager
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * クッキー情報の書き込み処理
     *
     * 処理したクッキーを出力する
     *
     * @access public
     * @return void
     */
    public function flash()
    {
        if (class_exists('APP_Session')) {
            APP_Session::flash_cookie_all();
        }
    }
}

