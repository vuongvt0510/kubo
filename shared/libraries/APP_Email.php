<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "third_party/Qdmail/qdmail.php";
require_once SHAREDPATH . "third_party/Qdmail/qdsmtp.php";
require_once BASEPATH . '/libraries/Email.php';
/**
 * Qdmail CI拡張クラス
 *
 * Qdsmtpにdisplay_errorの設定が引き継がれないようなので強引にクラスを再定義
 *
 * @author Yoshikazu Ozawa
 */
class CI_Qdmail extends Qdmail {
    var $is_qmail = FALSE;

    function & smtpObject($null = false)
    {
        if(is_null($null)){
            $this->smtp_object = null;
            return true;
        }
        if( isset( $this->smtp_object ) && is_object( $this->smtp_object ) ){
            return $this->smtp_object;
        }

        if( ! class_exists ('CI_Qdsmtp')) {
            return $this->errorGather('Plese load SMTP Program - Qdsmtp http://hal456.net/qdsmtp', __LINE__);
        }

        $smt = new CI_Qdsmtp();
        $this->smtp_object =& $smt;
        return $this->smtp_object;
    }
}



/**
 * Qdsmtp CI拡張クラス
 *
 * display_errorの設定がQdmailから引き継がれないようなので強引にクラスを再定義
 *
 * @author Yoshikazu Ozawa
 */
class CI_Qdsmtp extends Qdsmtp {
    var $error_display = FALSE;

    var $time_out = 5;
    var $stream_time_out = 30;

    /**
     * SMTPサーバーへ接続する
     *
     * エラー時のメッセージ処理を拡張
     *
     * @access public
     * @param string $host
     * @param string $port
     * @param int $status
     * @return resource
     */
    public function connect($host = NULL , $port = NULL , $status = 220)
    {
        if (is_null($host)) {
            $host = $this->smtp_param['HOST'];
        }
        if (is_null($port)) {
            $port = $this->smtp_param['PORT'];
        }
        $sock = fsockopen($host , $port , $err , $errst , $this->time_out);
        if (!is_resource($sock)) {
            return $this->errorGather('Connection error HOST: '.$host.' PORT: '.$port.' ERRNO: '.$err.'ERRSTR: '.$errst, __LINE__);
        }
        stream_set_timeout($sock , $this->stream_time_out);
        return $sock;
    }

    /**
     * タイムアウト設定
     *
     * @access public
     * @param int $sec 秒数
     * @return mixed
     */
    public function streamTimeOut($sec = NULL)
    {
        if (is_null($sec)) {
            return $this->stream_time_out;
        }
        if (is_numeric($sec)) {
            $this->stream_time_out = $sec;
            return $this->errorGather();
        } else {
            return $this->errorGather(__FUNCTION__.'  specified error',__LINE__);
        }
    }

    /**
     * ログ出力
     *
     * CIのログ出力で出力を行う
     *
     * @access public
     * @param string $type
     * @param string $message
     * @return true
     */
    public function logWrite($type , $message)
    {
        log_message($type, get_class($this) . ": ". trim($message));
        return TRUE;
    }

    /**
     * SMTPの通信ログ出力
     *
     * @access public
     * @return true
     */
    public function log()
    {
        $mes = null;
        foreach($this->smtp_log as $line){
            $mes .= trim($line) . $this->log_LFC;
        }

        $this->logWrite(null , "communicate log\n" . $mes);
        $this->smtp_log = array();
    }
}


/**
 * メールクラス
 *
 * Qdmailを使うように拡張されている
 *
 * @property CI_Qdmail _mailer
 *
 * @author Yoshikazu Ozawa
 */
class APP_Email extends CI_Email {

    /**
     * テンプレートモデル
     * @var string
     */
    protected $template_model = "mail_template_model";

    /**
     * テンプレートキャッシュ
     * @var string
     */
    protected $template_cache = array();

    /**
     * 送信元
     * @var array
     */
    protected $default_sender = NULL;

