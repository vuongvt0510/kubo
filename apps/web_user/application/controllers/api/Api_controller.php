<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/application_controller.php';

/**
 * APIコントローラ
 *
 * 全てのAPIコントローラはこのクラスを継承する
 *
 * @package Controller
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Tomoyuki Kakuda <kakuda@interest-marketing.net>
 */
class Api_controller extends Application_controller
{
    public $is_api = TRUE;


    public function _render_error($message, $submessage, $status = '500', $options = [])
    {
        if (!isset($options['format']) || empty($options['format'])) {
            $options['format'] = 'json';
        }

        parent::_render_error($message, $submessage, $status, $options);
    }


    public function _rendercontent($data = array(), $template_path = NULL)
    {
        $this->_skip_action();
        if ( ! isset($template_path)) {
            return [];
        }
        // assign dateformat
        $data['dformat'] = '%Y.%-m.%-d %-H:%M';
        // テンプレートエンジンの選定
        $engine =& $this->_template_engine();
        return $engine->view($template_path, $data, TRUE);
    }
}
