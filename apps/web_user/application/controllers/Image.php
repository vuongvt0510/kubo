<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * 画像コントローラ
 *
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class Image extends Application_controller
{

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['show']
        ]);
    }

    /**
     * @param string $key
     * @param string $type
     */
    public function show($key = null, $type = null)
    {
        $this->load->model('image_model');

        if (empty($type)) {
            $type = 'original';
        }

        /** @var object $image */
        $image = $this->image_model->find_by(['key' => $key, 'type' => $type]);

        if (empty($image)) {
            $cache_file_name = APPPATH.'../public_html/images/no-image.png';
            $data = file_get_contents($cache_file_name);
            $image = (object)[
                'key' => $key ? $key : 'no_image_logo',
                'data' => $data,
                'updated_at' => filemtime($cache_file_name),
                'content_type' => 'image/png',
                'size' => filesize($cache_file_name)
            ];
        }

        session_cache_limiter('none');

        $mtime = strtotime($image->updated_at);
        $cache_file_name = $image->key;

        $gmt_mtime = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
        $etag = sprintf('%08x-%08x', crc32($cache_file_name), $mtime);

        header('ETag: "' . $etag . '"');
        header('Last-Modified: ' . $gmt_mtime);
        header('Cache-Control: private');
        header("Content-type: " . $image->content_type);
        header("Content-length: " . $image->size);

        if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && !empty($_SERVER['HTTP_IF_NONE_MATCH']))
        {
            $tmp = explode(';', $_SERVER['HTTP_IF_NONE_MATCH']); // IE fix!
            if(!empty($tmp[0]) && strtotime($tmp[0]) == strtotime($gmt_mtime))
            {
                header('HTTP/1.1 304 Not Modified');
                return;
            }
        }

        if(isset($_SERVER['HTTP_IF_NONE_MATCH']))
        {
            if(str_replace(array('\"', '"'), '', $_SERVER['HTTP_IF_NONE_MATCH']) == $etag)
            {
                header('HTTP/1.1 304 Not Modified');
                return;
            }
        }

        echo $image->data;
    }
}

