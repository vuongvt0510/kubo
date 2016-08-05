<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__) . "/APP_Model_exception.php";
require_once dirname(__FILE__) . "/APP_Model_operator.php";
require_once dirname(__FILE__) . "/APP_Model_errors.php";
require_once dirname(__FILE__) . "/APP_Model_interface.php";
require_once dirname(__FILE__) . "/APP_Record.php";
require_once dirname(__FILE__) . "/APP_DB_schema.php";


/**
 * モデル
 *
 * 派生クラスでテーブル名を指定することで、そのテーブルに対する基本的なCRUDメソッドを提供する
 *
 * @method APP_Model where(Array $params, Array $options = [])
 * @method APP_Model select(String $statement, Array $options = [])
 * @method APP_Model where_in(Array $params, Array $options = [])
 * @method APP_Model group_by(String $statement, Array $options = [])
 * @method APP_Model order_by(String $statement, String $order, Array $options = [])
 * @method APP_Model join(String $statement, String $condition, Array $options = [])
 * @method APP_Model transaction(Callable $callback)
 * @method APP_Model offset(Int $number, Array $options = [])
 * @method APP_Model limit(Int $number, Array $options = [])
 * @method APP_Model close()
 * @method APP_Model like(String $name, String $value)
 *
 * @method APP_Model trans_begin_without_savepoint()
 * @method APP_Model trans_rollback_all_savepoint()
 * @method APP_Model trans_rollback_to_latest_savepoint()
 * @method APP_Model trans_complete($bool = TRUE)
 * @method APP_Model trans_commit_to_latest_savepoint()
 * @method APP_Model trans_start($bool = TRUE)
 * @method APP_Model calc_found_rows()
 *
 * @method APP_Model set(String $name, Mixed $value, Bool $bool)
 *
 * @property object config

 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 * @uses APP_DB_active_record
 * @uses APP_DB_schema
 * @uses APP_Record
 * @see CI_Model
 */
class APP_Model extends CI_Model implements APP_Model_interface
{

    const DB_ERROR_MESSAGE = "データベースでエラーが発生しました。";

    /**
     * モデルの操作者
     * @static
     * @ver object
     */
    static $operator = NULL;

    /**
     * モデルの操作者テーブル
     * @static
     * @ver string
     */
    static $operator_table_name = "users";

    /**
     * スキーマ
     */
    static $schema = NULL;

    /**
     * マスタデータベースへのインスタンス名 (name of master's instance)
     * @var string
     */
    public $master_name = 'db';

    /**
     * スレーブデータベースのインスタンス名 (name of slave's instance)
     * @var string
     */
    public $slave_name = 'dbs';

    /**
     * マスターデータベースのインスタンス
     * @var CI_DB
     */
    public $master = NULL;

    /**
     * スレーブデータベースのインスタンス
     * @var CI_DB
     */
    public $slave = NULL;

    /**
     * データベース名 (database name)
     * @var string
     */ 
    public $database_name = NULL;

    /**
     * テーブル名
     * @var string
     */
    public $table_name = NULL;

    /**
     * プライマリキー名
     * @var string
     */
    public $primary_key = 'id';

    /**
     * レコードオブジェクトの型
     * @var string
     */
    public $record_class = "object";

    /**
     * テーブルのフィールド一覧
     * @var array
     */
    public $fields = NULL;

    /**
     * 外部キー設定
     * @var array
     */
    public $foreign_keys = array();

    /**
     * エラー一覧
     * @var object
     */
    public $errors = NULL;

    /**
     * 作成日時カラム名
     * @var string
     */
    public $created_at_column_name = NULL;

    /**
     * 更新日時カラム名
     * @var string
     */
    public $updated_at_column_name = NULL;

    /**
     * 作成者名カラム名
     * @var string
     */
    public $created_by_column_name = NULL;

    /**
     * 更新者名カラム名
     * @var string
     */
    public $updated_by_column_name = NULL;

    /**
     * オブザーバー
     * @var array
     */
    public $observers = array();

    /**
     * 作成日時カラム検索用正規表現
     * @access protected
     * @var string
     */
    protected $created_at_column_name_regex = "/^([a-z]+_)?created_at$/";

    /**
     * 更新日時カラム検索用正規表現
     * @access protected
     * @var string
     */
    protected $updated_at_column_name_regex = "/^([a-z]+_)?updated_at$/";

    /**
     * 作成者カラム検索用正規表現
     * @access protected
     * @var string
     */
    protected $created_by_column_name_regex = "/^([a-z]+_)?created_by$/";

    /**
     * 更新者カラム検索用正規表現
     * @access protected
     * @var string
     */
    protected $updated_by_column_name_regex = "/^([a-z]+_)?updated_by$/";