    /**
     * コンストラクタ
     *
     * @access public
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->_mailer = new CI_Qdmail();
        $this->_mailer->errorDisplay(FALSE);
        $this->_mailer->smtpLoglevelLink(TRUE);

        if (empty($config)) {
            $files = array(
                SHAREDPATH . "config/email.php",
                SHAREDPATH . "config/" . ENVIRONMENT . "/email.php",
                APPPATH . "config/email.php",
                APPPATH . "config/" . ENVIRONMENT . "/email.php"
            );

            foreach ($files as $f) {
                if (is_file($f)) {
                    include $f;
                }
            }

            if (!empty($email)) {
                $config = $email;
            }
        }

        if (count($config) > 0) {
            $this->initialize($config);
        }

        log_message('debug', "Email Class Initialized");
    }

    /**
     * 初期設定を行う
     *
     * @access public
     * @param array $config
     * @return void
     */
    public function initialize($config = array())
    { 
        switch (@$config['protocol']) {
        case 'sendmagic':
            $qdmail_options = array();
            $qdmail_options['protocol'] = 'SMTP';
            $qdmail_options['host'] = $config['smtp_host'];
            $qdmail_options['port'] = $config['smtp_port'] === FALSE ? 25 : $config['smtp_port'];

            $this->_mailer->smtp(TRUE);
            $this->_mailer->smtpServer($qdmail_options);
            break;

        case 'smtp':
            $qdmail_options = array();

            if (empty($config['smtp_user']) && empty($config['smtp_pass'])) {
                $qdmail_options['protocol'] = 'SMTP';
            } else {
                $qdmail_options['protocol'] = 'SMTP_AUTH';
                $qdmail_options['user'] = $config['smtp_user'];
                $qdmail_options['pass'] = $config['smtp_pass'];

            }

            $qdmail_options['host'] = $config['smtp_host'];
            $qdmail_options['port'] = isset($config['smtp_port']) ? $config['smtp_port'] : 25;

            $this->_mailer->smtp(TRUE);
            $this->_mailer->smtpServer($qdmail_options);
            break;

        default:
            // do nothing
            break;
        }

        $this->protocol = empty($config['protocal']) ? "mail" : $config['protocal'];

        // 送信者を設定する
        if (!empty($config['default_sender'])) {
            $this->default_sender = $config['default_sender'];
        }

        $this->clear();
    }

    /**
     * 初期化する
     *
     * 添付ファイルも初期化されるので注意すること
     *
     * @access public
     * @param bool $clear_attachments
     */
    public function clear($clear_attachments = FALSE)
    {
        $this->_mailer->resetHeaderBody();
        if (isset($this->default_sender)) {
            $this->from($this->default_sender['address'], $this->default_sender['name']);
        }
    }

    /**
     * 送信元設定
     *
     * @access public
     * @param string $from
     * @param string $name
     *
     * @return APP_Email
     */
    public function from($from, $name = '')
    {
        $this->_mailer->from($from, $name);
        if (isset($this->_mailer->smtp) && TRUE === $this->_mailer->smtp) {
            $this->_mailer->smtpServer(array('FROM' => $from));
        }
        return $this;
    }

    /**
     * 返信元設定
     *
     * @access public
     * @param string
     * @param string
     *
     * @return APP_Email
     */
    public function reply_to($from, $name = null)
    {
        $this->_mailer->replyto($from, $name);
        return $this;
    }

    /**
     * 送信先設定
     *
     * @access public
     * @param string $to
     * @param string $name
     * @param bool $point
     *
     * @return APP_Email
     */
    public function to($to, $name = NULL, $point = FALSE)
    {
        $this->_mailer->to($to, $name, $point);
        return $this;
    }

    /**
     * CC設定
     *
     * @access public
     * @param string $cc
     * @param string $name
     * @param bool $point
     *
     * @return APP_Email
     */
    public function cc($cc, $name = NULL, $point = FALSE)
    {
        $this->_mailer->cc($cc, $name, $point);
        return $this;
    }

    /**
     * BCC設定
     *
     * @access public
     * @param string $bcc
     * @param string $name
     * @param bool $point
     *
     * @return APP_Email
     */
    public function bcc($bcc, $name = NULL, $point = FALSE)
    {
        $this->_mailer->bcc($bcc, $name, $point);
        return $this;
    }

    /**
     * 件名設定
     *
     * @access public
     * @param string $subject
     *
     * @return APP_Email
     */
    public function subject($subject)
    {
        $this->_mailer->subject($subject);
        return $this;
    }

    /**
     * 本文設定
     *
     * @access public
     * @param string $body
     *
     * @return APP_Email
     */
    public function message($body)
    {
        $this->text($body);
        return $this;
    }

    /**
     * 本文設定(HTML)
     *
     * @access public
     * @param string $body
     *
     * @return APP_Email
     */
    public function html($body)
    {
        $this->html($body);
        return $this;
    }

