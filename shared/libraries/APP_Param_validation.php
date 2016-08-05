<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('APP_Form_validation')) {
    require_once dirname(__FILE__) . "/APP_Form_validation.php";
}

/**
 * パラメータ検証クラス
 *
 * @method void require_login()
 *
 * $_REQUESTの内容を検証するためのクラス
 *
 */
class APP_Param_validation extends APP_Form_validation {

    public function __construct($rules = array())
    {
        parent::__construct($rules);
        $this->params =& $_REQUEST;
    }
}

