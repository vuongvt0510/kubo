<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'third_party/google-api-php-client-1.1.6/src/Google/autoload.php';

/**
 * Google API Base Library
 */
class Google_base{

    /**
     * @var Google_Client client object
     */
    private $client;

    /**
     * @var object Google management class
     */
    private $instance;

    /**
     * @var array config
     */
    protected $config;

    /**
     * Google_base constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->client = new Google_Client();
        $this->instance = NULL;
        $this->config = NULL;
    }

    /**
     * Google API の認証設定
     * @param string $scope
     * @return bool
     * @throws Google_Exception_api
     */
    public function authorize($scope = '')
    {
        $files = array(
            SHAREDPATH . "config/google.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/google.php",
            APPPATH . "config/google.php",
            APPPATH . "config/" . ENVIRONMENT . "/google.php"
        );

        foreach ($files as $f) {
            if (is_file($f)) {
                include $f;
            }
        }

        /** @var array $google */
        $this->config = $google;

        $this->client->setAccessType('offline');

        if (!empty($google['client_id'])) {
            $this->client->setClientId($google['client_id']);
        }

        if( isset($google['server_key_location']) && !empty($google['server_key_location']) )
        {
            $this->client->setApplicationName( $this->get_app_name() );

            // Service Account Key を指定されている場合
            $keys = file_get_contents($google['server_key_location']);
            if( empty($keys) ){
                throw new Google_Exception_api("Service Account Key don't Reading.", -1);
            }

            $cred = FALSE;
            if( strpos($google['server_key_location'], '.json') === FALSE ){
                $cred = new Google_Auth_AssertionCredentials($this->client->getApplicationName(), $scope, $keys);
                $this->client->setAssertionCredentials($cred);
            }
            else{
                $this->client->setScopes($scope);
                $this->client->setAuthConfig($keys);
            }

            unset($cred, $key);
        }
        else if( isset($google['api_key']) && !empty($google['api_key']) )
        {
            // API Key を指定されている場合
            $this->client->setDeveloperKey($google['api_key']);
        }

        return TRUE;
    }

    /**
     * インスタンスの取得
     *
     * @param string $class クラス名
     * @return object
     */
    protected function getInstance($class = '')
    {
        if( $this->instance ){
            return $this->instance;
        }

        $this->instance = new $class($this->client);

        return $this->instance;
    }

    /**
     * application Name
     *
     * @return string
     */
    protected function get_app_name()
    {
        return $this->config['app_name'];
    }
}

class Google_Exception_api extends APP_Exception
{
}
