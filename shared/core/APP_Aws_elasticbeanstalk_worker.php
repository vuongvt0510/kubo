<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('APP_Controller')) {
    require_once dirname(__FILE__) . "/APP_Controller.php";
}


/**
 * AWS ElasticBeanstalk用 コントローラ
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa
 */
class APP_Aws_elasticbeanstalk_worker extends APP_Controller
{

    /**
     * @throws APP_DB_Exception
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->output->enable_profiler(FALSE);
        set_time_limit(0);
    }

    /**
     * @param $name
     * @param $method
     * @param array $params
     * @param array $options
     *
     * @return array
     * @throws APP_Api_internal_call_exception
     */
    protected function _worker($name, $method, $params = array(), $options = array())
    {
        return $this->_internal_api($name, $method, $params, $options);
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @throws APP_Exception
     * @throws Exception
     */
    public function _remap($method, $params = array())
    {
        global $_POST;

        $content_type = $this->input->server("CONTENT_TYPE");
        $aws_sqsd_msgid = $this->input->server("HTTP_X_AWS_SQSD_MSGID");
        $aws_sqsd_queue = $this->input->server("HTTP_X_AWS_SQSD_QUEUE");
        $aws_sqsd_first_received_at = $this->input->server("HTTP_X_AWS_SQSD_FIRST_RECEIVED_AT");
        $aws_sqsd_receive_count = $this->input->server("HTTP_X_AWS_SQSD_RECEIVE_COUNT");

        switch (strtolower($content_type)) {
        case "application/x-www-form-urlencoded":
            break;

        case "application/json":
            // JSONの場合にJSONを展開して値をPOST値に埋め込む
            $body = file_get_contents('php://input');
            if (empty($body)) {
                throw new APP_Exception("data is empty.");
            }

            $json = @json_decode($body, TRUE);
            if (empty($json)) {
                throw new APP_Exception("json decode failed. body is {$body}");
            }

            foreach ($json as $key => $value) {
                $_POST[$key] = $value;
            }

            break;
        }

        $_POST["aws_sqsd_msgid"] = $aws_sqsd_msgid;
        $_POST["aws_sqsd_queue"] = $aws_sqsd_queue;
        $_POST["aws_sqsd_first_received_at"] = $aws_sqsd_first_received_at;
        $_POST["aws_sqsd_receive_count"] = $aws_sqsd_receive_count;

        parent::_remap($method, $params);
    }

    /**
     * @param Exception $e
     *
     * @return bool|void
     */
    public function _catch_exception($e)
    {
        // 例外時は必ずHTTPステータスコードを500で返すように書き換え
 
        if (! $e instanceof APP_Exception) {
            log_exception("ERROR", $e);
        }
        $this->output->set_status_header(500);
    }

}
