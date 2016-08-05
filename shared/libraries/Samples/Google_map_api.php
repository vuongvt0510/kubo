<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "libraries/APP_External_api.php";

/**
 * 外部APIサンプル - Google マップAPI
 *
 * @author Yoshikazu Ozawa
 */
class Google_map_api extends APP_External_api {

    /**
     * 接続先ドメイン
     * @var string
     */
    protected $domain = "http://maps.googleapis.com/maps/api/";

    /**
     * ジオコードAPI
     *
     * @access public
     * @param array $params
     * @return array
     * @exception APP_External_api_exception
     */
    public function geocode($params, $options = array())
    {
        return $this->call("GET", "geocode/json", $params, $options);
    }


    protected function call($method, $path, $params = array(), $options = array())
    {
        $url = $this->domain . $path;

        $data = parent::call($method, $url, $params, $options);

        if ($this->last_http_code() != 200) {
            throw new APP_External_api_http_status_exception($this);
        }

        $data = json_decode($data, TRUE);

        if ($data['status'] != "OK") {
            throw new Google_map_api_exception($this, $data);
        }

        return $data;
    }
}


/**
 * Google マップ API 例外クラス
 *
 * @author Yoshikazu Ozawa
 */
class Google_map_api_exception extends APP_External_api_exception {

    /**
     * APIレスポンスステータス
     * @var string
     */
    public $last_status = NULL;

    public function __construct($base, $data)
    {
        $this->last_status = $data['status'];
        $message = "response status is illegal [{$this->last_status}]";

        parent::__construct($base, $message, 99);
    }
}

