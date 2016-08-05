<?php

require_once dirname(__FILE__) . "/../config/rpn_apnsphp_autoload.php";

/**
 * プッシュ通知ライブラリ
 *
 * @aurhor Yoshikazu Ozawa
 */
class Rpn_sender {

    protected $CI = NULL;

    protected $app = NULL;
    protected $mode = NULL;
    protected $ios_pem = NULL;
    protected $ios_authority = NULL;

    protected $gcm_url = "https://android.googleapis.com/gcm/send";
    protected $gcm_title = NULL;
    protected $gcm_key = NULL;


    public function __construct($params = array())
    {
        if (empty($params['app_name'])) {
            throw new Rpn_sender_exception("appname is not set.");
        }

        $app_name = $params['app_name'];
        unset($params['app_name']);

        $this->CI =& get_instance();

        $this->CI->load->model('rpn_application_model');
        $this->app = $this->CI->rpn_application_model->find_by_name($app_name);
        if (empty($this->app)) {
            throw new Rpn_sender_exception("app (NAME:{$app_name}) is not found.");
        }

        if (!empty($params)) {
            $config = $params;
        } else {
            $this->CI->config->load("rpn", TRUE);
            $config = $this->CI->config->item($app_name, "rpn");

            if (empty($config)) {
                throw new Rpn_sender_exception("config (NAME:{$app_name}) is not set.");
            }
        }

        $this->mode = @$config["mode"];
        $this->ios_pem = @$config["ios"]["pem_file"];
        $this->ios_authority = @$config["ios"]["authority_file"];
        $this->gcm_title = @$config["android"]["title"];
        $this->gcm_key = @$config["android"]["key"];
    }

    /**
     * プッシュ通知
     *
     * @access public
     * @param string $sent_at
     * @return bool
     */
    public function send($sent_at = NULL)
    {
        $this->ios_send($sent_at);
        $this->gcm_send($sent_at);
    }

    /**
     * フィードバック
     *
     * @access public
     * @return bool
     */
    public function feedback()
    {
        $this->ios_feedback();
    }

    /**
     * 送信済みのキューをログへ移す
     *
     * @access public
     * @return bool
     */
    public function clean($time = NULL)
    {
        if (empty($time)) {
            $time = new DateTime();
            $time->modify('-1 day');
            $time = $time->format('Y-m-d H:i:s');
        }

        $limit = 100;

        while (true) {
            $this->CI->load->model('rpn_queue_model');
            $queue = $this->CI->rpn_queue_model
                ->where('application_id', $this->app->id)
                ->where('sending_at <', $time)
                ->where('status <>', 'waiting')
                ->limit($limit)
                ->logging();

            if (empty($queue)) break;
            }

        return TRUE;
    }

    /**
     * APNsプッシュ通知
     *
     * @access public
     * @param string $sent_at 送信時間
     * @return bool
     */
    public function ios_send($sent_at = NULL)
    {
        $mode = ($this->mode === "production") ? ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION : ApnsPHP_Abstract::ENVIRONMENT_SANDBOX;

        if (empty($sent_at)) $sent_at = date('Y-m-d H:i:s');

        $limit = 100;

        while (true) {
            $this->CI->load->model('rpn_queue_model');
            $queue = $this->CI->rpn_queue_model
                ->where('rpn_queue.application_id', $this->app->id)
                ->sending('ios', $sent_at)->limit($limit)->all_with_update_sending();
            if (empty($queue)) {
                break;
            }

            $apns = new ApnsPHP_Push($mode, $this->ios_pem);
            $apns->setRootCertificationAuthority($this->ios_authority);
            $apns->setLogger(new Rpn_sender_logger);

            $apns->connect();

            try {
                foreach ($queue as $q) {
                    $message = new ApnsPHP_Message($q->token);
                    $message->setExpiry(0);
                    $message->setSound();
                    $message->setText($q->message);

                    $badge = $this->_get_badge($q);
                    if (!empty($badge)) {
                        $message->setBadge($badge);
                    }

                    if (!empty($q->urlscheme)) {
                        $message->setCustomProperty('urlscheme', $q->urlscheme);
                    }

                    if (!empty($q->custom_properties)) {
                        foreach ($q->custom_properties as $k => $v) {
                            $message->setCustomProperty($k, $v);
                        }
                    }

                    $apns->add($message);

                }

                $errors = $apns->getErrors();

                $apns->send();

            }catch (ApnsPHP_Message_Exception $e){
                $e->getMessage();
            }

            $apns->disconnect();

            // 送信したステータスを更新
            $ids = array_map(function($r){ return $r->id; }, $queue);
            $this->CI->rpn_queue_model->where_in('id', $ids)->update_all(array('status' => 'succeed'));
            
            foreach ((empty($errors) ? array() : $errors) as $e) {
                $m = $e["MESSAGE"];

                $token = $m->getRecipient(0);

                $ids = array_map(function($q){ return $q->id; },
                    array_filter($queue, function($q) use($token){ return $q->token == $token; }));
                $error_text = implode("\n", array_map(function($e){ return $e["statusCode"] . " - " . $e["statusMessage"]; }, $e["ERRORS"]));

                if (!empty($ids)) {
                    $this->CI->rpn_queue_model->where_in('id', $ids)->update_all(array('status' => 'failed', 'error' => $error_text));
                }
            }

            // 次接続のために一定時間スリープ
            usleep(2000000);
        }
    }

