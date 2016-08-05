<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Amazon DynamoDB Model
 *
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 * @uses APP_Aws_dynamodb
 * @property-read APP_Loader load
 * @property-read APP_Aws_dynamodb dynamodb
 */
class APP_Dynamodb_model extends CI_Model implements APP_Model_interface
{
    /**
     * テーブル名
     * @var string
     */
    public $table_name = NULL;

    /**
     * プライマリーキー
     * @var mixed
     */
    public $primary_key = 'id';

    /**
     * レコードクラス
     * @var string
     */
    public $record_class = 'stdClass';

    /**
     * スキーマ情報
     * @var string
     */
    public $schema = array();

    /**
     * 次のカーソルキー
     * @var array()
     */
    protected $next_cursor = NULL;

    /**
     * クエリ生成クラス
     * @var object
     */
    protected $query_builder = NULL;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // NOTE: 気にくわないが仕方がない。
        $this->load->library("Aws/APP_Aws_dynamodb", array(), "dynamodb");

        $this->query_builder = new APP_Dynamodb_model_query_builder($this);
    }

    /**
     * 内部コール
     *
     * @param string $name
     * @param array $arguments
     *
     * @return $this
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        // クエリ生成系のメソッドの場合は移譲する
        if (method_exists($this->query_builder, $name)) {
            call_user_func_array(array($this->query_builder, $name), $arguments);
            return $this;
        }

        throw new Exception("undefined method " . $name);
    }

    /**
     * レコード取得
     *
     * @access public
     * @param mixed ...
     * @return object
     */
    public function find(/* polymorphic */)
    {
        list($args, $options) = $this->_parse_find_args(func_get_args());

        $options = array_merge(array(
            'sanitize_options' => array(),
            'request_options' => array()
        ), $options);

        $attributes = $this->_build_attributes_by_primary_key($args);
        $attributes = $this->dynamodb->adjust_attribute_format($attributes, 'put', NULL, $this->schema, $options['sanitize_options']);

        $result = $this->dynamodb->get_item($this->table_name, $attributes, $options['request_options']);

        return $this->dynamodb->extract_to_object($result['Item'], $this->record_class);
    }

    /**
     * インデックス検索
     *
     * @access public
     * @param array $options
     * @return array
     */
    public function all($options = array())
    {
        $options = array_merge(array(
            'sanitize_options' => array(),
            'request_options' => array()
        ), $options);

        $params = $this->query_builder->build('KeyConditions');

        $this->next_cursor = NULL;
        $this->query_builder->clear();

        $result = $this->dynamodb->query($this->table_name, array(), array_merge($params, $options['request_options']));

        if (!empty($result['LastEvaluatedKey'])) {
            $this->next_cursor = 
                get_object_vars($this->dynamodb->extract_to_object($result['LastEvaluatedKey'], 'stdClass'));
        }

        return array_map(function($r){
            return $this->dynamodb->extract_to_object($r, $this->record_class);
        }, $result['Items']);
    }

    /**
     * スキャン検索
     *
     * @access public
     * @param array $options
     * @return mixed
     */
    public function scan($options = array())
    {
        $options = array_merge(array(
            'sanitize_options' => array(),
            'request_options' => array()
        ), $options);

        $params = $this->query_builder->build();

        $this->next_cursor = NULL;
        $this->query_builder->clear();

        $result = $this->dynamodb->scan($this->table_name, array(), array_merge($params, $options['request_options']));

        if (!empty($result['LastEvaluatedKey'])) {
            $this->next_cursor = 
                get_object_vars($this->dynamodb->extract_to_object($result['LastEvaluatedKey'], 'stdClass'));
        }

        return array_map(function($r){
            return $this->dynamodb->extract_to_object($r, $this->record_class);
        }, $result['Items']);
    }

    /**
     * 次のカーソルを取得する
     *
     * @access public
     * @return mixed
     */
    public function next_cursor()
    {
        return $this->next_cursor;
    }

    /**
     * レコード作成
     *
     * @access public
     * @param array $attributes
     * @param array $options
     * @return true|object
     */
    public function create($attributes, $options = array())
    {
        $options = array_merge(array(
            'skip_sanitize' => FALSE,
            'return' => FALSE,
            'request_options' => array(),
            'sanitize_options' => array(),
        ), $options);

        $sanitized_attributes = empty($options['skip_sanitize']) ? $this->dynamodb->adjust_attribute_format($attributes, 'put', NULL, $this->schema, $options['sanitize_options']) : $attributes;

        $result = $this->dynamodb->put_item($this->table_name, $sanitized_attributes, $options['request_options']);

        if ($options['return'] === TRUE) {
            if (is_array($this->primary_key)) {
               $primary_key = array();
                foreach ($this->primary_key as $pk) {
                    $primary_key[] = @$attributes[$pk];
                }
            } else {
                $primary_key = $attributes[$this->primary_key];
            }

            $find_args = is_array($primary_key) ? $primary_key : array($primary_key);
            $find_args[] = array("master" => TRUE);

            $result = call_user_func_array(array($this, "find"), $find_args);
        } else {
            $result = TRUE;
        }

        // @TODO: after_createのサポート

        return $result;
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
        $options = array_merge(array(
            'skip_sanitize' => FALSE,
            'return' => FALSE,
            'sanitize_options' => array(),
            'request_options' => array()
        ), $options);

        $sanitized_array = array();
        if (empty($options['skip_sanitize'])) {
            foreach ($array as $attributes) {
                $sanitized_array[] = $this->dynamodb->adjust_attribute_format($attributes, 'put', NULL, $this->schema, $options['sanitize_options']);
            }
        } else {
            $sanitized_array = $array;
        }

        $result = $this->dynamodb->put_item_with_write_batch($this->table_name, $sanitized_array, $options['request_options']);

        return TRUE;
    }

    /**
     * レコード更新
     *
     * @access public
     * @param mixed ...
     * @return true|object
     */
    public function update(/* polymorphic */)
    {
        list($args, $attributes, $options) = $this->_parse_update_args(func_get_args());

        $options = array_merge(array(
            'skip_sanitize' => FALSE,
            'sanitize_action' => 'PUT',
            'return' => FALSE,
            'sanitize_options' => array()
        ), $options);

        $keys = $this->_build_attributes_by_primary_key($args);
        $keys = $this->dynamodb->adjust_attribute_format($keys, 'put', NULL, $this->schema, $options['sanitize_options']);

        $sanitized_attributes = empty($options['skip_sanitize']) ?
            $this->dynamodb->adjust_attribute_format($attributes, 'update', $options['sanitize_action'], $this->schema, $options['sanitize_options']) :
            $attributes;

        $result = $this->dynamodb->update_item($this->table_name, $keys, $sanitized_attributes,
            empty($options['request_options']) ? array() : $options['request_options']);

        if ($options['return'] === TRUE) {
            $result = call_user_func_array(array($this, "find"), $args);
        } else {
            $result = TRUE;
        }

        // @TODO: after_updateのサポート

        return $result;
    }

    /**
     * レコード削除
     *
     * @access public
     * @param mixed ...
     * @return true
     */
    public function destroy(/* polymorphic */)
    {
        list($args, $options) = $this->_parse_destroy_args(func_get_args());

        $options = array_merge(array(
            'sanitize_options' => array(),
            'request_options' => array()
        ), $options);

        // @TODO: before_destroy

        $attributes = $this->_build_attributes_by_primary_key($args);
        $attributes = $this->dynamodb->adjust_attribute_format($attributes, 'put', NULL, $this->schema, $options['sanitize_options']);

        $this->dynamodb->delete_item($this->table_name, $attributes, $options['request_options']);

        return TRUE;
    }

    /**
     * @ignore
     *
     * @param $attributes
     * @param array $options
     */
    public function update_all($attributes, $options = array())
    {
        throw new RuntimeException('APP_Dynamodb_model::update_all() is not supported.');
    }

    /**
     * @ignore
     *
     * @param array $options
     */
    public function destroy_all($options = array())
    {
        throw new RuntimeException('APP_Dynamodb_model::destory_all() is not supported.');
    }

    /**
     * findの引数を分解
     *
     * @access protected
     * @param array $args findの引数
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
     * @param array $args updateの引数
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
     * @return array
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
}



/**
 * Amazon DynamoDB Model クエリ生成 クラス
 *
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Dynamodb_model_query_builder
{
    /**
     * 変換表
     * @var array
     */
    static protected $operators = [
        '=' => 'EQ',
        '!=' => 'NE',
        '<>' => 'NE',
        '<=' => 'LE',
        '<' => 'LT',
        '>=' => 'GE',
        'IS NOT NULL' => 'NOT_NULL',
        'IS NULL' => 'NULL',
        'LIKE' => 'BEGINS_WITH',
        'BETWEEN' => 'BETWEEN',
        'IN' => 'IN',
        'CONTAINS' => 'CONTAINS',
        'NOT CONTAINS' => 'NOT_CONTAINS'
    ];

    protected $base = NULL;
    protected $where = [];
    protected $select = [];
    protected $limit = FALSE;
    protected $cursor = [];
    protected $order = FALSE;
    protected $index = FALSE;
    protected $and = TRUE;

    /**
     * QueryFilter用builder
     * @var APP_Dynamodb_model_query_builder
     */
    protected $query_filter = NULL;

    /**
     * Excepted用builder
     * @var APP_Dynamodb_model_query_builder
     */
    protected $excepted_filter = NULL;


    public function __construct($base)
    {
        $this->base = $base;
    }


    /**
     * DynamoDB用のクエリを生成する
     *
     * @public function
     * @param string $where_key
     * @return array
     */
    public function build($where_key = 'ScanFilter')
    {
        $attributes = [
            $where_key => [],
            'ConditionalOperator' => $this->and ? 'AND' : 'OR'
        ];

        if (!empty($this->limit)) {
            $attributes['Limit'] = $this->limit;
        }

        $CI =& get_instance();

        // SELECT処理
        if (!empty($this->select)) {
            $attributes['Select'] = 'SPECIFIC_ATTRIBUTES';
            $attributes['AttributesToGet'] = $this->select;
        }

        // WHERE処理
        foreach ($this->where as $column => $item) {
            $k = [
                'AttributeValueList' => [],
                'ComparisonOperator' => self::$operators[$item['operator']],
            ];

            foreach ($item['values'] as $v) {
                switch ($k['ComparisonOperator']) {
                case 'NULL':
                case 'NOT_NULL':
                    // 何もしない
                    // $k['AttributeValueList'][] = 
                    //    $CI->dynamodb->adjust_attribute_format([$column => NULL], 'put', NULL, $this->base->schema)[$column];
                    break;

                case 'BETWEEN':
                case 'IN':
                    foreach ($v as $w) {
                        $k['AttributeValueList'][] =
                            $CI->dynamodb->adjust_attribute_format([$column => $w], 'put', NULL, $this->base->schema)[$column];
                    }
                    break;

                default:
                    $k['AttributeValueList'][] = 
                        $CI->dynamodb->adjust_attribute_format([$column => $v], 'put', NULL, $this->base->schema)[$column];
                    break;
                }
            }

            $attributes[$where_key][$column] = $k;
        }

        // INDEX処理
        if (!empty($this->index)) {
            $attributes['IndexName'] = $this->index;
        }

        // SORT処理
        if (!empty($this->order)) {
            if (strtolower($this->order) == 'desc') {
                $attributes['ScanIndexForward'] = FALSE;
            } else {
                $attributes['ScanIndexForward'] = TRUE;
            }
        }

        // CURSOR処理
        if (!empty($this->cursor)) {
            $attributes['ExclusiveStartKey'] = 
                $CI->dynamodb->adjust_attribute_format($this->cursor, 'put', NULL, $this->base->schema);
        }

        // QueryFilter処理
        if (!empty($this->query_filter)) {
            $q = $this->query_filter->build('QueryFilter');

            if (!empty($q['QueryFilter'])) {
                $attributes['QueryFilter'] = $q['QueryFilter'];
                $attributes['ConditionalOperator'] = empty($q['ConditionalOperator']) ? NULL : $q['ConditionalOperator'];
            }
        }

        // Excepted処理
        if (!empty($this->excepted_filter)) {
            $q = $this->excepted_filter->build('Excepted');

            if (!empty($q['Excepted'])) {
                $attributes['Excepted'] = $q['Excepted'];
                $attributes['ConditionalOperator'] = empty($q['ConditionalOperator']) ? NULL : $q['ConditionalOperator'];
            }
        }

        // WHERE句が複数ない場合はConditionalIOperatorは削除
        if ((empty($attributes['QueryFilter']) || count($attributes['QueryFilter']) <= 1) &&
            (empty($attributes['Expected']) || count($attributes['Expected']) <= 1) &&
            (empty($attributes['ScanFilter']) || count($attributes['ScanFilter']) <= 1)) {
            unset($attributes['ConditionalOperator']);
        }

        return $attributes;
    }

    /**
     * クエリを初期化する
     *
     * @public function
     * @return void
     */
    public function clear()
    {
        $this->select = [];
        $this->limit = FALSE;
        $this->where = [];
        $this->cursor = [];
        $this->order = FALSE;
        $this->and = TRUE;
        $this->index = FALSE;
        $this->query_filter = NULL;
        $this->excepted_filter = NULL;
    }

    /**
     * SELECT句を設定
     *
     * @access public
     * @param mixed $query
     * @return self
     */
    public function select($query)
    {
        $query = is_array($query) ? $query : [$query];

        $s = [];
        foreach ($query as $q) {
            $ca = explode(",", $q);
            foreach ($ca as $c) {
                $s[] = trim($c);
            }
        }

        $this->select = array_unique($this->select + $s);

        return $this;
    }

    /**
     * WHERE句を設定
     *
     * @access public
     * @param string $column
     * @param string $value
     * @return self
     */
    public function where($column, $value = NULL)
    {
        $operators = "(" . implode("|", array_keys(self::$operators)) . ")";

        if (FALSE === preg_match("/^\s*(\S+)\s*({$operators})?\s*$/", $column, $matches)) {
            throw new InvalidArgumentException("where `{$column} {$value}` is not supported.");
        }

        $column = $matches[1];
        $operator = empty($matches[2]) ? '=' : $matches[2];

        if (!empty($this->where[$column]) && $this->where[$column]['operator'] != $operator) {
            throw new InvalidArgumentException("where `{$column} {$value}` can not set. operator {$this->where[$column]['operator']} already set.");
        }

        if (empty($this->where[$column])) {
            $this->where[$column] = array(
                'operator' => $operator,
                'values' => array($value)
            );
        } else {
            $this->where[$column]['values'] = $value;
        }

        return $this;
    }

    /**
     * OR WHERE句を設定
     *
     * @access public
     * @param string $column
     * @param string $value
     * @return self
     */
    public function or_where($column, $value)
    {
        $this->and = FALSE;
        return $this->where($column, $value);
    }

    /**
     * IN句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $value
     * @return self
     */
    public function where_in($column, $value)
    {
        return $this->where($column . " IN", $value);
    }

    /**
     * OR IN句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $value
     * @return self
     */
    public function or_where_in($column, $value)
    {
        return $this->or_where($column . " IN", $value);
    }

    /**
     * BETWEEN句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $low
     * @param mixed $high
     * @return self
     */
    public function between($column, $low, $high)
    {
        return $this->where($column . " BETWEEN", [$low, $high]);
    }

    /**
     * OR BETWEEN句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $low
     * @param mixed $high
     * @return self
     */
    public function or_between($column, $low, $high)
    {
        return $this->or_where($column . " BETWEEN", [$low, $high]);
    }

    /**
     * LIKE句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $value
     * @return self
     */
    public function like($column, $value)
    {
        return $this->where($column . " LIKE", $value);
    }

    /**
     * OR LIKE句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $value
     * @return self
     */
    public function or_like($column, $value)
    {
        return $this->or_where($column . " LIKE", $value);
    }

    /**
     * CONTAINS句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $value
     * @return self
     */
    public function contains($column, $value)
    {
        return $this->where($column . " CONTAINS", $value);
    }

    /**
     * OR CONTAINS句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $value
     * @return self
     */
    public function or_contains($column, $value)
    {
        return $this->or_where($column . " CONTAINS", $value);
    }

    /**
     * NOT CONTAINS句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $value
     * @return self
     */
    public function not_contains($column, $value)
    {
        return $this->where($column . " NOT CONTAINS", $value);
    }

    /**
     * NOT CONTAINS句を設定
     *
     * @access public
     * @param string $column
     * @param mixed $value
     * @return self
     */
    public function or_not_contains($column, $value)
    {
        return $this->or_where($column . " NOT CONTAINS", $value);
    }

    /**
     * limit句を設定
     *
     * @access public
     * @param int $limit
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * cursor句を設定
     *
     * @access public
     * @param array $cursor
     * @return self
     */
    public function cursor($cursor)
    {
        $this->cursor = $cursor;
        return $this;
    }

    /**
     * USE INDEX句を設定
     *
     * @access public
     * @param string $index
     * @return self
     */
    public function use_index($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * ORDER句を設定
     *
     * @access public
     * @param string $order
     * @return self
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * QueryFilterを設定
     *
     * @access public
     * @param callable $callback
     * @return self
     */
    public function filter($callback)
    {
        if (empty($this->query_filter)) {
            $this->query_filter = new APP_Dynamodb_model_query_builder($this->base);
        }

        $callback($this->query_filter);

        return $this;
    }

    /**
     * Expectedを設定
     *
     * @access public
     * @param callable $callback
     * @return self
     */
    public function expected($callback)
    {
        if (empty($this->excepted_filter)) {
            $this->excepted_filter = new APP_Dynamodb_model_query_builder($this->base);
        }

        $callback($this->excepted_filter);
    }
}


