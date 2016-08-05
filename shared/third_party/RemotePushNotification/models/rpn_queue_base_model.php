<?php

/**
 * プッシュ通知キュー基底モデル
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_queue_base_model extends APP_Model {

    /**
     * 検索
     *
     * custom_propertiesを自動でデコードする
     *
     * @access public
     * @param int $id
     * @param array $options
     * @return mixed
     */
    public function find(/* polymorphic */)
    {
        list($args, $options) = $this->_parse_find_args(func_get_args());

        $result = call_user_func_array('parent::find', func_get_args());
        if (empty($result)) {
            return $result;
        }

        if (@$options['decode_custom_properties'] !== FALSE) {
            $this->_decode_custom_properties($result);
        }
        return $result;
    }

    /**
     * 検索
     *
     * @access public
     * @param array $options
     * @return mixed
     */
    public function all($options = array())
    {
        $result = parent::all($options);
        if (empty($result)) {
            return $result;
        }
        foreach ($result as &$r) {
            $this->_decode_custom_properties($r);
        }
        return $result;
    }

    /**
     * 登録
     *
     * @access public
     * @param array $attributes 登録内容
     * @param array $options
     * @return mixed
     */
    public function create($attributes, $options = array())
    {
        $this->_encode_custom_properties($attributes);
        return parent::create($attributes, $options);
    }

    /**
     * 一括登録
     *
     * @access public
     * @param array $array 登録内容
     * @param array $options
     * @return bool
     */
    public function bulk_create($array, $options = array())
    {
        foreach ($array as &$a) {
            $this->_encode_custom_properties($a);
        }
        return parent::bulk_create($array, $options);
    }

    protected function _decode_custom_properties(& $object)
    {
        $object->custom_properties = @json_decode($object->custom_properties, TRUE);
    }

    protected function _encode_custom_properties(& $attributes)
    {
        if (empty($attributes['custom_properties'])) {
            $attributes['custom_properties'] = array();
        }
        if (!is_array(@$attributes['custom_properties'])) {
            throw new Rpn_queue_base_model_exception('invalid custom_properties');
        }
        $attributes['custom_properties'] = @json_encode($attributes['custom_properties']);
    }
}

/**
 * プッシュ通知キュー例外クラス
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_queue_base_model_exception extends Exception {
}

