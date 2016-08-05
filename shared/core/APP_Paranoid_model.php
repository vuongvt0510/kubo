<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('APP_Model')) {
    require_once dirname(__FILE__) . "/APP_Model.php";
}

require_once dirname(__FILE__) . "/APP_Paranoid_model_exception.php";


/**
 * 論理削除モデル
 *
 * 論理削除を行うテーブルに対して制御を入れ込んでくれるモデル
 *
 * @property object config
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interst-marketing.net>
 */
class APP_Paranoid_model extends APP_Model
{
    /**
     * 削除日時カラム名
     * @var string
     */
    public $deleted_at_column_name = NULL;

    /**
     * 削除者カラム名
     * @var string
     */
    public $deleted_by_column_name = NULL;

    /**
     * 削除フラグ
     * @var string
     */
    public $deleted_flag_column_name = NULL;

    /**
     * 削除フラグで制御するかどうか
     * @var bool
     */
    public $use_deleted_flag = FALSE;

    /**
     * 削除日時カラム検索用正規表現
     * @var bool
     */
    protected $deleted_at_column_name_regex = "/^([a-z]+_)?deleted_at$/";

    /**
     * 削除者カラム検索用正規表現
     * @var bool
     */
    protected $deleted_by_column_name_regex = "/^([a-z]+_)?deleted_by$/";

    /**
     * 削除フラグカラム検索用正規表現
     * @var bool
     */
    protected $deleted_flag_column_name_regex = "/^([a-z]+_)?deleted_flag$/";

    /**
     * コンストラクタ
     *
     * @throws APP_Model_exception
     * @throws APP_Paranoid_model_exception
     */
    public function __construct()
    {
        parent::__construct();

        // 設定ファイルに基づき削除用のカラムを検索する
        $this->config->load('model_settings', TRUE, TRUE);

        $keys = array(
            'deleted_at_column_name',
            'deleted_by_column_name',
            'deleted_flag_column_name',
        );

        foreach ($keys as $key) {
            if (FALSE !== ($value = $this->config->item($key, 'model_settings'))) {
                $key = $key . "_regex";
                $this->{$key} = $value;
            }
        }

        foreach ($this->fields as $field) {
            if (preg_match($this->deleted_by_column_name_regex, $field)) {
                $this->deleted_by_column_name = $field;
            }
            if (preg_match($this->deleted_at_column_name_regex, $field)) {
                $this->deleted_at_column_name = $field;
            }
            if (preg_match($this->deleted_flag_column_name_regex, $field)) {
                $this->deleted_flag_column_name = $field;
            }
        }

        if (empty($this->deleted_at_column_name)) {
            throw new APP_Paranoid_model_exception("deleted_at column is not found in {$this->table_name}", 9000);
        }

        if (empty($this->deleted_by_column_name)) {
            throw new APP_Paranoid_model_exception("deleted_by column is not found in {$this->table_name}", 9000);
        }

        if ($this->use_deleted_flag && empty($this->deleted_flag_column_name)) {
            throw new APP_Paranoid_model_exception("deleted_flag column is not found in {$this->table_name}", 9000);
        }
    }

    /**
     * レコード取得
     *
     * 指定したプライマリキーのレコードを取得する。
     * 論理削除したレコードは取得されない。ただし with_deleted オプションを付与すると取得できる。
     *
     * @access public
     * @return false|object レコード
     * @throws APP_Model_exception
     *
     * @internal param string $id プライマリキー
     * @internal param array $options オプション
     */
    public function find(/* polymorphic */)
    {
        list($args, $options) = $this->_parse_find_args(func_get_args());

        if (empty($options['with_deleted']) || $options['with_deleted'] !== TRUE) {
            unset($options['with_deleted']);
            $this->_set_without_deleted_conditions();
        }

        array_push($args, $options);
        return call_user_func_array("parent::find", $args);
    }

    /**
     * レコード全件取得
     *
     * 条件に一致したレコードを全件取得する。
     * 論理削除したレコードは取得されない。ただし with_deleted オプションを付与すると取得できる。
     * 
     * @access public
     * @param array $options オプション
     * @return false|array レコード
     */
    public function all($options = array())
    {
        if (empty($options['with_deleted']) || $options['with_deleted'] !== TRUE) {
            unset($options['with_deleted']);
            $this->_set_without_deleted_conditions();
        }
        return parent::all($options);
    }

    /**
     * レコード件数取得
     *
     * 条件に一致したレコードを件数取得する。
     * 論理削除したレコードは取得されない。ただし with_deleted オプションを付与すると取得できる。
     *
     * @access public
     * @param array $options オプション
     * @return false|int 取得件数
     */
    public function count_rows($options = array())
    {
        if (empty($options['with_deleted']) || $options['with_deleted'] !== TRUE) {
            unset($options['with_deleted']);
            $this->_set_without_deleted_conditions();
        }
        return parent::count_rows($options);
    }

    /**
     * レコード更新
     *
     * 指定されたIDのレコードを更新する。
     * 論理削除したレコードは更新されない。ただし with_deleted オプションを付与すると更新できる。
     *
     * @access public
     * @return false|int|object 更新件数 または 再取得した更新対象のレコード
     * @throws APP_Model_exception
     *
     * @internal param int $id プライマリキー
     * @internal param array $attributes 更新パラメータ
     */
    public function update(/* polymorphic */)
    {
        list($args, $attributes, $options) = $this->_parse_update_args(func_get_args());

        if (empty($options['with_deleted']) || $options['with_deleted'] !== TRUE) {
            unset($options['with_deleted']);
            $this->_set_without_deleted_conditions();
        }

        array_push($args, $attributes, $options);
        return call_user_func_array("parent::update", $args);
    }

