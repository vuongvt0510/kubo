<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Timeline_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Timeline_api extends Base_api
{

    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Timeline_api_validator';

    /**
     * Create a new activity Spec T-010
     *
     * @param array $params
     *
     * @internal param int $type
     * @internal param int $user_id
     * @internal param int $target_id
     * @internal param int $title
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('timeline_key', 'Timeline Key', 'required');
        $v->set_rules('type', 'タイプ', 'required|valid_timeline_type');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('timeline_model');

        $res = $this->timeline_model->create_timeline($params['timeline_key'],
            $params['type'],
            !empty($params['target_id']) ? $params['target_id'] : null,
            !empty($params['title']) ? $params['title'] : null,
            !empty($params['play_id']) ? $params['play_id'] : null,
            !empty($params['play_type']) ? $params['play_type'] : null
        );

        return $this->true_json($res);

    }

    /**
     * Get list timelines of user Spec T-020
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $type
     * @internal param int $get_friend (TRUE/FALSE)
     * @internal param int $limit
     * @internal param int $offset
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'integer|valid_user_id');
        $v->set_rules('type', 'タイムラインタイプ', 'valid_timeline_type');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('timeline_model');

        if (isset($params['type_get_list']) && isset($params['created_at']) && $params['type_get_list'] == 'new') {
            $this->timeline_model->where('timeline.created_at > ', business_date('Y-m-d H:i:s', strtotime($params['created_at'])));
        }

        if (isset($params['type_get_list']) && isset($params['created_at']) && $params['type_get_list'] == 'old') {
            $this->timeline_model->where('timeline.created_at < ', business_date('Y-m-d H:i:s', strtotime($params['created_at'])));
        }

        $this->timeline_model
            ->calc_found_rows()
            ->select('timeline.id, timeline.user_id, timeline.type, timeline.target_id, timeline.created_at, timeline.extra_data, timeline.play_id, timeline.play_type')
            ->select('(SELECT COUNT(timeline_comment.timeline_id) FROM timeline_comment WHERE timeline.id = timeline_comment.timeline_id) AS comment_total')
            ->select('(SELECT COUNT(timeline_good.timeline_id) FROM timeline_good WHERE timeline.id = timeline_good.timeline_id) AS good_total')
            ->select('user.nickname, user.login_id, user.primary_type, user_profile.avatar_id, timeline_good.user_id AS good_from_operator')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->join('timeline_good', 'timeline.id = timeline_good.timeline_id AND timeline_good.user_id = '.$this->operator()->id, 'left')
            ->join('user', 'user.id = timeline.user_id', 'left')
            ->join('user_profile', 'user_profile.user_id = timeline.user_id', 'left')
            ->order_by('timeline.created_at', 'desc')
            ->limit($params['limit'], $params['offset']);


        if(isset($params['user_id']) && !isset($params['get_friend'])) {
            $this->timeline_model->where('timeline.user_id', $params['user_id']);
        }

        if(isset($params['type'])) {
            $this->timeline_model->where('timeline.type', $params['type']);
        }

        if(isset($params['timeline_id'])) {
            $this->timeline_model->where('timeline.id', $params['timeline_id']);
        }

        if(isset($params['get_friend']) && $params['get_friend'] == TRUE && isset($params['user_id'])) {
            $this->timeline_model->join('( SELECT target_id FROM user_friend WHERE user_id = '.$params['user_id'].' AND status = "active" ) AS friends', 'friends.target_id = timeline.user_id');
        }

        // Get records
        $res = $this->timeline_model->all();

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->timeline_model->found_rows()
        ]);
    }

    /**
     * Get timeline detail Spec T-030
     *
     * @param array $params
     * @internal param int $timeline_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('timeline_id', 'タイムラインID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('timeline_model');

        $res = $this->timeline_model
            ->select('timeline.id, timeline.user_id, timeline.type, timeline.target_id, timeline.created_at, timeline.extra_data, timeline.play_id, timeline.play_type')
            ->select('(SELECT COUNT(timeline_comment.timeline_id) FROM timeline_comment WHERE timeline.id = timeline_comment.timeline_id) AS comment_total')
            ->select('(SELECT COUNT(timeline_good.timeline_id) FROM timeline_good WHERE timeline.id = timeline_good.timeline_id) AS good_total')
            ->select('user.nickname, user.login_id, user.primary_type, user_profile.avatar_id, timeline_good.user_id AS good_from_operator')
            ->join('user', 'user.id = timeline.user_id')
            ->join('user_profile', 'user_profile.user_id = timeline.user_id', 'left')
            ->join('timeline_good', 'timeline.id = timeline_good.timeline_id AND timeline_good.user_id = '.$this->operator()->id, 'left')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('timeline.id', $params['timeline_id'])
            ->first();

        if (empty($res)) {
            return $this->false_json(self::NOT_FOUND);
        }

        return $this->true_json($this->build_responses($res));
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

        if (empty($res)) {
            return [];
        }

        if (isset($res->play_id) && isset($res->play_type)) {
            switch($res->play_type) {

                case 'individual_play':
                    $this->load->model('user_playing_stage_model');

                    $play_data = $this->user_playing_stage_model->find($res->play_id);

                    $total_question = 0;
                    $correct_answer = 0;
                    $questions = json_decode($play_data->question_answer_data, TRUE);
                    foreach ($questions['questions'] AS $question) {

                        $total_question++;

                        if ((isset($question['status']) && $question['status'] == 'remember') || (isset($question['score']) && $question['score'] > 0)) {
                            $correct_answer++;
                        }
                    }
                    $time = $play_data->second;
                    break;

                case 'video':
                    $this->load->model('learning_history_model');

                    $video_data = $this->learning_history_model
                        ->select('learning_history.question_answer_data, learning_history.second, schooltv_content.video_question_timeline.question_id')
                        ->join('schooltv_content.video_question_timeline', 'schooltv_content.video_question_timeline.video_id = learning_history.video_id', 'left')
                        ->where('learning_history.id', $res->play_id)
                        ->all();

                    $correct_answer = 0;
                    $total_question = count($video_data);
                    $questions = json_decode($video_data[0]->question_answer_data, TRUE);
                    if(!empty($questions)) {
                        foreach ($questions AS $question) {

                            if ($question['point'] > 0) {
                                $correct_answer++;
                            }
                        }
                    }
                    $time = $video_data[0]->second;
                    break;
            }
        }

        return [
            'id'  => (int) $res->id,
            'user_id'  => (int) $res->user_id,
            'type' => $res->type,
            'created_at' => $res->created_at,
            'target_id' => (int) $res->target_id,
            'total_question' => isset($total_question) ? $total_question : null,
            'correct_answer' => isset($correct_answer) ? $correct_answer : null,
            'time' => isset($time) ? $time : null,
            'good_total' => (int) $res->good_total,
            'comment_total' => (int) $res->comment_total,
            'extra_data' => json_decode($res->extra_data, true),
            'link' => $res->type == 'timeline' && !in_array(json_decode($res->extra_data, true)['timeline_key'], ['make_friend_timeline', 'video_timeline'])? TRUE : FALSE,
            'nickname' => $res->nickname,
            'login_id' => $res->login_id,
            'avatar_id' => (int) $res->avatar_id,
            'primary_type' => $res->primary_type,
            'good_from_operator' => isset($res->good_from_operator) ? 1 : 0
        ];
    }
}

/**
 * Class Timeline_api_validator
 *
 * @property imeline_api $base
 */
class Timeline_api_validator extends Base_api_validation
{

    /**
     * Validate timeline type
     *
     * @param string $type of timeline
     *
     * @return bool
     */
    function valid_timeline_type($type = '')
    {

        // Validate timeline type
        if (!in_array($type, ['trophy', 'timeline'])) {
            $this->set_message('valid_timeline_type', '無効なタイムラインタイプです。');
            return FALSE;
        }

        return TRUE;
    }
}