    /**
     * テンプレートアサイン
     *
     * @access public
     * @param int $template_id
     * @param array $attributes
     *
     * @return APP_Email
     */
    public function template($template_id, $attributes = array())
    {
        if (FALSE === ($result = $this->parse_template($template_id, $attributes))) {
            return $this;
        }

        $this->subject($result['subject']);
        if ($result['content_type'] === "html") {
            $this->html($result['content']);
        } else {
            $this->message($result['content']);
        }

        if (!empty($result['from_address'])) {
            $this->from($result['from_address'], $result['from_name']);
        }

        return $this;
    }

    /**
     * テンプレートデータからパースする
     *
     * @access public
     * @param int $template_id
     * @param array $attributes
     *
     * @return APP_Email
     */
    protected function parse_template($template_id, $attributes = array())
    {
        $CI =& get_instance();
        if (empty($this->template_cache[(int)$template_id])) {
            $CI->load->model($this->template_model);

            $template = $CI->{$this->template_model}->find($template_id, array('master' => TRUE));
            if (empty($template)) {
                log_message("ERROR", "APP_Email::template() template(ID:{$template_id}) is not found.");
                return FALSE;
            }

            $this->template_cache[(int)$template_id] = $template;
        } else {
            $template = $this->template_cache[(int)$template_id];
        }

        $CI->load->library('parser');

        $subject = $CI->parser->parse_string($template->subject, $attributes, TRUE);
        $content = $CI->parser->parse_string($template->content, $attributes, TRUE);

        return array(
            'subject' => $subject,
            'content' => $content,
            'content_type' => $template->content_type,
            'from_name' => $template->from_name,
            'from_address' => $template->from_address
        );
    }

    /**
     * 添付ファイル設定
     *
     * @access public
     * @param string $filename
     * @param string $disposition
     * @param bool $point
     *
     * @return APP_Email
     */
    public function attach($filename, $disposition = 'attachment', $point = FALSE)
    {
        if ($disposition == 'inline') {
            $this->_mailer->inline_mode = true;
        } else {
            $this->_mailer->inline_mode = false;
        }
        $this->_mailer->attach($filename, $point);
        return $this;
    }

    /**
     * ヘッダーを追加
     *
     * @access public
     *
     * @param string $name
     * @param string $value
     */
    public function header($name, $value)
    {
        $this->_mailer->addHeader($name, $value);
    }

    /**
     * 送信
     *
     * @access public
     * @param null $option
     *
     * @return bool
     * @internal param array $options
     */
    public function send($option = null)
    {
        log_message("INFO", sprintf("Send mail to %s, subject: %s",
            json_encode($this->_mailer->to),
            isset($this->_mailer->subject["CONTENT"]) ? $this->_mailer->subject["CONTENT"] : json_encode($this->_mailer->subject)
        ));

        if ($this->protocol === 'sendmagic') {
            $this->header('X-SM-Envelope-From', $this->_mailer->from[0]["mail"]);
        }

        $result = $this->_mailer->send($option);
        if (FALSE === $result) {
            $message = print_r($this->_mailer->errorStatment(FALSE), TRUE);
            log_message("ERROR", "Email send Error: " . $message);
        }

        return $result;
    }

    /**
     * 本文設定
     *
     * @access public
     * @param string
     * @param int
     * @param string
     * @param string
     * @param string
     * @return object
     */
    public function text($content, $length = NULL, $charset = NULL, $enc = NULL, $org_charset = NULL)
    {
        $this->_mailer->text($content, $length, $charset, $enc, $org_charset);
        return $this;
    }

    public function print_debugger()
    {
        $msg = '<pre>';
        $msg =  'to: ' . print_r($this->_mailer->to, TRUE) . "\n";
        $msg .= 'cc: ' . print_r($this->_mailer->cc, TRUE) . "\n";
        $msg .= 'bcc: ' . print_r($this->_mailer->bcc, TRUE) . "\n";
        $msg .= 'from: ' . print_r($this->_mailer->from, TRUE) . "\n";
        $msg .= 'replyto: ' . print_r($this->_mailer->replyto, TRUE) . "\n";
        $msg .= 'otherheader: ' . print_r($this->_mailer->other_header, TRUE) . "\n";
        $msg .= 'subject: ' . print_r($this->_mailer->subject, TRUE) . "\n";
        $msg .= 'body: ' . print_r($this->_mailer->content, TRUE) . "\n";
        $msg .= '</pre>';
        return $msg;
    }
}