    /**
     * レコード全体更新
     *
     * 指定された条件に一致するレコード全件を更新する。
     * 論理削除したレコードは更新されない。ただし with_deleted オプションを付与すると更新できる。
     *
     * @access public
     * @param array $attributes 更新するレコードのパラメータ
     * @param array $options
     * @return false|int 更新件数
     */
    public function update_all($attributes = array(), $options = array())
    {
        if (empty($options['with_deleted']) || $options['with_deleted'] !== TRUE) {
            unset($options['with_deleted']);
            $this->_set_without_deleted_conditions();
        }
        return parent::update_all($attributes, $options);
    }

    /**
     * レコード論理削除
     *
     * 指定されたプライマリキーのレコードを論理削除にする。
     * 論理削除したレコードは、対象とならない。ただし with_deleted オプションを付与すると対象とできる。
     *
     * @access public
     * @return false|int 削除件数
     *
     * @throws APP_Model_exception
     * @throws Exception
     *
     * @internal param int $id プライマリキー
     * @internal param array $options オプション
     */
    public function destroy(/* polymorphic */)
    {
        list($args, $options) = $this->_parse_destroy_args(func_get_args());

        if (empty($options['with_deleted']) || $options['with_deleted'] !== TRUE) {
            unset($options['with_deleted']);
            $this->_set_without_deleted_conditions();
        }

        $this->trans_begin_without_savepoint();

        try {

            // 削除対象のレコードを取得せずにbefore_destoryへ進むと、今までの条件が消えてしまうので、
            // 削除対象のレコードを先に取得しておく
            $find_args = $args;
            array_push($find_args, array_merge($options, array('master' => TRUE)));

            $this->for_update();
            $record = call_user_func_array("parent::find", $find_args);
            if (FALSE === $record) {
                $this->trans_rollback_to_latest_savepoint();
                return FALSE;
            }

            if (empty($record)) {
                $this->trans_commit_to_latest_savepoint();
                return 0;
            }

            if ((!array_key_exists('skip_callback', $options) || $options['skip_callback'] !== TRUE) && method_exists($this, "before_destroy")) {
                if (FALSE === $this->before_destroy(count($args) > 1 ? $args : $args[0], $options)) {
                    $this->trans_rollback_to_latest_savepoint();
                    return FALSE;
                }
            }

            $attributes = array(); 
            $this->_set_deleted_timestamp($attributes, FALSE, TRUE);

            array_push($args, $attributes, array_merge($options, array(
                'skip_auto_timestamp' => TRUE,
                'skip_callback' => TRUE,
                'skip_observer' => TRUE
            )));

            $result = call_user_func_array("parent::update", $args);
            if (FALSE === $result) {
                $this->trans_rollback_to_latest_savepoint();
                return FALSE;
            }

            $this->trans_commit_to_latest_savepoint();

        } catch (Exception $e) {
            $this->trans_rollback_to_latest_savepoint();
            throw $e;
        }

        if ((!array_key_exists('skip_observer', $options) || $options['skip_observer'] !== TRUE)) {
            foreach ($this->observers as $o) {
                $this->{$o . "_observer"}->invoke('after_destroy', $record, $options);
            }
        }

        return $result;
    }

    /**
     * レコード全件論理削除
     *
     * 指定された条件のレコードを論理削除にする。
     * 論理削除したレコードは、対象とならない。ただし with_deleted オプションを付与すると対象とできる。
     *
     * @access public
     * @param array $options オプション
     * @return false|int 削除件数
     */
    public function destroy_all($options = array())
    {
        if (empty($options['with_deleted']) || $options['with_deleted'] !== TRUE) {
            unset($options['with_deleted']);
            $this->_set_without_deleted_conditions();
        }

        $attributes = array(); 
        $this->_set_deleted_timestamp($attributes, FALSE, TRUE);

        return call_user_func_array("parent::update_all", array($attributes, 
            array_merge($options, array('skip_auto_timestamp' => TRUE))));
    }

    /**
     * レコード削除
     *
     * 指定したプライマリキーのレコードを物理削除する。
     *
     * @access public
     * @return false|int 削除件数
     *
     * @internal param int $id プライマリキー
     * @internal param array $options オプション
     */
    public function real_destroy(/* polymorphic */)
    {
        return call_user_func_array("parent::destroy", func_get_args());
    }

    /**
     * レコード全件削除
     *
     * 指定した条件のレコードを物理削除する。
     *
     * @access public
     * @param array $options オプション
     * @return false|int 削除件数
     */
    public function real_destroy_all($options = array())
    {
        return parent::destroy_all($options);
    }

    /**
     * 論理削除レコード除外条件を追加
     *
     * @access protected
     * @return self
     */
    protected function _set_without_deleted_conditions()
    {
        return $this->use_deleted_flag ?
            $this->where($this->column_name($this->deleted_flag_column_name), 0) :
            $this->where($this->column_name($this->deleted_at_column_name) . " IS NULL");
    }

    /**
     * 削除フラグを設定
     *
     * 更新内容に削除フラグを設定する
     *
     * @access protected
     * @param array $attributes 更新内容
     * @return void
     */
    protected function _set_deleted_timestamp(& $attributes)
    {
        $timestamp = business_date('Y-m-d H:i:s');
        $attributes = $this->_object_to_array($attributes);

        if ($this->use_deleted_flag) {
            $attributes[$this->deleted_flag_column_name] = TRUE;
        }
        $attributes[$this->deleted_at_column_name] = $timestamp;
        $attributes[$this->deleted_by_column_name] = self::operator()->_operator_identifier();
    }
}

