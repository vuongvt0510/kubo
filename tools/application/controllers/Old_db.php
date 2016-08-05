<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Batch use for import data from old db
 *
 * @package Controller
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */

require_once SHAREDPATH . "core/APP_Cli_controller.php";

/**
 * Class Old_db
 *
 * @property User_model user_model
 * @property User_profile_model user_profile_model
 * @property User_power_model user_power_model
 * @property Group_model group_model
 */
class Old_db extends APP_Cli_controller
{
    var $old_db_name = 'schooltv_old';

    function __construct()
    {
        parent::__construct();

        set_time_limit(-1);
    }

    public function import_user()
    {
        $this->load->model('user_model');
        $this->load->model('user_profile_model');
        $this->load->model('group_model');
        $this->load->model('user_power_model');
        $this->load->model('user_group_model');
        $this->load->model('user_grade_history_model');
        $this->load->model('user_promotion_code_model');
        $this->load->model('user_friend_model');

        /** @var array $old_family_group */
        $old_family_group = [];

        /** @var array $map_user $old_id => $new_id */
        $map_user = [];

        /** @var array $map_group */
        $map_group = [];

        /** @var array $map_user_grade */
        $map_user_grade = [];

        $query = "SELECT count(family_id), family_id, user_type
          FROM {$this->old_db_name}.user
          WHERE enabled = 1 AND delete_flag = 0
          GROUP BY family_id
          HAVING count(family_id) >= 2";

        $family_result = $this->db->query($query)->result_array();

        $query = "SELECT user_id, family_id
          FROM {$this->old_db_name}.user
          WHERE family_id IN (
            SELECT family_id
            FROM {$this->old_db_name}.user
            WHERE enabled = 1
              AND delete_flag = 0
            GROUP BY family_id
            HAVING count(family_id) >= 2

          ) AND user_type = 0 AND enabled = 1 AND delete_flag = 0

          ORDER BY user_id ASC";

        $owner_result = $this->db->query($query)->result_array();

        foreach ($family_result AS $row) {
            // Just store family has 2 user
            $old_family_group[$row['family_id']] = [
                'owner' => NULL,
                'is_create_owner' => FALSE,
                'student_member_ids' => []
            ];
        }

        foreach ($owner_result AS $row) {
            if (empty($old_family_group[$row['family_id']]['owner'])) {
                $old_family_group[$row['family_id']]['owner'] = $row['user_id'];
            }
        }

        /*
        // Find map grade id to user
        $query = "SELECT u.user_id, c.grade_id
          FROM {$this->old_db_name}.user AS u
          LEFT JOIN (
            SELECT user_id, MAX(course_id) AS course_id
            FROM {$this->old_db_name}.user_textbook GROUP BY user_id
          ) AS ut ON ut.user_id = u.user_id
          LEFT JOIN {$this->old_db_name}.course AS c ON c.course_id = ut.course_id
          WHERE user_type = 1 AND enabled = 1 AND u.delete_flag = 0 AND c.grade_id IS NOT NULL ";

        $user_grade_result = $this->db->query($query)->result_array();

        foreach ($user_grade_result as $user_grade) {
            $map_user_grade[$user_grade['user_id']] = $user_grade['grade_id'];
        }
        */

        // Fetch and import old user
        $users_res = $this->db
            ->where('enabled', 1)
            ->where('delete_flag', 0)
            ->order_by('user_id', 'ASC')
            ->get($this->old_db_name . '.user')
            ->result_array();

        foreach ($users_res AS $old_user) {

            log_message('info', 'Start to import user ' . $old_user['login_id']);

            $user = $this->user_model->find_by([
                'login_id' => $old_user['login_id']
            ]);

            if (!empty($user)) {
                log_message('info', 'Ignore import this user');
                continue;
            }

            $user = $this->user_model->create([
                'login_id' => strtolower($old_user['login_id']),
                'password' => $old_user['password'],
                'email' => $old_user['mail_address'],
                'primary_type' =>  $old_user['user_type'] == 0 ? 'parent' : 'student',
                'email_verified' => 1,
                'status' => 'active'
            ], [
                'master' => TRUE,
                'return' => TRUE
            ]);


            $this->user_profile_model->create([
                'user_id' => $user->id,
                'birthday' => $old_user['birthday'],
                'gender' => $old_user['gender'],
                'postalcode' => $old_user['zip_code'],
                'address' => $old_user['address'],
                'phone' => $old_user['tel']
            ]);

            // Create promotion code
            if (!empty($old_user['invite_code'])) {
                $this->user_promotion_code_model->create([
                    'user_id' => $user->id,
                    'code' => $old_user['invite_code'],
                    'type' => $this->user_promotion_code_model->type[1]
                ]);
            }

            // TODO: add user power And grade
            if ($user->primary_type == 'student') {

                // power
                $this->user_power_model->create([
                    'user_id' => $user->id,
                    'max_power' => DEFAULT_MAX_USER_POWER,
                    'current_power' => DEFAULT_MAX_USER_POWER
                ], [
                    'master' => TRUE
                ]);

                // Grade
                // Find user grade
                $grade_id = $this->detect_grade_from_birthday($old_user['birthday']);

                if ($grade_id) {
                    $u_grade = $this->user_grade_history_model->create([
                        'user_id' => $user->id,
                        'grade_id' => $grade_id,
                        'registered_at' => business_date('Y-m-d H:i:s')
                    ], [
                        'master' => TRUE,
                        'return' => TRUE
                    ]);

                    // Update again user grade
                    $this->user_model->update($user->id, [
                        'current_grade' => $u_grade->id
                    ], [
                        'master' => TRUE
                    ]);

                    $this->auto_dump_user_textbook_inuse($user->id, $grade_id);
                }
            }

            // Create group
            if (isset($old_family_group[$old_user['family_id']])) {
                $is_owner = FALSE;
                if ($old_family_group[$old_user['family_id']]['owner'] && $old_user['user_id'] == $old_family_group[$old_user['family_id']]['owner']) {
                    $is_owner = TRUE;
                }

                if (!$old_family_group[$old_user['family_id']]['owner'] && !$old_family_group[$old_user['family_id']]['is_create_owner']) {
                    // First person in group is owner
                    $is_owner = TRUE;

                    $old_family_group[$old_user['family_id']]['is_create_owner'] = TRUE;
                }

                if (!isset($map_group[$old_user['family_id']])) {
                    // Create Group
                    $group_res = $this->group_model->create([
                        'primary_type' => 'family',
                        'name' => '家族グループ名未設定'
                    ], [
                        'return' => TRUE
                    ]);

                    $map_group[$old_user['family_id']] = $group_res->id;
                }

                // Add user to group
                $this->user_group_model->create([
                    'group_id' => $map_group[$old_user['family_id']],
                    'user_id' => $user->id,
                    'role' => $is_owner ? 'owner' : 'member'
                ]);

                // Create friend
                if ($user->primary_type == 'student') {
                    $old_family_group[$old_user['family_id']]['student_member_ids'][] = $user->id;
                }
            }

            $map_user[$old_user['user_id']] = $user->id;
        }

        // TODO: make friend for student in group
        foreach ($old_family_group AS $family_group) {
            if (count($family_group['student_member_ids']) > 2) {
                foreach ($family_group['student_member_ids'] AS $user_id_1) {
                    foreach ($family_group['student_member_ids'] AS $user_id_2) {
                        if ($user_id_1 != $user_id_2) {
                            $this->user_friend_model->create([
                                'user_id' => $user_id_1,
                                'target_id' => $user_id_2,
                                'status' => 'active'
                            ], [
                                'mode' => 'replace'
                            ]);
                        }

                    }
                }
            }
        }
    }

