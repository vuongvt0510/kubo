<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Image Control API
 *
 * @property object load
 * @property Image_Model image_model
 * @property APP_Upload upload
 */
class Image_api extends Base_api
{

    /**
     *
     * Get image from database
     * @param array $params
     * @return void
     */
    public function get_image($params = [])
    {
        $this->load->model('image_model');
        $this->load->helper('url');

        $res = $this->image_model->find_by_key($params['key'], !empty($params['type']) ? $params['type'] : 'original');

        if (!$res) {
            die('Image not exists');
        }

        session_cache_limiter('none');

        $mtime = strtotime($res->updated_at);
        $cache_file_name = $res->key;
        header(sprintf('Content-type: %s', $res->content_type));

        $gmt_mtime = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
        $etag = sprintf('%08x-%08x', crc32($cache_file_name), $mtime);

        header('ETag: "' . $etag . '"');
        header('Last-Modified: ' . $gmt_mtime);
        header('Cache-Control: private');


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

        echo $res->data;
    }

    /**
     * @param array $params
     *
     * @return array|void
     */
    public function upload($params = [])
    {
        $this->load->model('image_model');
        $this->load->helper('url');

        $this->load->library('upload', [
            'allowed_types' => 'jpg|jpeg|gif|png',
            'upload_path' => ini_get('upload_tmp_dir'),
            'max_size' => 3072
        ]);

        if (!$this->upload->is_upload('image')) {
            return $this->false_json(self::BAD_REQUEST);
        }

        if (FALSE === $this->upload->do_upload('image')) {
            return $this->false_json(self::INVALID_PARAMS, implode("\n", $this->upload->error_msg));
        }

        $data = $this->upload->data();

        $this->image_model->create([
            'path' => $data['full_path'],
            'source_url' => empty($params['source']) ? NULL : $params['source']
        ], ['hold_file' => FALSE]);

        return $this->true_json();
    }
}