    /**
     * データベースインスタンスへ委譲するメソッド一覧
     * こちらに指定すると戻り値として自身のオブジェクトを返すようになる
     * @access protected
     * @var array
     */
    protected $delegate_method_chains = array(
        'select',
        'select_max',
        'select_sum',
        'where',
        'or_where',
        'where_in',
        'where_not_in',
        'or_where_not_in',
        'like',
        'or_like',
        'not_like',
        'or_not_like',
        'distinct',
        'having',
        'or_having',
        'join',
        'group_by',
        'order_by',
        'order_by_rand',
        'order_by_field',
        'offset',
        'limit',
        'calc_found_rows',
        'for_update',
        'set'
    );

    /**
     * データベースインスタンスへ委譲するメソッド一覧
     * こちらに指定すると戻り値としてDBインスタンスへ委譲したメソッドの戻り値を返すようになる
     * @access protected
     * @var array
     */
    protected $delegate_methods = array(
        'escape_like_str',
        'escape_str'
    );

    /**
     * マスターデータベースインスタンスへ委譲するメソッド一覧
     * こちらに指定すると戻り値としてDBインスタンスへ委譲したメソッドの戻り値を返すようになる
     * @access protected
     * @var array
     */
    protected $delegate_methods_to_master = array(
        'trans_start',
        'trans_complete',
        'trans_status',
        'trans_begin',
        'trans_rollback',
        'trans_force_rollback',
        'trans_commit',
        'trans_reset_status',
        'insert_id',

        'transaction',
        'trans_begin_with_savepoint',
        'trans_begin_without_savepoint',
        'trans_rollback_to_latest_savepoint',
        'trans_rollback_all_savepoint',
        'trans_commit_to_latest_savepoint',
        'trans_commit_all_savepoint'
    );

    public function __construct()
    {
        parent::__construct();

        $CI =& get_instance();

        if (!isset($CI->{$this->master_name})) {
            throw new APP_Model_exception('instance' . $this->master_name . ' is uninialized.', 9000);
        }

        if (!isset($CI->{$this->slave_name})) {
            throw new APP_Model_exception('instance ' . $this->slave_name . ' is uninialized.', 9000);
        }

        $this->master =& $CI->{$this->master_name};
        $this->slave  =& $CI->{$this->slave_name};

        foreach ($this->observers as $o) {
            $CI->load->model($o . "_observer");
        }

        $this->config->load('model_settings', TRUE, TRUE);

        $keys = array(
            'created_at_column_name',
            'updated_at_column_name',
            'created_by_column_name',
            'updated_by_column_name'
        );

        foreach ($keys as $key) {
            if (FALSE !== ($value = $this->config->item($key, 'model_settings'))) {
                $key = $key . "_regex";
                $this->{$key} = $value;
            }
        }

        if ( ! empty($this->table_name)) {
            $this->errors = new APP_Model_errors($this);

            if ( ! isset($this->fields)) {
                $this->slave->change_database($this->database_name);

                // 開発モード以外はスキーマダンプからスキーマ情報を取得する
                if (ENVIRONMENT !== 'development' && ENVIRONMENT !== 'auto_test') {
                    if (!isset(self::$schema)) {
                        self::$schema = new APP_DB_schema();
                    }
                    $this->fields = self::$schema->list_fields($this->database_name, $this->table_name);
                } else {
                    $this->fields = $this->slave->list_fields($this->table_name);
                }

                foreach ($this->fields as $field) {
                    if (preg_match($this->created_at_column_name_regex, $field)) {
                        $this->created_at_column_name = $field;
                    }
                    if (preg_match($this->updated_at_column_name_regex, $field)) {
                        $this->updated_at_column_name = $field;
                    }
                    if (preg_match($this->created_by_column_name_regex, $field)) {
                        $this->created_by_column_name = $field;
                    }
                    if (preg_match($this->updated_by_column_name_regex, $field)) {
                        $this->updated_by_column_name = $field;
                    }
                }
            }
        }
    }

    public function __call($name, $arguments)
    {
        // master参照系のメソッドが呼ばれた場合は、masterインスタンスに処理を委譲する
        if (in_array($name, $this->delegate_methods_to_master)) {
            return call_user_func_array(array($this->master, $name), $arguments);
        }

        // slave参照系のメソッドが呼ばれた場合は、masterインスタンスに処理を委譲する
        // TODO: 戻り値を確認してチェイン用のメソッドなのか判断するほうがシンプルか。。。
        if (in_array($name, $this->delegate_methods)) {
            return call_user_func_array(array($this->slave, $name), $arguments);
        }

        if (in_array($name, $this->delegate_method_chains)) {
            call_user_func_array(array($this->slave, $name), $arguments);
            return $this;
        }

        throw new Exception("undefined method " . $name);
    }

    /**
     * 操作者取得
     *
     * @access public
     * @return object
     */
    public static function operator()
    {
        if (empty(APP_Model::$operator)) {
            APP_Model::set_operator(new APP_Model_operator);
        }

        return APP_Model::$operator;
    }

