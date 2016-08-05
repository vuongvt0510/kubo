<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Inquiry Controller
 *
 * @author DiepHQ
 */
class Inquiry extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * Send question to admin site AS10
     */
    public function index()
    {
        $user_mail = $this->current_user->email;
        $view_data = [
            'form_errors' => []
        ];
        $view_data['email'] = $user_mail;

        if ($this->input->is_post()) {

            // Call API to create question
            $res = $this->_api('contact')->send_question([
                'type' => $this->input->post('type'),
                'question' => $this->input->post('question'),
                'user_id' => (int) $this->current_user->id
            ]);

            if (isset($res['result'])) {
                $this->_flash_message('お問い合わせを受付けました');
                return redirect('setting');
            }

            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();
        }

        $this->_render($view_data);
    }
    
}
