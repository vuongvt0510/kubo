<?php

require_once "Constraint/Api.php";
require_once "Constraint/Api/IsSuccess.php";
require_once "Constraint/Api/IsError.php";
require_once "Constraint/Api/IsSubmitError.php";
require_once "Constraint/Api/HasInvalidField.php";


/**
 * APIのテストケース追加
 */
class CIUnit_ApiTestCase extends CIUnit_ModelTestCase
{
    public $apis = array();


    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        foreach ($this->apis as $a) {
            if (!file_exists($file = TESTSPATH . "/builders/api/{$a}_builder.php")) {
                continue;
            }

            require_once $file;
            $builder_instance = strtolower($a."_builder");
            $builder_class = ucfirst($a."_builder");
            $this->{$builder_instance} = new $builder_class($this);
        }
    }

    /**
     * Set Up
     *
     * APIの自動読み込み
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        foreach ($this->apis as $a) {
            // TODO: 階層構造に対応させる
            $this->CI->load->library("API/" . $a);
            $this->{strtolower($a)} =& $this->CI->{strtolower($a)};
        }
    }

    public static function assertApiSuccess($response, $message = '')
    {
        self::assertThat($response, new CIUnit_Api_Constraint_IsSuccess, $message);
    }

    public static function assertApiError($response, $errno = NULL, $errmsg = NULL, $message = '')
    {
        self::assertThat($response, new CIUnit_Api_Constraint_IsError($errno, $errmsg), $message);
    }

    public static function assertApiSubmitError($response, $message = '')
    {
        self::assertThat($response, new CIUnit_Api_Constraint_IsSubmitError, $message);
    }

    public static function assertApiHasInvalidField($field, $rule = NULL, $response, $message = '')
    {
        self::assertThat($response, new CIUnit_Api_Constraint_HasInvalidField($field, $rule), $message);
    }
}

