<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/application_controller.php';

/**
 * API Controller
 *
 * All API Controller should extend this class
 *
 * @package Controller
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class Api_controller extends Application_controller
{
    /**
     * @var bool
     */
    public $is_api = TRUE;

    /**
     * @param string $message
     * @param string $submessage
     * @param string $status
     * @param array $options
     */
    public function _render_error($message, $submessage, $status = '500', $options = [])
    {
        if (!isset($options['format']) || empty($options['format'])) {
            $options['format'] = 'json';
        }

        parent::_render_error($message, $submessage, $status, $options);
    }

    /**
     * @param array $data
     * @param string $template_path
     * @return array
     */
    public function _render_content($data = array(), $template_path = NULL)
    {
        $this->_skip_action();
        if ( ! isset($template_path)) {
            return [];
        }

        // テンプレートエンジンの選定
        $engine =& $this->_template_engine();
        return $engine->view($template_path, $data, TRUE); 
    }

}
