<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "libraries/Aws/APP_Aws.php";


/**
 * AWS DynamoDBクラス
 *
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Aws_dynamodb extends APP_Aws
{
    /**
     * インスタンス名を取得
     *
     * @access public
     * @return string
     */
    public function instance_name()
    {
        return "DynamoDb";
    }

    /**
     * アイテム登録
     *
     * @access public
     * @param string $table_name
     * @param array $attributes
     * @param array $options
     * @return mixed
     */
    public function put_item($table_name, $attributes, $options = array())
    {
        $result = $this->instance()->putItem(array_merge(array(
            'TableName' => $table_name,
            'Item' => $attributes,
            'ReturnConsumedCapacity' => 'TOTAL',
        ), $options));

        return $result;
    }

    /**
     * アイテム登録 バッチ処理
     *
     * @access public
     * @param string $table_name,
     * @param array $array
     * @param array $options
     * @return mixed
     */
    public function put_item_with_write_batch($table_name, $array, $options = array())
    {
        $batch = Aws\DynamoDb\Model\BatchRequest\WriteRequestBatch::factory($this->instance());

        foreach ($array as $attributes) {
            $batch->add($item = new APP_Dynamodb_put_request($attributes, $table_name));
        }

        return $batch->flush();
    }

    /**
     * アイテム取得
     *
     * @access public
     * @param string $table_name
     * @param array $primary_keys
     * @param array $options
     * @return mixed
     */
    public function get_item($table_name, $primary_keys, $options = array())
    {
        $result = $this->instance()->getItem(array_merge(array(
            'ConsistentRead' => TRUE,
            'TableName' => $table_name,
            'Key' => $primary_keys
        ), $options));

        return $result;
    }

    /**
     * クエリ検索
     *
     * @access public
     * @param string $table_name
     * @param array $conditions
     * @param array $options
     * @return mixed
     */
    public function query($table_name, $conditions, $options = array())
    {
        $params = array_merge(array(
            'TableName' => $table_name,
            'KeyConditions' => $conditions,
        ), $options);

        log_message("DEBUG", "[dynamodb] query " . json_encode($params));

        $result = $this->instance()->query($params);

        return $result;
    }

    /**
     * スキャン検索
     *
     * @access public
     * @param string $table_name
     * @param array $conditions
     * @param array $options
     * @return mixed
     */
    public function scan($table_name, $conditions, $options = array())
    {
        $params = array_merge(array(
            'TableName' => $table_name,
            'ScanFilter' => $conditions,
        ), $options);

        log_message("DEBUG", "[dynamodb] scan " . json_encode($params));

        $result = $this->instance()->scan($params);

        return $result;
    }

    /**
     * アイテム更新
     *
     * @access public
     * @param string $table_name
     * @param array $keys
     * @param array $attributes
     * @param array $options
     * @return mixed
     */
    public function update_item($table_name, $keys, $attributes, $options = array())
    {
        $result = $this->instance()->updateItem(array_merge(array(
            'TableName' => $table_name,
            'Key' => $keys,
            'AttributeUpdates' => $attributes
        ), $options));

        return $result;
    }

    /**
     * アイテム削除
     *
     * @access public
     * @param string $table_name
     * @param array $primary_keys
     * @param array $options
     * @return mixed
     */
    public function delete_item($table_name, $primary_keys, $options = array())
    {
        $result = $this->instance()->deleteItem(array_merge(array(
            'TableName' => $table_name,
            'Key' => $primary_keys
        ), $options));

        return $result;
    }

    /**
     * アイテム削除 バッチ処理
     *
     * @access public
     * @param string $table_name
     * @param array $array
     * @param array $options
     * @return mixed
     */
    public function delete_item_with_write_batch($table_name, $array, $options = array())
    {
        $batch = Aws\DynamoDb\Model\BatchRequest\WriteRequestBatch::factory($this->instance());

        foreach ($array as $attributes) {
            $batch->add(new APP_Dynamodb_delete_request($attributes, $table_name));
        }

        return $batch->flush();
    }

    /**
     * DynamoDBの属性値をstdObjectに変換
     *
     * @access public
     * @param array $attributes
     * @param string $clazz
     * @return object
     */
    public function extract_to_object($attributes, $clazz = 'stdClass')
    {
        $object = new $clazz();

        foreach ($attributes as $key => $p) {

            foreach ($p as $type => $value) {
                switch ($type) {
                case "M":
                    $object->{$key} = $this->extract_to_object($value, 'stdClass');
                    break;

                case "L":
                    $object->{$key} = array_map(function($v){ return $this->extract_to_object($v, 'stdClass'); }, $value);
                    break;

                case "NULL":
                    $object->{$key} = NULL;
                    break;

                default:
                    $object->{$key} = $value;
                    break;
                }
            }
        }

        return $object;
    }

    /**
     * 属性値をDyanamoDBの属性値に変換
     *
     * @access public
     * @param array $attributes
     * @param string $type
     * @param string $action
     * @param array $schema
     * @return array
     */
    public function adjust_attribute_format($attributes, $type = 'put', $action = 'put', $schema = NULL, $options = array())
    {
        $options = array_merge(array(
            'remove_null' => TRUE
        ), $options);

        if (is_null($schema)) {
            $schema = empty($this->schema) ? array() : $this->schema;
        }

        $sanitized = array();

        foreach ($attributes as $key => $value) {

            if (array_key_exists($key, $schema)) {
                // スキーマに定義が存在する場合、スキーマ通りに値を変換する

                if (is_array($schema[$key])) {
                    if (is_hash($value)) {
                        $this->_adjust_action_format($sanitized, $key, $type, $action, "M", $this->adjust_attribute_format($value, $type, $action, $schema[$key], $options), $options);
                    } else if (is_strict_array($value)) {
                        $this->_adjust_action_format(
                            $sanitized, $key, $type, $action, "L",
                            array_map(function($v)use($type, $action, $schema, $key, $options){ return $this->adjust_attribute_format($v, $type, $action, $schema[$key], $options); }, $value),
                            $options
                        );
                    }
                } else {
                    if ($schema[$key] !== "BOOL" && empty($value)) {
                        $this->_adjust_action_format($sanitized, $key, $type, $action, 'NULL', TRUE, $options);
                    } else {
                        $this->_adjust_action_format($sanitized, $key, $type, $action, $schema[$key], $value, $options);
                    }
                }
            } else {
                // スキーマに定義が存在しない場合、
                // 渡されている値の情報から自動で予測して型を変換する

                if (is_hash($value)) {
                    // ハッシュ型と解釈して変換する
                    $this->_adjust_action_format($sanitized, $key, $type, $action, "M", $this->adjust_attribute_format($value, $type, $action, array(), $options));

                } else if (is_strict_array($value)) {
                    if (is_array($value[0])) {
                        // ハッシュ型が複数登録されていると解釈して変換する
                        // @TODO: 配列全体をチェックするかどうか検討する
                        $this->_adjust_action_format(
                            $sanitized, $key, $type, $action, "L",
                            array_map(function($v)use($type, $action, $options){ return $this->adjust_attribute_format($v, $type, $action, array(), $options); }, $value),
                            $options
                        );
                    } else {
                        // 配列型と解釈して変換する

                        // @TODO: バイナリの判定をどうするか
                        $format = ((string)intval($value[0]) == $value[0]) ? "NS" : "SS";
                        $this->_adjust_action_format($sanitized, $key, $type, $action, $format, $value, $options);
                    }

                } else {
                    // スカラー型と解釈して展開する
                    if ($value === TRUE || $value === FALSE) {
                        $this->_adjust_action_format($sanitized, $key, $type, $action, "BOOL", $value, $options);
                    } else if (!is_numeric($value) && empty($value)) {
                        $this->_adjust_action_format($sanitized, $key, $type, $action, "NULL", TRUE, $options);
                    } else {
                        // @TODO: バイナリの判定をどうするか
                        $format = ((string)intval($value) == $value) ? "N" : "S";
                        $this->_adjust_action_format($sanitized, $key, $type, $action, $format, $value, $options);
                    }
                }
            }
        }

        return $sanitized;
    }

    /**
     * 属性値を条件によって適切なフォーマットに変換する
     *
     * @access protected
     * @param array $data
     * @param string $type
     * @param string $action
     * @param string $format
     * @param string $value
     * @return array
     */
    protected function _adjust_action_format(&$data, $key, $type, $action, $format, $value, $options = array())
    {
        $options = array_merge(array(
            'remove_null' => TRUE
        ), $options);

        switch (strtolower($type))
        {
        case 'update':
            //if ($format == 'NULL' && $options['remove_null'] === TRUE) {
            //    //$data[$key] = array(
            //    //    'Action' => 'DELETE',
            //    //    // TODO: 型チェックが厳密にできない
            //    //);
            //} else {
                $data[$key] = array(
                    'Action' => strtoupper($action),
                    'Value' => array($format => $value)
                );
            //}

        default:
            if ($format != 'NULL' || $options['remove_null'] === FALSE) {
                $data[$key] = array($format => $value);
            }
        }

        return $data;
    }
}

/**
 * @ignore toArrayが存在しないとエラーになるのでオーバーライトして対応
 */
class APP_Dynamodb_put_request extends Aws\DynamoDb\Model\BatchRequest\PutRequest
{
    public function toArray()
    {
        return array('PutRequest' => array('Item' => $this->item));
    }
}

/**
 * @ignore toArrayが存在しないとエラーになるのでオーバーライトして対応
 */
class APP_Dynamodb_delete_request extends Aws\DynamoDb\Model\BatchRequest\DeleteRequest
{
    public function toArray()
    {
        $key = $this->key;
        foreach ($key as &$element) {
            if ($element instanceof Attribute) {
                $element = $element->toArray();
            }
        }
        return array('DeleteRequest' => array('Key' => $key));
    }
}

