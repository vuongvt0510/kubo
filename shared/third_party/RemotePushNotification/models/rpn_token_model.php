<?php

/**
 * プッシュ通信トークンモデル
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_token_model extends APP_Model {
    var $database_name = "remote_push_notification";
    var $table_name = "rpn_token";
    var $primary_key = "id";

    /**
     * トークン検索
     *
     * @access public
     * @param string $type デバイス種別
     * @param string $token デバイストークン
     * @param array $options
     * @return mixed
     */
    public function find_by_token($application_id, $type, $token, $options = array())
    {
        return $this->find_by(array(
            'application_id' => $application_id,
            'type' => $type,
            'token' => $token
        ), $options);
    }

    /**
     * デバイストークンを追加
     * (デットロックリトライ対応)
     *
     * @access public
     * @param string $application_id アプリケーションID
     * @param string $type デバイス種別
     * @param string $token デバイストークン
     * @param int $sid アカウントID
     * @param array $options
     * @return mixed
     */
    public function add($application_id, $type, $token, $sid = NULL, $options = array())
    {
        $cnt_retry = 0;
        
        while (TRUE) {
            try {
                $this->trans_start();

                $result = $this->_add($application_id, $type, $token, $sid, $options);
                
                $this->trans_complete();
            } catch (APP_DB_exception_lock_dead_lock $e) {
                if (++$cnt_retry >= 10) {
                    throw $e;
                } else {
                    $this->trans_complete(FALSE);
                    $this->trans_reset_status();

                    continue;
                }
            }
            
            break;
        }      
        
        return $result;
    }
    
    /**
     * デバイストークンを追加
     *
     * @access private
     * @param string $application_id アプリケーションID
     * @param string $type デバイス種別
     * @param string $token デバイストークン
     * @param int $sid アカウントID
     * @param array $options
     * @return mixed
     */
    private function _add($application_id, $type, $token, $sid = NULL, $options = array())
    {
        $record = $this->for_update()
                       ->find_by_token($application_id, $type, $token, array('master' => TRUE));
        if (FALSE === $record) {
            $this->trans_complete(FALSE);
            return FALSE;
        }

        // 登録・更新で処理を切り分ける
        if (empty($record)) {
            $result = $this->create(array(
                'application_id' => $application_id,
                'type' => $type,
                'token' => $token,
                'sid' => $sid
            ), $options);
        } else {
            if (!empty($sid) && !empty($record->sid) && $record->sid != $sid) {
                // TODO: sidが異なるということを許容するかどうか検討
                $this->trans_complete(FALSE);
                return FALSE;
            }
            $result = $this->update($record->id, array(
                'application_id' => $application_id,
                'type' => $type,
                'token' => $token,
                'sid' => $sid
            ), $options);
        }
        
        return $result;
    }    
    
    /**
     * デバイストークンを削除する
     *
     * @access public
     * @param int $application_id アプリケーションID
     * @param string $type デバイス種別
     * @param string $token デバイストークン
     * @param array $options
     * @return bool
     */
    public function remove($application_id, $type, $token, $options = array())
    {
        return $this
            ->where('application_id', $application_id)
            ->where('type', $type)
            ->where('token', $token)
            ->destroy_with_logging($options);
    }

    /**
     * 指定されたアカウントのデバイストークンを全て削除する
     *
     * @access public
     * @param int $application_id アプリケーションID
     * @param int $sid アカウントID
     * @param array $options
     * @return mixed
     */
    public function remove_by_sid($application_id, $sid, $options = array())
    {
        return $this
            ->where(array('application_id' => $application_id, 'sid' => $sid))
            ->destroy_with_logging($options);
    }

    /**
     * 削除&ログ書き込み
     *
     * @access public
     * @return bool
     */
    public function destroy_with_logging($options = array())
    {
        $this->trans_start();

        $records = $this->for_update()->all(array('master' => TRUE));
        if (empty($records)) {
            $this->trans_complete();
            return TRUE;
        }

        $array = array_map(function($r){
            return array(
                'token_id' => $r->id,
                'application_id' => $r->application_id,
                'type' => $r->type,
                'token' => $r->token,
                'sid' => @$r->sid,
                'created_at' => $r->created_at
            );
        }, $records);

        $this->load->model('rpn_deleted_token_log_model');
        $this->rpn_deleted_token_log_model->bulk_create($array);

        foreach ($records as $r) {
            $this->destroy($r->id, $options);
        }

        $this->trans_complete();

        return TRUE;
    }
}

