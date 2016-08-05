<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once BASEPATH . 'database/DB_driver.php';
require_once BASEPATH . 'database/DB_query_builder.php';

require_once dirname(__FILE__) . '/APP_DB_exception.php';

/**
 * ActiveRecord拡張モデル
 * MySQL専用の拡張モデルになっている
 *
 * @method Int _error_number()
 * @method String _error_message()
 * @method Bool db_select()
 *
 * @method Void trans_begin()
 *
 * @author Yoshikazu Ozawa
 */
class APP_DB_active_record extends CI_DB_query_builder {

    const SAVEPOINT_NAME_FORMAT = "CIPOINT%d";
    const NOUSE_SAVEPOINT_NAME = "NOUSE";

    /**
     * DBエラー時に例外を吐くかどうか
     * @var bool
     */
    var $db_exception = FALSE;

    /**
     * FOUND_ROWSの件数
     * @ver int
     */
    var $found_rows = 0;

    var $qb_calc_found_rows = FALSE;
    var $qb_for_update = FALSE;

    var $qb_like = array();
    var $qb_order = array();

    // TODO: MySQL専用になってしまっている
    static $error_exception_mappings = array(
        '1205' => 'APP_DB_exception_lock_wait_timeout',
        '1213' => 'APP_DB_exception_dead_lock',
        '1062' => 'APP_DB_exception_duplicate_key_entry'
    );

    protected $error_number = 0;
    protected $error_message = "";
    protected $error_query = "";

    protected $_trans_savepoints = array();


    /**
     * 生成した全てのインスタンスを保持する
     * @var array[APP_Model]
     */
    static public $instances = array();

    public function get_property($key)
    {
        return $this->{$key};
    }

    public function set_property($key, $value)
    {
        return $this->{$key} = $value;
    }

    /**
     * 全てのインスタンスのコネクションを切断する
     *
     * @access public
     *
     * @param bool $excaption
     *
     * @throws Exception
     */
    static public function close_all($excaption = FALSE)
    {
        /** @var APP_Model $i */
        foreach (self::$instances as & $i) {
            try {
                $i->close();
            } catch (Exception $e) {
                if ($excaption) throw $e;
            }
        }
    }

    /**
     * 全てのインスタンスのトランザクションをロールバックする
     *
     * @access public
     * @return void
     */
    static public function trans_rollback_all()
    {
        foreach (self::$instances as & $i) {
            $i->trans_rollback_all_savepoint();
        }
    }

    /**
     * コンストラクタ
     *
     * @access public
     *
     * @param array $params
     */
    public function __construct($params)
    {
        // 本番環境ではデフォルトでSQLをキャッシュしない
        if ('production' === ENVIRONMENT) {
            $params = array_merge(array('save_queries' => FALSE), $params);
        }

        parent::__construct($params);
        self::$instances[] =& $this;
    }

    /**
     * データベース初期化
     *
     * @access public
     * @throws APP_DB_exception_connection_error
     */
    public function initialize()
    {
        if (FALSE === parent::initialize()) {
            if ($this->db_exception) {
                $this->error_query = NULL;
                $this->error_number = 0;
                $this->error_message = "Unable to connect to the database `{$this->database}`";
                throw new APP_DB_exception_connection_error($this);
            }
        }

        return TRUE;
    }

    /**
     * データベース変更
     *
     * @access public
     *
     * @param string $database
     *
     * @return mixed
     * @throws APP_DB_exception
     * @throws APP_DB_exception_connection_error
     */
    public function change_database($database = NULL)
    {
        if ( ! isset($database)) { 
            return TRUE;
        }

        if ($this->database == $database) {
            return TRUE;
        }

        if ( ! $this->conn_id)
        {
            $this->initialize();
        }

        log_message('debug', 'Change database to ' . $database . ' from ' . $this->database);
        $this->database = $database;

        if (FALSE === $this->db_select()) {
            $error = $this->error();
            $this->error_number = $error['code'];
            $this->error_message = $error['message'];

            if ($this->db_exception && ! $this->db_debug) {
                if (array_key_exists((string)$this->error_number, self::$error_exception_mappings)) {
                    $class = self::$error_exception_mappings[(string)$this->error_number];
                    throw new $class($this);
                } else {
                    throw new APP_DB_exception($this);
                }
            }

            return FALSE;
        }

        return TRUE;
    }

