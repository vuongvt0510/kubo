<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 例外クラス
 *
 * 自動的にログに出力し、必要に応じて通知も行われる
 *
 * @author Yoshikazu Ozawa
 */
class APP_Exception extends Exception {

    /**
     * ログレベル
     *
     * @var string
     */
    protected $log_level = "error";

    /**
     * 通知するかしないか
     *
     * @var bool
     */
    protected $notified = FALSE;

    /**
     * 通知クラス
     *
     * @var string
     */
    protected $notifier = 'default';


    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->logging();

        if ($this->notified) {
            $this->notify();
        }
    }

    /**
     * ログレベルを返す
     *
     * @access public
     * @return string
     */
    public function log_level()
    {
        return $this->log_level;
    }

    /**
     * ログを出力する
     *
     * @access protected
     *
     * @param array $options
     */
    protected function logging($options = array())
    {
        $message = sprintf("Throw exception '%s' (%d) with message '%s' in %s:%d",
            get_class($this),
            $this->getCode(),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine());

        log_message($this->log_level, $message);
    }

    /**
     * 通知する
     *
     * @access protected
     *
     * @param array $options
     */
    protected function notify($options = array())
    {
        if ($this->notifier === 'default') {
            $CI =& get_instance();
            if (isset($CI->_error_notifier)) {
                $this->notifier =& $CI->_error_notifier;
            } else {
                $this->notifier = NULL;
            }
        }

        if (isset($this->notifier)) {
            $this->notifier->send_exception($this);
        }
    }
}

