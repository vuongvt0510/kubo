<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . '/third_party/Brightcove/autoload.php';

use Brightcove\API\Client;
use Brightcove\API\CMS;
use Brightcove\API\DI;
use Brightcove\API\PM;
use Brightcove\API\Exception\AuthenticationException;

class Brightcove_studio {

    /**
     * OAuth2 client id.
     *
     * @var string
     */
    protected $client_id;

    /**
     * OAuth2 client secret.
     *
     * @var string
     */
    protected $client_secret;

    /**
     * Brightcove account ID.
     *
     * @var string
     */
    protected $account;

    /**
     * A local address on which a PHP webserver can be started.
     *
     * @see waitForHTTPCallback()
     * @see startServer()
     *
     * @var string
     */
    protected $callback_host;

    /**
     * A remote address which could be used for HTTP callbacks.
     *
     * @see waitForHTTPCallback()
     *
     * @var string
     */
    protected $callback_addr_remote;

    /**
     * A Brightcove client to be used with the endpoint wrapper classes.
     *
     * @var Client
     */
    protected $client;

    /**
     * A wrapper instance on the CMS API.
     *
     * @var CMS
     */
    protected $cms;

    /**
     * A wrapper instance on the DI API.
     *
     * @var DI
     */
    protected $di;

    /**
     * A wrapper instance on the PM API.
     *
     * @var PM
     */
    protected $pm;

    /**
     * Brightcove constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
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
            $params = array_merge($brightcove, $params);
        }

        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        $this->authenticate_client();
    }

    /**
     * authenticate client
     */
    public function authenticate_client()
    {
        $this->client = $this->getClient();
        $this->cms = $this->createCMSObject($this->client);
        $this->di = $this->createDIObject($this->client);
        $this->pm = $this->createPMObject($this->client);
    }

    /**
     * Creates a new authorized client instance.
     *
     * @return Client
     * @throws AuthenticationException
     */
    protected function getClient() {
        return Client::authorize($this->client_id, $this->client_secret);
    }

    /**
     * Creates a new CMS object instance.
     *
     * @param Client $client
     *   The $client instance to use. If NULL, then the client of this class will be used.
     * @return CMS
     */
    protected function createCMSObject(Client $client = NULL) {
        if ($client === NULL) {
            $client = $this->getClient();
        }
        return new CMS($client, $this->account);
    }

    /**
     * Creates a new DI object instance.
     *
     * @param Client $client
     *   The $client instance to use. If NULL, then the client of this class will be used.
     * @return DI
     */
    protected function createDIObject(Client $client = NULL) {
        if ($client === NULL) {
            $client = $this->getClient();
        }
        return new DI($client, $this->account);
    }

    /**
     * Creates a new PM object instance.
     *
     * @param Client $client
     *   The $client instance to use. If NULL, then the client of this class will be used.
     * @return PM
     */
    protected function createPMObject(Client $client = NULL) {
        if ($client === NULL) {
            $client = $this->getClient();
        }
        return new PM($client, $this->account);
    }

    /**
     * Get video images
     *
     * @param string $video_id
     * @return \Brightcove\Object\Video\Images
     */
    public function get_video_images($video_id = '')
    {
        return $this->cms->getVideoImages($video_id);
    }

    /**
     * Get video image link by type
     *
     * @param string $video_id
     * @param string $type (poster|thumbnail)
     * @param string $protocol (http|https)
     *
     * @return bool|array
     */
    public function get_video_image_link($video_id = '', $type = 'poster', $protocol = 'https')
    {
        $image = $this->get_video_images($video_id);

        if (empty($image)) {
            return FALSE;
        }

        $image_data = $type == 'thumbnail' ? $image->getThumbnail() : $image->getPoster();

        return $protocol == 'http' ? $image_data['sources'][0]['src'] : $image_data['sources'][1]['src'];
    }

    /**
     * Get video information
     *
     * @param string $video_id
     * @return \Brightcove\Object\Video\Video
     */
    public function get_video($video_id = '')
    {
        return $this->cms->getVideo($video_id);
    }
}