    /**
     * 検索
     *
     * CALC_FOUND_ROWSオプションが指定されている場合、総件数も合わせて取得する
     * 
     * @access public
     * @param string $table
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function get($table = '', $limit = null, $offset = null)
    {
        $calc = $this->qb_calc_found_rows;

        // from句にテーブルを指定しないクエリがあるための暫定対応
        // from句が指定されている場合は無視テーブル指定をする
        if ( ! empty($this->qb_from)) $table = '';

        $query = parent::get($table, $limit, $offset);
        if ($query == FALSE) {
            return FALSE;
        }

        if ($calc) {
            $this->found_rows = $this->found_rows();
        }

        return $query;
    }

    /**
     * クエリ実行
     *
     * @access public
     *
     * @param string $sql
     * @param array|bool $binds
     * @param bool $return_object
     *
     * @return mixed
     * @throws APP_DB_exception
     * @throws Exception
     */
    public function query($sql, $binds = FALSE, $return_object = TRUE)
    {
        // 例外機能をONにしていた場合に、分析用のデータの整合性が合わない可能性があるので、
        // それを補填する。
        try {
            $result = parent::query($sql, $binds, $return_object);
        } catch (APP_DB_exception $e) {
            if ($this->save_queries == TRUE && count($this->queries) != count($this->query_times)) {
                $this->query_times[] = 0;
            }
            throw $e;
        }

        return $result;
    }

    /**
     * クエリ実行
     *
     * @access public
     *
     * @param string $sql
     *
     * @return mixed
     * @throws APP_DB_exception
     */
    public function simple_query($sql)
    {
        $this->error_number = $this->error_message = $this->error_query = FALSE;

        list($sm, $ss) = explode(' ', microtime());

        $result = parent::simple_query($sql);

        list($em, $es) = explode(' ', microtime());

        log_message('debug', sprintf("[SQL][%s] %f %s", $this->database, ($em + $es) - ($sm + $ss),
            str_replace(array("\r\n", "\n", "\r"), " ", $sql)));

        if (FALSE === $result) {
            $error = $this->error();

            $this->error_number = $error['code'];
            $this->error_message = $error['message'];
            $this->error_query = $sql;

            $this->_reset_select();
            $this->_reset_write();

            if ($this->db_exception && ! $this->db_debug) {
                if (array_key_exists((string)$this->error_number, self::$error_exception_mappings)) {
                    $class = self::$error_exception_mappings[(string)$this->error_number];
                    throw new $class($this);
                } else {
                    throw new APP_DB_exception($this);
                }
            }
        }

        return $result;
    }

    /**
     * SELECT句生成
     * 重複しているSELECT句は無視する
     *
     * @access public
     * @param mixed
     * @param bool
     * @return object
     */
    public function select($select = '*', $escape = NULL)
    {
        if (is_string($select)) {
            $select = explode(',', $select);
        }

        $unique = array();
        foreach ($select as $val) {
            if ( ! in_array($val, $this->qb_select)) {
                $unique[] = $val;
            }
        }

        return parent::select($unique, $escape);
    }

    /**
     * WHERE句生成
     *
     * @access public
     *
     * @param $key
     * @param null $value
     * @param bool $escape
     *
     * @return object
     */
    public function where($key, $value = NULL, $escape = TRUE)
    {
        if (is_string($key) && is_array($value)) {
            return $this->_bind_where($key, $value, ' AND ');
        } else {
            return parent::where($key, $value, $escape);
        }
    }

