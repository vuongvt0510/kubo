<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * File download
 *
 * @property File_model file_model
 *
 * @author DuyTT <duytt@nal.vn>
 */
class File extends Application_controller
{

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['get']
        ]);
    }

    /**
     * Get file data by key
     * @param string $key
     */
    public function get($key = null)
    {
        $this->load->model('file_model');

        /** @var object $file */
        $file = $this->file_model->find_by(['key' => $key]);

        if (empty($file)) {
            set_status_header(404);
            return;
        }

        session_cache_limiter('none');

        $mtime = strtotime($file->updated_at);
        $cache_file_name = $file->key;

        $gmt_mtime = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
        $etag = sprintf('%08x-%08x', crc32($cache_file_name), $mtime);

        header('ETag: "' . $etag . '"');
        header('Last-Modified: ' . $gmt_mtime);
        header('Cache-Control: private');
        header("Content-type: " . $file->content_type);
        header("Content-length: " . $file->size);

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

        echo $file->data;
    }
}

