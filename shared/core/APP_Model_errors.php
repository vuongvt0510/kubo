<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__) . "/APP_Errors.php";

/**
 * モデル用エラークラス
 *
 * @author Yoshikazu Ozawa
 * @see APP_Errors
 */
class APP_Model_errors extends APP_Errors {

    /**
     * @var null
     */
    var $base = NULL;

    /**
     * @param $model
     */
    public function __construct(& $model)
    {
        parent::__construct();
        $this->base =& $model;
    }

    /**
     * @param $column_name
     * @param $message
     *
     * @return bool
     */
    public function add($column_name, $message)
    {
        $args = array();
        if (func_num_args() > 2) {
            if ( ! is_array($args = func_get_arg(2))) {
                $args = array_slice(func_get_args(), 2);
            }
        }

        array_unshift($args, $this->translate("lang:" . $this->base->table_name . "." .  $column_name));

        $this->errors[$column_name][] = $this->translate($message, $args);
        return TRUE;
    }

}