    /**
     * 操作者設定
     *
     * @access public
     *
     * @param APP_Operator $object
     *
     * @return APP_Operator
     */
    public static function set_operator($object)
    {
        if (! $object instanceof APP_Operator) {
            throw new InvalidArgumentException("operator didn't have APP_Operator.");
        }

        return APP_Model::$operator = $object;
    }

    /**
     * レコード取得
     * 指定したプライマリキーのレコードを取得する
     *
     * @access public
     * @return object
     *
     * @throws APP_DB_exception
     * @throws APP_Model_exception
     *
     * @internal param string $id
     * @internal param array $options
     */
    public function find(/* polymorphic */)
    {
        $this->errors->clear();

        list($args, $options) = $this->_parse_find_args(func_get_args());

        $this->_set_condisions_by_primary_key($args);

        $this->_join_scopes(isset($options['with']) ? $options['with'] : NULL, $options);
        $this->_set_conditions($options);

        $instance = $this->_select_instance($options);
        $instance->change_database($this->database_name);

        $query = $instance->get($this->table_name, 1);
        if ($query === FALSE) {
            $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
            return FALSE;
        }

        if ($query->num_rows() > 0) {
            $result = $query->row(0, isset($options['record_class']) ? $options['record_class'] : $this->record_class);
            $query->free_result();
            return $result;
        } else {
            return array();
        }
    }

    /**
     * レコード取得
     * 条件に一致した最初のレコードを取得する。
     * 
     * @access public
     * @param array $where
     * @param array $options
     * @return array
     */
    public function find_by($where, $options = array())
    {
        return $this->where($where)->first($options);
    }

    /**
     * レコード取得
     * 条件に一致した最初のレコードを取得する。
     * 
     * @access public
     * @param array $options
     * @return array
     */
    public function first($options = array())
    {
        $this->limit(1);
        $result = $this->all($options);
        if (FALSE === $result) {
            return FALSE;
        }

        return count($result) > 0 ? $result[0] : array();
    }

    /**
     * レコード全件取得
     * 条件に一致したレコードを全件取得する。
     * 
     * @access public
     * @param array $options
     * @return array
     */
    public function all($options = array())
    {
        $this->errors->clear();

        $this->_join_scopes(isset($options['with']) ? $options['with'] : NULL, $options);
        $this->_set_conditions($options);

        $instance = $this->_select_instance($options);
        $instance->change_database($this->database_name);

        $query = $instance->get($this->table_name);
        if ($query === FALSE) {
            $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
            return FALSE;
        }

        $result = $query->result(isset($options['record_class']) ? $options['record_class'] : $this->record_class);
        $query->free_result();
        return $result;
    }

    /**
     * レコード件数取得
     * 条件に一致したレコードを全件取得する。
     *
     * @access public
     * @param array $options レコード取得条件 APP_Model#allと同じ
     * @return int 取得件数
     */
    public function count_rows($options = array())
    {
        $this->errors->clear();

        $this->_set_conditions($options);

        $instance = $this->_select_instance($options);
        $instance->change_database($this->database_name);

        $result = $instance->count_all_results($this->table_name);
        if ($result === FALSE) {
            $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
            return FALSE;
        }

        return $result;
    }

    /**
     * レコード件数取得
     *
     * 条件に一致したレコードを全件取得する。
     * SQL_CALC_FOUND_ROWSを設定した検索後に取得できる。
     *
     * @access public
     * @param array $options
     * @return int 取得件数
     */
    public function found_rows($options = array())
    {
        return $this->_select_instance($options)->found_rows;
    }

