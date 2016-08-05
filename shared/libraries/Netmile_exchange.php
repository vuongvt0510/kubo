<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Netmile_exchange
 */
class Netmile_exchange
{
    // Get user login session on netmile URI
    const URL_CHECK_ACCOUNT = 'ctrl/user/CNC.do';

    // Check current mile of user URI
    const URL_CHECK_CURRENT_MILE = "services/UserInfoService/showCurrentMile";


    // Check EmailExist url
    const URL_CHECK_EMAIL_EXIST = 'services/UserInfoService/checkEMailExist';

    // Check netmile encrypt user ID is exist URI
    const URL_CHECK_ENC_USERID = 'services/UserInfoService/checkEncUserIdExist';

    // Public mile URI
    const URL_REQUEST_MILE = 'services/MilePublishService/publish_encid';

    /**
     * @var string|null $site_id
     */
    private $site_id = null;

    /**
     * @var string|null $site_id_encrypt
     */
    private $site_id_encrypt = null;

    /**
     * @var string|null $campaign_id
     */
    private $campaign_id = null;

    /**
     * @var string|null $security_key
     */
    private $security_key = null;

    /**
     * @var string|null $domain
     */
    private $domain = null;

    /**
     * @var string|null $service
     */
    private $service = null;

    /**
     * @var string|null $basic_auth_account
     */
    private $basic_auth_account = null;

    /**
     * @var string|null $basic_auth_password
     */
    private $basic_auth_password = null;

