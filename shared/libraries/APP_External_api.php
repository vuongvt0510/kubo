<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "third_party/Apache_log4php/log4php/Logger.php";

/**
 * 外部API 抽象クラス
 *
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_External_api {

    /**
     * マスキングされたときの値
     * @const
     */
    const MASKED_VALUE = "[MASKED]";

    /**
     * マスキング対象のパラメータ名
     * @var array
     */
    protected $masking_params = array("password", "token");

    /**
     * タイムアウト時間
     * @var int
     */
    protected $timeout_sec = 15;

    /**
     * タイムアウトリトライ回数
     * @var int
     */
    protected $retry_count = 3;

    /**
     * ロガー
     * @var object
     */
    protected $logger = NULL;

    protected $last_method = NULL;
    protected $last_url = NULL;
    protected $last_params = NULL;
    protected $last_errno = NULL;
    protected $last_http_code = NULL;
    protected $last_response = NULL;

    /**
     * コンストラクタ
     *
     * @access public
     * @param array $params
     */
    public function __construct($params = array())
    {
        if (isset($params['logger'])) {
            $this->logger = $params['logger'];
        }

        if (empty($this->logger)) {
            $log =& Logger::getLogger(strtolower(get_class($this)));
            if (empty($log)) {
                $log = Logger::getLogger("external_api");
            }
            if (empty($log)) {
                $log = Logger::getRootLogger();
            }

            $this->logger = $log;
        }
    }

    /**
     * 最後に実行したリクエストメソッドを返す
     *
     * @access public
     * @return string
     */
    public function last_method()
    {
        return $this->last_method;
    }

    /**
     * 最後に実行したリクエストURIを返す
     * 
     * @access public
     * @return string
     */
    public function last_url()
    {
        return $this->last_url;
    }

    /**
     * 最後に実行したリクエストパラメータを返す
     *
     * @access public
     * @return array
     */
    public function last_params()
    {
        return $this->last_params;
    }

    /**
     * 直前のリクエストパラメータをマスクして返します
     *
     * @access public
     * @return string 直前のリクエストパラメータ(マスク済み)
     */
    public function masked_last_params()
    {
        if (empty($this->last_params)) {
            return NULL;
        }

        return http_build_query($this->_masked_params($this->last_params));
    }

    /**
     * 最後に実行したエラー番号を返す
     * 
     * @access public
     * @return int cURLエラー番号
     */
    public function last_errno()
    {
        return $this->last_errno;
    }

    /**
     * 最後に実行したHTTPステータスコードを返す
     * 
     * @access public
     * @return int HTTPステータスコード
     */
    public function last_http_code()
    {
        return $this->last_http_code;
    }

    /**
     * 最後に実行したレスポンスを返す
     *
     * @access public
     * @return string
     */
    public function last_response()
    {
        return $this->last_response;
    }


    /**
     * 直前のレスポンスをマスクして返します
     *
     * @access public
     * @return string 直前のレスポンス(マスク済み)
     */
    public function masked_last_response()
    {
        if (empty($this->last_response)) {
            return NULL;
        }

        return $this->_masked_response($this->last_response);
    }

    /**
     * cURLによるデータ取得関数
     *
     * @access protected
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $options
     * @return false|string
     *
     * @throws APP_External_api_curl_exception
     * @throws APP_External_api_curl_timeout
     */
    protected function call($method, $url, $params = array(), $options = array())
    {
        $this->last_method = $method;
        $this->last_url = $url;
        $this->last_params = $params;
        $this->last_errno = NULL;

        $curl_opts = array(
            CURLOPT_TIMEOUT => $this->timeout_sec,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => FALSE
        );

        switch(strtolower($method)) {
        case 'post':
            $curl_opts[CURLOPT_POST] = TRUE;
            break;
        case 'put':
        case 'delete':
            $curl_opts[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            break;
        }

        if (!empty($params)) {
            switch(strtolower($method)) {
            case 'post':
                $curl_opts[CURLOPT_POSTFIELDS] = http_build_query($params);
                break;

            default: // GET
                $url .= (strpos($url, "?") === FALSE ? "?" : "&") . http_build_query($params);
                break;
            }
        }

        if (!empty($options["header"])) {
            $curl_opts[CURLOPT_HTTPHEADER] = $options["header"];
        }

        if (!empty($options["curl_options"])) {
            $curl_opts = array_merge($curl_opts, $options["curl_options"]);
        }

        $this->_log("debug", "request %s %s %s", strtoupper($method), $url, json_encode($this->_masked_params($params)));

        $ch = curl_init($url);

        curl_setopt_array($ch, $curl_opts);

        for ($retry = 1; $retry < $this->retry_count; $retry++) {
            $this->last_response = curl_exec($ch);

            $this->last_errno = curl_errno($ch);
            if ($this->last_errno != CURLE_OPERATION_TIMEOUTED) {
                break;
            }

            $this->_log("warn", "retry (%d/%d) request %s %s %s", $retry, $this->retry_count, strtoupper($method), $url, json_encode($this->_masked_params($params)));
        }

        $this->last_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->_log("debug", "errno: %d", $this->last_errno);
        $this->_log("debug", "http code: %d", $this->last_http_code);
        $this->_log("debug", "body: %s", $this->_masked_response($this->last_response));

        if ($this->last_errno == 0) {
            return $this->last_response;
        } else {
            $this->_log("error", "response error (%d) on %s %s %s", $this->last_errno, strtoupper($method), $url, json_encode($this->_masked_params($params)));

            switch ($this->last_errno) {
            case CURLE_OPERATION_TIMEOUTED:
                throw new APP_External_api_curl_timeout($this);
                break;
            default:
                throw new APP_External_api_curl_exception($this);
                break;
            }
        }
    }

    /**
     * マスクしたパラメータを返す
     *
     * @access protected
     * @param array $params
     * @return array
     */
    protected function _masked_params($params)
    {
        $result = array();

        foreach ($params as $idx => $value) {
            if (is_object($value)) {
                $value = get_object_vars($value);
            }

            if (is_array($value)) {
                $value = $this->_masked_params($value);
            } else {
                // マスク値のチェック
                if (preg_match("/" . implode("|", $this->masking_params) . "/", $idx)) {
                    $value = self::MASKED_VALUE;
                }
            }

            $result[$idx] = $value;
        }

        return $result;
    }

    /**
     * マスクしたレスポンスを返す
     *
     * @access protected
     * @param mixed $response
     * @return mixed
     */
    protected function _masked_response($response)
    {
        return $response;
    }

    /**
     * ログ出力
     *
     * @access public
     * @return void
     */
    protected function _log(/* polymorphic */)
    {
        if (empty($this->logger)) {
            // ログ設定がない場合は無視する
            return;
        }

        $args = func_get_args();

        $level = array_shift($args);
        $message = array_shift($args);

        if (count($args) > 0) {
            $message = vsprintf($message, $args);
        }

        switch (strtolower($level)) {
        case 'fatal':
        case 'error':
        case 'warn':
        case 'info':
        case 'trace':
            call_user_func(array($this->logger, strtolower($level)), $message);
            break;

        default:
            $this->logger->debug($message);
            break;
        }
    }
}

