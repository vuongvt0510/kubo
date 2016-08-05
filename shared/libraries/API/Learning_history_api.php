<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';
require_once SHAREDPATH . 'libraries/STV_Action_type.php';

/**
 * Class User_action_api
 *
 * Do not action user has value of deleted_by or status is not active
 */
class Learning_history_api extends Base_api
{

    /**
     * Create or update learning history
     *
     * @param array $params
     *
     * @return array
     */
    public function update_history($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('id', 'ID', 'required|integer');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer|valid_user_id');
        $v->set_rules('video_id', 'ビデオID', 'required|integer|valid_video_id');
        $v->set_rules('status', 'ステータス', 'integer');
        $v->set_rules('question_answer_data[]', 'スコア');
        $v->require_login();

        // Validate error
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('learning_history_model');

        // Get history info
        $history_data = $this->learning_history_model
            ->for_update()
            ->where('id', $params['id'])
            ->where('user_id', $params['user_id'])
            ->where('video_id', $params['video_id'])
            ->first([
                'master' => TRUE
            ]);

        if (isset($params['question_answer_data'])) {
            $params['question_answer_data'] = json_encode($params['question_answer_data']);
        }

        if ($history_data->status == 1) {
            $params['status'] = 1;
        }

        $params['second'] = $history_data->second + 5;

        $result = $this->learning_history_model->update(
            (int)$params['id'],
            $params, [
            'return' => TRUE
        ]);

        // Return
        return $this->true_json($this->build_response($result));

    }

    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer|valid_user_id');
        $v->set_rules('video_id', 'ビデオID', 'required|integer|valid_video_id');

        $v->require_login();

        // Validate error
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('learning_history_model');

        // Get history info
        $history_data = $this->learning_history_model
            ->where('user_id', $params['user_id'])
            ->where('video_id', $params['video_id'])
            ->order_by('created_at', 'desc')
            ->all();

        $status = 0;
        if (!empty($history_data)) {

            foreach ($history_data as $history) {
                if ($history->status == 1) {
                    $status = 1;
                    break;
                }
            }
        }

        $learning_history = $this->learning_history_model->create([
            'user_id' => $params['user_id'],
            'video_id' => $params['video_id'],
            'status' => $status
        ], [
            'return' => TRUE
        ]);

