<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (!class_exists('CI_Email')) {
    require_once BASEPATH . "libraries/Email.php";
}

require_once SHAREDPATH . "libraries/APP_Email.php";


/**
 * メールクラス
 *
 * 直接メールを送信するのではなくキューへためる
 *
 * @author Yoshikazu Ozawa
 */
class APP_Queuing_email extends APP_Email {

    /**
     * 利用するモデル
     * @var string
     */
    protected $model = 'mail_queue_model';

    /**
     * 送信要求時間
     * @var string
     */
    protected $requesting_at = NULL;


    /**
     * 送信時間を設定する
     *
     * @access public
     * @param string $time
     * @return void
     */
    public function requesting_at($time)
    {
        $this->requesting_at = $time;
    }

    /**
     * 送信
     *
     * @access public
     * @param array $options
     * @return bool
     */
    public function send($options = array())
    {
        $options = array_merge(array(
            'queuing' => TRUE
        ), $options);

        // ユニットテストの際はキューに入れるだけ
        if (ENVIRONMENT === 'auto_test') {
            $options = array_merge($options, array(
                'queuing' => TRUE
            ));
        }

        if ($options['queuing'] === TRUE) {
            return $this->queue_mail();
        } else {
            $queue = $this->queue_mail(array('status' => 'sending'));
            return $this->send_from_queue($queue, array('force' => TRUE));
        }
    }

    /**
     * キューから送信を行う
     *
     * @access public
     * @return void
     */
    public function send_from_all_queue($options = array())
    {
        $CI =& get_instance();
        $CI->load->model($this->model);

        $model =& $CI->{$this->model};

        while (TRUE) {
            $q = $model->where('status', 'waiting')
                ->where('requesting_at <=', date('Y-m-d H:i:s'))
                ->order_by('requesting_at', 'asc')
                ->first(array('master' => TRUE));

            if (FALSE === $q || empty($q)) {
                break;
            }

            $this->send_from_queue($q, $options);
        }

        return TRUE;
    }

    /**
     * キューから送信する
     *
     * @access public
     * @param object|int $queue
     * @return bool
     */
    public function send_from_queue($queue, $options = array())
    {
        $this->clear();

        $CI =& get_instance();
        $CI->load->model($this->model);

        $model =& $CI->{$this->model};

        if (!is_object($queue)) {
            $queue_id = $queue;
        } else {
            $queue_id = $queue->id;
        }

        $model->transaction(function() use($model, $queue_id, &$queue) {
            $queue = $model->for_update()->find($queue_id, ['master' => TRUE]);

            if (empty($queue)) {
                return FALSE;
            }

            if (empty($options['force']) || $options['force'] !== TRUE) {
                // 未送信以外の場合は無視する
                if ($queue->status !== "waiting") {
                    return TRUE;
                }

                // 送信日時になっていない場合は無視する
                if (!empty($queue->requesting_at) && strtotime($queue->requesting_at) > time()) {
                    return TRUE;
                }
            }

            // 送信中に更新
            if (FALSE === $model->update($queue->id, ['status' => 'sending'])) {
                return FALSE;
            }
        });

        if (!is_object($queue)) {
            return $queue;
        }

        // 送信元の設定
        $this->from($queue->from_address, empty($queue->from_name) ? '' : $queue->from_name);

        // 送信先の設定
        $to = json_decode($queue->to, TRUE);
        if (is_array($to)) {
            foreach($to as $t) {
                $this->to($t['mail'], empty($t['name']) ? NULL : $t['name'], TRUE);
            }
        } else {
            $this->to($queue->to, NULL, TRUE);
        }

        // 常に BCC 追加
        $this->bcc($queue->bcc);

        // 件名の設定
        $this->subject($queue->subject);

        // 本文の設定
        if ($queue->content_type === "html") {
            $this->html($queue->content);
        } else {
            $this->text($queue->content);
        }

        // 送信
        $result = parent::send();

        $status = ($result !== TRUE) ? 'failed' : 'succeed';

        if ($result !== TRUE) {
            $error = implode(" ", $this->_mailer->errorStatment(FALSE));
        } else {
            $error = NULL;
        }

        $this->update_mail_queue($queue->id, $status, $error);

        return $result;
    }

    /**
     * メールをキューにためる
     *
     * @access public
     * @param array $options
     * @return bool
     */
    public function queue_mail($options = array())
    {
        $CI =& get_instance();

        $CI->load->model($this->model);

        $attributes = array();

        // 送信元名
        if (!empty($this->_mailer->from[0]) && !empty($this->_mailer->from[0]["mail"])) {
            $attributes['from_address'] = $this->_mailer->from[0]["mail"];
            $attributes['from_name'] = empty($this->_mailer->from[0]["name"]) ? NULL : $this->_mailer->from[0]["name"];
        } else {
            log_message("ERROR", "APP_Queuing_email::queue_mail() `from` is not set.");
            return FALSE;
        }

        // 送信先
        if (!empty($this->_mailer->to)) {
            $attributes['to'] = json_encode($this->_mailer->to);
        } else {
            log_message("ERROR", "APP_Queuing_email::queue_mail() `to` is not set.");
            return FALSE;
        }

        // ヘッダー
        if (!empty($this->_mailer->other_header)) {
            $attributes['header'] = json_encode($this->_mailer->other_header);
        }

        // 件名
        if ( !empty($this->_mailer->subject["CONTENT"])) {
            $attributes['subject'] = $this->_mailer->subject["CONTENT"];
        } else {
            log_message("ERROR", "APP_Queuing_email::queue_mail() `subject` is not set.");
            return FALSE;
        }

        // 本文の設定
        if (!empty($this->_mailer->content["TEXT"]["CONTENT"])) {
            $attributes['content_type'] = 'plain';
            $attributes['content'] = $this->_mailer->content["TEXT"]["CONTENT"];
        } elseif (!empty($this->_mailer->content["HTML"]["CONTENT"])) {
            $attributes['content_type'] = 'html';
            $attributes['content'] = $this->_mailer->content["TEXT"]["CONTENT"];
        } else {
            log_message("ERROR", "APP_Queuing_email::queue_mail() `content` is not set.");
        }

        $attributes['priority'] = $this->priority;
        $attributes['pushed_at'] = date('Y-m-d H:i:s');
        $attributes['requesting_at'] = empty($this->requesting_at) ? date('Y-m-d H:i:s') : $this->requesting_at;

        if (!empty($options['status'])) {
            $attributes['status'] = $options['status'];
        }

        $queue = $CI->{$this->model}->create($attributes, array('return' => TRUE));

        return $queue;
    }

    /**
     * キューを更新する
     *
     * @access protected
     * @param $id
     * @param string $status
     * @param string $error
     * @return mixed
     */
    public function update_mail_queue($id, $status, $error = NULL)
    {
        $CI =& get_instance();
        $CI->load->model($this->model);

        $attributes = array(
            'status' => $status,
            'sent_at' => date('Y-m-d H:i:s'),
            'sent_evidence' => $error
        );

        $CI->{$this->model}->update($id, $attributes);

        return TRUE;
    }
}

