<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';
require_once SHAREDPATH . 'controllers/modules/APP_STV_setting_helper.php';
/**
 * Profile controller
 *
 * @author Duy Phan <duy.phan@interest-marketing.net>
 */
class Profile extends Application_controller
{
    public $layout = "layouts/base";
    use APP_STV_setting_helper;
    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * Profile screen MY10
     *
     * @param string  $nickname|user_id
     *
     * return void
     */
    public function detail()
    {
        if ($this->current_user->primary_type == 'parent' && empty($this->students)) {
            return redirect();
        }

        $current_user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id;
        // Get user infomation
        $user_info = $this->_internal_api('user', 'get_detail', [
            'id' => $current_user_id
        ]);

        $cond = [
            'user_id' => $current_user_id,
            'grade_id' => !empty($this->input->param('grade_id')) ? $this->input->param('grade_id') : $user_info['current_grade']['id'],
            'limit' => 100
        ];

        // Get user current textbook
        $res = $this->_api('user_textbook')->get_list($cond);

        // If user don't have any video to show , let shows the most popular
        if(empty($res['result']['items'])) {

            // Get user infomation
            $user_info = $this->_internal_api('user', 'get_detail', [
                'id' => $current_user_id
            ]);

            $subject_list = $this->_api('subject')->get_list([
                'grade_id' => $user_info['current_grade']['id']
            ]);

            $s_list = [];
            foreach ($subject_list['result']['items'] as $k) {
                $s_list[] = $k['id'];
            }

            // Get the most popular subject
            $s_list = $this->_internal_api('video_textbook','get_most_popular',[
                'subject_id' => implode(',', $s_list)
            ]);

            $user_textbooks = $s_list['items'];
        } else {
            $total = count($res['result']['items']);
            $user_textbooks = $res['result']['items'];

            // Get all user textbook items by grade
            while($total < $res['result']['total']){
                $user_textbook = $this->_api('user_textbook')->get_list($cond);
                $total += 100;
                $user_textbooks = $user_textbook['result']['items'];
            }
        }

        $textbook_id = [];
        $t_subject = [];
        foreach($user_textbooks as $k){
            $textbook_id[] = $k['textbook']['id'];
            $t_subject[$k['subject']['id']] = $k['subject'];
        }

        // Get the user video progress
        $video_progress = $this->_api('user_video')->get_progressing([
            'user_id' => $current_user_id
        ]);
       
        $video_id = [];
        // Get video progress status
        foreach($video_progress['result']['items'] as $k) {
            $video_id[] = $k['video']['id'];
        }

        $videos_status = [];
        if($video_id) {
            // Get the user video progress
            $video_progressing = $this->_api('video')->get_progress([
                'video_id' => $video_id
            ]);

            if($video_progressing['result']['items']) {
                foreach($video_progressing['result']['items'] as $k) {
                    $videos_status[$k['video_id']] = $k;
                }
            }
        }

        $cond = ['grade_id' =>  $user_info['current_grade']['id']];
        $cond = !empty($textbook_id) ? array_merge($cond, ['textbook_id' => $textbook_id]) : $cond;

        // Get the most viewer video
        $most_viewer_video = $this->_internal_api('video', 'get_list', $cond);

        // Get list of subject
        $subject_list = $this->_internal_api('subject', 'get_list', [
            'grade_id' => $user_info['current_grade']['id']
        ]);

        $s_list = [];
        foreach($subject_list['items'] as $k) {
            $s_list[] = $k['id'];
        }

        // Get the most popular subject
        $s_list =  $this->_internal_api('video_textbook', 'get_most_popular' ,[
            'subject_id' => implode(',', $s_list)
        ]);

        $chapters = [];
        $deck_id = [];
        foreach($textbook_id as $k) {

            // Get the chapter of each textbook
            $chapter = $this->_internal_api('video_textbook', 'get_chapter', [
                'textbook_id' => $k
            ]);

            if(isset($chapter['items'])) {
                foreach($chapter['items'] as $c) {
                    if(!empty($c['deck_id'])) {
                        $deck_id[] = $c['deck_id'];
                    }
                }
                $chapters[$k] = $chapter['items'];
            }
        }

        // Get video detail
        if(!empty($deck_id)) {
            $video =  $this->_internal_api('deck','get_detail',[
                'deck_id' => $deck_id
            ], [
                'get_questions' => FALSE
            ]);
        }

        $videos = [];
        // Get the video detail
        if(isset($video['items'])) {
            foreach($video['items'] as $d) {
                if (empty($d['id'])) {
                    continue;
                }
                $videos[$d['id']] = $d['video'];
            }
        }

        // Get decks index
        $decks = $this->_api('deck')->get_list([
            'user_id' => $current_user_id,
            'limit' => 12
        ]);

        $user_contract = $this->_api('user_contract')->get_detail([
            'user_id' => $current_user_id
        ]);

        /** @var array $header_news Fetch latest news */
        $header_news = $this->_api('user_news')->get_list_unread([
            'status' => 'public',
            'public_status' => 'available',
            'limit' => 1,
            'sort_by' => 'started_at',
            'sort_position' => 'desc'
        ]);

        $this->_render([
            'videos' => $videos,
            'video_progress' => $video_progress['result']['items'],
            'most_viewer_video' => $this->_sort_subject($most_viewer_video['items'], ['chapter', 'subject_type']),
            'chapters' => $chapters,
            'subject_list' => $this->_sort_subject($s_list['items']),
            'textbooks' => $this->_sort_subject($user_textbooks),
            'grade_name' => $user_info['current_grade']['name'],
            'header_news' => isset($header_news['result']['items'][0]) ?
                $header_news['result']['items'][0] : null,
            'videos_status' => $videos_status,
            'decks' => $decks['result']['items'],
            'user_contract' => isset($user_contract['result']) ? $user_contract['result'] : ''
        ]);
    }

    public function history()
    {
        $view_data = [];
        if($this->current_user->primary_type == 'parent' && empty($user_id) && empty($this->students)) {
            $view_data['no_child'] = TRUE;
            $view_data['history_page'] = TRUE;
            $this->_render($view_data);
            return;
        }

        // Get user id from group
        $this->get_user_id($user_id);

        $params = [];
        $page = !empty($_GET['p']) && is_numeric($_GET['p']) ? $_GET['p'] : 1;
        $limit = PAGINATION_DEFAULT_LIMIT;
        $offset = ($page - 1) * $limit;

        $params = array_merge($params, [
            'limit' => $limit,
            'offset' => $offset,
            'user_id' => $user_id
        ]);

        // Get user information
        $res = $this->_internal_api('learning_history', 'get_list_learning_history', $params);

        $this->_render([
            'videos' => $res,
            'pagination' => [
                'page' => (int) $page,
                'items_per_page' => (int) $params['limit'],
                'total' => (int) $res['total'],
                'offset' => (int) $params['offset'] + 1,
                'limit' => $params['offset'] + $params['limit'] > $res['total'] ? $res['total'] : (int) $params['offset'] + $params['limit']
            ],
            'history_page' => TRUE
        ]);
    }
}