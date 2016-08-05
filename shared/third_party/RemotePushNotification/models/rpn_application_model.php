<?php

/**
 * プッシュ通信アプリモデル
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_application_model extends APP_Model {
    var $database_name = "remote_push_notification";
    var $table_name = "rpn_application";
    var $primary_key = "id";

    var $record_class = "Rpn_application_record";

    /**
     * アプリケーション名検索
     *
     * @access public
     * @param string $name アプリケーション名
     * @param array $options
     * @return mixed
     */
    public function find_by_name($name, $options = array())
    {
        return $this->find_by(array('name' => $name), $options);
    }
}

/**
 * プッシュ通信アプリレコード
 *
 * プッシュ通知で必要な各種メソッドをラッピング
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_application_record {

    /**
     * デバイストークンを追加する
     *
     * @access public
     * @param string $type
     * @param string $token
     * @param int $sid
     * @param array $options
     * @return mixed
     */
    public function add_token($type, $token, $sid = NULL, $options = array())
    {
        return $this->_token_model()->add($this->id, $type, $token, $sid, $options);
    }

    /**
     * デバイストークンを削除する
     *
     * @access public
     * @param string $type
     * @param string $token
     * @param int $sid
     * @param array $options
     * @return mixed
     */
    public function remove_token($type, $token, $options = array())
    {
        return $this->_token_model()->remove($this->id, $type, $token, $options);
    }

    /**
     * 指定されたアカウントのトークンを全て削除する
     *
     * @access public
     * @param int $sid
     * @param array $options
     * @return mixed
     */
    public function remove_token_by_sid($sid, $options = array())
    {
        return $this->_token_model()->remove_by_sid($this->id, $sid, $options);
    }

    /**
     * 指定したトークンに対して、指定した通知内容をキューに貯める
     *
     * @access public
     * @param string $type トークン種別
     * @param string $token トークン
     * @param array $attributes 通知内容
     * @param array $options
     * @return mixed
     */
    public function push_to_device($type, $token, $attributes, $options = array())
    {
        return $this->_queue_model()->push_to_device($this->id, $type, $token, $attributes, $options);
    }

    /**
     * 指定したsidのトークン全てに対して、指定した通知内容をキューに貯める
     *
     * @access public
     * @param int $application_id アプリID
     * @param int $sid SID
     * @param array $attributes 通知内容
     * @param array $options
     * @return mixed
     */
    public function push_to_sid($sid, $attributes, $options = array())
    {
        return $this->_queue_model()->push_to_sid($this->id, $sid, $attributes, $options);
    }

    /**
     * 登録済みのトークン全てに対して、指定した通知内容をキューに貯める
     *
     * @access public
     * @param array $application_id アプリID
     * @param array $attributes 通知内容
     * @param array $options
     * @return bool
     */
    public function push_to_all($attributes, $options = array())
    {
        return $this->_queue_model()->push_to_all($this->id, $attributes, $options);
    }

    private function & _token_model()
    {
        $CI =& get_instance();
        $CI->load->model('rpn_token_model');
        return $CI->rpn_token_model;
    }

    private function & _queue_model()
    {
        $CI =& get_instance();
        $CI->load->model('rpn_queue_model');
        return $CI->rpn_queue_model;
    }
}

