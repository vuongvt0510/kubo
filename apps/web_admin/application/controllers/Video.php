<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Video Controller
 *
 * @property object uri
 */
class Video extends Application_controller
{

    /**
     * Get saved image from database
     */
    public function show()
    {
        $key = $this->uri->segment(2);
        $type = $this->uri->segment(3);

        $this->_api('video')->get_video([
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

        $this->_api('video')->upload($params);
        $this->_render();
    }
}