    /**
     * レコード作成
     * 指定された値のレコードを作成する
     *
     * @access public
     *
     * @param array $attributes 作成するレコードのパラメータ
     * @param array $options
     *
     * @return bool 作成結果
     *
     * @throws APP_DB_exception
     * @throws Exception
     */
    public function create($attributes, $options = array())
    {
        $this->errors->clear();

        if (!array_key_exists('skip_auto_timestamp', $options) || $options['skip_auto_timestamp'] !== TRUE) {
            $this->_set_timestamp($attributes, TRUE);
        }

        // TODO: バリデーション処理の追加
        $query = $this->master->insert_string($this->table_name, $attributes);
        
        switch (array_value_or_default('mode', $options, 'normal')) {
        case 'ignore':
            $query = preg_replace('/^INSERT /', 'INSERT IGNORE ', $query);
            $skip_affected_rows = TRUE;
            break;
        case 'replace':
            $update_attributes = array();
            foreach ($attributes as $key => $value) {
                if (preg_match("/(created_by|created_at)$/", $key)) continue;
                $update_attributes[$key] = $value;
            }

            $query       = $query . " ON DUPLICATE KEY UPDATE ";
            $bind_query  = implode(', ', array_map(function($c) { return "`$c` = ?"; }, array_keys($update_attributes)));
            $query      .= $this->master->compile_binds($bind_query, array_values($update_attributes));
            $skip_affected_rows = TRUE;
            break;
        default:
            $skip_affected_rows = FALSE;
            break;
        }

        $this->master->change_database($this->database_name);

        try {
            $this->trans_begin_without_savepoint();

            if (FALSE === $this->master->query($query)) {
                $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
                $this->trans_rollback_to_latest_savepoint();
                return FALSE;
            }

            if ( ! $skip_affected_rows && $this->master->affected_rows() <= 0) {
                $this->trans_rollback_to_latest_savepoint();
                return FALSE;
            }

            $primary_key = NULL;

            if (is_array($this->primary_key)) {
                // 複合プライマリキーの場合
                $primary_key = array();

                foreach ($this->primary_key as $pk) {
                    $primary_key[] = @$attributes[$pk];
                }
            } else {
                // 単一プライマリキーの場合
                if (array_key_exists($this->primary_key, $attributes)) {
                    $primary_key = $attributes[$this->primary_key];
                } else {
                    $primary_key = $this->master->insert_id();
                }
            }

            if (array_key_exists('return', $options) && $options['return'] === TRUE) {

                $find_args = is_array($primary_key) ? $primary_key : array($primary_key);
                $find_args[] = array("master" => TRUE);

                $result = call_user_func_array(array($this, "find"), $find_args);
            } else {
                $result = $primary_key;
            }

            if ((!array_key_exists('skip_callback', $options) || $options['skip_callback'] !== TRUE) && method_exists($this, "after_create")) {
                if (FALSE === $this->after_create($result, $options)) {
                    $this->trans_rollback_to_latest_savepoint();
                    return FALSE;
                }
            }

            $this->trans_commit_to_latest_savepoint();

        } catch (Exception $e) {
            $this->trans_rollback_to_latest_savepoint();
            throw $e;
        }

        if ((!array_key_exists('skip_observer', $options) || $options['skip_observer'] !== TRUE) && !empty($this->observers)) {
            foreach ($this->observers as $o) {
                $this->{$o . "_observer"}->invoke('after_create', find_record($this, $result), $options);
            }
        }

        return $result;
    }

    /**
     * 複数レコード作成
     * 指定された配列のレコードをまとめて作成する
     *
     * @access public
     *
     * @param array $array 作成するレコードのパラメータの配列
     * @param array $options オプション
     *
     * @return bool
     * @throws APP_DB_exception
     */
    public function bulk_create($array, $options = array())
    {
        $this->errors->clear();

        $this->master->change_database($this->database_name);

        if (!array_key_exists('skip_auto_timestamp', $options) || $options['skip_auto_timestamp'] !== TRUE) {
            foreach ($array as & $attributes) {
                $this->_set_timestamp($attributes, TRUE);
            }
        }

        return $this->master->insert_batch($this->table_name, $array);
    }

    /**
     * レコード更新
     * 指定されたIDのレコードを更新する
     *
     * @access public
     * @return bool 更新結果
     *
     * @throws APP_DB_exception
     * @throws APP_Model_exception
     * @throws Exception
     *
     * @internal param int $id レコードのプライマリID
     * @internal param array $attributes 更新するレコードのパラメータ
     */
    public function update(/* polymorphic */)
    {
        $this->errors->clear();

        list($args, $attributes, $options) = $this->_parse_update_args(func_get_args());

        if (empty($attributes) && empty($this->slave->get_property('qb_set'))) {
            return 0;
        }

        // TODO: バリデーション処理の追加
        if (!array_key_exists('skip_auto_timestamp', $options) || $options['skip_auto_timestamp'] !== TRUE) {
            $this->_set_timestamp($attributes);
        }

        $this->_set_condisions_by_primary_key($args);

        $this->_delegate_write_queue($this->slave, $this->master);

        $this->master->change_database($this->database_name);

        try {
            $this->trans_begin_without_savepoint();

            if (FALSE === $this->master->update($this->table_name, $attributes, NULL)) {
                $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
                $this->trans_rollback_to_latest_savepoint();
                return FALSE;
            }

            $affected_rows = $this->master->affected_rows();

            if (array_key_exists('return', $options) && $options['return'] === TRUE) {
                $result = $this->_set_condisions_by_primary_key($args)->first(array('master' => TRUE));
            } else {
                $result = count($args) > 1 ? $args : $args[0];
            }

            if ((!array_key_exists('skip_callback', $options) || $options['skip_callback'] !== TRUE) && method_exists($this, "after_update")) {
                if (FALSE === $this->after_update($result, $options)) {
                    $this->trans_rollback_to_latest_savepoint();
                    return FALSE;
                }
            }

            $this->trans_commit_to_latest_savepoint();

        } catch (Exception $e) {
            $this->trans_rollback_to_latest_savepoint();
            throw $e;
        }

        if ((!array_key_exists('skip_observer', $options) || $options['skip_observer'] !== TRUE) && !empty($this->observers)) {
            $r = $this->_set_condisions_by_primary_key($args)->first(array('master' => TRUE));
            foreach ($this->observers as $o) {
                $this->{$o . "_observer"}->invoke('after_update', $r, $options);
            }
        }

        if (array_key_exists('return', $options) && $options['return'] === TRUE) {
            return $result;
        } else {
            return $affected_rows;
        }
    }