        // Return
        return $this->true_json($this->build_response($learning_history));
    }

    /**
     * Get list learning history
     *
     * @param array $params
     *
     * @return array
     */
    public function get_list_learning_history($params = [])
    {
        // Validate
        $v = $this->validator($params);

        $v->set_rules('offset', '取得開始', 'integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        $v->require_login();

        // Validate error
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('learning_history_model');
        $this->load->model('video_model');
        $this->load->model('textbook_content_model');
        $this->load->model('user_group_model');

        // Get list video ids
        $data = $this
            ->learning_history_model
            ->calc_found_rows()
            ->order_by('created_at, id', 'desc')
            ->offset($params['offset'])
            ->limit($params['limit'])
            ->where('user_id', (int)$params['user_id'])
            ->group_by("CAST(created_at AS DATE), video_id")
            ->all();

        $res = [];
        if (!empty($data)) {
            foreach ($data As $element) {
                $element = get_object_vars($element);
                $created_at = business_date("Y/m/d", strtotime($element['created_at']));

                // Get video_detail from video
                $deck = $this->video_model->get_video_detail($element['video_id']);

                $element['thumbnail_url'] = !empty($deck) ? $deck->brightcove_thumbnail_url : null;

                if (!empty($deck->image_key)) {
                    $element['thumbnail_url'] = '/image/show/' . $deck->image_key;
                }


                if (!empty($deck->deck_id)) {
                    // Get chaper
                    $data = $this->textbook_content_model
                        ->select('textbook_content.id, textbook_content.textbook_id, textbook_content.name, textbook_content.chapter_name ')
                        ->select('textbook_content.description, textbook_content.order, textbook_content.deck_id, master_subject.name As subject_name')
                        ->join('textbook', 'textbook.id = textbook_content.textbook_id')
                        ->join('master_subject', 'textbook.subject_id = master_subject.id And master_subject.display_flag = 1')
                        ->where('textbook_content.deck_id', (int) $deck->deck_id)
                        ->first();

                    $chapter = $this->build_chapter_response($data);
                    $chapter['subject_name'] = isset($data->subject_name) ? $data->subject_name : null;
                    $chapter['status'] = $element['status'];
                    $element['chapter'] = $chapter;
                }

                $res[$created_at][] = $element;
            }
        }

        // Return
        return $this->true_json([
            'items' => $res,
            'total' => (int)$this->learning_history_model->found_rows()
        ]);

    }

    /**
     * Get chapter answers API
     *
     * @param array $params
     * @internal param array $video_ids
     * @internal param string $user_id
     *
     * @return array
     */
    public function get_chapter_answers($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('video_id[]', 'ビデオID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('learning_history_model');

        /** @var object $res Create the progress*/
        $res = $this->learning_history_model
            ->select('learning_history.video_id, learning_history.question_answer_data, learning_history.status, learning_history.second, learning_history.created_at')
            ->join ("(SELECT MAX(created_at) AS latest FROM learning_history WHERE user_id = ".$params['user_id']." GROUP BY (video_id)) AS latest_date", 'latest_date.latest = learning_history.created_at')
            ->where_in('learning_history.video_id', $params['video_id'])
            ->where('learning_history.user_id', $params['user_id'])
            ->all();

        $total_second = $this->learning_history_model
            ->select('video_id, user_id, SUM(second) AS total_second')
            ->where_in('video_id', $params['video_id'])
            ->where('user_id', $params['user_id'])
            ->group_by('user_id, video_id')
            ->all();

        $return = [];
        foreach ($total_second AS $total) {
            $return[$total->video_id]['total_second'] = $total->total_second;
        }

        foreach($res as $item) {
            $return[$item->video_id]['question_answer_data'] = json_decode($item->question_answer_data, TRUE);
            $return[$item->video_id]['status'] = $item->status;
            $return[$item->video_id]['second'] = $item->second;
            $return[$item->video_id]['created_at'] = $item->created_at;
        }

        // Return
        return $this->true_json(['items'=> $return]);
    }

    /**
     * Send learning situation of student to parent API
     *
     * @param array $params
     * @internal param array $video_ids
     * @internal param string $user_id
     *
     * @return array
     */
    public function send_students_situation($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('parent_id', 'Parent ID', 'required|integer|valid_user_id');
        $v->set_rules('parent_email', 'Parent Email', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');

        $list_groups = $this->group_model->get_user_group([
            'user_id' => $params['parent_id'],
            'group_type' => 'family'
        ]);


        $list_students = [];

        foreach ($list_groups AS $group) {

            foreach ($group['members'] AS $member) {
                if (!$member['email_verified']) {
                    continue;
                }

                if ($member['primary_type'] == 'student' && $member['status'] == 'active' && !isset($list_students[$member['user_id']])) {

                    $member['link_content'] = sprintf('%scontent/learning_history/%s', $this->config->item('site_url'), $member['user_id']);
                    $member['link_play_status'] = sprintf('%slogin?r=play/select_drill/&user_id=%s', $this->config->item('site_url'), $member['user_id']);
                    $list_students[$member['user_id']] = $member;

                }
            }
        }

        if (!empty($list_students)) {
            $mail_data = [
                'list_students' => $list_students
            ];
            // Send mail for user
            $this->send_mail('mails/children_learning_situation', [
                'to' => $params['parent_email'],
                'subject' => $this->subject_email['students_learning']
            ], $mail_data);
        }

        // Return
        return $this->true_json();
    }

    /**
     * Get monthly report API
     *
     * @param array $params
     * @internal param string $user_id
     *
     * @return array
     */
    public function get_monthly_report($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');

        // Get user info
        $user = $this->user_model->available(TRUE)
            ->find($params['user_id']);

        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Load model
        $this->load->model('learning_history_model');

        $res = $this->learning_history_model
            ->select('video_id, user_id, second, created_at')
            ->where('user_id', $params['user_id'])
            ->order_by('created_at', 'desc')
            ->all();

        $return = [];

        $register_month = strtotime(business_date('Y-m', strtotime($user->created_at)));

        for ($i=0; ; $i+=30) {

            $count_month = business_date('Y-m', strtotime('-' . $i . 'days'));

            if (strtotime($count_month) >= $register_month) {
                $return[$count_month] = 0;
            } else {
                break;
            }
        }

        foreach($res as $item) {
            $month = business_date('Y-m', strtotime($item->created_at));

            $return[$month] += $item->second;
        }

        // Return
        return $this->true_json($return);
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = [])
    {

        if (!$res) {
            return [];
        }

        return [
            'id' => isset($res->id) ? (int)$res->id : null,
            'user_id' => isset($res->user_id) ? $res->user_id : null,
            'status' => isset($res->status) ? $res->status : null,
            'second' => isset($res->second) ? $res->second : null,
            'question_answer_data' => isset($res->question_answer_data) ? $res->question_answer_data : null,
            'video_id' => isset($res->video_id) ? (int)$res->video_id : null,
            'created_at' => isset($res->created_at) ? (int)$res->created_at : null,
            'updated_at' => isset($res->updated_at) ? (int)$res->updated_at : null
        ];


        return $res;
    }
}


/**
 * Class Learning_history_api_validator
 *
 * @property Learning_history_api $base
 */
class Learning_history_api_validator extends Base_api_validation
{
}