    /**
     * WHERE句生成
     *
     * @access public
     *
     * @param $key
     * @param null $value
     * @param bool $escape
     *
     * @return object
     */
    public function or_where($key, $value = NULL, $escape = TRUE)
    {
        if (is_string($key) && is_array($value)) {
            return $this->_bind_where($key, $value, ' OR ');
        } else {
            return parent::or_where($key, $value, $escape);
        }
    }

    /**
     * CALC_FOUND_ROWSオプション追加
     *
     * SELECT句にCALC_FOUND_ROWSを追加する
     *
     * @access public
     * @param    bool
     * @return object
     */
    public function calc_found_rows($val = TRUE)
    {
        $this->qb_calc_found_rows = (is_bool($val)) ? $val : TRUE;
        return $this;
    }

    /**
     * FOR UPDATE オプション追加
     *
     * SELECT句にFOR UPDATEを追加する
     *
     * @access public
     * @param bool
     * @return object
     */
    public function for_update($val = TRUE)
    {
        $this->qb_for_update = (is_bool($val)) ? $val : TRUE;
        return $this;
    }

    /**
     * CALC_FOUND_ROWSオプション結果取得
     *
     * CALC_FOUND_ROWSを追加したSELECTのFOUND_ROWSを取得する
     *
     * @return int
     */
    public function found_rows()
    {
        $result = $this->query("SELECT FOUND_ROWS() as frows");
        if ($result->num_rows() == 1) {
            return (int) $result->row()->frows;
        } else {
            return FALSE;
        }
    }

    /**
     * FIELD関数によるORDER BY句生成
     *
     * @access public
     * @param mixed
     * @param bool
     * @return object
     */
    public function order_by_field($column, $ids)
    {
        $q = array_map(function($i){ return "?"; }, $ids);
        $query = implode(", ", $q);
        $query = $this->compile_binds($query, $ids);

        $this->qb_orderby[] = sprintf("FIELD(%s, %s)", $this->protect_identifiers($column), $query);
        return $this;
    }

    /**
     * RAND関数によるORDER BY句生成
     *
     * @access public
     * @param mixed
     * @param bool
     * @return object
     */
    public function order_by_rand()
    {
        $this->qb_orderby[] = "RAND()";
        return $this;
    }

    /**
     * トランザクション開始
     *
     * @access public
     * @param bool $test_mode
     * @return bool
     */
    public function trans_start($test_mode = FALSE)
    {
        if ( ! $this->trans_enabled) return FALSE;

        if (ENVIRONMENT !== 'production') {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            trigger_error(sprintf("trans_start() is depricated. Please check '%s':%s and use transaction().", $trace[0]["file"], $trace[0]["line"]), E_USER_DEPRECATED);
        }

        if (count($this->_trans_savepoints) > 0) {
            $this->_trans_savepoints[] = self::NOUSE_SAVEPOINT_NAME;
            log_message('debug', 'Increment DB Transaction Counter (counter:' . count($this->_trans_savepoints) . ')');
            return TRUE;
        }

        log_message('debug', 'DB Transaction Begin');
        $this->trans_begin($test_mode);
        $this->_trans_savepoints[] = self::NOUSE_SAVEPOINT_NAME;
    }

    /**
     * トランザクション完了
     *
     * トランザクションを完了させる
     *
     * @access public
     * @param bool $success
     * @return bool
     */
    public function trans_complete($success = TRUE)
    {
        if ( ! $this->trans_enabled) return FALSE;

        if (ENVIRONMENT !== 'production') {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            trigger_error(sprintf("trans_start() is depricated. Please check '%s':%s and use transaction().", $trace[0]["file"], $trace[0]["line"]), E_USER_DEPRECATED);
        }

        if ( ! $success && $this->_trans_status) $this->_trans_status = FALSE;

        if (count($this->_trans_savepoints) <= 0) {
            //$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            //trigger_error(sprintf("DB transaction don't start. You should call trans_start(). Please check '%s':%s.", $trace[0]["file"], $trace[0]["line"]), E_USER_ERROR);
            return FALSE;
        }

        array_pop($this->_trans_savepoints);

        if (count($this->_trans_savepoints) > 0) {
            log_message('debug', 'Decrement DB Transaction Counter (counter:' . count($this->_trans_savepoints) . ')');
            return TRUE;
        }

        if ($this->_trans_status === FALSE) {
            $this->trans_rollback();

            log_message('debug', 'DB Transaction Rollback');
            return FALSE;
        }

        $this->trans_commit();
        log_message('debug', 'DB Transaction Commit');

        return TRUE;
    }

