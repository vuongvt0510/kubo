<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Subject controller
 *
 * @author Duy Phan <duy.phan@interest-marketing.net>
 */
class Subject extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['detail']
        ]);
    }

    /**
     * Subject screen TP15
     *
     * @param string $subject_type
     * @param int $textbook_id
     *
     * return void
     */
    public function detail($subject_id = null, $textbook_id = null)
    {
        if (!empty($textbook_id)) {
            // Get textbook detail
            $textbook = $this->_internal_api('textbook', 'get_detail', [
                'textbook_id' => (int) $textbook_id
            ]);
        } else {
            // Get the most popular subject
            $s_list = $this->_api('video_textbook')->get_most_popular([
                'subject_id' => (int) $subject_id
            ]);

            $textbook = reset($s_list['result']['items']);
            $textbook_id = isset($textbook['textbook']['id']) ? $textbook['textbook']['id'] : null;
        }

        // Show 404 Page if record does not exist
        if (!$textbook_id) {
            return $this->_render_404();
        }

        // Call the chapter API
        $res = $this->_api('video_textbook')->get_chapter([
            'textbook_id' => (int) $textbook_id
        ]);

        // Show 404 Page if record does not exist
        if (!isset($res['result']['items']) || empty($res['result']['items'])) {
            return $this->_render_404();
        }

        $deck_id = [];
        foreach ($res['result']['items'] as $k) {
            if (!empty($k['deck_id'])) {
                $deck_id[] = $k['deck_id'];
            }
        }

        // Get other subject
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
        $s_list = $this->_api('video_textbook')->get_most_popular([
            'subject_id' => implode(',', $s_list)
        ]);

        // Get the video detail
        $video = $this->_api('deck')->get_detail([
            'deck_id' => $deck_id
        ]);

        // Get the chapter for subject
        $o_chapters = [];
        $o_subject = [];
        foreach ($s_list['result']['items'] as $k) {

            if ($textbook_id != $k['textbook']['id']) {
                // Get the chapter of each textbook
                $chapter = $this->_api('video_textbook')->get_chapter([
                    'textbook_id' => $k['textbook']['id']
                ]);

                if ($chapter['result']['items']) {
                    foreach ($chapter['result']['items'] as $c) {
                        if (!empty($c['deck_id'])) {
                            $deck_id[] = $c['deck_id'];
                        }
                    }
                    $o_chapters = $chapter['result']['items'];
                }
                $o_subject = $k;
                break;
            }
        }

        // Get chapter video
        $videos = [];
        if (isset($video['result']['items']) || !empty($video['result']['items'])) {
            foreach ($video['result']['items'] as $k) {
                $videos[$k['id']] = $k['video'];
            }
        }
        // Get other textbook
        $o_textbooks = $this->_api('textbook')->get_list([
            'subject_id' => $textbook['subject']['id']
        ]);

        $this->_render([
            'first_chapter' => array_shift($res['result']['items']),
            'chapters' => $res['result']['items'],
            'o_chapters' => $o_chapters,
            'videos' => $videos,
            'textbook' => $textbook,
            'o_subject' => $o_subject,
            'o_textbooks' => $o_textbooks['result']['items'],
            'meta' => [
                'title' => $textbook['grade']['name']."の".$textbook['subject']['short_name']."（". $textbook['publisher']['name']. "）を予習・復習するならスクールTV",
                'description' => "無料の動画で" . $textbook['grade']['name']. "の" . $textbook['subject']['short_name']."（". $textbook['publisher']['name']. "）を予習・復習しよう。スクールTVなら、ポイントをしぼった解説・説明でわかりやすい！あなたのスマートフォンやPCで、いつでもどこでも学習できます。"
            ]
        ]);
    }
}