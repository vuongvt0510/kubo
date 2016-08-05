<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Video Control API
 *
 * @property Brightcove_video brightcove_video
 * @property Video_progress_model video_progress_model
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Video_api extends Base_api
{
    /*
    * Standard Validator Class
    *
    * @var string
    */
    public $validator_name = 'Video_api_validator';

    /**
     * Get Al video
     *
     * @param array $params
     * @internal param int $grade_id
     *
     * @return array
     */
    public function get_al_video($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('grade_id', '学年', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('video_model');

        switch (TRUE) {
            case $params['grade_id'] <= 3:
                $this->video_model
                    ->like('name', 'A-');
                break;
            case $params['grade_id'] <= 6:
                $this->video_model
                    ->like('name', 'B-');
                break;
            case $params['grade_id'] <= 9:
                $this->video_model
                    ->like('name', 'C-');
                break;
            default:
                break;
        }

        // Load model
        $res = $this->video_model
            ->where('type', 'active_learning')
            ->order_by('id', 'random')
            ->first();

        // Return response
        return $this->true_json(
            $this->build_response($res, ['brightcove_detail'])
        );

    }

    /**
     * Get list of video API Spec VD-030
     *
     * @param array $params
     * @internal param int $video_id
     * @internal param int $subject_id
     * @internal param int $grade_id
     * @internal param int $textbook_id
     * @internal param int $offset
     * @internal param int $limit
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Load model
        $this->load->model('video_model');
        $this->load->model('textbook_model');
        $this->load->model('textbook_content_model');

        $cond = NULL;

        // If there is no params to find return
        if (empty($params['grade_id']) && empty($params['textbook_id']) && empty($params['subject_id'])) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Set query condition
        if(isset($params['grade_id'])) {
            $this->textbook_model->where('grade_id', $params['grade_id']);
        }
        if(isset($params['textbook_id'])) {

            // Cast textbook_id to array condition
            $params['textbook_id'] = !is_array($params['textbook_id'])
                ? [$params['textbook_id']] : $params['textbook_id'];

            $this->textbook_model->where_in('textbook_id', $params['textbook_id']);
        }
        if(isset($params['subject_id'])) {
            $this->textbook_model->where('subject_id', $params['subject_id']);
        }

        // Get the textbook detail
        $res = $this->textbook_model->select('textbook.id')
            ->with_master_subject()
            ->with_master_grade()
            ->with_textbook_content()
            ->all();

        // Return null if not exist
        if(!$res) {
            return $this->true_json([
                'items' => [],
                'total' => 0
            ]);
        }

        // Read the deck_id
        $decks = [];
        $subjects = [];
        $chapters = [];
        foreach ($res as $v) {
            if(!empty($v->deck_id)) {
                $decks[$v->subject_id][$v->deck_id] = $v->deck_id;
                $subjects[$v->subject_id] = $v;
                $chapters[$v->deck_id] = $v;
            }
        }

        // Build the response
        $res = $this->build_responses($subjects, ['list' => TRUE, 'deck' => $decks, 'chapter' => $chapters]);

        // Remove null data
        $res = array_values(array_filter($res));

        // Return response
        return $this->true_json([
            'items' => $res
        ]);
    }

    /**
     * Get detail of video view count
     *
     * @param array $params
     * @internal param array $deck
     *
     * @return array
     */
    public function get_video_view_count($params = []) {

        // Validate
        $v = $this->validator($params);

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Get most viewer video by subject
        $res = $this->video_model
            ->calc_found_rows()
            ->select('deck_video_inuse.deck_id as deck_id, video.id, video.name, video.description, video.brightcove_id, video.type')
            ->select('video.image_key, video.brightcove_thumbnail_url')
            ->join('deck_video_inuse', 'deck_video_inuse.video_id = video.id')
            ->join('video_view_count', 'video_view_count.video_id = video.id', 'left')
            ->where_in('deck_video_inuse.deck_id', $params['deck'])
            ->where('video_view_count.date <=', business_date('Y-m-d'))
            ->where('video_view_count.date >=', business_date('Y-m-d', strtotime('7 days ago')))
            ->group_by('deck_video_inuse.deck_id')
            ->order_by('video_view_count.count', 'DESC')
            ->first();

        $video = $this->build_video_response($res);

        $video['thumbnail_url'] = !empty($res->brightcove_thumbnail_url) ?
            $res->brightcove_thumbnail_url : null;

        // Image key is high priority than brightcove_thumbnail_url
        if (!empty($res->image_key)) {
            $video['thumbnail_url'] = '/image/show/' . $res->image_key;
        }

        $video['deck_id'] = isset($res->deck_id) ? $res->deck_id : null ;

        return $this->true_json($video);
    }

    /**
     * Get detail of video API Spec VD-010
     *
     * @param array $params
     * @internal param $video_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('video_id', 'ビデオID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('video_model');

        // Return response
        return $this->true_json(
            $this->build_response($this->video_model->find($params['video_id']), ['brightcove_detail'])
        );
    }

    /**
     * Create the progress of video API Spec VD-021
     *
     * @param array $params
     * @internal param $video_id
     * @internal param $cookie_id
     * @internal param $second
     * @internal param $session_id
     * @internal param $learning_history_id
     *
     * @return array
     */
    public function update_progress($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('video_id', 'ビデオID', 'required|valid_video_id');
        $v->set_rules('cookie_id', 'クッキー', 'required');
        $v->set_rules('second', '秒数', 'required');
        $v->set_rules('duration', '存続期間', 'required');
        $v->set_rules('session_id', 'セッションID', 'required');
        $v->set_rules('done_flag', '終了フラグ', 'integer');
        $v->set_rules('have_questions', 'Have Questions', 'integer');
        $v->set_rules('learning_history_id', 'Learning History ID', 'integer');
        $v->set_rules('scores[]', 'スコア');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('video_progress_model');

        $param = [
            'video_id' => $params['video_id'],
            'cookie_id' => $params['cookie_id'],
            'session_id' => $params['session_id'],
            'second' => $params['second'],
            'duration' => $params['duration'],
            'done_flag' => isset($params['done_flag']) ? (int) $params['done_flag'] : 0
        ];

        $create_flg = TRUE;

        // In logged in user case
        if(isset($this->operator->id)) {
            // Get the video detail
            $video = $this->video_progress_model->find_by([
                'video_id' => $params['video_id'],
                'user_id' => $this->operator->id
            ]);

            $param  = array_merge($param, ['user_id' => $this->operator->id]);
            if($video) {
                // Don't update cookie
                $param['cookie_id'] = NULL;
                $create_flg = FALSE;
                $res = $this->video_progress_model->update($video->id, $param);
            }
        }

        if($create_flg) {
            /** @var object $res Create the progress*/
            $res = $this->video_progress_model->create( $param , [
                'mode' => 'replace',
                'return' => TRUE
                ]
            );
        }
        $trophy = FALSE;
        $point = FALSE;
        if (isset($params['done_flag']) && $params['done_flag'] == 1 && isset($this->operator()->id) && $this->operator()->primary_type == 'student') {

            if (isset($params['learning_history_id'])) {

                $this->load->model('video_model');

                $video_detail = $this->video_model->find($params['video_id']);

                $this->load->model('learning_history_model');

                $learning_history = $this->learning_history_model->find($params['learning_history_id']);

                if ($learning_history->second >= (0.5 * ($video_detail->duration / 1000))) {
                    $this->load->model('timeline_model');
                    $trophy = $this->timeline_model->create_timeline('video', 'trophy');
                }
            }
            $this->load->model('user_rabipoint_model');

            if (isset($params['have_questions']) && $params['have_questions'] == 1) {

                // First time score
                $video_score = FALSE;
                if (!empty($params['scores'])) {
                    $video_score = $this->user_rabipoint_model->create_rabipoint([
                        'user_id' => $this->operator()->id,
                        'case' => 'video_score',
                        'target_id' => $params['video_id']
                    ]);

                    if ($video_score != FALSE) {
                        $point['video_score'] = $video_score;
                    }
                }

                $count_corect_answer = 0;
                // Give point for all correct answers
                foreach ($params['scores'] as $score) {
                    if ($score['point'] > 0) {
                        $video_correct_answer = $this->user_rabipoint_model->create_rabipoint([
                            'user_id' => $this->operator()->id,
                            'case' => 'video_correct_answer',
                            'target_id' => $params['video_id']
                        ]);
                        if ($video_correct_answer != FALSE) {
                            $count_corect_answer ++;
                        }
                    }
                }

                if ($count_corect_answer > 0) {
                    $video_correct_answer['base_point'] = $count_corect_answer * $video_correct_answer['base_point'];
                    $point['video_correct_answer'] = $video_correct_answer;
                }

                // Give point for every time
                if (!empty($params['scores']) && $video_score == FALSE) {
                    $video_score_every_time = $this->user_rabipoint_model->create_rabipoint([
                        'user_id' => $this->operator()->id,
                        'case' => 'video_score_every_time',
                        'target_id' => $params['video_id']
                    ]);

                    if ($video_score_every_time != FALSE) {
                        $point['video_score_every_time'] = $video_score_every_time;
                    }
                }

                // Give point for 2nd score in a day
                $score_count = $this->user_rabipoint_model->check_score_2nd_in_aday($this->operator()->id);
                if ($score_count >= 2) {
                    $score_video_2nd = $this->user_rabipoint_model->create_rabipoint([
                        'user_id' => $this->operator()->id,
                        'case' => 'score_video_2nd',
                        'target_id' => $params['video_id']
                    ]);

                    if ($score_video_2nd != FALSE) {
                        $point['score_video_2nd'] = $score_video_2nd;
                    }
                }
            }

            if (isset($params['have_questions']) && isset($params['learning_history_id']) && $params['have_questions'] == 0) {

                $this->load->model('video_model');

                $video_detail = $this->video_model->find($params['video_id']);

                $this->load->model('learning_history_model');

                $learning_history = $this->learning_history_model->find($params['learning_history_id']);

                if ($learning_history->second < (0.5 * ($video_detail->duration / 1000))) {
                    return $this->true_json(['video_id' => (int) $video->id, 'trophy' => $trophy, 'point' => $point]);
                }

                // Give point for the first time
                $watch_video = $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $this->operator()->id,
                    'case' => 'watch_video',
                    'target_id' => $params['video_id']
                ]);

                if ($watch_video != FALSE) {
                    $point['watch_video'] = $watch_video;
                }

                if (!isset($point['watch_video'])) {
                    // Give point everytime
                    $watch_video_every_time = $this->user_rabipoint_model->create_rabipoint([
                        'user_id' => $this->operator()->id,
                        'case' => 'watch_video_every_time',
                        'target_id' => $params['video_id']
                    ]);
                }

                if ($watch_video_every_time != FALSE) {
                    $point['watch_video_every_time'] = $watch_video_every_time;
                }

                $view_count = $this->user_rabipoint_model->check_watch_2nd_in_aday($this->operator()->id);
                // Give point everytime
                if ($view_count >= 2) {
                    $watch_2nd_video = $this->user_rabipoint_model->create_rabipoint([
                        'user_id' => $this->operator()->id,
                        'case' => 'watch_2nd_video',
                        'target_id' => $params['video_id']
                    ]);

                    if ($watch_2nd_video != FALSE) {
                        $point['watch_2nd_video'] = $watch_2nd_video;
                    }
                }

                // Watch video everyday continuously
                $watch_continuously = $this->user_rabipoint_model->check_watch_video_continuously($this->operator()->id);
                if ($watch_continuously == TRUE) {
                    $watch_video_continuously = $this->user_rabipoint_model->create_rabipoint([
                        'user_id' => $this->operator()->id,
                        'case' => 'watch_video_continuously',
                        'target_id' => $params['video_id']
                    ]);

                    if ($watch_video_continuously != FALSE) {
                        $point['watch_video_continuously'] = $watch_video_continuously;
                    }
                }
            }
        }

        // Return
        return $this->true_json(['video_id' => isset($video) ? (int) $video->video_id : (int) $res->video_id, 'trophy' => $trophy, 'point' => $point]);
    }

    /**
     * Get the progress of video API Spec VD-022
     *
     * @param array $params
     * @internal param int $video_id
     * @internal param string $cookie_id
     * @return array
     */
    public function get_progress($params = [])
    {
        // Validate
        $v = $this->validator($params);
        // $v->set_rules('video_id', 'ビデオID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('video_progress_model');

        // Add the cookie condition
        if (isset($params['cookie_id'])) {
            $this->video_progress_model->where('video_progress.cookie_id', $params['cookie_id']);
        }

        // Add the user_id condition
        if (isset($this->operator()->id)) {
            $this->video_progress_model->where('video_progress.user_id', $this->operator->id);
        }

        /** @var object $res Create the progress*/
        $res = $this->video_progress_model
            ->where_in('video_progress.video_id', $params['video_id'])
            ->order_by('video_progress.updated_at', 'DESC')
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['video_progress'])
        ]);
    }

    /**
     * Get the progress of video API Spec VD-023
     *
     * @param array $params
     * @internal param array $video_id
     * @internal param string $type
     * @return array
     */
    public function get_chapter_progress($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('type', 'タイプ', 'valid_type');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('video_progress_model');

        if (isset($params['user_id'])) {
            $this->video_progress_model->where('video_progress.user_id', $params['user_id']);
        }

        /** @var object $res Create the progress*/
        $res = $this->video_progress_model
            ->select('video_progress.video_id')
            ->where_in('video_progress.video_id', $params['video_id'])
            ->where('video_progress.done_flag', $params['type'])
            ->all();

        // Return
        return $this->true_json(['items'=>$this->build_responses($res, ['chapter_progress'])]);
    }

    /**
     * Get the progress of video API Spec VD-025
     *
     * @param array $params
     * @internal param int $video_id
     * @internal param int $count_up
     * @return array
     */
    public function update_view_count($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('video_id', '動画ID', 'required|integer');
        $v->set_rules('count_up', 'カウントアップ', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('video_view_count_model');

        $this->video_view_count_model->transaction(function() use ($params, &$res) {

            $res = FALSE;
            /** @var object $video Create the progress*/
            $video = $this->video_view_count_model
                ->where('video_id', $params['video_id'])
                ->where('date', business_date('Y-m-d'))
                ->for_update()->first(['master' => TRUE]);

            if($video) {
                $res = $this->video_view_count_model->update($video->video_id, [
                    'count' => $video->count + $params['count_up']
                ], [
                    'return' => TRUE,
                    'master' => TRUE
                ]);
            } else {
                // Create new
                $res = $this->video_view_count_model->create([
                    'video_id' => $params['video_id'],
                    'count' => $params['count_up'],
                    'date' => business_date('Y-m-d')
                ], [
                    'master' => TRUE,
                    'return' => TRUE
                ]);
            }
        });

        if(!$res) {
            return $this->false_json();
        }

        // Return
        return $this->true_json();
    }

    /**
     * Get the progress of video API Spec VD-026
     *
     * @param array $params
     * @internal param int $user_id
     *
     * @return array
     */
    public function update_video_count_second($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|valid_user_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_video_count_second_model');

        $user = $this->user_video_count_second_model
            ->where('user_id', $params['user_id'])
            ->for_update()
            ->first(['master' => TRUE]);

        if (!empty($user)) {
            $this->user_video_count_second_model->update($user->user_id, [
                'second' => $user->second + 5
            ], [
                'master' => TRUE
            ]);
        } else {
            // Create new
            $this->user_video_count_second_model->create([
                'user_id' => $params['user_id'],
                'second' => 5
            ], [
                'master' => TRUE
            ]);
        }

        $this->load->model('timeline_model');
        
        $trophy = $this->timeline_model->create_timeline('video_minute', 'trophy');

        return $this->true_json(['trophy' => $trophy]);
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_list_most_view_video($res, $options = [])
    {
        if(!isset($options['deck'][$res->subject_id])) {
            return [];
        }

        $result =  $this->get_video_view_count(['deck' => $options['deck'][$res->subject_id]]);

        if(!isset($result['result']['id'])) {
            return [];
        }
        $deck_id = $result['result']['deck_id'];

        return  [
            'video' => $result['result'],
            'chapter' => isset($options['chapter'][$deck_id])
                ? $this->build_chapter_response($options['chapter'][$deck_id]) : null
        ];
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

        // Build video progress response
        if(in_array('list', $options)) {
            return $this->build_list_most_view_video($res, $options);
        }

        // Build video progress response
        if(in_array('chapter_progress', $options)) {
            return (int) $res->video_id;
        }

        // Build video progress response
        if(in_array('video_progress', $options)) {
            return [
                'video_id' => isset($res->video_id) ? (int) $res->video_id : NULL ,
                'second' => isset($res->second) ? (float) $res->second : NULL,
                'duration' => isset($res->duration) ? (float) $res->duration : NULL,
            ];
        }

        $detail = [];
        if(in_array('brightcove_detail', $options)) {
            $detail = [
                'thumbnail_url' => isset($res->brightcove_thumbnail_url) ? $res->brightcove_thumbnail_url : NULL,
            ];
        }

        // Build video list response
        if(isset($options['chapters'])) {
            $chapters = [];
            if(isset($options['chapters'])) {

                // Build the chapter response
                if(isset($options['chapters'][$res->deck_id])) {
                    $chapters = $this->build_chapter_response($options['chapters'][$res->deck_id]);
                }
            }

            $video = $this->build_video_response($res);
            $video['thumbnail_url'] = !empty($res->brightcove_thumbnail_url) ?
                $res->brightcove_thumbnail_url : null;

            // Image key is high priority than brightcove_thumbnail_url
            if (!empty($res->image_key)) {
                $video['thumbnail_url'] = '/image/show/' . $res->image_key;
            }

            return  [
                'video' => $video,
                'chapter' => $chapters
            ];
        }

        return array_merge($this->build_video_response($res), $detail) ;
    }

}

/**
 * Class Video_api_validator
 *
 * @property Video_api $base
 */
class Video_api_validator extends Base_api_validation {

    /**
     * Validate type
     *
     * @param String $type
     *
     * @return bool
     */
    function valid_type($type) {

        if(!in_array($type, [0, 1])) {
            $this->set_message('valid_type', 'タイプは（0: 処理中　1: 完了）です。');
            return FALSE;
        }

        return TRUE;
    }
}