    /**
     * レコード全体更新
     *
     * 指定された条件に一致するレコード全件を更新する
     *
     * @access public
     *
     * @param array $attributes 更新するレコードのパラメータ
     * @param array $options
     *
     * @return bool 更新結果
     * @throws APP_DB_exception
     */
    public function update_all($attributes = array(), $options = array())
    {
        $this->errors->clear();

        if (!array_key_exists('skip_auto_timestamp', $options) || $options['skip_auto_timestamp'] !== TRUE) {
            $this->_set_timestamp($attributes);
        }

        // TODO: $options['return'] が TRUE の時にオブジェクトを返すようにする
        $this->_delegate_write_queue($this->slave, $this->master);

        $this->master->change_database($this->database_name);

        if (FALSE === $this->master->update($this->table_name, $attributes, NULL)) {
            $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
            return FALSE;
        }

        $affected_rows = $this->master->affected_rows();

        return $affected_rows;
    }

    /**
     * レコード削除
     * 指定されたIDのレコードを削除する
     *
     * @access public
     * @return bool 削除結果
     *
     * @throws APP_Model_exception
     * @throws Exception
     *
     * @internal param int $id レコードのプライマリキー
     */
    public function destroy(/* polymorphic */)
    {
        $this->errors->clear();

        list($args, $options) = $this->_parse_destroy_args(func_get_args());

        $target = NULL;
        if ((!array_key_exists('skip_observer', $options) || $options['skip_observer'] !== TRUE) && !empty($this->observers)) {
            $target = $this->_set_condisions_by_primary_key($args)->first(array('master' => TRUE));
        }

        try {
            $this->trans_begin_without_savepoint();

            if ((!array_key_exists('skip_callback', $options) || $options['skip_callback'] !== TRUE) && method_exists($this, "before_destroy")) {
                if (FALSE === $this->before_destroy(count($args) > 1 ? $args : $args[0], $options)) {
                    $this->trans_rollback_to_latest_savepoint();
                    return FALSE;
                }
            }

            $this->_set_condisions_by_primary_key($args);
            $this->_delegate_write_queue($this->slave, $this->master);

            $this->master->change_database($this->database_name);
            $result = $this->master->delete($this->table_name);
            if ($result === FALSE) {
                $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
                $this->trans_rollback_to_latest_savepoint();
                return FALSE;
            }

            $affected_rows = $this->master->affected_rows();

            $this->trans_commit_to_latest_savepoint();

        } catch (Exception $e) {
            $this->trans_rollback_to_latest_savepoint();
            throw $e;
        }

        if ((!array_key_exists('skip_observer', $options) || $options['skip_observer'] !== TRUE)) {
            foreach ($this->observers as $o) {
                $this->{$o . "_observer"}->invoke('after_destroy', $target, $options);
            }
        }

        return $affected_rows;
    }

    /**
     * レコード全体削除
     * 指定された条件に一致するレコード全件を削除する
     *
     * @access public
     *
     * @param array $options
     *
     * @return bool 削除結果
     * @throws APP_DB_exception
     */
    public function destroy_all($options = array())
    {
        $this->errors->clear();

        $this->_delegate_write_queue($this->slave, $this->master);
        $this->master->change_database($this->database_name);
        $result = $this->master->delete($this->table_name);
        if ($result === FALSE) {
            $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
            return FALSE;
        }

        $affected_rows = $this->master->affected_rows();

        return $affected_rows;
    }

    /**
     * レコード全件削除
     * 
     * @access public
     * @return bool
     */
    public function empty_table()
    {
        $this->errors->clear();

        $this->_delegate_write_queue($this->slave, $this->master);
        $this->master->change_database($this->database_name);
        $result = $this->master->empty_table($this->table_name);
        if ($result === FALSE) {
            $this->errors->add_to_base(self::DB_ERROR_MESSAGE);
            return FALSE;
        }

        return $result;
    }

