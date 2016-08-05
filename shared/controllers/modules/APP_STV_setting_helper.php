<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Setting helper
 *
 * @package APP\Controller
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
trait APP_STV_setting_helper
{

    /* array */
    private $subject_order = [
        'english' => 0,
        'math' => 1,
        'japanese-language' => 2,
        'science' => 3,
        'geography' => 4,
        'history' => 5,
        'civics' => 6
    ];

    /*
     * Setting subject by school
     * @param int school_id
     * @param int grade_id
     *
     */
    function _setting_school_subject($school_id, $grade_id, $user_id){

        // Set default textbook for this user
        $textbook = $this->_api('textbook')->search([
            'school_id' => (int) $school_id,
            'grade_id' => (int) $grade_id
        ]);

        // If school don't have any text book , set the most popular
        $s_list = [];
        $t_list = [];
        $is_school = TRUE;
        if(empty($textbook['result']['items'])) {

            $textbook = $this->_api('subject')->get_list([
                'grade_id' => (int) $grade_id
            ]);

            $is_school = FALSE;
        }

        // Get the subject_id
        foreach ($textbook['result']['items'] as $k) {
            $s_list[] = ($is_school) ?  $k['subject']['id'] : $k['id'];

            if($is_school) {
                $t_list[] = $k['textbook']['id'];
            }
        }

        $conds = [
            'subject_id' => implode(',', $s_list),
        ];

        if(!empty($t_list)) {
            $conds['textbook_id'] = $t_list;
        }

        // Get the most popular subject
        $most_popular_subject = $this->_api('video_textbook')->get_most_popular($conds);

        // Prevent the duplicate subject
        $textbooks = [];
        foreach($most_popular_subject['result']['items'] as $k) {
            if(!array_key_exists($k['subject']['type'], $textbooks)) {
                $textbooks[$k['subject']['type']] = $k;
            }
        }

        // Sort the subject
        $textbooks = $this->_sort_subject($textbooks);

        if ($textbooks) {
            // Remove default textbook setting
            $this->_api('user_textbook')->delete([
                'user_id' => $user_id,
                'grade_id' => (int) $this->input->post('grade_id')
            ]);

            foreach ($textbooks as $k) {
                // Update user textbook
                $this->_api('user_textbook')->create([
                    'user_id' => $user_id,
                    'textbook_id' => $k['textbook']['id'],
                ]);
            }
        }
    }

    /*
     * Validate the group members
     *
     * @param int $member_id
     *
     * @return boolean
    */
    private function _valid_group_members($member_id)
    {
        // Get the group detail
        $res = $this->_api('user')->get_list_groups([
            'user_id' => $this->current_user->id
        ]);

        if(!$res) {
            return FALSE;
        }

        $user_ids = [];
        foreach($res['result']['groups'] as $k) {

            // Get the members list
            if($k['user_role'] === 'owner') {
                $members = $this->_api('user_group')->get_list_members([
                    'group_id' => (int) $k['group_id']
                ]);

                if(empty($members['result']['users'])) {
                    continue;
                }

                foreach($members['result']['users'] as $m){
                    $user_ids[] = $m['id'];
                }
            }
        }

        if(in_array($member_id, $user_ids)) {
            return TRUE;
        }

        return FALSE;
    }


    /*
     * Reorder subject
     *
     * @param array $res
     *
     * @return Array
     */
    private function _sort_subject($res, $keys = ['subject', 'type'])
    {
        // Sort subject
        $subject = [];
        if ($res)
        {
            foreach ($res as $k)
            {
                $key_val = $k;
                foreach($keys as $key) {
                    $key_val = $this->_get_key($key_val, $key);
                }

                $subject[$this->subject_order[$key_val]] = $k;
            }
            ksort($subject);
        }

        return $subject;
    }

    /*
     * Reorder subject
     *
     * @param array $res
     *
     * @return String
     */
    private function _get_key($res, $keys)
    {
        return isset($res[$keys]) ? $res[$keys] : null;
    }

}