    /**
     * iOSプッシュ通知フィードバック
     *
     * @access public
     * @return bool
     */
    public function ios_feedback()
    {
        $mode = ($this->mode === "production") ? ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION : ApnsPHP_Abstract::ENVIRONMENT_SANDBOX;

        $apns = new ApnsPHP_Feedback($mode, $this->ios_pem);
        $apns->setLogger(new Rpn_sender_logger);

        $apns->connect();

        $tokens = $apns->receive();

        $apns->disconnect();

        if (empty($tokens)) {
            return TRUE;
        }

        $tokens = array_map(function($r){
            return $r['deviceToken'];
        }, $tokens);

        $this->CI->load->model('rpn_token_model');

        $this->CI->rpn_token_model
            ->where('application_id', $this->app->id)
            ->where('type', 'ios')
            ->where_in('token', $tokens)
            ->destroy_with_logging();

        return TRUE;
    }

    /**
     * GCMプッシュ通知
     *
     * @access public
     * @param string $sent_at
     * @return bool
     */
    public function gcm_send($sent_at = NULL)
    {
        if (empty($sent_at)) $sent_at = date('Y-m-d H:i:s');

        $limit = 100;

        $header = array(
            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8",
            "Authorization: key={$this->gcm_key}"
        );

        while (true) {
            $this->CI->load->model('rpn_queue_model');
            $queue = $this->CI->rpn_queue_model
                ->where('rpn_queue.application_id', $this->app->id)
                ->sending('android', $sent_at)->limit($limit)->all_with_update_sending();
            if (empty($queue)) {
                break;
            }

            foreach ($queue as $q) {
                $post = array(
                    'registration_id' => $q->token,
                    'collapse_key' => 'notice',
                    'data.title' => $this->gcm_title,
                    'data.message' => $q->message
                );

                $ch = curl_init($this->gcm_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                $ret = curl_exec($ch);

                curl_close($ch);

                $result = explode("=", $ret);

                // 送信したステータスを更新
                if ($result[0] !== 'Error') {
                    $this->CI->rpn_queue_model->update($q->id, array('status' => 'succeed'));
                } else {
                    if ($result[1] == 'InvalidRegistration') {
                        // トークン削除
                    }
                    $this->CI->rpn_queue_model->update($q->id, array('status' => 'failed', 'error' => $ret));
                }
            }
        }
    }

    /**
     * バッジ情報取得
     *
     * リアルタイムでバッジ情報を取得したい場合は、ここを拡張する
     *
     * @access public
     * @return void
     */
    protected function _get_badge($queue)
    {
        return (int)$queue->badge;
    }
}

/**
 * プッシュ通知ライブラリ例外クラス
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_sender_exception extends Exception {
}

/**
 * ApnsPHPログ拡張クラス
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_sender_logger implements ApnsPHP_Log_Interface {

    public function log($sMessage)
    {
        $level = "DEBUG";

        if (preg_match("/^INFO:/", $sMessage)) {
            $level = "INFO";
        }

        if (preg_match("/^STATUS:/", $sMessage)) {
            $level = "DEBUG";
        }

        if (preg_match("/^WARNING:/", $sMessage)) {
            $level = "ERROR";
        }

        if (preg_match("/^ERROR:/", $sMessage)) {
            $level = "ERROR";
        }

        log_message($level, "[ApnsPHP] " . $sMessage);
    }
}

