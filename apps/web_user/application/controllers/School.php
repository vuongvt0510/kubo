<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';
require_once SHAREDPATH . 'controllers/modules/APP_STV_setting_helper.php';
/**
 * School controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class School extends Application_controller
{
    public $layout = "layouts/base";
    use APP_STV_setting_helper;

    /**
     * Index Spec SC-10
     */
    public function search($user_id = '')
    {
        $view_data['user_id'] = $user_id;

        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $user_id = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;
        } else {
            $user_id = $user_id == null ? $this->current_user->id : $user_id;
        }

        $view_data['chosen_school'] = $this->get_current_school($user_id);

        // Display school searching result
        if ($this->session->userdata('school_search')) {

            // Call api to get detail school
            $res = $this->_api('school')->get_detail([
                'id' => $this->session->userdata('school_search')
            ]);

            if ($res['result']) {
                $view_data['chosen_school'] = $res['result'];
            }
        }

        if (!empty($this->input->post('grade_id')) && empty($this->input->post('update_school_id'))) {

            $update_grade = $this->_api('user_grade')->update([
                'id' => $user_id,
                'grade_id' => $this->input->post('grade_id')
            ]);

            // Set default textbook for user first login
            $user_textbook = $this->_api('user_textbook')->get_list([
                'user_id' => $user_id,
                'grade_id' => (int)$this->input->post('grade_id')
            ]);

            if (empty($user_textbook['result']['items'])) {

                $subject_list = $this->_api('subject')->get_list([
                    'grade_id' => (int)$this->input->post('grade_id')
                ]);

                $s_list = [];
                foreach ($subject_list['result']['items'] as $k) {
                    $s_list[] = $k['id'];
                }

                // Get the most popular subject
                $most_popular_subject = $this->_api('video_textbook')->get_most_popular([
                    'subject_id' => implode(',', $s_list)
                ]);

                if ($most_popular_subject['result']['items']) {
                    $most_popular_subject = array_slice($most_popular_subject['result']['items'], 0, 7);

                    foreach ($most_popular_subject as $tb) {
                        $this->_api('user_textbook')->create([
                            'user_id' => $user_id,
                            'textbook_id' => $tb['textbook']['id'],
                        ]);
                    }
                }
            }

            if (isset($update_grade['result'])) {
                $this->_flash_message('学校を設定しました');
                $this->session->unset_userdata('school_search');
                $this->session->set_flashdata('get_trophy', $update_grade['result']['trophy']);
                $this->session->set_flashdata('get_point', $update_grade['result']['point']);
                redirect('setting');
                return;
            }
        }
        // Update grade
        if ($this->input->post('grade_id') && $this->input->post('update_school_id')) {
            $group_type = $view_data['chosen_school']['type'];

            if((($group_type == 'juniorhigh' || $group_type == 'secondary') && $this->input->post('grade_id') > 6) || ($group_type == 'elementary' && $this->input->post('grade_id') <= 6)) {

                $update_grade = $this->_api('user_grade')->update([
                    'id' => $user_id,
                    'grade_id' => $this->input->post('grade_id')
                ]);

                // Update school for user
                $update_school = $this->_api('user_school')->update([
                    'school_id' => (int)$this->input->post('update_school_id'),
                    'user_id' => $user_id
                ]);

                // Set default textbook for user first login
                $user_textbook = $this->_api('user_textbook')->get_list([
                    'user_id' => $user_id,
                    'grade_id' => (int)$this->input->post('grade_id')
                ]);

                if (empty($user_textbook['result']['items'])) {

                    $subject_list = $this->_api('subject')->get_list([
                        'grade_id' => (int)$this->input->post('grade_id')
                    ]);

                    $s_list = [];
                    foreach ($subject_list['result']['items'] as $k) {
                        $s_list[] = $k['id'];
                    }

                    // Get the most popular subject
                    $most_popular_subject = $this->_api('video_textbook')->get_most_popular([
                        'subject_id' => implode(',', $s_list)
                    ]);

                    if ($most_popular_subject['result']['items']) {
                        $most_popular_subject = array_slice($most_popular_subject['result']['items'], 0, 7);

                        foreach ($most_popular_subject as $tb) {
                            $this->_api('user_textbook')->create([
                                'user_id' => $user_id,
                                'textbook_id' => $tb['textbook']['id'],
                            ]);
                        }
                    }
                }

                if (isset($update_school['result']) && isset($update_grade['result'])) {
                    // Change textbook by school
                    $this->_setting_school_subject($this->input->post('update_school_id'), $this->input->post('grade_id'), $user_id);
                    $this->_flash_message('学校を設定しました');
                    $this->session->unset_userdata('school_search');
                    $this->session->set_flashdata('get_trophy', $update_grade['result']['trophy']);
                    $this->session->set_flashdata('get_point', $update_grade['result']['point']);
                    redirect('setting');
                    return;
                }
            } else {
                $view_data['errmsg'] = '学年が間違っています';
            }
        }

        $grades = $this->_api('grade')->get_list();
        // Selection grade
        if (!empty($grades['result']['items'])) {
            $view_data['grades']['all'] = $grades['result']['items'];
        }

        $user = $this->_api('user')->get_detail(['id' => $user_id]);

        $view_data['gender'] = $user['result']['gender'];
        if (!empty($user['result']['current_grade'])) {
            $view_data['grade'] = $user['result']['current_grade']['id'];
        } else {
            $view_data['student_has_no_grade'] = TRUE;
        }
        if ($this->input->post('grade_id')) {
            $view_data['grade'] = $this->input->post('grade_id');
        }
        $this->_render($view_data);
    }

    /**
     * Index Spec SC-20
     */
    public function search_postalcode($user_id = '')
    {

        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $user_id = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;
        } else {
            $user_id = $user_id == null ? $this->current_user->id : $user_id;
        }

        $view_data = [
            'form_errors' => []
        ];

        $view_data['user_id'] = $user_id;

        $view_data['chosen_school'] = $this->get_current_school($user_id);

        // Display search result
        if ($this->input->is_post() && empty($this->input->post('school_id'))) {

            $res = $this->_api('school')->search($this->input->post());

            // Show error if form is incorrect
            $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : "" ;

            if ($res['result']) {
                $view_data['list_schools'] = $res['result']['items'];
                $view_data['post'] = $this->input->post();
            }
        }

        if ($this->input->post('school_id')) {
            $this->session->set_userdata('school_search', $this->input->post('school_id'));
            redirect($this->current_user->id == $user_id ? 'school/search/' : 'school/search/'.$user_id);
            return;
        }

        $this->_render($view_data);
    }

    /**
     * Index Spec SC-30
     */
    public function search_keyword($user_id = '')
    {
        $view_data = [
            'form_errors' => []
        ];

        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $user_id = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;
        } else {
            $user_id = $user_id == null ? $this->current_user->id : $user_id;
        }

        $view_data['user_id'] = $user_id;

        $view_data['chosen_school'] = $this->get_current_school($user_id);


        // Get list prefectures
        $res = $this->_api('prefecture')->get_list();
        if ($res['result']) {
            $view_data['prefs'] = $res['result']['items'];
        }

        // Display search result
        if ($this->input->is_post() && empty($this->input->post('school_id'))) {

            $schools = $this->_api('school')->search($this->input->post());

            $areas = $this->_api('area')->get_list(['pref_id' => $this->input->post('pref_id')]);
            $view_data['areas'] = isset($areas['result']['items']) ? $areas['result']['items'] : [];

            $pref_error = isset($areas['invalid_fields']) ? $areas['invalid_fields'] :[];

            // Error form
            $view_data['form_errors'] = isset($schools['invalid_fields']) ? array_merge($pref_error, $schools['invalid_fields']) : $pref_error;
            $view_data['post'] = $this->input->post();

            if (isset($schools['result'])) {
                $view_data['list_schools'] = $schools['result']['items'];
            }
        }

        if ($this->input->post('school_id')) {
            $this->session->set_userdata('school_search', $this->input->post('school_id'));
            redirect($this->current_user->id == $user_id ? 'school/search/' : 'school/search/'.$user_id);
            return;
        }
        $this->_render($view_data);
    }

    /**
     * Get list of areas by prefecture id
     */
    public function get_list_areas()
    {
        $res = $this->_api('area')->get_list($this->input->post());

        $this->_build_json($res['result']);
    }

    /**
     * Get current school of current user
     */
    public function get_current_school($school_id = null)
    {
        $res = $this->_api('user')->get_detail([
            'id' => $school_id
        ]);
        return $res['result']['current_school'];
    }
}