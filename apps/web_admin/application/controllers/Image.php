<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * 画像コントローラ
 *
 * @property object uri
 */
class Image extends Application_controller
{

    /**
     * Get saved image from database
     */
    public function show()
    {
        $key = $this->uri->segment(2);
        $type = $this->uri->segment(3);

        $this->_api('image')->get_image([
            'key' => $key,
            'type' => $type
        ]);
    }

    /**
     * Upload Video from GUI
     */
    public function upload()
    {
        $params = $this->input->post();

        $this->_api('image')->upload($params);
        $this->_render();
    }
}