    /**
     * Detect grade id from birthday
     *
     * @param string $birthday
     *
     * @return bool|int|string
     */
    public function detect_grade_from_birthday($birthday = '')
    {
        if (empty($birthday)) {
            return FALSE;
        }

        // Because this function is imported from 4/2016
        $grade_times[1] = [strtotime('2009-04-02'), strtotime('2010-04-01')];

        for ($i = 2; $i <= 9; ++$i) {
            $grade_times[$i] = [
                strtotime('-1 year', $grade_times[$i - 1][0]),
                strtotime('-1 year', $grade_times[$i - 1][1])
            ];
        }

        $birthday = strtotime($birthday);

        foreach ($grade_times AS $key => $grade_time) {
            if ( $grade_time[0] <= $birthday && $birthday <= $grade_time[1]) {
                return $key;
            }
        }

        // If user is too old, just return the last grade
        return FALSE;
    }

    /**
     * Update user grade by birthday
     */
    public function update_birthday()
    {
        $this->load->model('user_model');
        $this->load->model('user_profile_model');
        $this->load->model('user_grade_history_model');

        $users = $this->user_profile_model
            ->select('user_id, birthday')
            ->where('birthday >= ', '2009-04-02')
            ->all();

        foreach ($users as $user) {
            $grade_id = $this->detect_grade_from_birthday($user->birthday);

            if ($grade_id) {
                $u_grade = $this->user_grade_history_model->create([
                    'user_id' => $user->user_id,
                    'grade_id' => $grade_id,
                    'registered_at' => business_date('Y-m-d H:i:s')
                ], [
                    'master' => TRUE,
                    'return' => TRUE
                ]);

                // Update again user grade
                $this->user_model->update($user->user_id, [
                    'current_grade' => $u_grade->id
                ], [
                    'master' => TRUE
                ]);

                $this->auto_dump_user_textbook_inuse($user->user_id, $grade_id);
            }
        }
    }

    /**
     * Dump user textbook inuse base on grade id
     * @param $user_id
     * @param $grade_id
     */
    public function auto_dump_user_textbook_inuse($user_id, $grade_id)
    {
        // Set default textbook for user first login
        $user_textbook = $this->_api('user_textbook')->get_list([
            'user_id' => $user_id,
            'grade_id' => $grade_id
        ]);

        if(empty($user_textbook['result']['items'])) {

            $subject_list = $this->_api('subject')->get_list([
                'grade_id' => $grade_id
            ]);

            $s_list = [];

            if (!empty($subject_list['result']['items'])) {
                foreach ($subject_list['result']['items'] as $k) {
                    $s_list[] = $k['id'];
                }
            }

            // Get the most popular subject
            $most_popular_subject = $this->_api('video_textbook')->get_most_popular([
                'subject_id' => implode(',', $s_list)
            ]);

            if(!empty($most_popular_subject['result']['items'])) {
                $most_popular_subject = array_slice($most_popular_subject['result']['items'], 0, 7);

                foreach($most_popular_subject as $tb) {
                    $this->_api('user_textbook')->create([
                        'user_id' => $user_id,
                        'textbook_id' => $tb['textbook']['id'],
                    ]);
                }
            }
        }
    }
}
