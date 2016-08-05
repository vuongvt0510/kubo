<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';
require_once SHAREDPATH . 'controllers/modules/APP_STV_setting_helper.php';

/**
 * トップ コントローラ
 *
 */
class School extends Application_controller
{
    use APP_STV_setting_helper;

    /*
     * Search school by postal code
     */
    public function search_by_postal_code()
    {
        $errors = [];

        if ($this->input->is_post()) {

            $res = $this->_api('school')->search([
                'postal_code' => ltrim($this->input->post('postal_code'))
            ]);

            if (isset($res['result'])) {
                return $this->_true_json($res['result']['items']);
            }

            $errors = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];
        }

        return $this->_build_json($errors);
    }

    public function get_list()
    {
        $this->_render();
    }


    public function index()
    {
        $this->_render();
    }

    public function update()
    {

        if (!empty($this->input->post('grade_id')) && empty($this->input->post('school_id'))) {

            $update_grade = $this->_api('user_grade')->update([
                'id' => $this->input->post('user_id'),
                'grade_id' => $this->input->post('grade_id')
            ]);

            // Set default textbook for user first login
            $user_textbook = $this->_api('user_textbook')->get_list([
                'user_id' => $this->input->post('user_id'),
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
                            'user_id' => $this->input->post('user_id'),
                            'textbook_id' => $tb['textbook']['id'],
                        ]);
                    }
                }
            }
            if (isset($update_grade['result'])) {
                $this->_flash_message('学校を設定しました');
                return $this->_build_json(true);
            }
        }
        // Update grade
        if ($this->input->post('grade_id') && $this->input->post('school_id')) {

            $group_type = $this->_api('school')->get_detail([
                'id' => $this->input->post('school_id')
            ])['result']['type'];

            if((($group_type == 'juniorhigh' || $group_type == 'secondary') && $this->input->post('grade_id') > 6) || ($group_type == 'elementary' && $this->input->post('grade_id') <= 6)) {

                $update_grade = $this->_api('user_grade')->update([
                    'id' => $this->input->post('user_id'),
                    'grade_id' => $this->input->post('grade_id')
                ]);

                // Update school for user
                $update_school = $this->_api('user_school')->update([
                    'school_id' => (int)$this->input->post('school_id'),
                    'user_id' => $this->input->post('user_id')
                ]);

                // Set default textbook for user first login
                $user_textbook = $this->_api('user_textbook')->get_list([
                    'user_id' => $this->input->post('user_id'),
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
                                'user_id' => $this->input->post('user_id'),
                                'textbook_id' => $tb['textbook']['id'],
                            ]);
                        }
                    }
                }

                if (isset($update_school['result']) && isset($update_grade['result'])) {
                    // Change textbook by school
                    $this->_setting_school_subject($this->input->post('school_id'), $this->input->post('grade_id'), $this->input->post('user_id'));
                    $this->_flash_message('学校を設定しました');
                    return $this->_build_json(true);
                }
            } else {
                return $this->_build_json(false);
            }
        }
    }

    /*
     * Get list area  by prefecture
     */
    public function get_list_area()
    {
        $res = $this->_api('area')->get_list([
            'pref_id' => $this->input->post('prefecture_id')
        ]);

        if (isset($res['result'])) {
            return $this->_build_json($res['result']['items']);
        }
        else {
            return $this->_build_json($res);
        }

    }

    /*
     * Search by name or address
     */
    public function search_name_address() {

        $res = $this->_api('school')->search([
            'area_id' => $this->input->post('area_id'),
            'pref_id' => $this->input->post('pres_id'),
            'school_name' => ltrim($this->input->post_or_default('school_name', ''))
        ]);

        if (isset($res['result'])) {
            return $this->_build_json($res['result']['items']);
        }
        else {
            return $this->_build_json($res);
        }

    }
}