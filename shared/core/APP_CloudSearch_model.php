<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Amazon CloudSearch Model
 *
 * @property object load
 * @property APP_Aws_cloudsearch cloudsearch
 *
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 *
 * @uses APP_Aws_cloudsearch
 */
class APP_CloudSearch_model extends CI_Model implements APP_Model_interface
{
    /**
     * 検索エンドポイント
     * @var string
     */
    public $search_endpoint = NULL;

    /**
     * ドキュメントエンドポイント
     * @var string
     */
    public $document_endpoint = NULL;

    /**
     * プライマリーキー
     * @var string
     */
    public $primary_key = 'id';

    /**
     * レコードクラス
     * @var string
     */
    public $record_class = 'stdClass';

    /**
     * 検索結果
     * @var object
     */
    protected $result = NULL;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // NOTE: 気にくわないが仕方がない。
        $this->load->library("Aws/APP_Aws_cloudsearch", array(
            'search_endpoint' => $this->search_endpoint,
            'document_endpoint' => $this->document_endpoint
        ), "cloudsearch");
    }

    /**
     * レコード取得
     *
     * @access public
     * @return object
     * @throws APP_Model_exception
     *
     * @internal param string $id
     * @internal param array $options
     */
    public function find(/* polymorphic */)
    {
        list($args, $options) = $this->_parse_find_args(func_get_args());

        $this->cloudsearch->set_document_endpoint($this->document_endpoint);
        $this->cloudsearch->set_search_endpoint($this->search_endpoint);

        $query = $this->_build_query_by_primary_keys($args);

        $this->_clear_result();
        $result = $this->cloudsearch->search($query, array(
            'queryParser' => 'structured',
            'size' => 1
        ), $options);
        $this->_set_result($result);

        return $this->cloudsearch->extract_to_object($result['hits']['hit'], $this->record_class)[0];
    }

    /**
     * ヒットした総件数を取得
     *
     * @var string
     * @return int
     */
    public function found_rows()
    {
        return isset($this->result['hits']['found']) ? $this->result['hits']['found'] : 0;
    }

    /**
     * レコード作成
     *
     * @access public
     * @param array $attributes
     * @param array $options
     * @return mixed
     */
    public function create($attributes, $options = array())
    {
        $this->cloudsearch->set_document_endpoint($this->document_endpoint);
        $this->cloudsearch->set_search_endpoint($this->search_endpoint);

        $document_id = $this->_build_document_id_from_attributes($attributes);

        $this->_clear_result();
        $result = $this->cloudsearch->upload('add', $document_id, $attributes, $options);
        $this->_set_result($result);

        // @TODO: after_createのサポート

        if ($result['status'] !== "success") {
            return FALSE;
        }

        return $result["adds"];
    }

    /**
     * レコードの一括登録
     *
     * @access public
     * @param array $array
     * @param $options
     * @return mixed
     */
    public function bulk_create($array, $options = array())
    {
        $this->cloudsearch->set_document_endpoint($this->document_endpoint);
        $this->cloudsearch->set_search_endpoint($this->search_endpoint);

        foreach ($array as & $a) {
            $a = array(
                'type' => 'add',
                'id' => $this->_build_document_id_from_attributes($a),
                'fields' => $a
            );
        }

        $this->_clear_result();
        $result = $this->cloudsearch->bulk_upload($array, 'application/json', $options);
        $this->_set_result($result);

        if ($result['status'] !== "success") {
            return FALSE;
        }

        return $result["adds"];
    }

    /**
     * レコード更新
     *
     * @access public
     * @return mixed
     *
     * @throws APP_Model_exception
     *
     * @internal param int $id
     * @internal param array $attributes
     */
    public function update(/* polymorphic */)
    {
        list($args, $attributes, $options) = $this->_parse_update_args(func_get_args());

        $this->cloudsearch->set_document_endpoint($this->document_endpoint);
        $this->cloudsearch->set_search_endpoint($this->search_endpoint);

        $document_id = $this->_build_document_id($args);

        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);
        for ($i = 0; $i < count($primary_keys); $i++) {
            unset($attributes[$primary_keys[$i]]);
            $attributes[$primary_keys[$i]] = $args[$i];
        }

        $this->_clear_result();
        $result = $this->cloudsearch->upload('add', $document_id, $attributes, $options);
        $this->_set_result($result);

        // @TODO: after_updateのサポート

        if ($result['status'] !== "success") {
            return FALSE;
        }

        return $result["adds"];
    }

    /**
     * レコード削除
     *
     * @access public
     * @return mixed
     *
     * @throws APP_Model_exception
     * @internal param int $id
     */
    public function destroy(/* polymorphic */)
    {
        list($args, $options) = $this->_parse_destroy_args(func_get_args());

        $options = array_merge(array(
            'request_options' => array()
        ), $options);

        // @TODO: before_destroy
 
        $this->cloudsearch->set_document_endpoint($this->document_endpoint);
        $this->cloudsearch->set_search_endpoint($this->search_endpoint);

        $document_id = $this->_build_document_id($args);

        $this->_clear_result();
        $result = $this->cloudsearch->upload('delete', $document_id, array(), $options['request_options']);
        $this->_set_result($result);

        if ($result['status'] !== "success") {
            return FALSE;
        }

        return $result["deletes"];
    }

    /**
     * クエリ検索
     *
     * @access public
     * @param string $query
     * @param array $params
     * @param array $options
     * @return mixed
     * @todo プレースホルダの実装
     */
    public function query($query, $params  = array(), $options = array())
    {
        $options = array_merge(array(
            'queryParser' => 'structured',
            'size' => 100
        ), $options);

        // $params = array_map(function($p){ return $this->_escape($p); }, $params);

        $this->cloudsearch->set_document_endpoint($this->document_endpoint);
        $this->cloudsearch->set_search_endpoint($this->search_endpoint);

        $this->_clear_result();
        $result = $this->cloudsearch->search($query, $options);
        $this->_set_result($result);

        return $this->cloudsearch->extract_to_object($result['hits']['hit'], $this->record_class);
    }

    /**
     * @ignore
     */
    public function all($options = array())
    {
        throw new RuntimeException('APP_CloudSearch_model::all() is not supported.');
    }

    /**
     * @ignore
     */
    public function update_all($attributes, $options = array())
    {
        throw new RuntimeException('APP_CloudSearch_model::update_all() is not supported.');
    }

    /**
     * @ignore
     */
    public function destroy_all($options = array())
    {
        throw new RuntimeException('APP_CloudSearch_model::destory_all() is not supported.');
    }

    /**
     * findの引数を分解
     *
     * @access protected
     * @param array $args findの引数
     * @return array プライマリキー・オプション情報
     *
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
     * @param array $args updateの引数
     * @param array $validates
     * @return array プライマリキー・更新内容・オプション情報
     *
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
     * @access protected
     *
     * @param array $args destroyの引数
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
     * プライマリキーによる条件を作成
     *
     * @access protected
     * @param array $args プライマリキーの値のリスト
     * @return void
     */
    protected function _build_attributes_by_primary_key($args)
    {
        $where = array();

        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);
        for ($i = 0; $i < count($primary_keys); $i++) {
            $value = $args[$i];
            if (is_object($value) || is_array($value)) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                new APP_Model_exception(sprintf("%s::%s Argument #%d (%s) can't set object or array.", get_class($this), $trace[0]["function"], $i + 1, $primary_keys[$i]));
            }

            $where[$primary_keys[$i]] = $value;
        }

        return $where;
    }

    /**
     * データを初期化する
     *
     * @access protected
     * @return void
     */
    protected function _clear_result()
    {
        $this->result = NULL;
    }

    /**
     * 結果を設定する
     *
     * @access protected
     * @param object $result
     * @return void
     */
    protected function _set_result($result)
    {
        $this->result = $result;
    }

    /**
     * エスケープ処理
     *
     * @access public
     * @param string $str
     * @return string
     */
    public function escape($str)
    {
        // TODO: エスケープ処理の見直し
        return str_replace("'", "\\'", $str);
    }

    /**
     * ドキュメントID生成
     *
     * @access protected
     * @param array $args
     * @return string
     */
    protected function _build_document_id($args)
    {
        $keys = array();

        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);
        for ($i = 0; $i < count($primary_keys); $i++) {
            $value = $args[$i];
            if (is_object($value) || is_array($value)) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                new APP_Model_exception(sprintf("%s::%s Argument #%d (%s) can't set object or array.", get_class($this), $trace[0]["function"], $i + 1, $primary_keys[$i]));
            }

            $keys[] = $value;
        }

        return implode(":", $keys);
    }

    /**
     * ドキュメントID生成
     *
     * @access protected
     * @param array $attributes
     *
     * @return string
     */
    protected function _build_document_id_from_attributes($attributes)
    {
        $keys = array();

        $i = 0;
        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);
        foreach ($primary_keys as $k) {
            if (empty($attributes[$k])) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                new APP_Model_exception(sprintf("%s::%s Attributes %s is not set.",
                    get_class($this), $trace[0]["function"], $i + 1, $primary_keys[$i]));
            }

            $value = $attributes[$k];

            if (is_object($value) || is_array($value)) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                new APP_Model_exception(sprintf("%s::%s Argument #%d (%s) can't set object or array.", get_class($this), $trace[0]["function"], $i + 1, $primary_keys[$i]));
            }

            $keys[] = $value;
        }

        return implode(":", $keys);
    }

    /**
     * プライマリキー検索用クエリ生成
     *
     * @access protected
     * @param array $args
     * @return string
     */
    protected function _build_query_by_primary_keys($args)
    {
        $query = array("and");

        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);

        for ($i = 0; $i < count($primary_keys); $i++) {
            $value = $args[$i];
            if (is_object($value) || is_array($value)) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                new APP_Model_exception(sprintf("%s::%s Argument #%d (%s) can't set object or array.", get_class($this), $trace[0]["function"], $i + 1, $primary_keys[$i]));
            }

            $value = $this->escape($value);

            $query[] = "{$primary_keys[$i]}:'{$value}'";
        }

        return "(" . implode(" ", $query) . ")";
    }
}