    /**
     * トランザクション強制ロールバック
     *
     * トランザクションを強制的にロールバックさせる
     *
     * @access public
     * @return bool
     */
    public function trans_force_rollback()
    {
        $time = count($this->_trans_savepoints);
        for ($i = 0; $i < $time; $i++) {
            $this->trans_complete(FALSE);
        }
        return TRUE;
    }

    /**
     * トランザクションステータスをリセットする
     *
     * @access public
     * @return bool
     */
    public function trans_reset_status()
    {
        if (count($this->_trans_savepoints) > 0) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            trigger_error(sprintf("DB transaction status can't reset. You should call trans_force_rollback() or trans_complete(FALSE). Please check '%s':%s.", $trace[0]["file"], $trace[0]["line"]), E_USER_ERROR);
            return;
        }

        $this->_trans_status = TRUE;
    }

    /**
     * トランザクション
     *
     * セーブポイントやデッドロックリトライに対応
     *
     * @access public
     *
     * @param callable $callback
     * @param array $options
     *
     * @return bool
     * @throws APP_DB_exception_dead_lock_retry_over
     * @throws Exception
     */
    public function transaction($callback, $options = array())
    {
        $options = array_merge(array(
            'rollback' => 'latest',
            'deadlock_retry' => 0
        ), $options);

        $retry = 0;

        while (TRUE) {
            try {
                $this->trans_begin_with_savepoint();

                $result = $callback();

                if ($result === FALSE) {
                    throw new APP_DB_exception_transaction_rollback($this);
                }

                $this->trans_commit_to_latest_savepoint();

                return $result;

            } catch (Exception $e) {
                // デッドロックリトライ
                if ($e instanceof APP_DB_exception_dead_lock) {
                    if (isset($options['deadlock_retry']) && $options['deadlock_retry'] > 0 && $retry++ <= $options['deadlock_retry']) {
                        $this->trans_rollback_to_latest_savepoint();
                        continue;
                    }

                    $e = new APP_DB_exception_dead_lock_retry_over($this, $options['deadlock_retry'], $e);
                }

                if (isset($options['rollback']) && $options['rollback'] === 'latest') {
                    $this->trans_rollback_to_latest_savepoint();
                } else {
                    $this->trans_rollback_all_savepoint();
                }

                // $this->_trans_status = FALSE;

                throw $e;
            }
        }

        return TRUE;
    }

    /**
     * トランザクションを開始しセーブポイントを設定する
     *
     * 既にトランザクションが開始されている場合は、セーブポイントのみを設定する
     *
     * @access public
     * @return bool
     */
    public function trans_begin_with_savepoint()
    {
        $savepoint = $this->_generate_savepoint();

        if (count($this->_trans_savepoints) > 0) {
            $this->simple_query("SAVEPOINT {$savepoint}");
        } else {
            $this->simple_query('SET AUTOCOMMIT=0');
            $this->simple_query('START TRANSACTION');
            $this->simple_query("SAVEPOINT {$savepoint}");
        }

        $this->_trans_savepoints[] = $savepoint;

        return TRUE;
    }

    /**
     * トランザクションを開始する
     *
     * @access public
     * @return bool
     */
    public function trans_begin_without_savepoint()
    {
        //$savepoint = self::NOUSE_SAVEPOINT_NAME;

        if (count($this->_trans_savepoints) <= 0) {
            $this->simple_query('SET AUTOCOMMIT=0');
            $this->simple_query('START TRANSACTION');
        }

        $this->_trans_savepoints[] = self::NOUSE_SAVEPOINT_NAME;

        return TRUE;
    }

    /**
     * 直前に設定したセーブポイントまでロールバックする
     *
     * @access public
     * @return bool
     */
    public function trans_rollback_to_latest_savepoint()
    {
        if (count($this->_trans_savepoints) <= 0) {
            // $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            // trigger_error(sprintf("DB transaction has not started. You should call trans_begin_with_savepoint() before trans_rallback_to_latest_savepoint(). Please check '%s':%s.", $trace[0]["file"], $trace[0]["line"]), E_USER_ERROR);
            return TRUE;
        }

        $savepoint = array_pop($this->_trans_savepoints);

        if (count($this->_trans_savepoints) <= 0) {
            $this->simple_query("ROLLBACK");
            $this->simple_query("SET AUTOCOMMIT=1");
        } else {
            if ($savepoint !== self::NOUSE_SAVEPOINT_NAME) {
                $this->simple_query("ROLLBACK TO SAVEPOINT {$savepoint}");
                $this->simple_query("RELEASE SAVEPOINT {$savepoint}");
            }
        }

        return TRUE;
    }

    /**
     * 直前に設定したセーブポイントまでコミットする
     *
     * @access public
     * @return bool
     */
    public function trans_commit_to_latest_savepoint()
    {
        if (count($this->_trans_savepoints) <= 0) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            trigger_error(sprintf("DB transaction has not started. You should call trans_begin_with_savepoint() before trans_commit_to_latest_savepoint(). Please check '%s':%s.", $trace[0]["file"], $trace[0]["line"]), E_USER_ERROR);
            return TRUE;
        }

        array_pop($this->_trans_savepoints);

        if (count($this->_trans_savepoints) > 0) {
            // 何もしない
            // $this->simple_query("RELEASE SAVEPOINT {$savepoint}");
        } else {
            $this->simple_query("COMMIT");
            $this->simple_query("SET AUTOCOMMIT=1");
            $this->_trans_savepoints = array();
        }

        return TRUE;
    }

    /**
     * トランザクションをすべてコミットする
     *
     * @access public
     * @return bool
     */
    public function trans_commit_all_savepoint()
    {
        $this->simple_query("COMMIT");
        $this->simple_query("SET AUTOCOMMIT=1");
        $this->_trans_savepoints = array();
        return TRUE;
    }

    /**
     * トランザクションをすべてロールバックする
     *
     * @access public
     * @return bool
     */
    public function trans_rollback_all_savepoint()
    {
        $this->simple_query("ROLLBACK");
        $this->simple_query("SET AUTOCOMMIT=1");
        $this->_trans_savepoints = array();
        return TRUE;
    }

    /**
     * トランザクション中かどうか
     *
     * @access public
     * @return bool
     */
    public function is_transaction()
    {
        return count($this->_trans_savepoints) > 0 || $this->_trans_depth > 0;
    }

    /**
     * DBクローズ
     *
     * トランザクション中にDBをクローズしようとした場合に例外となる
     *
     * @access public
     * @throws APP_DB_exception_transaction_incompleted
     *
     * @return bool
     */
    public function close()
    {
        if (!(is_resource($this->conn_id) OR is_object($this->conn_id))) {
            return FALSE;
        }

        log_message("debug", "Close DB connection {$this->hostname}.");

        $rollback = FALSE;
        if (count($this->_trans_savepoints) > 0) {
            $rollback = TRUE;
            $this->trans_rollback_all_savepoint();
        }

        parent::close();

        if ($rollback) {
            trigger_error("DB transaction don't complete.", E_USER_ERROR);

            if ($this->db_exception) {
                throw new APP_DB_exception_transaction_incompleted($this);
            }
        }

        return TRUE;
    }

    /**
     * エラー番号取得
     *
     * クエリ実行失敗時のエラー番号を取得する。取得するエラー番号はドライバに依存する
     *
     * @access public
     * @return mixed
     */
    public function error_number()
    {
        return $this->error_number;
    }

    /**
     * エラーメッセージ取得
     *
     * クエリ実行失敗時のエラーメッセージを取得する。取得するエラーメッセージはドライバに依存する
     *
     * @access public
     * @return mixed
     */
    public function error_message()
    {
        if (empty($this->error_number) && empty($this->error_query)) {
            return $this->error_message;
        } else if (empty($this->error_query)) {
            return sprintf("ERROR %d: %s", $this->error_number, $this->error_message);
        } else {
            return sprintf("ERROR %d: %s on %s", $this->error_number, $this->error_message, $this->error_query);
        }
    }

    /**
     * エラー時の実行クエリ取得
     *
     * クエリ実行失敗時のクエリを取得する。
     *
     * @access public
     * @return mixed
     */
    public function error_query()
    {
        return $this->error_query;
    }

    /**
     * エラー表示
     *
     * ネストしているトランザクションが存在する場合はすべてロールバックしてエラー表示する
     *
     * @access public
     * @param string $error
     * @param string $swap
     * @param bool $native
     * @return void
     */
    public function display_error($error = '', $swap = '', $native = FALSE)
    {
        $this->trans_force_rollback();
        parent::display_error($error, $swap, $native);
    }

    /**
     * SELECT系クエリキュー削除
     *
     * @access public
     * @return void
     */
    public function reset_select()
    {
        $this->_reset_select();
    }

    /**
     * INSERT・UPDATE・DELETE系クエリキュー削除
     *
     * @access public
     * @return void
     */
    public function reset_write()
    {
        $this->_reset_write();
    }

    /**
     * WHERE句生成
     *
     * @access public
     *
     * @param string $sql
     * @param array|bool $binds
     * @param string $type
     *
     * @return $this
     */
    public function _bind_where($sql, $binds = FALSE, $type = ' AND ')
    {
        $prefix = (count($this->qb_where) == 0 AND count($this->qb_cache_where) == 0) ? '' : $type;
        $sql = "(" . $sql . ")";

        if ( ! empty($binds)) {
            $sql = $this->compile_binds($sql, $binds);
        }
        $this->qb_where[] = $prefix . $sql;

        if ($this->qb_caching === TRUE)
        {
            $this->qb_cache_where[] = $prefix . $sql;
            $this->qb_cache_exists[] = 'where';
        }

        return $this;
    }

    /**
     * Compile the SELECT statement
     *
     * Generates a query string based on which functions were used.
     * Should not be called directly.  The get() function calls it.
     *
     * @param bool $select_override
     *
     * @return string
     */
    protected function _compile_select($select_override = FALSE)
    {
        $sql = parent::_compile_select($select_override);

        if ($this->qb_calc_found_rows === TRUE) {
            $sql = preg_replace("/^SELECT /", "SELECT SQL_CALC_FOUND_ROWS ", $sql);
        }

        if ($this->qb_for_update === TRUE) {
            $sql = $sql . " FOR UPDATE";
        }

        return $sql;
    }

    /**
     * SELECT系クエリキュー削除
     *
     * SELECT文関係で利用したクエリのキューをリセットする。
     * 本クラスで拡張したCALC_FOUND_ROWSのオプションも合わせてリセットする。
     *
     * @return void
     */
    protected function _reset_select()
    {
        parent::_reset_select();

        $qb_reset_items = array(
            'qb_calc_found_rows' => FALSE,
            'qb_for_update' => FALSE
        );

        $this->_reset_run($qb_reset_items);
    }

    /**
     * セーブポイント名生成
     *
     * @access protected
     * @return string
     */
    protected function _generate_savepoint()
    {
        return sprintf(self::SAVEPOINT_NAME_FORMAT, count($this->_trans_savepoints) + 1);
    }
}

/**
 * Class CI_DB
 *
 * @method CI_DB affected_rows()
 * @method CI_DB insert_id()
 */
class CI_DB extends APP_DB_active_record {
}