    /**
     * Netmile_exchange constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $files = [
            SHAREDPATH . 'config/netmile.php',
            SHAREDPATH . 'config/' . ENVIRONMENT . '/netmile.php',
            APPPATH . 'config/netmile.php',
            APPPATH . 'config/' . ENVIRONMENT . '/netmile.php'
        ];

        foreach ($files as $f) {
            if (is_file($f)) {
                /** @var string $f */
                include $f;
            }
        }

        if (!empty($netmile)) {
            $params = array_merge($netmile, $params);
        }

        foreach ($params AS $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        // echo "<pre>"; print_r($params); echo "</pre>";
    }

    /**
     * Init curl
     *
     * @param string $url
     *
     * @return object
     */
    public function init($url = null)
    {
        // CURL init
        $url_connect = curl_init();
        // POST method
        curl_setopt($url_connect, CURLOPT_POST, 1);
        // Set URL
        curl_setopt($url_connect, CURLOPT_URL, $url);
        // Set return data
        curl_setopt($url_connect, CURLOPT_RETURNTRANSFER, 1);
        // Configure SSL authentication
        curl_setopt($url_connect, CURLOPT_SSL_VERIFYHOST, 2);
        // Configure verification at server
        curl_setopt($url_connect, CURLOPT_SSL_VERIFYPEER, false);
        // Configure error return
        curl_setopt($url_connect, CURLOPT_FAILONERROR, false);

        return $url_connect;
    }

    /**
     * Add basic authentication
     *
     * @param object &$url_connect
     *
     * @return object
     */
    public function add_basic_authentication(&$url_connect)
    {
        if ($url_connect && $this->basic_auth_account && $this->basic_auth_password) {
            // Set basic authentication
            curl_setopt($url_connect, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($url_connect, CURLOPT_USERPWD, sprintf("%s:%s", $this->basic_auth_account, $this->basic_auth_password));
        }
    }

    /**
     * Send post data request
     * @param object &$url_connect
     * @param array $params
     * @return object
     */
    public function send_post_data(&$url_connect, $params = [])
    {
        $headers[] = 'Content-type: application/x-www-form-urlencoded; charset=UTF-8';
        $user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)';

        // Set headers
        curl_setopt($url_connect, CURLOPT_HTTPHEADER, $headers);
        // Send POST request
        // Need user http_build_query to form encodeurl params
        curl_setopt($url_connect, CURLOPT_POSTFIELDS, http_build_query($params));
        // Set user agent
        curl_setopt($url_connect, CURLOPT_USERAGENT, $user_agent);
        // Set CURL encode utf-8
        curl_setopt($url_connect, CURLOPT_ENCODING, 'UTF-8');

        $res = curl_exec($url_connect);

        return $res;
    }

    /**
     * Close connect curl
     */
    public function close(&$url_connect)
    {
        if ($url_connect) {
            curl_close($url_connect);
        }
    }

    /**
     * Generate hash confirm code
     * 
     * @param  array $params
     * 
     * @return string        
     */
    public function generate_code($params = [])
    {
        $params = array_merge([
            $this->site_id,
            $this->security_key
        ], $params);

        $string = join('', $params);

        return base64_encode(hash_hmac('SHA256', $string, $this->security_key, TRUE));
    }

    /**
     * Skeleton connect Netmile service
     *
     * @param string $url
     * @param array $params
     *
     * @return array         
     */
    public function connect($url = '', $params = [])
    {
        // Init CURL
        $url_connect = $this->init($url);
        // Add basic authentication
        $this->add_basic_authentication($url_connect);
        // Send data
        $res = $this->send_post_data($url_connect, $params);
        // Close connect
        $this->close($url_connect);
        // Return array
        return $this->parse_xml($res);
    }

    /**
     * Netmile API service check exist encrypt userId
     * @param array $params
     * @return array        
     */
    public function check_enc_user_id_exist($params = [])
    {
        // Set url
        $url = $this->service . self::URL_CHECK_ENC_USERID;

        $date = date('YmdHis');

        // Generate auth code
        $auth_code = $this->generate_code([$date]);

        // Set params to use service
        $params = [
            'earnSiteId' => $this->site_id_encrypt,
            'userId' => $params['enc_user_id'],
            'timestamp' => $date,
            'authcode' => $auth_code
        ];

        // Return data
        return $this->connect($url, $params);
    }

    /**
     * Netmile API service get current mile of user
     * @param array $params
     * @return array 
     */
    public function check_current_mile($params = [])
    {
        // Set url
        $url = $this->service. self::URL_CHECK_CURRENT_MILE;
        $date = date('YmdHis');

        // Generate auth code
        $auth_code = $this->generate_code([
            $date,
            $params['user_id']
        ]);

        // Set params to use service
        $params = [
            'earnSiteId' => $this->site_id_encrypt,
            'userId' => $params['user_id'],
            'timestamp' => $date,
            'authcode' => $auth_code
        ];
        
        // Return data
        return $this->connect($url, $params);
    }

    /**
     * Netmile API serivce publish mile to user
     * @param array $params
     * @return array 
     */
    public function get_mile_publish_encId($params = [])
    {
        // Set url
        $url = $this->service. self::URL_REQUEST_MILE;
        $date = date('YmdHis');

        // Generate auth code
        $auth_code = $this->generate_code([
            $date,
            $params['mile']
        ]);

        // Set params to use service
        $params = [
            'earnSiteId' => $this->site_id_encrypt,
            'campaignId' => $this->campaign_id,
            'userId' => $params['enc_user_id'],
            'mile' => $params['mile'],
            'specificKey' => isset($params['specific_key']) ? $params['specific_key'] : '',
            'displayName' => isset($params['display_name']) ? $params['display_name'] : '',
            'timestamp' => $date,
            'authcode' => $auth_code
        ];

        // Return data
        return $this->connect($url, $params);
    }

    /**
     * API service check login user Netmile
     *
     * @param array $params
     *
     * @return string $url url with param enc_user_id
     */
    public function get_redirect_link($params = [])
    {
        // Set url
        $url = $this->domain. self::URL_CHECK_ACCOUNT;

        // Set params
        $redirect = $url. "?". http_build_query($params);

        return $redirect;
    }

    /**
     * Sync with document Netmile
     *
     * @param string $text
     *
     * @return string
     */
    protected function sync_text($key = '')
    {
        if (!$key) {
            return '';
        }

        $convert_text = [
            'ERRORCODE' => 'errorCode',
            'MESSAGE' => 'message',
            'EXCHANGEABLEMILE' => 'exchangeableMile',
            'RESERVATIONMILE' => 'reservationMile',
            'EMAILKIND' => 'emailKind',
            'USERID' => 'userId',
            'PUBLISHID' => 'publishId',
            'PUBLISHMILE' => 'publishMile',
            'CANCELMILE' => 'cancelMile',
            'CANCELPUBLISHID' => 'cancelPublishId',
            'TAATTENDID' => 'taAttendId',
            'PUBLISHRESPONSE' => 'publishResponse',
            'PUBLISH_ENCIDRESPONSE' => 'publish_encidResponse',
            'CANCELRESPONSE' => 'cancelResponse',
            'CHECKEMAILEXISTRESPONSE' => 'checkEMailExistResponse',
            'SHOWCURRENTMILERESPONSE' => 'showCurrentMileResponse',
            'CHECKENCUSERIDEXISTRESPONSE' => 'checkEncUserIdExistResponse',
            'ATTENDRESPONSE' => 'attendResponse'
        ];
        return $convert_text[$key];
    }

    /**
     * Parse xml data
     *
     * @param string $xml
     *
     * @return array|bool
     */
    protected function parse_xml($xml = null)
    {
        if (!$xml) {
            return FALSE;
        }
        
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $xml, $values);
        xml_parser_free($xml_parser);

        $result = [];

        foreach ($values AS $key => $value) {

            switch ($value['type']) {

                case 'complete':
                    // Get key
                    $k = substr($value['tag'], 5);
                    $k = $this->sync_text($k);

                    // Add result
                    $result[$k] = isset($value['value']) ? $value['value'] : null;
                    break;

                case 'open':
                    if ($value['level'] == 1) {
                        // Get type
                        $type = substr($value['tag'], 3);
                        $type = $this->sync_text($type);
                        $result['type'] = $type;
                    }
            }
        }

        return $result;
    }
}
