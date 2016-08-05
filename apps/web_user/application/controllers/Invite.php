<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Invite Controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class Invite extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * Invite user page Spec ST-50
     */
    public function index()
    {
        $view_data = [
            'form_errors' => []
        ];
        // Check input data
        if ($this->input->is_post()) {

            // Call API to register for parent
            $res = $this->_api('user')->send_invite([
                'email' => $this->input->post('email')
            ]);

            if ($res['success'] && !isset($res['invalid_fields'])) {
                $this->_flash_message('招待メールを送信しました');
                redirect('setting');
                return;
            }
            // Show error if form is incorrect
            $view_data['form_errors'] =  isset($res['invalid_fields']) ? $res['invalid_fields'] : [];

            $view_data['post'] = $this->input->post();
        }

        $this->_render($view_data);
    }
}