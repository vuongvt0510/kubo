<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Top controller
 *
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class Top extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['index']
        ]);

        $this->_before_filter('_is_parent', [
            'only' => ['switch_student']
        ]);
    }

    /**
     * Top Spec TP1
     */
    public function index()
    {
        if (!$this->input->get('grade_id') && $this->current_user->is_login()) {
            if ($this->current_user->primary_type == 'student') {
                redirect('profile/detail');
            }
            if ($this->current_user->primary_type == 'parent') {
                if (!empty($this->students)) {
                    redirect('profile/detail');
                }
            }
        }

        $hide_main_visual = FALSE;
        // Get current grade
        if (!empty($this->input->get('grade_id'))) {
            $hide_main_visual = TRUE;
            $this->input->set_cookie('current_grade_id', $this->input->get('grade_id'), time() + 86400 * 365);
            $this->session->set_userdata('current_grade_id', $this->input->get('grade_id'));
        }

        $grade_id = !empty($this->session->userdata('current_grade_id')) ?
            $this->session->userdata('current_grade_id') : 1;

        $subject_list = $this->_api('subject')->get_list([
            'grade_id' => $grade_id
        ]);

        $s_list = [];
        foreach ($subject_list['result']['items'] as $k) {
            $s_list[] = $k['id'];
        }

        // Get the most popular subject
        $most_popular_subject = $this->_api('video_textbook')->get_most_popular([
            'subject_id' => implode(',', $s_list)
        ]);

        // Get the chapter for subject
        $chapters = [];
        $deck_id = [];
        $textbook_id = [];
        $most_popular_videos = [];

        // Get most popular video of each subject
        $most_popular_video = $this->_api('video_subject')->get_list([
            'subject_id' => $s_list,
            'limit' => 1
        ]);

        if(!empty($most_popular_video['result']['items'])) {
            foreach($most_popular_video['result']['items'] as $k) {
                $most_popular_videos = array_merge($most_popular_videos, $k['popular']);
            }
        }

        foreach ($most_popular_subject['result']['items'] as $k) {

            // Get the chapter of each textbook
            $chapter = $this->_api('video_textbook')->get_chapter([
                'textbook_id' => $k['textbook']['id']
            ]);

            // Get the textbook belong to subject
            $textbook_id[$k['subject']['id']] = $k['textbook']['id'];

            if ($chapter['result']['items']) {
                foreach ($chapter['result']['items'] as $c) {
                    if (!empty($c['deck_id'])) {
                        $deck_id[] = $c['deck_id'];
                    }
                }
                $chapters[$k['subject']['id']] = $chapter['result']['items'];
            }
        }

        // Get video detail
        if (!empty($deck_id)) {
            $video = $this->_api('deck')->get_detail([
                'deck_id' => $deck_id
            ], [
                'get_questions' => FALSE
            ]);
        }

        $videos = [];
        // Get the video detail
        if (isset($video['result']['items'])) {
            foreach ($video['result']['items'] as $d) {
                $videos[$d['id']] = $d['video'];
            }
        }

        // Get english textbook
        $english = $this->_api('textbook')->get_list([
            'grade_id' => $grade_id,
            'type' => 'english'
        ]);

        $user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->_operator_id();
        // Get decks index
        $decks = $this->_api('deck')->get_list([
            'user_id' => $user_id,
            'limit' => 12
        ]);

        $show_modal_lead_to_register = FALSE;
        if ($this->uri->segment(3)=='limit_video') {
            $show_modal_lead_to_register = TRUE;
        }

        $this->_render([
            'hide_main_visual' => $hide_main_visual,
            'subject_list' => $subject_list['result']['items'],
            'most_popular' => !empty($most_popular_videos) ? array_values($most_popular_videos) : null,
            'chapters' => $chapters,
            'videos' => $videos,
            'textbooks' => $textbook_id,
            'english' => $english['result']['items'],
            'decks' => $decks['result']['items'],
            'show_modal_lead_to_register' => $show_modal_lead_to_register,
            'meta' => [
                'title' => "【スクールTV】無料の動画で授業の予習・復習をするならスクールTV"
            ]
        ]);
    }

    /**
     * Switch account to student when current user is parent
     */
    public function switch_student() {

        $user_id = $this->input->get('user_id');
        $redirect = $this->input->get('redirect');

        if (isset($this->students[$user_id])) {

            $this->session->set_userdata('switch_student_id', $user_id);

            $segments = explode('/', $redirect);
            switch ($segments[0]) {
                case 'dashboard':
                    $redirect = 'dashboard/' . $user_id;
                    break;

                case 'coin':
                    if (isset($segments[1])) {
                        $redirect = 'coin/' . $user_id;
                    }
                    break;

                case 'friend':
                    if (isset($segments[1])) {
                        $redirect = 'friend/' . $user_id;
                    }
                    break;

                case 'station':
                    if (isset($segments[1]) && $segments[1] == 'purchased' && isset($segments[2])) {
                        $redirect = 'station/purchased/' . $user_id;
                    }
                    break;

                case 'school':
                    if (isset($segments[1]) && $segments[1] == 'search' && isset($segments[2])) {
                        $redirect = 'school/search/' . $user_id;
                    }
                    break;

                case 'textbook':
                    if (isset($segments[1])) {
                        $redirect = 'textbook/' . $user_id;
                    }
                    break;

                case 'timeline':
                    if (isset($segments[1])) {
                        $redirect = 'timeline/' . $user_id;
                    }
                    break;

                case 'pay_service':
                    if (isset($segments[1])) {
                        $redirect = 'pay_service/' . $user_id;
                    }
                    break;

                case 'trophy':
                    if (isset($segments[1])) {
                        $redirect = 'trophy/' . $user_id;
                    }
                    break;

                case 'play':
                    if (isset($segments[1]) && ($segments[1] == 'select_stage' || $segments[1] == 'view_status' || $segments[1] == 'memorize')) {
                        $redirect = 'play/select_drill';
                    }
                    break;
            }
        }

        return redirect($redirect);
    }

    public function change_modal_shown ()
    {
        if (!$this->input->post('id')) {
            return;
        }

        $this->_api('user_rabipoint')->change_modal_shown([
            'id' => $this->input->post('id')
        ]);
        return;
    }
}