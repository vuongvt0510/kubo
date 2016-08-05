<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_textbook_api
 *
 * @property User_textbook_inuse_model user_textbook_inuse_model
 * @property User_grade_history_model user_grade_history_model
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_textbook_api extends Base_api
{

    /**
     * Get user list textbook API Spec UTB-010
     *
     * @param array $params
     *
     * @internal param int $user_id search keyword
     * @internal param int $offset number of record
     * @internal param int $limit number of max record
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required');
        $v->set_rules('grade_id', '学年ID', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');
        $v->set_rules('limit', '取得件数', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_textbook_inuse_model');

        // Set default offset , limit
        $this->_set_default($params);

        // Check user id
        $user = $this->user_model->available(TRUE)->find($params['user_id']);

        // Return error if user does not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Get by grade_id if exist
        if (isset($params['grade_id'])) {
            $this->user_textbook_inuse_model->where('user_textbook_inuse.grade_id', $params['grade_id']);
        }

        // Get user textbook info
        $res = $this->user_textbook_inuse_model
            ->calc_found_rows()
            ->get_list($params);

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->user_textbook_inuse_model->found_rows()
        ]);
    }

    /**
     * User textbook create  API Spec UTB-040
     *
     * @param array $params
     *
     * @internal param int $user_id identity user id
     * @internal param int $textbook_id
     * @internal param int $no_rabipoint
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('textbook_id', '教科書ID', 'required|integer|valid_textbook_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_textbook_inuse_model');
        $this->load->model('user_grade_history_model');

        // Return error if user is not exist
        if (!($user = $this->user_model->available(TRUE)->find($params['user_id']))) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Get the grade_id
        $grade = $this->user_grade_history_model->find($user->current_grade);

        // Return error if user is not exist
        if (!$grade) {
            return $this->false_json(self::NOT_FOUND, '現在の学年が見つかりません。');
        }

        /** @var object $res Create the textbook data*/
        $res = $this->user_textbook_inuse_model->create([
            'user_id' => $params['user_id'],
            'textbook_id' => $params['textbook_id'],
            'grade_id' => isset($grade->grade_id) ? $grade->grade_id : NULL
        ],[
            'mode' => 'replace',
            'return' => TRUE,
            'master' => TRUE
        ]);

        $res_rabipoint = FALSE;
        if (!$this->operator()->is_administrator() && $this->operator()->primary_type == 'student' && !isset($params['no_rabipoint'])) {
            $this->load->model('user_rabipoint_model');
            $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $params['user_id'],
                'case' => 'register_profile',
                'modal_shown' => 1
            ]);
        }

        // Return
        return $this->true_json(['id' => (int) $res->id, 'point' => $res_rabipoint]);
    }

    /**
     * User textbook update  API Spec UTB-050
     *
     * @param array $params
     *
     * @internal param int $user_id identity user id
     * @internal param int $textbook_id textbook id before update
     * @internal param int $new_textbook_id textbook id after update
     *
     * @return array
     */
    public function update($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('textbook_id', '教科書ID', 'required|integer|valid_textbook_id');
        $v->set_rules('new_textbook_id', '新しい教科書ID', 'required|integer|valid_textbook_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_textbook_inuse_model');
        $this->load->model('user_grade_history_model');

        // Return error if user is not exist
        if (!($user = $this->user_model->available(TRUE)->find($params['user_id']))) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Get the grade_id
        $grade = $this->user_grade_history_model->find($user->current_grade);

        // Return error if user is not exist
        if (!$grade) {
            return $this->false_json(self::NOT_FOUND, '現在の学年が見つかりません。');
        }

        $user_textbook = (array) $this->user_textbook_inuse_model->find_by([
            'user_id' => $params['user_id'],
            'textbook_id' => $params['textbook_id']
        ]);

        if(!isset($user_textbook['id'])) {
            return $this->false_json(self::NOT_FOUND, 'この教科書はこのユーザーのものではありません。');
        }

        $user_new_textbook = (array) $this->user_textbook_inuse_model->find_by([
            'user_id' => $params['user_id'],
            'textbook_id' => $params['new_textbook_id']
        ]);

        if(!isset($user_new_textbook['id'])) {
            $user_textbook['textbook_id'] = $params['new_textbook_id'];

            /** @var object $res Update the textbook data*/
            $this->user_textbook_inuse_model
                ->update($user_textbook['id'], $user_textbook);
        } elseif(isset($user_new_textbook['id']) && $user_new_textbook['id'] != $user_textbook['id']) {

            /** @var object $res Delete the textbook data*/
            $this->user_textbook_inuse_model
                ->destroy($user_textbook['id']);
        }

        // Return
        return $this->true_json();
    }

    /**
     * User textbook update  API Spec UTB-060
     *
     * @param array $params
     * @internal param int $grade_id
     * @internal param int $user_id
     *
     * @return array
     */
    public function delete($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('grade_id', '学年ID', 'required|integer');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_textbook_inuse_model');

        // Return error if user is not exist
        if (!($user = $this->user_model->available(TRUE)->find($params['user_id']))) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $res = $this->user_textbook_inuse_model->where([
            'user_id' => $params['user_id'],
            'grade_id' => $params['grade_id']
        ])->destroy_all();

        // Return
        return $this->true_json();
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = []){

        if(!$res) {
            return [];
        }

        return array_merge([
            'user_textbook' => [
                'id' => (int) $res->id
            ]
        ], $this->build_user_textbook_response($res, $options = []));
    }

}
