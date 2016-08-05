<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__) . '/APP_Exception.php';

/**
 * DB例外クラス
 *
 * @package Antenna
 * @subpackage Model
 * @author Yoshikazu Ozawa
 */
class APP_DB_exception extends APP_Exception {

    /**
     * @var mixed|null
     */
    protected $last_query = NULL;

    /**
     * @param null $db
     * @param bool $message
     * @param bool $code
     */
    public function __construct(& $db, $message = FALSE, $code = FALSE)
    {
        $this->db =& $db;

        if (empty($message)) $message = $db->error_message();
        if (empty($code)) $code = $db->error_number();

        $this->last_query = $this->db->error_query();
        if (!empty($this->last_query)) {
            $this->last_query = preg_replace("/\r?\n|\r/", " ", $this->last_query);
        }

        parent::__construct($message, $code);
    }

    /**
     * @return mixed
     */
    public function last_query()
    {
        return $this->last_query();
    }

    /**
     * @param array $options
     */
    protected function logging($options = array())
    {
        $message = sprintf("Throw exception '%s' (%d) with message `%s`",
            get_class($this),
            $this->getCode(),
            $this->getMessage()
        );

        if (!empty($this->last_query)) {
            $message .= sprintf(" query << %s >>", $this->last_query);
        }

        $message .= sprintf(" in %s:%d", $this->getFile(), $this->getLine());

        log_message($this->log_level, $message);
    }
}

/**
 * DB例外クラス - コネクションエラー
 *
 * @author Yoshikazu Ozawa
 */
class APP_DB_exception_connection_error extends APP_DB_exception {
}

/**
 * DB例外クラス - 重複キーエラー
 *
 * @author Yoshikazu Ozawa
 */
class APP_DB_exception_duplicate_key_entry extends APP_DB_exception {
}

/**
 * DB例外クラス - デッドロック
 *
 * @author Yoshikazu Ozawa
 */
class APP_DB_exception_dead_lock extends APP_DB_exception {
    /**
     * @var string
     */
    public $log_level = 'warn';
}

/**
 * DB例外クラス - デッドロックリトライオーバー
 *
 * @author Yoshikazu Ozawa
 */
class APP_DB_exception_dead_lock_retry_over extends APP_DB_exception {

    /**
     * @param null $db
     * @param bool $count
     * @param bool $previous
     */
    public function __construct(& $db, $count, $previous)
    {
        $message = "deadlock retry count over (count:{$count}). ";
        $message .= $previous->getMessage();
        $code = $previous->getCode();

        parent::__construct($db, $message, $code);
    }
}

/**
 * DB例外クラス - ロック待ちタイムアウト
 *
 * @author Yoshikazu Ozawa
 */
class APP_DB_exception_lock_wait_timeout extends APP_DB_exception {
}

/**
 * DB例外クラス - トランザクションロールバック
 *
 * @author Yoshikazu Ozawa
 */
class APP_DB_exception_transaction_rollback extends APP_DB_exception
{
    /**
     * @param null $db
     * @param bool $message
     * @param bool $code
     */
    public function __construct(& $db, $message = FALSE, $code = FALSE)
    {
        $this->db =& $db;

        if (empty($message)) $message = 'DB transaction callback was returned FALSE.';
        if (empty($code)) $code = 9999;

        parent::__construct($db, $message, $code);
    }
}

/**
 * DB例外クラス - トランザクション未完了
 *
 * @author Yoshikazu Ozawa
 */
class APP_DB_exception_transaction_incompleted extends APP_DB_exception {

    /**
     * @param null $db
     * @param bool $message
     * @param bool $code
     */
    public function __construct(& $db, $message = FALSE, $code = FALSE)
    {
        $message = sprintf("DB Transaction don't complete.");
        parent::__construct($db, $message, 0);

        $db->trans_force_rollback();
    }
}

