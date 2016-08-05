<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('APP_Cli_controller')) {
    require_once dirname(__FILE__) . "/APP_Cli_controller.php";
}


/**
 * バッチ基底コントローラ
 *
 * @author Yoshikazu Ozawa
 */
class APP_Batch_controller extends APP_Cli_controller {

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '2048M');
    }

    /**
     * 指定された条件で全体を走査して指定された関数を実行する
     *
     * @access public
     *
     * @param mixed $model
     * @param mixed $primary_id
     * @param array $find_options
     * @param callable $callback
     * @param array $options
     *
     * @return mixed
     */
    protected function call_callback_to($model, $primary_id, $find_options, $callback, $options = array())
    {
        $find_options = $find_options ? $find_options : array();

        if ($primary_id === 'all') {

            $limit = 500;
            $offset = 0;

            while (TRUE) {
                $targets = $model->offset($offset)->limit($limit)->all($find_options);
                if (empty($targets)) {
                    return TRUE;
                }
                foreach ($targets as $t) {
                    $callback($t, $this);
                }
                $offset += $limit;
            }

            return TRUE;

        } else {
            $target = $model->find($primary_id, $find_options);
            if (empty($target)) {
                log_message('error', sprintf("%s(ID:%s) is not found.", get_class($model), $primary_id));
                return FALSE;
            }

            return $callback($target, $this, $options);
        }
    }

}

