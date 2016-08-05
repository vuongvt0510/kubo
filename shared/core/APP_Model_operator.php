<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__) . "/APP_Operator.php";


/**
 * デフォルトDB操作者クラス
 *
 * デフォルトのデータベース操作者として提供しているクラス
 *
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Model_operator implements APP_Operator
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
    public function _operator_name() { return "system"; }

    /**
     * @return string
     */
    public function _operator_identifier() { return "system"; }
}
