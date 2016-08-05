<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 操作者用インターフェイス
 *
 * 各種モデルのレコードとして使えるインターフェイス
 *
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
interface APP_Operator
{
    /**
     * 未ログインユーザーかどうか
     *
     * @access public
     * @return bool
     */
    public function is_anonymous();

    /**
     * ログインしているかどうか
     *
     * @access public
     * @return bool
     */
    public function is_login();

    /**
     * 管理者かどうか
     *
     * @access public
     * @return bool
     */
    public function is_administrator();

    /**
     * 管理権限があるかどうか
     *
     * @param $permission
     * @return mixed
     */
    public function has_permission($permission);

    /**
     * 操作者IDを返す
     *
     * @access public
     * @return mixed
     */
    public function _operator_id();

    /**
     * 操作者名を返す
     *
     * @access public
     * @return string
     */
    public function _operator_name();

    /**
     * 操作者識別子を返す
     *
     * @access public
     * @return string
     */
    public function _operator_identifier();
}


/**
 * デフォルト操作者クラス
 *
 * 未ログインしていない操作者として提供しているクラス
 *
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Anonymous_operator implements APP_Operator
{

    /**
     * @return bool
     */
    public function is_anonymous() { return TRUE; }

    /**
     * @return bool
     */
    public function is_login() { return FALSE; }

    /**
     * @return bool
     */
    public function is_administrator() { return FALSE; }

    /**
     * @param string $permission
     * @return bool
     */
    public function has_permission($permission) { return FALSE; }

    /**
     * @return null
     */
    public function _operator_id() { return NULL; }

    /**
     * @return string
     */
    public function _operator_name() { return "anonymous"; }

    /**
     * @return string
     */
    public function _operator_identifier() { return "anonymous"; }
}

