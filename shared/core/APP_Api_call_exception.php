<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * API 呼出 例外クラス
 *
 * @package APP\Controller
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 * @uses APP_Api
 */
class APP_Api_call_exception extends APP_Exception
{
    /**
     * API名
     * @var string $api
     */
    public $api = NULL;

    /**
     * メソッド名
     * @var string $method
     */
    public $method = NULL;

    /**
     * レスポンス
     * @var array $response
     */
    public $response = NULL;

    /**
     * ロギングレベル
     *
     * @var string
     */
    protected $log_level = 'error';

    /**
     * レスポンスを持っているか
     *
     * @var string
     * @return bool
     */
    public function has_response()
    {
        return !is_null($this->response);
    }

    /**
     * 例外を持っているか
     *
     * @var string
     * @return bool
     */
    public function has_exception()
    {
        return !is_null($this->getPrevious());
    }

    /**
     * コンストラクタ
     *
     * @param string $api
     * @param int $method
     * @param object $response
     * @param object $previous
     */
    public function __construct($api, $method, $response = NULL, $previous = NULL)
    {
        $this->api = $api;
        $this->method = $method;
        $this->response = $response;

        if (!is_null($previous)) {

            $errcode = APP_Api::UNKNOWN_ERROR;
            $errmsg = sprintf("[API:%s/%s] threw exception '%s' (%d) with message '%s'",
                $api, $method, get_class($previous), $previous->getCode(), $previous->getMessage());

        } else if (!is_null($response)) {

            if ($response['success'] && ! $response['submit']) {
                $this->log_level = 'error';

                $errcode = APP_Api::INVALID_PARAMS;
                $errmsg = sprintf("[API:%s/%s] submit error. invalid fields is %s.", $api, $method, json_encode($response['invalid_fields']));
            } else {

                $errcode = $response['errcode'];
                $errmsg = sprintf("[API:%s/%s] response error. errcode is %s.", $api, $method, $response['errcode']);

                switch (APP_Api::status_type($response['errcode']))
                {
                case APP_Api::TYPE_CLIENT_ERROR:
                    $this->log_level = 'debug';
                    break;

                default:
                    $this->log_level = 'error';
                    break;
                }
            }

        } else {
            throw new InvalidArgumentException('response or previous is not set.');
        }

        parent::__construct($errmsg, $errcode, $previous);
    }

    /**
     * ロギング
     *
     * @param array $options
     */
    protected function logging($options = array())
    {
        $message = sprintf("%s in %s:%d",
            $this->getMessage(),
            $this->getFile(),
            $this->getLine());

        log_message($this->log_level, $message);
    }

}