    /**
     * カウンタインクリメント
     * 指定されたIDのカウンタをインクリメントする
     *
     * @access public
     * @return bool 更新結果
     *
     * @throws APP_Model_exception
     *
     * @internal param int $id レコードのプライマリID
     * @internal param array $column_names インクリメントするカラム名
     */
    public function increment(/* polymorphic */)
    {
        list($args, $column_names, $options) = $this->_parse_update_args(func_get_args(), array("validate_attributes" => FALSE));

        if (!is_array($column_names)) $column_names = array($column_names);

        foreach ($column_names as $name) {
            $this->set($name, "{$name} + 1", FALSE);
        }

        $args[] = array();
        $args[] = $options;

        return call_user_func_array(array($this, "update"), $args);
    }

    /**
     * カウンタデクリメント
     * 指定されたIDのカウンタをデクリメントする
     *
     * @access public
     * @return bool 更新結果
     *
     * @throws APP_Model_exception
     *
     * @internal param int $id レコードのプライマリID
     * @internal param array $column_names デクリメントするカラム名
     */
    public function decrement(/* polymorphic */)
    {
        list($args, $column_names, $options) = $this->_parse_update_args(func_get_args(), array("validate_attributes" => FALSE));

        if (!is_array($column_names)) $column_names = array($column_names);

        foreach ($column_names as $name) {
            $this->set($name, "{$name} - 1", FALSE);
        }

        $args[] = array();
        $args[] = $options;

        return call_user_func_array(array($this, "update"), $args);
    }

    /**
     * カラム名にテーブル名を付与する
     *
     * @access public
     * @param string $name カラム名
     * @return string
     */
    public function column_name($name)
    {
        return $this->table_name . "." . $name;
    }

