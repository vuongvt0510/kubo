<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Brightcove_video {

    /**
     * Token for write in cloud.
     *
     * @var string
     */
    public $token_read = NULL;

    /**
     * Token for read in cloud.
     *
     * @var string
     */
    public $token_write = NULL;

    /**
     * Url for read in cloud.
     *
     * @var string
     */
    public $read_url = NULL;

    /**
     * Url for write in cloud.
     *
     * @var string
     */
    public $write_url = NULL;

    /**
     * Brightcove constructor.
     */
    public function __construct() {
        $files = array(
            SHAREDPATH . "config/brightcove.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/brightcove.php",
            APPPATH . "config/brightcove.php",
            APPPATH . "config/" . ENVIRONMENT . "/brightcove.php"
        );

        foreach ($files as $f) {
            if (is_file($f)) {
                include $f;
            }
        }

        if (!empty($brightcove)) {
            foreach ($brightcove as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    /**
     * Get the video details from brightcove
     *
     * @param string $video_id
     *
     * @return array
     */
    public function get_detail($video_id = NULL){

        $query = $this->_build_request( [
            'command' => 'find_video_by_id',
            'video_id' => $video_id,
            'token' => $this->token_read
        ]);

        return $this->_request('GET' ,NULL , ['url' => $query]);
    }

    /**
     * Send the request to server
     *
     * @param string $method POST|GET
     * @param array $params this one use for post method
     * @param array $options
     *
     * @return array
     */
    private function _request($method = 'POST', $params = [], $options = []) {

        // Create the instance
        $curl = curl_init();

        // Parse the url
        $url = ($method == 'POST') ? $this->write_url : $this->read_url;

        if(array_key_exists('url', $options)) {
            $url .= $options['url'];
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);

        switch ( $method ) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                curl_setopt($curl, CURLOPT_VERBOSE, TRUE );
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 300);
                curl_setopt($curl, CURLOPT_TIMEOUT, 300);
                curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
                break;
            case 'GET':
                break;
        }

        // Send the request
        $response = curl_exec($curl);

        // Close the curl
        curl_close($curl);

        // Responses are transfered in JSON, decode into PHP object
        $json = json_decode($response);

        // Return the response
        if(isset($json->error))	{
            return $json->error;
        } else {
            return $json;
        }
    }

    /**
     * Upload the video
     *
     * @param binary $file
     * @param array $post_fields
     * @internal param string $name video name
     * @internal param string $shortDescription video description
     *
     * @return array
     */
    public function createVideo($file = NULL, $post_fields = []) {
        $request = array();
        $post = array();
        $params = array();
        $video = array();

        foreach($post_fields as $key => $value) {
            $video[$key] = $value;
        }
        $params['token'] = $this->token_write;
        $params['video'] = $video;

        $post['method'] = 'create_video';
        $post['params'] = $params;

        $request['json'] = json_encode($post);

        if($file) {
            $request['file'] = new CurlFile($file['tmp_name'], $file['type'], $file['name']);
        }

        return $this->_request('POST', $request);
    }

    /**
     * Build the query request for GET method
     *
     * @param array $params
     *
     * @return String
     */
    private function _build_request($params = []){
        return http_build_query($params);
    }
}
