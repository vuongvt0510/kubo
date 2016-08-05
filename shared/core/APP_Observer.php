<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * オブサーバークラス
 *
 * モデルの登録/更新/削除を監視して、処理完了後の処理を行う定義することができる
 *
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
abstract class APP_Observer extends CI_Model
{
    /**
     * 実行
     *
     * オブザーバーはすべての実行した結果が異常だったとしても無視する
     * エラー通知は行う
     *
     * @access public
     * @param string $method
     * @param object $record
     * @param array $options
     * @return mixed
     */
    public function invoke($method, $record, $options = array())
    {
        $name = get_class($this);


        try {
            log_message("INFO", "observer `{$name}` {$method} start.");
            $this->{$method}($record, $options);
            log_message("INFO", "observer `{$name}` {$method} end.");
        } catch (Exception $e) {
            if (!$e instanceof APP_Exception) {
                log_exception('ERROR', $e);
            }

            if (isset($this->_error_notifier)) {
                $this->_error_notifier->send_exception($e);
            }
        }
    }

    /**
     * @param object $record
     * @param array $options
     * @return mixed
     */
    abstract protected function after_create($record, $options = array());

    /**
     * @param object $record
     * @param array $options
     * @return mixed
     */
    abstract protected function after_update($record, $options = array());

    /**
     * @param object $record
     * @param array $options
     * @return mixed
     */
    abstract protected function after_destroy($record, $options = array());
}

