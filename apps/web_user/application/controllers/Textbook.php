<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';
require_once SHAREDPATH . 'controllers/modules/APP_STV_setting_helper.php';
/**
 * Textbook controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class Textbook extends Application_controller
{
    public $layout = "layouts/base";
    use APP_STV_setting_helper;

    /**
     * Current user textbook page Spec TB-40
     */
    public function index($user_id = '')
    {
        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $user_id = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;
        } else {
            $user_id = $user_id == null ? $this->current_user->id : $user_id;
        }

        $users = $this->_api('user')->get_detail( [
            'id' => $user_id
        ]);

        $res = $this->_api('user_textbook')->get_list([
            'user_id' => $user_id,
            'grade_id' => $users['result']['current_grade']['id']
        ]);

        $res = $this->_sort_subject($res['result']['items']);

        // Is referrer from ST10 page
        $is_from_st10 = $this->agent->referrer() == site_url('setting');

        $this->_render([
            'list_textbooks' => $res,
            'is_from_st10' => $is_from_st10,
            'user_id' => ($user_id == $this->current_user->id) ? null : $user_id,
            'current_user_primary_type' => $this->current_user->primary_type
        ]);
    }

    /**
     * Textbook suggestion page Spec TB-10
     */
    public function search()
    {
        $view_data = [];

        $res = $this->_api('user')->get_detail( [
            'id' => $this->current_user->_operator_id()
        ]);

        if(isset($res['result']['current_school']['id'])) {
            $res = $this->_api('textbook')->search([
                'school_id' => $res['result']['current_school']['id']
            ]);
        }
        if(empty($res['result']['items'])) {
            $res = $this->_api('textbook')->search([
                'most_view' => true
            ]);
        }

        $view_data['textbooks'] = $res['result']['items'];

        $this->_render($view_data);
    }

    /**
     * Textbook searching page Spec TB-20
     *
     * @param string $keyword
     */
    public function search_keyword($keyword = '')
    {
        // Get user_id from url
        $user_id = $this->input->get('user_id');
        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $user_id = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;
        } else {
            $user_id = $user_id == null ? $this->current_user->id : $user_id;
        }

        $keyword = !empty($keyword) ? urldecode($keyword) : '';
        // Process if user post textbook selected
        $select_textbook = (int) $this->input->post('textbook');
        $subject_id = (int) $this->input->get('subject_id');

        if($this->input->post()) {

            if($select_textbook) {
                if($this->input->get('textbook_id')) {
                    $update_res = $this->_api('user_textbook')->update([
                        'user_id' => $user_id,
                        'textbook_id' => $this->input->get('textbook_id'),
                        'new_textbook_id' => $select_textbook
                    ]);

                    if(isset($update_res['success'])) {
                        $url = !empty($this->input->get('user_id')) ? 'textbook/'.$user_id : 'textbook';
                        redirect($url);
                        return;
                    }
                } else {
                    $create_res = $this->_api('user_textbook')->create([
                        'user_id' => $user_id,
                        'textbook_id' => $select_textbook
                    ]);

                    if(isset($create_res['result']['id'])) {
                        $this->session->set_flashdata('get_point', $create_res['result']['point']);
                        redirect('textbook/search');
                        return;
                    }
                }
            }
        }

        // Process search textbook page
        $view_data = [
            'keyword' => $this->input->post_or_default('keyword', $keyword),
            'list_textbooks' => []
        ];

        $chosen_textbook = $this->_api('textbook')->get_detail([
            'textbook_id' => $this->input->get('textbook_id')
        ]);
        $view_data['chosen_textbook'] = $chosen_textbook['result'];

        if(isset($view_data['keyword'])) {

            $users = $this->_api('user')->get_detail( [
                'id' => $user_id
            ]);

            $res = $this->_api('textbook')->search([
                'keyword' => $view_data['keyword'],
                'grade_id' => $users['result']['current_grade']['id'],
                'subject_id' => $subject_id
            ]);

            // Show error if form is incorrect
            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : "" ;

            if (isset($res['result']['items']) && !empty($res['result']['items'])) {
                $view_data['subject_short_name'] = $res['result']['items'][0]['subject']['short_name'];
                $view_data['list_textbooks'] = $res['result']['items'];
            }
        }

        $view_data['user_id'] = $user_id;
        $this->_render($view_data);
    }

    /**
     * Choose textbook complete page Spec TB-30
     */
    public function complete()
    {
        $view_data = [];

        $view_data['button'] = !$this->input->get('button') ? 'on' : 'off';

        $this->_render($view_data);
    }
}
