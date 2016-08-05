<?php

require_once "rpn_queue_base_model.php";

/**
 * プッシュ通知キューモデル
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_queue_model extends Rpn_queue_base_model {
    var $database_name = "remote_push_notification";
    var $table_name = "rpn_queue";
    var $primary_key = "id";

    /**
     * 送信対象を抽出する
     *
     * @access public
     * @param string $device
     * @param string $sending_at
     * @return object
     */
    public function sending($device, $sending_at)
    {
        return $this
            ->select('rpn_queue.*, rpn_token.sid, rpn_token.type, rpn_token.token')
            ->join('rpn_token', 'rpn_token.id = rpn_queue.token_id')
            ->where(array('rpn_token.type' => $device, 'rpn_queue.sending_at <=' => $sending_at));
    }

    /**
     * 検索するついでステータスを更新する
     *
     * @access public
     * @param array $options
     * @return mixed
     */
    public function all_with_update_sending($options = array())
    {
        $this->trans_start();

        $result = $this->where('status', 'waiting')->for_update()->all(array_merge($options, array('master' => TRUE)));
        if (empty($result)) {
            $this->trans_complete();
            return $result;
        }

        $this->where_in('id', array_map(function($r){ return $r->id; }, $result))->update_all(array('status' => 'sending'));

        foreach ($result as &$r) {
            $r->status = "sending";
        }

        $this->trans_complete();

        return $result;
    }

    /**
     * キューの情報をログに移動させる
     *
     * @access public
     * @param array $options
     * @return array
     */
    public function logging($options = array())
    {
        $this->load->model('rpn_sent_log_model');

        $this->trans_start();

        $queue = $this->for_update()->all(array_merge($options, array('master' => TRUE)));
        if (empty($queue)) {
            $this->trans_complete();
            return array();
        }

        $array = array_map(function($a){
            return array(
                'queue_id' => $a->id,
                'application_id' => $a->application_id,
                'token_id' => $a->token_id,
                'message' => $a->message,
                'badge' => $a->badge,
                'urlscheme' => $a->urlscheme,
                'custom_properties' => $a->custom_properties,
                'sending_at' => $a->sending_at,
                'status' => $a->status,
                'error' => $a->error,
                'created_at' => $a->created_at
            );
        }, $queue);

        $this->rpn_sent_log_model->bulk_create($array);

        $ids = array_map(function($a){
            return $a->id;
        }, $queue);

        $this->where_in('id', $ids)->destroy_all();

        $this->trans_complete();

        return $queue;
    }

    /**
     * 指定したトークンに対して、指定した通知内容をキューに貯める
     *
     * @access public
     * @param int $application_id アプリID
     * @param string $type トークン種別
     * @param string $token トークン
     * @param array $attributes 通知内容
     * @param array $options
     * @return mixed
     */
    public function push_to_device($application_id, $type, $token, $attributes, $options = array())
    {
        $this->load->model('rpn_token_model');
        $token = $this->rpn_token_model->find_by_token($application_id, $type, $token);
        if (empty($token)) {
            return FALSE;
        }

        return $this->_push(array($token), $attributes, $options);
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
    public function push_to_sid($application_id, $sid, $attributes, $options = array())
    {
        //get part of message
        $maxStr = 48;
        $cntMsg = mb_strlen($attributes['message'], 'UTF-8');
        if( $cntMsg > $maxStr ){
            $attributes['message'] = mb_substr($attributes['message'], 0, $maxStr, 'UTF-8') . '...';
        }

        //save data
        $this->load->model('rpn_token_model');
        $tokens = $this->rpn_token_model->where(array('application_id' => $application_id, 'sid' => $sid))->all(array('master' => TRUE));
        if (empty($tokens)) {
            return FALSE;
        } else {
            return $this->_push($tokens, $attributes, $options);
        }
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
    public function push_to_all($application_id, $attributes, $options = array())
    {
        $this->load->model('rpn_token_model');

        $limit = 100;
        $offset = 0;

        while(true) {
            $tokens = $this->rpn_token_model->where('application_id', $application_id)->all(array('limit' => $limit, 'offset' => $offset));
            if (empty($tokens)) {
                break;
            }
            if (FALSE === $this->_push($tokens, $attributes, $options)) {
                return FALSE;
            }
            $offset += $limit;
        }

        return TRUE;
    }

    protected function _push($tokens, $attributes, $options = array())
    {
        $array =  array();
        foreach ($tokens as $t) {
            unset($data);
            $data = $attributes;
            $data['token_id'] = $t->id;
            $data['application_id'] = $t->application_id;
            $array[] = $data;
        }

        return $this->bulk_create($array, $options);
    }
}