    /**
     * トランザクション開始
     *
     * @param bool $test_mode
     *
     * @return mixed
     */
    public function trans_begin($test_mode = FALSE)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        trigger_error(sprintf("trans_begin() is depricated. Please check '%s':%s and use trans_start().", $trace[0]["file"], $trace[0]["line"]), E_USER_DEPRECATED);
        return $this->trans_start($test_mode);
    }

    /**
     * トランザクション中止
     *
     * @return mixed
     */
    public function trans_rollback()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        trigger_error(sprintf("trans_rollback() is depricated. Please check '%s':%s and use trans_complete(FALSE).", $trace[0]["file"], $trace[0]["line"]), E_USER_DEPRECATED);
        return $this->trans_complete(FALSE);
    }

    /**
     * トランザクションコミット
     *
     * @return mixed
     */
    public function trans_commit()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        trigger_error(sprintf("trans_commit() is depricated. Please check '%s':%s and use trans_complete().", $trace[0]["file"], $trace[0]["line"]), E_USER_DEPRECATED);
        return $this->trans_complete();
    }

    /**
     * エラー番号取得
     *
     * @access public
     *
     * @param array $options
     *
     * @return mixed
     */
    public function error_number($options = array())
    {
        return $this->_select_instance($options)->error_number();
    }

    /**
     * エラーメッセージ取得
     *
     * @access public
     *
     * @param array $options
     *
     * @return mixed
     */
    public function error_message($options = array())
    {
        return $this->_select_instance($options)->error_message();
    }

    /**
     * インスタンス取得
     *
     * 検索時のインスタンスを取得する
     *
     * @access protected
     * @param array $options
     * @return CI_DB
     */
    protected function _select_instance($options = array())
    {
        $instance = "slave";

        // 強制的にslaveDBを見てもエラーにならないようにオプションが設定されているかどうかしか見ない。
        if ($this->master->is_transaction() && ! array_key_exists('master', $options)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

            // デバッグトレースを走査してどこでこのエラーが起きているのかわかりやすく表示させる
            $file = $trace[1]["file"];
            $line = $trace[1]["line"];
            foreach ($trace as $t) {
                if (!preg_match("/APP_Model/", $t["file"])) {
                    $file = $t["file"];
                    $line = $t["line"];
                    break;
                }
            }

            log_message("WARN", $message = sprintf("connection is changed to master compulsorily at %s:%s.", $file, $line));
            trigger_error($message, E_USER_WARNING);
            $options['master'] = TRUE;
        }

        if (array_value_or_default('master', $options, FALSE)) {
            $instance = "master";
            $this->_delegate_select_queue($this->slave, $this->master);
        }

        // スレーブDBが選択されている状態でFOR UPDATEオプションが付与されるのはおかしいので強制的にFOR UPDATEを取り除く
        if ($instance === "slave" && $this->{$instance}->qb_for_update) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

            // デバッグトレースを走査してどこでこのエラーが起きているのかわかりやすく表示させる
            $file = $trace[1]["file"];
            $line = $trace[1]["line"];
            foreach ($trace as $t) {
                if (!preg_match("/APP_Model/", $t["file"])) {
                    $file = $t["file"];
                    $line = $t["line"];
                    break;
                }
            }

            log_message("WARN", $message = sprintf("for_update options is removed compulsorily. if use for_update, change connection to master. for_update is used at %s:%s", $file, $line));
            trigger_error($message, E_USER_WARNING);
            $this->{$instance}->qb_for_update = FALSE;
        }

        return $this->{$instance};
    }

    /**
     * プライマリキーによる条件を追加
     *
     * @access protected
     * @param array $args プライマリキーの値のリスト
     * @return APP_Model
     */
    protected function _set_condisions_by_primary_key($args)
    {
        $where = array();

        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);
        for ($i = 0; $i < count($primary_keys); $i++) {
            $value = $args[$i];
            if (is_object($value) || is_array($value)) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                new APP_Model_exception(sprintf("%s::%s Argument #%d (%s) can't set object or array.", get_class($this), $trace[0]["function"], $i + 1, $primary_keys[$i]));
            }

            $where[$this->column_name($primary_keys[$i])] = $value;
        }

        return $this->where($where);
    }

    /**
     * findの引数を分解
     *
     * @access protected
     *
     * @param array $args findの引数
     *
     * @return array プライマリキー・オプション情報
     * @throws APP_Model_exception
     */
    protected function _parse_find_args($args)
    {
        $num = count($args);

        // オプションを抽出
        if ($num > 0 && is_hash($options = $args[$num - 1], TRUE)) {
            $options = array_pop($args);
            $num = count($args);
        } else {
            $options = array();
        }

        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);
        if ($num < count($primary_keys)) {
            $c = get_class($this);
            $primary_keys = array_slice($primary_keys, count($primary_keys) - $num - 1);
            throw new APP_Model_exception("Missing argument " . implode(", ", $primary_keys) . " for {$c}::find()");
        }

        if ($num > count($primary_keys)) {
            $c = get_class($this);
            throw new APP_Model_exception("Argument list too long for {$c}::find(). Argument is " . implode(", ", $primary_keys) . " and options");
        }

        return array($args, $options);
    }

    /**
     * updateの引数を分解
     *
     * @access protected
     *
     * @param array $args updateの引数
     * @param array $validates
     *
     * @return array プライマリキー・更新内容・オプション情報
     * @throws APP_Model_exception
     */
    protected function _parse_update_args($args, $validates = array())
    {
        $validates = array_merge(array(
            "validate_attributes" => TRUE
        ), $validates);

        $num = count($args);

        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);

        if ($num > count($primary_keys) + 2) {
            $c = get_class($this);
            throw new APP_Model_exception("Argument list too long for {$c}::update(). Argument is " . implode(", ", $primary_keys) . ", attributes and options");
        }

        if ($num < count($primary_keys) + 1) {
            $c = get_class($this);
            throw new APP_Model_exception("Missing argument " . implode(", ", $primary_keys) . " for {$c}::update()");
        }

        // オプションを取得
        $options = array();
        if ($num > count($primary_keys) + 1) {
            $options = array_pop($args);
        }

        // 更新内容を取得
        $attributes = array_pop($args);
        if ($validates["validate_attributes"]) {
            if (!is_object($attributes) && !is_array($attributes)) {
                throw new APP_Model_exception(sprintf('%s::update() Argument #%d ($attributes) can\'t set object or array.', get_class($this), count($primary_keys) + 1));
            }
        }

        return array($args, $attributes, $options);
    }

    /**
     * destroyの引数を分解
     *
     * @access public
     *
     * @param array $args destroyの引数
     *
     * @return array プライマリキー・オプション情報
     * @throws APP_Model_exception
     */
    protected function _parse_destroy_args($args)
    {
        $num = count($args);

        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);

        if ($num > count($primary_keys) + 1) {
            $c = get_class($this);
            throw new APP_Model_exception("Argument list too long for {$c}::destroy(). Argument is " . implode(", ", $primary_keys) . ", attributes and options");
        }

        if ($num < count($primary_keys)) {
            $c = get_class($this);
            throw new APP_Model_exception("Missing argument " . implode(", ", $primary_keys) . " for {$c}::destroy()");
        }

        // オプションを取得
        $options = array();
        if ($num > count($primary_keys)) {
            $options = array_pop($args);
        }

        return array($args, $options);
    }


    /**
     * スコープを追加する
     *
     * @access protected
     *
     * @params array $joins
     * @params array $options
     * @param $joins
     * @param array $options
     *
     * @return object
     */
    protected function _join_scopes($joins, $options = array())
    {
        if (empty($joins)) return;

        if ( ! is_array($joins)) $joins = array($joins);

        foreach ($joins as $join) {
            call_user_func(array($this, 'with_' . $join));
        }
    }

    /**
     * 検索や更新条件を設定する
     *
     * @access protected
     * @param array $options
     * @return void
     */
    protected function _set_conditions($options)
    {
        $conditions = array('select', 'where', 'order_by', 'group_by', 'limit', 'offset', 'calc_found_rows');
        foreach ($conditions as $c) {
            if (array_key_exists($c, $options)) {
                switch ($c) {
                case 'select':
                case 'where':
                    if (is_array($options[$c]) && array_values($options[$c]) === $options[$c]) {
                        call_user_func_array(array($this, $c), $options[$c]);
                    } else {
                        call_user_func(array($this, $c), $options[$c]);
                    }
                    break;
                case 'offset':
                case 'calc_found_rows':
                    call_user_func(array($this, $c), $options[$c]);
                    break;
                case 'limit':
                case 'order_by':
                case 'group_by':
                    if (is_array($options[$c])) {
                        call_user_func_array(array($this, $c), $options[$c]);
                    } else {
                        call_user_func(array($this, $c), $options[$c]);
                    }
                    break;
                }
            }
        }
    }

    /**
     * データベースインスタンス委譲
     *
     * @access protected
     * @param CI_DB $src
     * @param CI_DB $dest
     * @return void
     */
    protected function _delegate_select_queue(& $src, & $dest)
    {
        if ($src === $dest) return;

        foreach ($this->_delegate_select_queue_variables as $variable) {
            $dest->set_property($variable, $src->get_property($variable));
        }
        $src->reset_select();
    }

    protected $_delegate_select_queue_variables = array(
            'qb_select', 'qb_from', 'qb_join', 'qb_where', /*'qb_like',*/ 'qb_groupby', 'qb_having',
            'qb_orderby', /*'qb_wherein',*/ 'qb_aliased_tables', 'qb_no_escape', 'qb_distinct', 'qb_limit',
            'qb_offset', /*'qb_order',*/ 'qb_calc_found_rows', 'qb_for_update', 'qb_keys', 'qb_set',
            'qb_where_group_count', 'qb_no_escape'
    );

    /**
     * データベースインスタンス委譲
     *
     * @access protected
     * @param CI_DB $src
     * @param CI_DB $dest
     * @return void
     */
    protected function _delegate_write_queue(& $src, & $dest)
    {
        if ($src === $dest) return;

        foreach ($this->_delegate_write_queue_variables as $variable) {
            $dest->set_property($variable, $src->get_property($variable));
        }
        $src->reset_write();
    }

    protected $_delegate_write_queue_variables = array(
            'qb_set', 'qb_from', 'qb_where', 'qb_like', 'qb_orderby', 'qb_keys', 'qb_limit', 'qb_order'
    );

    /**
     * ユニークキーを生成する
     *
     * @access protected
     * @param int $length
     * @return string
     */
    protected function _generate_unique_key($column_name = 'access_key', $length = 32, $generator = 'generate_unique_key')
    {
        // 10回繰り返しても重複するなら諦める
        for ($i = 0; $i < 10; $i++) {
            $key = call_user_func($generator, $length);
            if ($this->where($column_name, $key)->count_rows(array('master' => TRUE, 'with_deleted' => TRUE)) <= 0) {
                return $key;
            }
        }
        return FALSE;
    }

    /**
     * 作成・更新時のタイムスタンプを設定する
     *
     * @access public
     * @param array $attributes
     * @param bool $created
     * @return void
     */
    protected function _set_timestamp(& $attributes, $created = FALSE)
    {
        $timestamp = business_date('Y-m-d H:i:s');

        // objectに対応
        $attributes = $this->_object_to_array($attributes);

        if ($created && isset($this->created_at_column_name) && ! array_key_exists($this->created_at_column_name, $attributes)) {
            $attributes[$this->created_at_column_name] = $timestamp;
        }

        if ($created && isset($this->created_by_column_name) && ! array_key_exists($this->created_by_column_name, $attributes)) {
            $attributes[$this->created_by_column_name] = self::operator()->_operator_identifier();
        }

        if (isset($this->updated_at_column_name) && ! array_key_exists($this->updated_at_column_name, $attributes)) {
            $attributes[$this->updated_at_column_name] = $timestamp;
        }

        if (isset($this->updated_by_column_name) && ! array_key_exists($this->updated_by_column_name, $attributes)) {
            $attributes[$this->updated_by_column_name] = self::operator()->_operator_identifier();
        }
    }

    /**
     * オブジェクトを配列に変換
     *
     * @param object
     * @return array
     */
    protected function _object_to_array($object)
    {
        if (!is_object($object)) {
            return $object;
        }

        $array = array();
        foreach (get_object_vars($object) as $key => $val) {
            if (!is_object($val) && !is_array($val) && $key != '_parent_name') {
                $array[$key] = $val;
            }
        }

        return $array;
    }

    /**
     * テーブルカラムに関係がないパラメータを取り除く
     *
     * @access public
     *
     * @param $attributes
     *
     * @return array
     */
    protected function _extract_extra_attributes(& $attributes)
    {
        $extra = array();

        $keys = array_diff(array_keys($attributes), $this->fields);

        foreach ($keys as $k) {
            $extra[$k] = $attributes[$k];
            unset($attributes[$k]);
        }

        return $extra;
    }

}
