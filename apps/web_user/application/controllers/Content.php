<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';
require_once SHAREDPATH . 'controllers/modules/APP_STV_setting_helper.php';

/**
 * Profile controller
 *
 * @author Duy Phan <duy.phan@interest-marketing.net>
 */
class Content extends Application_controller
{
    public $layout = "layouts/base";
    use APP_STV_setting_helper;

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login');

    }

    /**
     * Content page CO10
     *
     * @params int $subject_id
     */
    public function index($user_id = null)
    {
        if($this->current_user->primary_type == 'parent' && empty($this->students)) {
            $this->_render([
                'no_child' => TRUE,
                'content_page' => TRUE
            ]);
            return;
        }

        if($this->current_user->primary_type == 'parent' && !empty($user_id)) {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            } else {
                redirect('content');
                return;
            }
        }

        $user_id = $this->current_user->primary_type == 'student' ? $this->current_user->id : $this->session->userdata('switch_student_id');

        // Get user infomation
        $user_info = $this->_internal_api('user', 'get_detail', [
            'id' => $user_id
        ]);

        // Get user current textbook
        $textbook = $this->_internal_api('user_textbook', 'get_list', [
            'user_id' => $user_id,
            'grade_id' => !empty($user_info['current_grade']['id']) ? $user_info['current_grade']['id'] : null
        ]);

        $subjects = [];
        $subject_textbooks = [];
        $chapters = [];
        $videos = [];

        // If user don't have any video to show , let shows the most popular
        if (empty($textbook['items'])) {

            $subject_list = $this->_api('subject')->get_list([
                'grade_id' => !empty($user_info['current_grade']['id']) ? $user_info['current_grade']['id'] : null
            ]);

            $s_list = [];
            if (!empty($subject_list['result']['items'])) {
                foreach ($subject_list['result']['items'] as $k) {
                    $s_list[] = $k['id'];
                }
            }

            // Get the most popular subject
            if (!empty($s_list)) {
                $s_list = $this->_internal_api('video_textbook', 'get_most_popular', [
                    'subject_id' => implode(',', $s_list)
                ]);
                $textbook['items'] = $s_list['items'];
            }

        }

        // Group the textbook subject
        if (!empty($textbook['items'])) {
            foreach ($textbook['items'] as $k) {

                // Get the chapter of each textbook
                $chapter = $this->_internal_api('video_textbook', 'get_chapter', [
                    'textbook_id' => $k['textbook']['id']
                ]);

                if (isset($chapter['items'])) {

                    $deck_ids = [];
                    // Get deck_id for videos
                    foreach ($chapter['items'] as $i) {
                        if (!empty($i['deck_id'])) {
                            $deck_ids[] = $i['deck_id'];
                        }
                    }

                    // Get videos
                    if ($deck_ids) {
                        $video = $this->_internal_api('deck', 'get_detail', [
                            'deck_id' => $deck_ids
                        ]);
                    }

                    $video_ids = [];
                    // Get the video detail
                    if (isset($video['items'])) {
                        $total_quetions = [];
                        foreach ($video['items'] as $d) {
                            if (empty($d['id'])) continue;
                            $videos[$d['id']] = $d['video'];
                            $video_ids[] = $d['video']['id'];
                            $total_quetions[$d['id']] = count($d['questions']);
                        }

                        // Get answer situations
                        $situations = $this->_internal_api('learning_history', 'get_chapter_answers', [
                            'video_id' => $video_ids,
                            'user_id' => $user_id
                        ]);

                        $done = [];
                        $in_progress = [];
                        $answer_history = [];
                        foreach($situations['items'] as $key => $item) {
                            if ($item['status'] == 0) {
                                $in_progress[] = $key;
                            } else {
                                $done[] = $key;
                            }

                            $correct_answer = 0;
                            if (!empty($item['question_answer_data'])) {
                                foreach ($item['question_answer_data'] as $answer) {
                                    if ($answer['point'] > 0) {
                                        $correct_answer++;
                                    }
                                }
                            }
                            $answer_history[$key] = $correct_answer;
                        }
                    }

                    $chapters[$k['textbook']['id']]['chapters'] = $chapter['items'];
                    $chapters[$k['textbook']['id']]['in_progress'] = $in_progress;
                    $chapters[$k['textbook']['id']]['done'] = $done;
                    $chapters[$k['textbook']['id']]['correct_answer'] = $answer_history;
                    $chapters[$k['textbook']['id']]['total_questions'] = $total_quetions;

                }

                $subjects[$k['subject']['id']] = $k;
            }
        }

        $return_subjects = [];
        foreach($this->_sort_subject($subjects) AS $item) {
            $return_subjects[$item['subject']['id']] = $item;
            $subject_textbooks[$item['subject']['id']][] = $item['textbook']['id'];
        }

        $this->_render([
            'content_page' => TRUE,
            'chapters' => $chapters,
            'videos' => $videos,
            'subjects' => $return_subjects,
            'subject_textbooks' => $subject_textbooks
        ]);
    }

    /**
     * Content page CO10
     *
     * @params int $subject_id
     */
    public function learning_history($user_id = null)
    {
        if($this->current_user->primary_type == 'parent' && empty($this->students)) {
            $this->_render([
                'no_child' => TRUE,
                'content_page' => TRUE
            ]);
            return;
        }

        if($this->current_user->primary_type == 'parent' && !empty($user_id)) {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            } else {
                redirect('content');
                return;
            }
        }

        $user_id = $this->current_user->primary_type == 'student' ? $this->current_user->id : $this->session->userdata('switch_student_id');

        // Get user infomation
        $user_info = $this->_internal_api('user', 'get_detail', [
            'id' => $user_id
        ]);

        // Get user current textbook
        $textbook = $this->_internal_api('user_textbook', 'get_list', [
            'user_id' => $user_id,
            'grade_id' => !empty($user_info['current_grade']['id']) ? $user_info['current_grade']['id'] : null
        ]);

        $subjects = [];
        $subject_textbooks = [];
        $chapters = [];
        $videos = [];

        // If user don't have any video to show , let shows the most popular
        if (empty($textbook['items'])) {

            $subject_list = $this->_api('subject')->get_list([
                'grade_id' => !empty($user_info['current_grade']['id']) ? $user_info['current_grade']['id'] : null
            ]);

            $s_list = [];
            if (!empty($subject_list['result']['items'])) {
                foreach ($subject_list['result']['items'] as $k) {
                    $s_list[] = $k['id'];
                }
            }

            // Get the most popular subject
            if (!empty($s_list)) {
                $s_list = $this->_internal_api('video_textbook', 'get_most_popular', [
                    'subject_id' => implode(',', $s_list)
                ]);
                $textbook['items'] = $s_list['items'];
            }

        }

        // Group the textbook subject
        if (!empty($textbook['items'])) {
            foreach ($textbook['items'] as $k) {

                // Get the chapter of each textbook
                $chapter = $this->_internal_api('video_textbook', 'get_chapter', [
                    'textbook_id' => $k['textbook']['id']
                ]);

                if (isset($chapter['items'])) {

                    $deck_ids = [];
                    // Get deck_id for videos
                    foreach ($chapter['items'] as $i) {
                        if (!empty($i['deck_id'])) {
                            $deck_ids[] = $i['deck_id'];
                        }
                    }

                    // Get videos
                    if ($deck_ids) {
                        $video = $this->_internal_api('deck', 'get_detail', [
                            'deck_id' => $deck_ids
                        ]);
                    }

                    $video_ids = [];
                    // Get the video detail
                    if (isset($video['items'])) {
                        $total_quetions = [];
                        foreach ($video['items'] as $d) {
                            if (empty($d['id'])) continue;
                            $videos[$d['id']] = $d['video'];
                            $video_ids[] = $d['video']['id'];
                            $total_quetions[$d['id']] = count($d['questions']);
                        }

                        // Get answer situations
                        $situations = $this->_internal_api('learning_history', 'get_chapter_answers', [
                            'video_id' => $video_ids,
                            'user_id' => $user_id
                        ]);

                        $done = [];
                        $in_progress = [];
                        $answer_history = [];
                        $total_second = [];
                        $time_scores = [];
                        foreach($situations['items'] as $key => $item) {
                            if ($item['status'] == 0) {
                                $in_progress[] = $key;
                            } else {
                                $done[] = $key;
                            }

                            $correct_answer = 0;
                            if (!empty($item['question_answer_data'])) {
                                foreach ($item['question_answer_data'] as $answer) {
                                    if ($answer['point'] > 0) {
                                        $correct_answer++;
                                    }
                                }
                            }
                            $time_scores[$key] = $item['created_at'];
                            $answer_history[$key] = $correct_answer;
                            $total_second[$key] = $item['total_second'];
                        }
                    }

                    $chapters[$k['textbook']['id']]['chapters'] = $chapter['items'];
                    $chapters[$k['textbook']['id']]['in_progress'] = $in_progress;
                    $chapters[$k['textbook']['id']]['done'] = $done;
                    $chapters[$k['textbook']['id']]['correct_answer'] = $answer_history;
                    $chapters[$k['textbook']['id']]['total_questions'] = $total_quetions;
                    $chapters[$k['textbook']['id']]['total_second'] = $total_second;
                    $chapters[$k['textbook']['id']]['time_scores'] = $time_scores;

                }

                $subjects[$k['subject']['id']] = $k;
            }
        }

        $return_subjects = [];
        foreach($this->_sort_subject($subjects) AS $item) {
            $return_subjects[$item['subject']['id']] = $item;
            $subject_textbooks[$item['subject']['id']][] = $item['textbook']['id'];
        }

        $this_month = business_date('Y-m');
        $last_month = business_date('Y-m', strtotime('-30 days'));

        $watching_res = $this->_api('learning_history')->get_monthly_report([
            'user_id' => $user_id
        ]);

        $watching_monthly_report['this_month'] = isset($watching_res['result'][$this_month]) ? $watching_res['result'][$this_month] : 0;
        $watching_monthly_report['last_month'] = isset($watching_res['result'][$last_month]) ? $watching_res['result'][$last_month] : 0;

        $contract = $this->_api('user_contract')->get_detail([
            'user_id' => $user_id
        ]);

        $contract_status = strtotime($contract['result']['expired_time'])  < strtotime(business_date('Y-m-d H:i:s')) ? FALSE : TRUE;

        $this->_render([
            'watching_monthly_report' => $watching_monthly_report,
            'chapters' => $chapters,
            'videos' => $videos,
            'subjects' => $return_subjects,
            'subject_textbooks' => $subject_textbooks,
            'user_info' => $user_info,
            'contract_status' => $contract_status
        ]);
    }

    public function monthly_report($user_id = null)
    {
        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            } else {
                redirect('dashboard/'.$user_id);
            }
        } else {
            $user_id = $this->current_user->id;
        }

        $view_data = [];
        $view_data['user_nickname'] = $this->_api('user')->get_detail([
            'id' => $user_id
        ])['result']['nickname'];

        $view_data['monthly_report'] = $this->_api('learning_history')->get_monthly_report([
            'user_id' => $user_id
        ])['result'];

        return $this->_render($view_data);
    }
}