/**
 * 外部API 例外クラス
 *
 * @package ECM\API
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_External_api_exception extends APP_Exception {

    public function __construct($base, $message = '', $code = 0, $previous = NULL)
    {
        $this->base = $base;
        parent::__construct($message, $code, $previous);
    }
}

/**
 * 外部API XMLパース例外クラス
 *
 * @package ECM\API
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_External_api_xml_parse_error extends APP_External_api_exception {

    /**
     * APP_External_api_xml_parse_error constructor.
     * @param object $base
     * @param string $xml_error
     * @param object $previous
     */
    public function __construct($base, $xml_error, $previous = NULL)
    {
        $message = sprintf("XML parse error on %s %s %s",
            $base->last_method(), $base->last_url(), json_encode($base->last_params()));

        $message .= "\n" . $this->convert_xml_error_to_message($xml_error);

        parent::__construct($base, $message, 99, $previous);
    }

    /**
     * @param $error
     * @return string
     */
    private function convert_xml_error_to_message($error)
    {
        $array = array();

        foreach ($error as $e) {
            $msg = "--------\n";

            switch ($e->level) {
            case LIBXML_ERR_WARNING:
                $msg .= "Warning $e->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $msg .= "Error $e->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $msg .= "Fatal $e->code: ";
                break;
            }

            $msg .= trim($e->message) .
                "\n  Line: $e->line" .
                "\n  Column: $e->column";

            if ($e->file) {
                $msg .= "\n  File: $e->file";
            }

            $array[] = $msg;
        }

        return implode("\n", $array);
    }
}


/**
 * 外部API cURL例外クラス
 *
 * @package ECM\API
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_External_api_curl_exception extends APP_External_api_exception {

    public function __construct($base, $previous = NULL)
    {
        $message = sprintf("cURL error (%s) on %s %s %s",
            $base->last_errno(), $base->last_method(), $base->last_url(), json_encode($base->last_params()));

        parent::__construct($base, $message, $base->last_errno(), $previous);
    }
}


/**
 * 外部API タイムアウト例外クラス
 *
 * @package APP/Exception
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Tomoyuki Kakuda <kakuda@interest-marketing.net>
 */
class APP_External_api_curl_timeout extends APP_External_api_curl_exception {
}


/**
 * 外部API HTTPステータス例外クラス
 *
 * @package APP/Exception
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_External_api_http_status_exception extends APP_External_api_exception {

    public function __construct($base, $previous = NULL)
    {
        $message = sprintf("HTTP status error (%s) on %s %s %s",
            $base->last_http_code(), $base->last_method(), $base->last_url(), json_encode($base->last_params()));

        parent::__construct($base, $message, $base->last_http_code(), $previous);
    }
}

