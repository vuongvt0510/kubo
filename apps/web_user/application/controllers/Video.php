<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Video page controller
 *
 * @author Duy Phan <duy.phan@interest-marketing.net>
 */
class Video extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['detail', 'update_progress', 'update_rabbit_count', 'get_rabbit_count']
        ]);
    }

    /**
     * Chapter detail with Video Spec TP20
     *
     * @param $id
     * @throws APP_Api_internal_call_exception
     */
    public function detail($id)
    {
        /** @var array $chapter */
        $chapter = $this->_internal_api('video_textbook', 'get_chapter_detail', [
            'chapter_id' => $id
        ]);

        if (!$chapter) {
            $this->_render_404();
            return;
        }

        $user_id = FALSE;

        $allow_play_drill = TRUE;

        if ($this->current_user->is_login()) {
            switch ($this->current_user->primary_type) {
                case 'parent':
                    $user_id = $this->session->userdata('switch_student_id');
                    break;
                case 'student':
                    $user_id = $this->current_user->_operator_id();
            }

            if ($user_id) {
                $user_contract = $this->_internal_api('user_contract', 'get_detail', [
                    'user_id' => $user_id
                ]);

                $allow_play_drill = strtotime($user_contract['expired_time']) >= business_time();
            } else {
                // If user_id is still FALSE => that mean this condition is parent has no student
                // => Parent can play drill when he has no student event if the user registered_at is less 30 days than today
                $allow_play_drill = strtotime($this->current_user->registered_at) + DEFAULT_MONTHLY_PAYMENT_EXPIRED_DAY * 86400  >= business_time();
            }
        }

        $video_id = $deck_id[] = $chapter['deck_id'];
        $order = $chapter['order'];

        /** @var int $textbook_id */
        $textbook_id = $chapter['textbook_id'];

        // Get the chapters list
        $res = $this->_internal_api('video_textbook', 'get_chapter', [
            'textbook_id' => $textbook_id
        ]);

        /** @var array $chapter_list */
        $chapter_list = [];
        $next_chapter = [];
        if ($res['items']) {
            foreach ($res['items'] as $c) {
                if (!empty($c['deck_id'])) {
                    $deck_id[] = $c['deck_id'];
                }
                // Get the other chapter
                if ($c['order'] != $order) {
                    $chapter_list[] = $c;
                }
                // Get next chapter
                if ($order == $c['order'] - 1) {
                    $next_chapter = $c;
                }
            }
        }

        /** @var array $textbook */
        $textbook = $this->_internal_api('textbook', 'get_detail', [
            'textbook_id' => $textbook_id
        ]);

        /** @var array $deck */
        $deck = null;
        if (!empty($deck_id)) {
            $deck = $this->_internal_api('deck', 'get_detail', [
                'deck_id' => $deck_id
            ], [
                'get_questions' => $allow_play_drill
            ]);
        }

        // Get chapter video
        $videos = [];
        if (isset($deck['items']) || !empty($deck['items'])) {
            foreach ($deck['items'] as $k) {
                if ($video_id == $k['id']) {
                    $video_id = $k['video']['id'];
                }
                $videos[$k['id']] = $k['video'];
            }
        }

        /** @var array $other_list */
        $other_list = null;

        // Set title and description to HTML
        $title = sprintf("%s | %s (%s)",
            $chapter['name'], $textbook['subject']['short_name'], $textbook['publisher']['name']
        );
        $description = $chapter['description'];

        // Most popular video
        if ($user_id) {

            // Get user infomation
            $user_info = $this->_api('user')->get_detail([
                'id' => $user_id
            ]);

            $cond = [
                'user_id' => $this->current_user->id,
                'grade_id' => !empty($this->input->param('grade_id'))
                    ? $this->input->param('grade_id') : $user_info['result']['current_grade']['id'],
                'limit' => 100
            ];

            // Get user current textbook
            $res = $this->_api('user_textbook')->get_list($cond);

            // If user don't have any video to show , let shows the most popular
            if (empty($res['result']['items'])) {

                $subject_list = $this->_api('subject')->get_list([
                    'grade_id' => $user_info['result']['current_grade']['id']
                ]);

                $s_list = [];
                foreach ($subject_list['result']['items'] as $k) {
                    $s_list[] = $k['id'];
                }

                // Get the most popular subject
                $s_list = $this->_internal_api('video_textbook', 'get_most_popular', [
                    'subject_id' => implode(',', $s_list)
                ]);

                $user_textbooks = $s_list['items'];
            } else {
                $total = count($res['result']['items']);
                $user_textbooks = $res['result']['items'];

                // Get all user textbook items by grade
                while ($total < $res['result']['total']) {
                    $user_textbook = $this->_api('user_textbook')->get_list($cond);
                    $total += 100;
                    $user_textbooks = $user_textbook['result']['items'];
                }
            }

            $textbook_id = [];
            foreach ($user_textbooks as $k) {
                $textbook_id[] = $k['textbook']['id'];
            }

            $cond = ['grade_id' => $user_info['result']['current_grade']['id']];
            $cond = !empty($textbook_id) ? array_merge($cond, ['textbook_id' => $textbook_id]) : $cond;

            // Get the most viewer video
            $most_viewer_video = $this->_api('video')->get_list($cond);

            $learning_history = $this->_api('learning_history')->create([
                'user_id' => $user_id,
                'video_id' => $video_id
            ]);

            if ($this->current_user->primary_type == 'student') {
                // Create timeline and trophy when the first check
                $this->_api('timeline')->create([
                    'timeline_key' => 'video_timeline',
                    'type' => 'timeline',
                    'target_id' => $video_id,
                    'title' => $title,
                    'play_id' => $learning_history['result']['id'],
                    'play_type' => 'video'
                ]);
            }
        }

        // Get video progress
        $video_progress = $this->_api('video')->get_progress([
            'video_id' => $videos[$chapter['deck_id']]['id'],
            'cookie_id' => ($this->current_user->is_login()) ? null : $this->input->cookie('user_video_cookie')
        ]);

        // Get the video In Progress
        $in_progress = [];
        $done = [];

        if (!empty($videos) && $user_id) {
            $in_progress = $this->_internal_api('video', 'get_chapter_progress', [
                'video_id' => array_keys($videos),
                'type' => 0
            ])['items'];

            // Get the video is done
            $done = $this->_internal_api('video', 'get_chapter_progress', [
                'video_id' => array_keys($videos),
                'type' => 1
            ])['items'];
        }

        // Getting active learning video
        $al_video = [];
        if (!$this->input->cookie('alvideo_seen')) {
            if (!empty($this->session->userdata('current_grade_id'))) {
                $al_video = $this->_internal_api('video', 'get_al_video', [
                    'grade_id' => $this->session->userdata('current_grade_id')
                ]);
            }
        }

        // Remove the score
        $this->session->unset_userdata('user_score');

        $video_info = [
            'title' => $title,
            'description' => $description,
            'thumbnail_url' => strpos($videos[$video_id]['thumbnail_url'], 'http') === 0 ? $videos[$video_id]['thumbnail_url'] : site_url($videos[$video_id]['thumbnail_url']),
            'uploaded_at' => $videos[$video_id]['created_at']
        ];

        $this->_render([
            'meta' => [
                'title' => '動画で学習 - '.$title,
                'description' => $description. '- 小学生・中学生が勉強するならスクールTV。全国の学校の教科書に対応した動画で学習できます。授業の予習・復習にぴったり。',
                'breadcrumb' => [
                    [
                        'url' => site_url(sprintf('/s/%s/%s', $textbook['subject']['type'], $textbook['subject']['id'])),
                        'name' => sprintf('%s (%s)', $textbook['subject']['name'], $textbook['publisher']['name'])
                    ],
                    [
                        'url' => site_url(sprintf('/t/%s', $video_id)),
                        'name' => $chapter['chapter_name']
                    ]
                ],
                'video_info' => $video_info
            ],
            'chapter' => $chapter,
            'next_chapter' => $next_chapter,
            'textbook' => $textbook,
            'questions' => isset($videos[$chapter['deck_id']]['questions']) ? $videos[$chapter['deck_id']]['questions'] : null,
            'videos' => $videos,
            'video_id' => $video_id,
            'al_video' => $al_video,
            'other_list' => $other_list,
            'chapter_list' => $chapter_list,
            'video_progress' => isset($video_progress['result']['items']) ? reset($video_progress['result']['items']) : null,
            'most_viewer_video' => isset($most_viewer_video['result']['items']) ? $most_viewer_video['result']['items'] : null,
            'done' => $done,
            'in_progress' => $in_progress,
            'learning_history_id' => isset($learning_history['result']['id']) ? $learning_history['result']['id'] : null
        ]);
    }

    /**
     * Update video progress
     *
     * @throws APP_Api_internal_call_exception
     */
    public function update_progress()
    {
        $params = $this->input->param();

        $scores = [];
        if(isset($params['scores'])) {
            foreach ($params['scores'] as $score) {
                $scores[$score['id']] = [
                    'second' => $score['second'],
                    'point' => empty($score['point']) ? 0 : $score['point']
                ];
            }
        }
        $params['scores'] = $scores;

        // Update or insert learning history
        if ($this->current_user->is_login()) {
            $this->_internal_api('learning_history', 'update_history', [
                'user_id' => (int)$this->current_user->id,
                'video_id' => (int)$params['video_id'],
                'status' => isset($params['done_flag']) ? (int)$params['done_flag'] : 0,
                'question_answer_data' => $scores,
                'id' => $params['learning_history_id']
            ]);
        }

        $res = $this->_internal_api('video', 'update_progress', $params);

        return $this->_true_json($res);
    }

    public function update_video_count_second()
    {
        $res = [];
        // Update or insert learning history
        if ($this->current_user->is_login()) {
            $res = $this->_internal_api('video', 'update_video_count_second', [
                'user_id' => $this->current_user->id
            ]);
        }
        return $this->_true_json($res);
    }

    /**
     * Update user video_id
     * @return JSON
     * @internal param int $video_id
     *
     */
    public function update_video_count()
    {

        if (!$this->input->post('video_id') || !$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        try {
            $res = $this->_api('video')->update_view_count([
                'video_id' => (int)$this->input->post('video_id'),
                'count_up' => 1
            ]);
        } catch (APP_DB_exception_duplicate_key_entry $ex) {

            // Reupdate nickname
            $res = $this->_api('video')->update_view_count([
                'video_id' => (int)$this->input->post('video_id'),
                'count_up' => 1
            ]);
        }

        // return
        if ($res['success']) {
            return $this->_true_json();
        }
    }

    public function update_rabbit_count()
    {
        $res = [];
        $params = $this->input->param();
        if (!empty($params)) {
            $res = $this->_external_api('video_rabi_count', 'create', $params);
        }

        return $this->_true_json($res);
    }

    public function get_rabbit_count()
    {
        $res = [];
        $params = $this->input->param();
        if (!empty($params)) {
            $res = $this->_internal_api('video_rabi_count', 'get_list', $params);
        }

        header('Content-Type: application/json');
        echo json_encode($res);
        die;
    }
}