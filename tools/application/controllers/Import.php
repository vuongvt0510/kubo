<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Import master data from Spreadsheet API
 *
 * @package Controller
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */

require_once SHAREDPATH . "core/APP_Cli_controller.php";

/**
 * Class Import
 *
 * @property Google_Spreadsheet google_spreadsheet
 * @property Master_school_model master_school_model
 * @property Master_area_pref_model master_area_pref_model
 * @property Master_area_model master_area_model
 * @property Master_postalcode_model master_postalcode_model
 * @property Textbook_model textbook_model
 * @property Textbook_content_model textbook_content_model
 * @property Video_model video_model
 * @property Question_model question_model
 * @property Master_subject_model master_subject_model
 * @property Video_question_timeline_model video_question_timeline_model
 * @property Image_model image_model
 * @property Deck_image_model deck_image_model
 * @property Deck_model deck_model
 * @property Deck_package_model deck_package_model
 * @property Deck_category_model deck_category_model
 * @property Stage_model stage_model
 * @property Brightcove_studio brightcove_studio
 * @property Brightcove_video brightcove_video
 * @property Publisher_model publisher_model
 * @property Memorization_model memorization_model
 */
class Import extends APP_Cli_controller
{
    // Define type for enum db field
    var $define_school_type = [
        '小学校' => 'elementary',
        '中学校' => 'juniorhigh',
        '中等教育学校' => 'secondary'
    ];

    var $define_school_kind = [
        '国立' => 'national',
        '公立' => 'public',
        '私立' => 'private',
        '社立' => 'startcompany'
    ];

    var $define_question_type = [
        '選択式' => 'single',
        '複数選択式' => 'multiple',
        '連続質問' => 'multi_field',
        '数字入力' => 'text',
        '順序' => 'sort',
        'グループ' => 'group',
        '連続数字質問' => 'multi_text'
    ];

    // List school file
    var $list_school_files = [
        'juniorhigh' => '2015_juniorhigh_school',
        'secondary'  => '2015_secondary_school',
        'elementary' => '2015_primary_school',
    ];

    // Entry error when importing
    var $errors = [];

    function __construct()
    {
        parent::__construct();

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        // Load library
        $this->load->library('Google/Google_Spreadsheet');

        // Load model
        $this->load->model('master_school_model');
        $this->load->model('master_area_pref_model');
        $this->load->model('master_area_model');
        $this->load->model('master_postalcode_model');
    }

    /**
     * Execute
     */
    function execute()
    {
        //$this->publisher();
        //$this->subject();
        $this->textbooks();
        $this->drills();
        $this->images();

        $this->dump_brightcove_image_link();
        $this->dump_brightcove_image_to_db();

        $this->show_error_log();
    }

    /**
     * Import school list from Spreadsheet
     */
    function school()
    {
        foreach($this->list_school_files as $file) {
            // Get data of entries
            $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet($file, 'schools');

            foreach ($list_entries as $entry) {
                // Show kcode to terminal screen
                log_message('INFO', 'Import school "' . $entry['kcode'].'"');

                // Prepare data to insert or update
                $data = [
                    'area_id' => $this->get_area_id($entry['citycode'], $entry['pref'], $entry['city']),
                    'postalcode_id' => $this->get_postalcode_id($entry['postalcode']),
                    'kind' => $this->define_school_kind[$entry['kind']],
                    'type' => $this->define_school_type[$entry['schooltype']],
                    'name' => $entry['name1'],
                    'short_name' => isset($entry['name2']) ? $entry['name2'] : $entry['name1'],
                    'address' => $entry['address'],
                    'students' => (int) $entry['students'],
                    'kcode' => $entry['kcode']
                ];

                $this->master_school_model->create($data, ['mode' => 'replace']);

            }
        }
    }

    /**
     * Import postalcode from google sheet
     *
     * @throws Exception
     * @throws Google_Exception_api
     */
    function postalcode()
    {
        // Get data of entries
        $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet('master_postalcode', 'Sheet1');

        foreach ($list_entries as $entry) {
            // Show kcode to terminal screen
            log_message('INFO', 'Import postalcode "' . $entry['code'].'"');

            // Prepare data to insert or update
            $data = [
                'postalcode' => trim($entry['code'])
            ];

            $this->master_postalcode_model->create($data, ['mode' => 'replace']);
        }
    }

    /**
     * Import publisher from google sheet
     *
     * @throws Exception
     * @throws Google_Exception_api
     */
    function publisher()
    {
        $this->load->model('publisher_model');

        // Get data of entries
        $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet('Publisher_master', '出版社一覧');

        foreach ($list_entries as $entry) {
            // Show kcode to terminal screen
            log_message('INFO', 'Import publisher "' . $entry['name'].'"');

            // Prepare data to insert or update
            $data = [
                'id' => (int) $entry['id'],
                'name' => trim($entry['name']),
                'short_name' => trim($entry['shortname']),
            ];

            $this->publisher_model->create($data, ['mode' => 'replace']);
        }
    }

    /**
     * Import subject from google sheet
     *
     * @throws Exception
     * @throws Google_Exception_api
     */
    function subject()
    {
        $this->load->model('master_subject_model');

        // Get data of entries
        $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet('Publisher_master', '教科一覧');

        foreach ($list_entries as $entry) {
            if( !$entry['id'] ) continue;

            log_message('INFO', 'Import subject "' . $entry['subject'].'"');

            // Prepare data to insert or update
            $data = [
                'id' => (int) trim($entry['id']),
                'grade_id' => $this->get_grade_id($entry['grade'], $entry['gradeid']),
                'name' => $entry['grade'].' '.trim($entry['subject']),
                'short_name' => $entry['subject'],
                'color' => $entry['color'],
                'type' => $entry['type'],
                'rubi' => $entry['rubi']
            ];

            $this->master_subject_model->create($data, ['mode' => 'replace']);
        }
    }

    /**
     * Import or textbook
     * @param string $only_spread
     * @throws Google_Exception_api
     */
    function textbooks($only_spread = '')
    {
        /** @var string $folder_id of google drive folder of production textbook */
        $folder_id = '0B5mCmfGFQgZCN05HbG9SMXNTSk0';

        if (in_array(ENVIRONMENT, ['development', 'testing'])) {
            $folder_id = '0B5mCmfGFQgZCNGJvd0RkbTZxTGM';
        }

        $drive_service = $this->google_spreadsheet->get_drive_instance();

        $results = $drive_service->files->listFiles([
            'q' => "'$folder_id' in parents and mimeType='application/vnd.google-apps.spreadsheet'"
        ]);

        foreach ($results->getItems() as $item) {

            if (!empty($only_spread) && $item->title != $only_spread) {
                continue;
            }

            log_message('INFO', '[Import][Textbook] "' . $item->title . '"');

            $spread = $this->google_spreadsheet->get_spreadsheet_by_id($item->id);

            $this->textbook($item->title, $spread);
        }

    }

    /**
     * Import textbook from google sheet
     *
     * @param string $spread_title
     * @param object $spread
     * @throws Exception
     * @throws Google_Exception_api
     */
    private function textbook($spread_title = '', $spread = null)
    {
        $this->load->model('textbook_model');
        $this->load->model('textbook_content_model');

        // Get data of menu textbook entries
        $spread_title = empty($spread_title) ? 'Textbook master' : $spread_title;

        $textbook_spread = empty($spread) ? $this->google_spreadsheet->get_spreadsheet_by_title($spread_title) : $spread;

        $list_textbook_sheet_titles = $this->google_spreadsheet->get_list_sheet_titles($textbook_spread);

        foreach($list_textbook_sheet_titles AS $key => $sheet_title) {

            log_message('INFO', 'Import textbook "' . $sheet_title . '" of spread "'. $spread_title .'"');

            $entries = $this->google_spreadsheet->get_list_entries_in_sheet($textbook_spread, $sheet_title);

            // Store all header key for row
            $keys = [];
            // Process Beginning row of chapter content, because textbook has many publisher
            $chapter_header_row = 3;

            foreach($entries as $row => $entry) {
                if(count($entry) >= 6) {
                    // If meet header row
                    $chapter_header_row = $row;

                    foreach ($entry as $key => $val) {
                        $keys[] = $key;
                    }

                    break;
                }
            }

            // Store chapter data
            $chapter_datas = [];
            $chapter_ids_db = [];

            $total_row_in_sheet = count($entries);

            $chapter_order = 0;

            for( $i = $chapter_header_row + 1; $i < $total_row_in_sheet; ++$i ) {

                // Find deck_id
                $deck_id = null;
                if (!empty($entries[$i][$keys[5]])) {
                    $video_key = $entries[$i][$keys[5]];
                    $brightcove_key = isset($keys[6]) && !empty($entries[$i][$keys[6]]) ? $entries[$i][$keys[6]] : '';
                    $deck = $this->create_deck_from_video($video_key, $brightcove_key);
                    $deck_id = (int) $deck->id;
                }

                $chapter_datas[] = [
                    'textbook_id' => 0, // Process in below code
                    'deck_id' => $deck_id,
                    'chapter_name' => !empty($entries[$i][$keys[2]]) ?
                        str_replace(["\n", "\r", "\r\n"], "\\n", $entries[$i][$keys[2]]) : '',
                    'name' => !empty($entries[$i][$keys[3]]) ? $entries[$i][$keys[3]] : '',
                    'order' => ++$chapter_order,
                    'description' => !empty($entries[$i][$keys[4]]) ? $entries[$i][$keys[4]] : '',
                    'video_id' => $entries[$i][$keys[5]]
                ];

                // Dump textbook content
                $decode = $this->json_decode(end($entries[$i]));

                $chapter_ids_db[] = empty($decode) ? [] : $decode;
            }

            // Dump textbook with every publisher
            $textbook_data = [
                'year_id' => 1, // Currently, use 1 as default, this will be updated later
                'publisher_id' => 0,
                'subject_id' => (int)$entries[0][$keys[0]],
                'name' => trim($entries[0][$keys[1]])
            ];

            // Duplicate textbook with every publisher
            for($i = 1; $i < $chapter_header_row; ++$i) {
                $textbook_data['publisher_id'] = (int) $entries[$i][$keys[0]];

                // Check if textbook is exit in db
                $textbook = $this->textbook_model
                    ->find_by([
                        'year_id' => $textbook_data['year_id'],
                        'publisher_id' => $textbook_data['publisher_id'],
                        'subject_id' => $textbook_data['subject_id']
                    ]);

                if (!empty($textbook)) {
                    $textbook_data['id'] = $textbook->id;
                }

                $textbook = $this->textbook_model->create($textbook_data, [
                    'mode' => 'replace',
                    'return' => TRUE]
                );

                // Get all chapter relation with this textbook
                $list_rc_chapters = $this->textbook_content_model
                    ->where('textbook_id', $textbook->id)
                    ->all();

                $list_cache_chapters = [];

                foreach ($list_rc_chapters as $rc) {
                    $list_cache_chapters[(int) $rc->order] = $rc->id;
                }

                foreach($chapter_datas AS $key => $chapter_data) {

                    log_message('INFO', 'Import chapter "' . $chapter_data['order'] . '" for textbook ' . $textbook->id);

                    $chapter_data['textbook_id'] = $textbook->id;

                    if (isset($list_cache_chapters[$chapter_data['order']])) {
                        $chapter_data['id'] = $list_cache_chapters[$chapter_data['order']];
                    } else {
                        unset($chapter_data['id']);
                    }

                    $textbook_content = $this->textbook_content_model->create($chapter_data, [
                        'mode' => 'replace',
                        'return' => TRUE
                    ]);
                }
            }
        }
    }

    /**
     * Auto import image from google drive to sample_image folder for drill
     *
     * @param int|bool $allow_update_image, allow importer update image into DB
     * @throws Exception
     * @throws Google_Exception_api
     */
    public function images($allow_update_image = 0)
    {
        $this->load->helper('file');
        $this->load->model('image_model');

        $image_types = [
            //'small' => ['type' => 'max_width', 'max_width' => 160,  'quality' => 100],
            //'medium' => ['type' => 'max_width', 'max_width' => 320, 'quality' => 100],
            'original' => ['type' => 'max_width', 'max_width' => 700, 'quality' => 100]
        ];

        $drive = $this->google_spreadsheet->get_drive_instance();

        $folder_id = '0B5mCmfGFQgZCQVZ5MkJLQlBkMW8';

        if (in_array(ENVIRONMENT, ['development', 'testing'])) {
            $folder_id = '0B5mCmfGFQgZCcnBNazk4aDNsaGs';
        }

        $results = $drive->files->listFiles([
            'q' => "'$folder_id' in parents"
        ]);

        foreach ($results->getItems() as $item) {
            log_message('INFO', '[Import][Images] Import images on folder ' . $item->getTitle());

            $files_result = $drive->files->listFiles([
                'q' => "'$item->id' in parents"
            ]);

            foreach($files_result->getItems() AS $file) {

                if (strpos($file->mimeType, 'image') !== 0) {
                    $this->add_error_log("[Image] File '".$file->title."' in folder '".$item->title."'' is not image file");
                    continue;
                }

                log_message('INFO', '[Import][Images] '.$file->title.' | ' . $item->getTitle());

                // Check key
                $key = mb_trim($file->title);

                $image = $this->image_model
                    ->select('id, key')
                    ->where([
                        'key' => $key,
                        'holder_type' => 'drill_image'
                    ])
                    ->first();

                if (!empty($image) && !$allow_update_image) {
                    log_message('INFO', '[Import][Images] Ignore import '.$file->title.' | ' . $item->getTitle() . ' because it is exist in DB');
                    continue;
                }

                $request = new Google_Http_Request($file->downloadUrl, 'GET', null, null);

                $httpRequest = $drive->getClient()->getAuth()->authenticatedRequest($request);

                $data = [
                    'data' => $httpRequest->getResponseBody(),
                    'key' => $key,
                    'holder_type' => 'drill_image'
                ];

                if (empty($image)) {
                    $this->image_model->create_from_data($data, ['only_original' => FALSE], $image_types);
                } else if ($allow_update_image) {
                    $this->image_model->update_from_data($key, $data, ['only_original' => FALSE], $image_types);
                }
            }
        }
    }

    /**
     * Import all drills from google sheet
     * @param string $only_spread
     * @param string $only_sheet
     * @throws Google_Exception_api
     */
    public function drills($only_spread = '', $only_sheet = '')
    {
        /** @var string $folder_id of production import folder */
        $folder_id = '0B5mCmfGFQgZCdlBCQVJSYkExUFk';

        if (in_array(ENVIRONMENT, ['development', 'testing'])) {
            $folder_id = '0B5mCmfGFQgZCdEVYam1DY3MtdlE';
        }

        $drive_service = $this->google_spreadsheet->get_drive_instance();

        $results = $drive_service->files->listFiles([
            'q' => "'$folder_id' in parents and mimeType='application/vnd.google-apps.spreadsheet'"
        ]);

        foreach ($results->getItems() as $item) {

            if ($only_spread && $item->title != $only_spread) {
                continue;
            }

            log_message('INFO', 'Import Drill Spread "' . $item->title . '"');

            $spread = $this->google_spreadsheet->get_spreadsheet_by_id($item->id);

            $this->drill($spread, $item->title, $only_sheet);
        }
    }

    /**
     * Import drill from google sheet
     *
     * @param object $drill_spread
     * @param string $spread_title
     * @param string $only_sheet only import
     *
     * @throws Exception
     * @throws Google_Exception_api
     */
    private function drill($drill_spread = null, $spread_title = '', $only_sheet = '')
    {
        $this->load->model('question_model');
        $this->load->model('video_question_timeline_model');

        $list_drill_sheets = $this->google_spreadsheet->get_list_sheet_titles($drill_spread);

        foreach($list_drill_sheets as $sheet_title) {

            if ($only_sheet && $sheet_title != $only_sheet) {
                continue;
            }

            // Get data of question entries
            $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet($drill_spread, $sheet_title);

            foreach ($list_entries as $index => $entry) {

                if (empty(mb_trim($entry['videoid'])) && empty(mb_trim($entry['開始秒数秒'])) && empty(mb_trim($entry['問題id'])) && empty(mb_trim($entry['問題タイプ'])) ) {
                    continue;
                }

                $question_type = isset($this->define_question_type[$entry['問題タイプ']]) ? $this->define_question_type[$entry['問題タイプ']] : NULL;
                // Log error
                if(!$question_type) {
                    $this->add_error_log("[Question] Can not import entry $index of $spread_title | $sheet_title  : Wrong question type '" . $entry['問題タイプ']. "'");
                    continue;
                }

                if (empty($entry['videoid'])) {
                    $this->add_error_log("[Question] Can not import entry $index of $spread_title | $sheet_title  : Video ID is not setted");
                    continue;
                }

                if (empty($entry['問題id'])) {
                    $this->add_error_log("[Question] Can not import entry $index of $spread_title | $sheet_title  : Data is not valid at field 問題ID");
                    continue;
                }

                if (empty($entry['開始秒数秒'])) {
                    $this->add_error_log("[Question] Can not import entry $index of $spread_title | $sheet_title  : Data is not valid at field 開始秒数秒");
                    continue;
                }

                log_message('INFO', 'Import question '.$entry['問題id'].' for video "' . $entry['videoid'].'"');

                $video_id = mb_trim($entry['videoid']);

                $deck = $this->create_deck_from_video($video_id);

                // Add question

                // process function to get answer data for each question type. Example: get_data_answer_single
                $function_build_question = 'get_data_answer_'.$question_type;

                $question_data = [
                    'deck_id' => $deck->id,
                    'type' => $question_type,
                    'question_key' => $video_id . '-' . $entry['問題id'],
                    'data' => json_encode(
                        array_merge(
                            $this->get_question_data_common($entry),
                            $this->$function_build_question($entry)
                        ),
                        JSON_HEX_APOS
                    )
                ];

                $question = $this->question_model->find_by([
                    'question_key' => $question_data['question_key']
                ]);

                if (!empty($question)) {
                    $question_data['id'] = $question->id;
                }

                $question = $this->question_model->create($question_data, [
                    'mode' => 'replace',
                    'return' => TRUE
                ]);

                // Set timeline for question
                $video_question_timeline = $this->video_question_timeline_model
                    ->where('video_id', $deck->video_id)
                    ->where('question_id', $question->id)
                    ->first();

                if (empty($video_question_timeline)) {
                    $this->video_question_timeline_model->create([
                        'video_id' => $deck->video_id,
                        'question_id' => $question->id,
                        'second' => $this->convert_string_to_second($entry['開始秒数秒'])
                    ], ['mode' => 'replace']);
                } else {
                    $this->video_question_timeline_model->update($deck->video_id, $question->id, [
                        'second' => $this->convert_string_to_second($entry['開始秒数秒'])
                    ]);
                }
            }
        }

        //$this->show_error_log();
    }

    public function escape_special_characters($string = '')
    {
        if (!$string) return $string;

        return str_replace(["\r\n", "\n", "\r", "\b", "\f", "\t", "\v", '"', "\\"], ["\\n", "\\n", "\\n", "\\b", "\\f", "\\t", "\\v", '\"', '\\\\'], $string);

    }

    /**
     * Convert string to second
     * @param string $second_entry
     *
     * @return int|string
     */
    private function convert_string_to_second($second_entry = '') {

        if (strpos($second_entry, ':') !== FALSE) {
            list($minute, $second) = explode(':', $second_entry);
            $minute = (int) $minute;
            $second = (int) $second;
            $second_entry = $minute * 60 + $second;
        }

        return $second_entry;
    }

    /**
     * Build question data common for all type
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_question_data_common($entry)
    {
        $data = [
            'correct_return_second' => !empty($entry['正解時再生秒数秒']) && (string)$entry['正解時再生秒数秒'] != "0" ?
                $this->convert_string_to_second($entry['正解時再生秒数秒']) : null,
            'wrong_return_second' => !empty($entry['不正解時再生秒数秒']) && (string)$entry['不正解時再生秒数秒'] != "0" ?
                $this->convert_string_to_second($entry['不正解時再生秒数秒']) : null,
            'time_limit' => 30,
            'commentary' => '',
            'question' => $this->escape_special_characters($entry['問題']),
            'random' => (isset($entry['ランダム']) && strtoupper(trim($entry['ランダム'])) == 'OFF') ? 'off' : 'on'
        ];

        //
        if ($this->define_question_type[$entry['問題タイプ']] == 'multi_text') {
            $data['random'] = 'off';
        }

        // Process question image
        if( !empty($entry['画像ファイル名']) ) {
            $data['question_images'] = [];
            $images = explode(':', $entry['画像ファイル名']);

            foreach($images as $order => $image) {
                $data['question_images'][] = [
                    'url' => mb_trim($image),
                    'caption' => '',
                    'width' => null,
                    'height' => null,
                    'order' => $order + 1
                ];
            }
        }

        return $data;
    }

    /**
     * Clean all unnecessary, return array of answers. Ex: [1 => answer1, 2 => answer2 ..]
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_answer_entry($entry)
    {
        unset(
            $entry['videoid'],
            $entry['開始秒数秒'],
            $entry['問題id'],
            $entry['問題タイプ'],
            $entry['問題'],
            $entry['画像ファイル名'],
            $entry['正解時再生秒数秒'],
            $entry['不正解時再生秒数秒'],
            $entry['正解']
        );

        if (isset($entry['ランダム'])) {
            unset($entry['ランダム']);
        }

        // Remove end element of entry is json cache
        if ($this->json_decode(end($entry)) !== null) {
            array_pop($entry);
        }

        $answers = [];
        $count = 0;
        foreach($entry as $value) {
            $answers[++$count] = $value;
        }

        return $answers;
    }

    /**
     * Build data answer for single question type
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_data_answer_single($entry)
    {
        $data = [
            'answers' => []
        ];

        $right_answer = $entry['正解'];

        $entry = $this->get_answer_entry($entry);

        foreach($entry as $answer_key => $answer) {

            if (strlen($answer) == 0) {
                continue;
            }

            // Split image and text in answer
            $image = null;
            $text = $answer;
            if( strpos($answer, 'image:') !== FALSE ) {
                $split_arr = explode(':', $answer);
                $image = $split_arr[1];
                $text = $split_arr[2];
            }

            $data['answers'][] = [
                'text' => $this->escape_special_characters($text),
                'image_url' => $image,
                'correct' => $answer_key == $right_answer ? TRUE : FALSE,
                'order' => null
            ];
        }

        return $data;
    }

    /**
     * Build data answer for multiple question type
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_data_answer_multiple($entry)
    {
        $data = [
            'answers' => []
        ];

        // Array of right answers
        $right_answers = explode(',', trim($entry['正解']));

        $entry = $this->get_answer_entry($entry);

        foreach($entry as $answer_key => $answer) {
            if (strlen($answer) == 0) {
                continue;
            }
            // Split image and text in answer
            $image = null;
            $text = $answer;
            if( strpos($answer, 'image:') !== FALSE ) {
                $split_arr = explode(':', $answer);
                $image = $split_arr[1];
                $text = $split_arr[2];
            }

            $data['answers'][] = [
                'text' => $this->escape_special_characters($text),
                'image_url' => $image,
                'correct' => in_array($answer_key, $right_answers)  ? TRUE : FALSE,
                'order' => null
            ];
        }

        return $data;
    }

    /**
     * Build data answer for multi field question type
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_data_answer_multi_field($entry)
    {
        $data = [
            'answers' => []
        ];

        $entry = $this->get_answer_entry($entry);
        // Check if answer is dummy value
        $is_dummy = FALSE;

        foreach($entry as $answer_key => $answer) {
            if(strlen($answer) == 0) {
                // Enable dummy flash when answer is empty
                $is_dummy = TRUE;
                continue;
            }

            // Split image and text in answer
            $image = NULL;
            $text = $answer;
            if( strpos($answer, 'image:') !== FALSE ) {
                $split_arr = explode(':', $answer);
                $image = $split_arr[1];
                $text = $split_arr[2];
            }

            $data['answers'][] = [
                'text' => $this->escape_special_characters($text),
                'image_url' => $image,
                'correct' => !$is_dummy,
                'order' => $answer_key
            ];
        }

        return $data;
    }

    /**
     * Build data answer for text question type
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_data_answer_text($entry)
    {
        $data = [
            'answers' => []
        ];

        $right_answer = $entry['正解'];

        $entry = $this->get_answer_entry($entry);

        foreach($entry as $answer_key => $answer) {

            if (strlen($answer) == 0) {
                continue;
            }

            $data['answers'][] = [
                'text' => $this->escape_special_characters($answer),
                'image_url' => NULL,
                'correct' => $right_answer,
                'order' => NULL
            ];
        }

        return $data;
    }

    /**
     * Build data answer for sort question type
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_data_answer_sort($entry)
    {
        $data = [
            'answers' => []
        ];

        $right_answer = $entry['正解'];

        $entry = $this->get_answer_entry($entry);

        // Check if answer is dummy value
        $is_dummy = FALSE;

        foreach($entry as $answer_key => $answer) {
            if (strlen($answer) == 0) {
                $is_dummy = TRUE;
                continue;
            }
            // Split image and text in answer
            $image = NULL;
            $text = $answer;
            if( strpos($answer, 'image:') !== FALSE ) {
                $split_arr = explode(':', $answer);
                $image = $split_arr[1];
                $text = $split_arr[2];
            }

            $data['answers'][] = [
                'text' => $this->escape_special_characters($text),
                'image_url' => $image,
                'correct' => !$is_dummy,
                'order' => $answer_key
            ];
        }

        return $data;
    }

    /**
     * Build data answer for group question type
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_data_answer_group($entry)
    {
        $data = [
            'question_groups' => [],
            'answers' => []
        ];

        $right_answer = $entry['正解'];

        $entry = $this->get_answer_entry($entry);

        foreach($entry as $answer_key => $answer) {
            if (strlen($answer) == 0) {
                continue;
            }

            $split_arr = explode(':', $answer);

            $group = $split_arr[0];
            if( !in_array($group, $data['question_groups']) ) {
                $data['question_groups'][] = $group;
            }

            // Split image and text in answer
            $image = NULL;
            $text = $split_arr[1];

            if( $split_arr[1] == 'image' ) {
                $image = $split_arr[2];
                $text = $split_arr[3];
            }

            $data['answers'][] = [
                'text' => $this->escape_special_characters($text),
                'image_url' => $image,
                'correct' => $group,
                'order' => NULL
            ];
        }

        return $data;
    }

    /**
     * Build data answer for multi text question type
     *
     * @param array $entry of row google sheet
     * @return array
     */
    private function get_data_answer_multi_text($entry)
    {
        $data = [
            'answers' => []
        ];

        $entry = $this->get_answer_entry($entry);

        foreach($entry as $answer_key => $answer) {
            if (strlen($answer) == 0) {
                continue;
            }

            $data['answers'][] = [
                'text' => '',
                'image_url' => NULL,
                'correct' => $answer,
                'order' => NULL
            ];
        }

        return $data;
    }

    /**
     * Add error entry
     *
     * @param string $error entry
     * @return void
     */
    private function add_error_log($error = '')
    {
        $this->errors[] = $error;
    }

    /**
     * Show all error entry to cli
     *
     * @return void
     */
    private function show_error_log()
    {
        if(!empty($this->errors)) {
            log_message('INFO', 'Some entry can not import');
            foreach($this->errors as $error) {
                log_message('INFO', $error);
            }

            // Send email inform to admin
            $this->load->library('email');
            $this->email
                ->from($this->config->item('mail_from'), $this->config->item('mail_from_name'))
                ->to('nomura@interest-marketing.net', 'Akiyuki Nomura')
                ->cc('duytt@nal.vn', 'Duy Ton', TRUE)
                ->cc('tominaga@interest-marketing.net', 'Sayuri', TRUE)
                ->subject('[E-learning][Import-Cron] Some entry error when cronjob import data at ' . business_date('Y-m-d H:i:s'))
                ->message(implode("\r\n", $this->errors))
                ->send();

        }
    }

    /**
     * Auto generate video from name, unreal data
     *
     * @param string $name of video
     *
     * @return object $video
     */
    private function auto_generate_video($name = '')
    {
        $this->load->model('video_model');
        $this->load->helper('string');

        $video = $this->video_model->find_by(['name' => $name]);

        if( !$video ) {
            $data = [
                'name' => $name,
                'description' => 'Description Demo',
                'brightcove_id' => random_string('alnum', 64),
                'type' => 'textbook'
            ];

            $video = $this->video_model->create($data, [
                'mode' => 'replace',
                'return' => TRUE
            ]);
        }
        return $video;
    }

    /**
     * Get or create deck from
     * @param string $video_key
     * @param string $brightcove_id
     *
     * @return object $deck record
     * @throws Exception
     */
    private function create_deck_from_video($video_key = '', $brightcove_id = '')
    {
        $this->load->model('video_model');
        $this->load->model('deck_model');
        $this->load->model('deck_video_inuse_model');
        $this->load->helper('string');

        $deck = $this->deck_model
            ->select('deck.*, deck_video_inuse.video_id, brightcove_id')
            ->join('deck_video_inuse', 'deck_video_inuse.deck_id = deck.id', 'INNER')
            ->join('video', 'deck_video_inuse.video_id = video.id', 'INNER')
            ->where('video.name', $video_key)
            ->first();

        if( empty($deck) ) {
            $data = [
                'name' => $video_key,
                'description' => 'Video description of ' . $video_key,
                'brightcove_id' => '',
                'type' => 'textbook'
            ];

            if (!empty($brightcove_id)) {
                // Get thumbnail of brightcove id
                $url = $this->get_brightcove_image_link($brightcove_id);

                $data['brightcove_thumbnail_url'] = !empty($url) ? $url : null;

                $data['brightcove_id'] = $brightcove_id;
            }

            $video = $this->video_model->create($data, [
                'mode' => 'replace',
                'return' => TRUE
            ]);

            // Auto create Deck
            $deck = $this->deck_model->create([
                'name' => 'Deck - ' . $video_key,
                'description' => 'Deck description - '. $video_key
            ], [
                'mode' => 'replace',
                'return' => TRUE
            ]);

            // Map deck video inuse
            $this->deck_video_inuse_model->create([
                'deck_id' => $deck->id,
                'video_id' => $video->id
            ], [
                'mode' => 'replace'
            ]);

            $deck->video_id = $video->id;
            $deck->brightcove_id = $data['brightcove_id'];

        } else {
            // update brightcove id for video
            if (!empty($brightcove_id) && $deck->brightcove_id != $brightcove_id) {

                $url = $this->get_brightcove_image_link($brightcove_id);

                $this->video_model->create([
                    'id' => $deck->video_id,
                    'brightcove_id' => $brightcove_id,
                    'brightcove_thumbnail_url' => !empty($url) ? $url : null
                ], [
                    'mode' => 'replace',
                    'return' => TRUE
                ]);
            }
        }

        return $deck;
    }

    /**
     * Import video duration from brightcove service
     *
     * @param int $allow_update
     * @return bool
     */
    public function dump_video_duration($allow_update = 0)
    {
        $this->load->library('brightcove_studio');
        $this->load->model('video_model');

        // Find all video need to update duration
        $videos = $this->video_model
            ->select('id, name, brightcove_id, duration')
            ->where('brightcove_id != ', '')
            ->all();

        foreach ($videos AS $video) {

            if ($video->duration > 0 && !$allow_update) {
                continue;
            }

            log_message('info', '[VideoDuration] Import video ' . $video->name);

            $try = 1;

            while ($try <= 2) {
                try {
                    $brightcove_video = $this->brightcove_studio->get_video($video->brightcove_id);

                    if (!empty($brightcove_video)) {
                        $this->video_model->create([
                            'id' => $video->id,
                            'duration' => $brightcove_video->getDuration()
                        ], [
                            'mode' => 'replace'
                        ]);
                    }

                } catch (Exception $e) {
                    log_message('info', $e->getMessage());
                    switch ($e->getCode()) {
                        case 404:
                            $try = 10; // no need to try for this video
                            break;

                        case 401:
                            $this->brightcove_studio->authenticate_client();
                            break;
                    }
                }
                ++$try;
            }
        }

    }

    /**
     * Get brightcove image link
     * @param string $brightcove_id
     * @return bool
     */
    private function get_brightcove_image_link($brightcove_id = '')
    {
        if (empty($brightcove_id)) {
            return FALSE;
        }
        return FALSE;
        log_message('info', 'Get brightcove image url of ' . $brightcove_id);
        // Get thumbnail of brightcove id
        $this->load->library('brightcove_studio');

        $try = 1;

        $url = FALSE;

        while ($try <= 2) {
            try {
                $url = $this->brightcove_studio->get_video_image_link($brightcove_id);
            } catch (Exception $e) {
                log_message('info', $e->getMessage());
                switch ($e->getCode()) {
                    case 404:
                        return FALSE;
                        break;

                    case 401:
                        $this->brightcove_studio->authenticate_client();
                        break;
                }


                sleep(2); // Sleep 2 seconds to try again
            }
            // Except 404, retry to get brightcove image link
            ++$try;

        }

        return $url;
    }

    /**
     *
     */
    public function show_brightcove_image()
    {
        $brightcove_ids = func_get_args();

        foreach ($brightcove_ids AS $id) {
            log_message('info', sprintf('[%s] %s', $id, $this->get_brightcove_image_link($id)));
        }
    }

    /**
     * Dump brightcove image link to google sheet
     *
     * @throws Google_Exception_api
     */
    public function dump_brightcove_image_link()
    {
        if (ENVIRONMENT == 'development') {
            log_message('info', 'Ignore dump brightcove url on development environment');
        }

        $this->load->library('brightcove_video');
        $this->load->model('video_model');
        $this->load->helper('file');

        $spread_id = in_array(ENVIRONMENT, ['staging', 'production']) ?
            '1jWe28f2ehoABTNV0hpKiDK2tTpuAHVDwItrnAHVTp7w' : '18LyTylmCMvh1OPQ98j4YgG4fwcnl5BJI90NTBssC82E';

        $spread = $this->google_spreadsheet->get_spreadsheet_by_id($spread_id);

        $list_old_entries = $this->get_list_brightcove_image_links();

        $res = $this->video_model
            ->select('brightcove_id, name')
            ->where('brightcove_id != ', "")
            ->all();

        $batch_datas = [];

        $row = count($list_old_entries) + 1;

        $total = count($res);

        foreach ($res AS $key => $video) {

            if (isset($list_old_entries[$video->brightcove_id])) {
                if (empty($list_old_entries[$video->brightcove_id]['url'])) {
                    // If image link is not exist in google sheet, try to update again
                    try {

                        $video_brightcove = $this->brightcove_video->get_detail($video->brightcove_id);

                        $batch_datas[] = [
                            'row' => $list_old_entries[$video->brightcove_id]['row'],
                            'col' => 3,
                            'value' => !empty($video_brightcove) ? $video_brightcove->videoStillURL : ""
                        ];

                    } catch (Exception $e) {
                        // just ignore update the row
                    }
                }

                log_message('info', sprintf('[%s/%s] Update %s', $key + 1, $total, $video->brightcove_id));

                continue;
            }

            // Create new data
            log_message('info', sprintf('[%s/%s] %s', $key + 1, $total, $video->brightcove_id));

            ++$row;

            $batch_datas[] = [
                'row' => $row,
                'col' => 1,
                'value' => $video->name
            ];

            $batch_datas[] = [
                'row' => $row,
                'col' => 2,
                'value' => $video->brightcove_id
            ];

            try {

                $video_brightcove = $this->brightcove_video->get_detail($video->brightcove_id);

                $batch_datas[] = [
                    'row' => $row,
                    'col' => 3,
                    'value' => !empty($video_brightcove) ? $video_brightcove->videoStillURL : ""
                ];

            } catch (Exception $e) {

                $batch_datas[] = [
                    'row' => $row,
                    'col' => 3,
                    'value' => ""
                ];
            }

            if (count($batch_datas) > 300) {
                $this->google_spreadsheet->update_sheet_by_batch($spread, 'Sheet1', $batch_datas);
                $batch_datas = [];
            }
        }

        if (count($batch_datas)) {
            $this->google_spreadsheet->update_sheet_by_batch($spread, 'Sheet1', $batch_datas);
        }
    }

    /**
     * Get list brightcove image links from google sheet
     *
     * @return array
     * @throws Google_Exception_api
     */
    public function get_list_brightcove_image_links()
    {
        $spread_id = in_array(ENVIRONMENT, ['staging', 'production']) ?
            '1jWe28f2ehoABTNV0hpKiDK2tTpuAHVDwItrnAHVTp7w' : '18LyTylmCMvh1OPQ98j4YgG4fwcnl5BJI90NTBssC82E';

        $spread = $this->google_spreadsheet->get_spreadsheet_by_id($spread_id);

        $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet($spread, 'Sheet1');

        $return = [];

        foreach ($list_entries AS $key => $entry) {
            $return[$entry['brightcoveid']] = [
                'url' => isset($entry['url']) ? $entry['url'] : FALSE,
                'row' => $key + 2
            ];
        }

        return $return;
    }

    /**
     * @param int $allow_update
     */
    public function dump_brightcove_image_to_db($allow_update = 0)
    {
        //$this->load->library('brightcove_video');
        $this->load->model('video_model');
        $this->load->model('image_model');

        $res = $this->video_model
            ->select('id, brightcove_id, image_key')
            ->where('brightcove_id != ', "")
            ->all();

        $image_types = [
            'original' => ['type' => 'max_width', 'max_width' => 220, 'quality' => 100]
        ];

        $brightcove_id_errors = [];

        $list_brightcove_urls = $this->get_list_brightcove_image_links();

        foreach ($res AS $video) {

            $video_image_key = 'video_brightcove_' . $video->brightcove_id;

            // Check video image is exist in DB
            $image_res = $this->image_model
                ->select('key')
                ->where('key', $video_image_key)
                ->first();

            try {

                if (empty($image_res) || (!empty($image_res) && $allow_update) || ($video->image_key != $video_image_key)) {

                    log_message('info', '[DumpBrightcoveImage] ' . $video->brightcove_id);

                    $video_data = null;
                    //$video_brightcove = $this->brightcove_video->get_detail($video->brightcove_id);

                    if (isset($list_brightcove_urls[$video->brightcove_id])
                        && !empty($list_brightcove_urls[$video->brightcove_id]['url'])
                    ) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $list_brightcove_urls[$video->brightcove_id]['url']);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                        $video_data = curl_exec($ch);
                        curl_close($ch);
                    }

                    if ($video_data) {

                        $update_video_data = [
                            'id' => $video->id,
                            'image_key' => $video_image_key
                        ];

                        if (empty($image_res)) {
                            $this->image_model->create_from_data([
                                'data' => $video_data,
                                'key' => $video_image_key,
                                'holder_type' => 'brightcove_image'
                            ], [
                                'only_original' => FALSE
                            ], $image_types);

                        } else {
                            // This image was imported
                            $this->image_model->update_from_data($video_image_key, [
                                'data' => $video_data,
                                'key' => $video_image_key,
                                'holder_type' => 'brightcove_image'
                            ], [
                                'only_original' => FALSE
                            ], $image_types);
                        }

                        $this->video_model->create($update_video_data, [
                            'mode' => 'replace',
                            'master' => TRUE
                        ]);

                    } else {
                        throw new Exception();
                    }
                }
            } catch (Exception $e) {
                $brightcove_id_errors[] = $video->brightcove_id;
            }
        }

        if (empty($brightcove_id_errors)) {
            log_message('info', '[DumpBrightcoveImage] Success');
        } else {
            log_message('info', '[DumpBrightcoveImage] These brightcove Ids can not dump ' . implode(',', $brightcove_id_errors));
        }
    }

    /**
     * @param array $arr
     * @param string $key
     * @param mixed $default
     * @return bool
     */
    private function get_value_from_array($arr = [], $key = '', $default = FALSE)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Extend json_encode, use for updating string to google sheet
     * @param array $array
     *
     * @return string
     */
    private function json_encode($array = [])
    {
        return str_replace('"', "'", json_encode($array));
    }

    /**
     * Extend json_decode
     * @param string $string
     *
     * @return array|null
     */
    private function json_decode($string = '')
    {
        $string = str_replace("'", '"', $string);
        $decode = json_decode($string);

        if (empty($decode) || !is_object($decode)) {
            return null;
        }

        return get_object_vars($decode);
    }

    /**
     * Get id of pref by name, if name isn't exist in db, then insert this pref into db and return id
     *
     * @param string $name
     * @return int
     */
    private function get_area_pref_id($name = '')
    {
        // Use for store all area pref, store in array( title1 => id1, title2 => id2 )
        static $list_prefs_static;

        // Load all pref exist in db
        if(!$list_prefs_static) {
            $list_prefs_records = $this->master_area_pref_model->all();

            foreach($list_prefs_records as $pref) {
                $list_prefs_static[$pref->name] = $pref->id;
            }
        }

        if(!isset($list_prefs_static[$name])) {
            $pref_id = $this->master_area_pref_model->create(['name' => $name], ['mode' => 'replace']);

            $list_prefs_static[$name] = $pref_id;
        }

        return $list_prefs_static[$name];
    }

    /**
     * Get id of area by area code, if it isn't exist in db, then insert this city into db and return id
     *
     * @param string $area_code
     * @param string $pref_name
     * @param string $area_name
     * @return int
     */
    private function get_area_id($area_code = '', $pref_name = '', $area_name = '')
    {
        // Use for store all area pref, store in array( title1 => id1, title2 => id2 )
        static $list_cities_static;

        // Load all cities exist in db
        if(!$list_cities_static) {

            $list_city_records = $this->master_area_model->all();

            foreach($list_city_records as $city) {
                $list_cities_static[$city->area_code] = $city->id;
            }
        }

        $area_code = trim($area_code);

        if(!isset($list_cities_static[$area_code])) {
            // Create area record

            $city_id = $this->master_area_model->create([
                'pref_id' => $this->get_area_pref_id($pref_name),
                'name' => $area_name,
                'area_code' => $area_code
            ], ['mode' => 'replace']);

            $list_cities_static[$area_code] = $city_id;
        }

        return $list_cities_static[$area_code];
    }

    /**
     * Get id of postalcode, if it isn't exist in db, then insert this postalcode into db and return id
     *
     * @param string $postalcode
     * @return mixed
     */
    private function get_postalcode_id($postalcode = '')
    {
        // Trim space and remove '-' character in postalcode if exist
        $postalcode = trim( str_replace('-', '', $postalcode) );

        // Use for store all postalcodes, store in array( postalcode1 => id1, postalcode2 => id2 )
        static $list_postalcodes_static;

        // Load all cities exist in db
        if(!$list_postalcodes_static) {

            $list_postalcode_records = $this->master_postalcode_model->all();

            foreach($list_postalcode_records as $postalcode_rc) {
                $list_postalcodes_static[ $postalcode_rc->postalcode ] = $postalcode_rc->id;
            }
        }

        if(!isset($list_postalcodes_static[$postalcode])) {
            // Create postalcode record

            $postalcode_id = $this->master_postalcode_model->create(['postalcode' => $postalcode], ['mode' => 'ignore']);

            $list_postalcodes_static[$postalcode] = $postalcode_id;
        }

        return $list_postalcodes_static[$postalcode];
    }

    /**
     * Get grade id by grade name, if grade isn't exist => create record
     *
     * @param string $grade_name
     * @param int $grade_id
     * @return mixed
     */
    private function get_grade_id($grade_name, $grade_id)
    {
        // Clean grade name
        $grade_name = trim( $grade_name );

        // Use for store all grade, store in array( gradename1 => id1, gradename2 => id2 )
        static $list_grades_static;

        $this->load->model('master_grade_model');
        // Load all cities exist in db
        if(!$list_grades_static) {

            $list_grades_records = $this->master_grade_model->all();

            foreach($list_grades_records as $grade_record) {
                $list_grades_static[ $grade_record->name ] = $grade_record->id;
            }
        }

        if(!isset($list_grades_static[$grade_name])) {
            // Create postalcode record

            $this->master_grade_model->create([
                'id' => $grade_id,
                'name' => $grade_name
            ],
                ['mode' => 'ignore']
            );

            $list_grades_static[$grade_name] = $grade_id;
        }

        return $list_grades_static[$grade_name];
    }

    /**
     * Import textbook inuse from google sheet
     *
     * @param string $only_spread
     *
     * @throws Exception
     * @throws Google_Exception_api
     */
    public function textbook_inuse($only_spread = '')
    {
        $this->load->model('master_textbook_inuse_model');

        // currently default year_id of textbook ( id 1 is year 2015 ), will be changed later
        $year_id = 1;

        //
        $res = $this->master_textbook_inuse_model
            ->select('school_id, year_id, subject_id, textbook_id')
            ->where('year_id', $year_id)
            ->all();

        $textbook_inuse_db = [];

        foreach ($res AS $row) {
            $textbook_inuse_db[] = sprintf('%s-%s-%s-%s', $row->school_id, $row->year_id, $row->subject_id, $row->textbook_id);
        }

        unset($res);

        $list_spread_titles = $this->google_spreadsheet->get_list_gsheet_in_drive('0B5mCmfGFQgZCaHR3Qm01M3p1dUU');

        $list_entries = [];

        foreach ($list_spread_titles AS $spread_title) {

            if ($only_spread && $spread_title != $only_spread) {
                continue;
            }

            log_message('INFO', 'Import gsheet textbook inuse "' . $spread_title . '"');
            // Get data of menu textbook entries
            $spreadsheet = $this->google_spreadsheet->get_spreadsheet_by_title($spread_title);

            $list_inuse_sheets = $this->google_spreadsheet->get_list_sheet_titles($spreadsheet);

            foreach($list_inuse_sheets AS $key => $sheet) {
                $list_entries["$spread_title | $sheet"] = $this->google_spreadsheet->get_list_entries_in_sheet($spreadsheet, $sheet);
            }
        }

        // Start importing
        // Fetch list schools and textbooks
        log_message('info', '[Import][Textbook_inuse] Prepare list schools');
        $list_fetch_schools = $this->fetch_all_school_by_inuse();

        log_message('info', '[Import][Textbook_inuse] Prepare list textbooks');
        $list_fetch_textbooks = $this->fetch_all_textbook();

        $bulk_array = [];

        foreach ($list_entries AS $title => $entries) {

            log_message('info', '[Import][Textbook_inuse] Importing ' . $title);

            // store row contain subject ids
            $row_subject_ids = $entries[1];
            unset($entries[0], $entries[1]);

            foreach($entries AS $entry) {
                // Shift 3 first column
                // Col1 is prefecture id
                $col1_val = array_shift($entry);
                // Col2 is inuse id
                $col2_val = array_shift($entry);
                $col2_val = trim($col2_val);
                // Col3 ignore
                $col3_val = array_shift($entry);

                // Every $col_val is publisher short name
                foreach($entry as $col_key => $publisher_short_name) {
                    // Insert data
                    $list_subject_ids = explode(',', $row_subject_ids[$col_key]);

                    // If isset school by inuse
                    if(isset($list_fetch_schools[$col2_val])) {
                        // Import every school with every subject
                        foreach($list_fetch_schools[$col2_val] as $school_id) {
                            foreach($list_subject_ids as $subject_id) {
                                // Only import with exist textbook id
                                if(isset($list_fetch_textbooks[$year_id][$publisher_short_name][$subject_id])) {

                                    $key = sprintf('%s-%s-%s-%s', $school_id, $year_id, $subject_id, $list_fetch_textbooks[$year_id][$publisher_short_name][$subject_id]);

                                    if (in_array($key, $textbook_inuse_db)) {
                                        continue;
                                    }

                                    $bulk_array[] = [
                                        'school_id' => $school_id,
                                        'year_id' => $year_id,
                                        'subject_id' => $subject_id,
                                        'textbook_id' => $list_fetch_textbooks[$year_id][$publisher_short_name][$subject_id]
                                    ];

                                    if (count($bulk_array) >= 500) {
                                        $this->master_textbook_inuse_model->bulk_create($bulk_array);
                                        $bulk_array = [];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($bulk_array)) {
            $this->master_textbook_inuse_model->bulk_create($bulk_array);
            $bulk_array = [];
        }
    }

    /**
     * Fetch all school by inuse from google sheet
     *
     * @return array
     * @throws Google_Exception_api
     */
    public function fetch_all_school_by_inuse()
    {

        $this->load->model('master_school_model');

        $list_fetch_schools = [];

        foreach($this->list_school_files as $file) {
            // Get data of entries
            $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet($file, 'schools');

            foreach ($list_entries as $entry) {
                $inuse = trim($entry['inuse']);
                if(!empty($inuse)) {
                    // get school by kcode
                    $school = $this->master_school_model->find_by([
                        'kcode' => trim($entry['kcode'])
                    ]);

                    if(!empty($school)) {
                        $list_fetch_schools[$inuse][] = $school->id;
                    }
                }
            }
        }

        return $list_fetch_schools;
    }

    /**
     * Fetch all textbook by year, publisher, subject
     *
     * @return array
     */
    public function fetch_all_textbook()
    {

        $this->load->model('textbook_model');
        $list_fetch_textbook = [];

        $res = $this->textbook_model
            ->select('textbook.id as textbook_id, textbook.year_id')
            ->select('master_subject.id as subject_id')
            ->select('publisher.id as publisher_id, publisher.name as publisher_name, publisher.short_name as publisher_short_name')
            ->with_master_subject()
            ->with_publisher()
            ->all();

        foreach($res as $textbook) {
            $year_id = (int) $textbook->year_id;
            $publisher_name = !empty($textbook->publisher_short_name) ? $textbook->publisher_short_name : $textbook->publisher_name;
            $subject_id = $textbook->subject_id;
            $list_fetch_textbook[$year_id][$publisher_name][$subject_id] = $textbook->textbook_id;
        }

        return $list_fetch_textbook;
    }

    /**
     * Auto import deck question for stage 2
     *
     * @param int $folder
     * @param int $deck_package_id
     * @param int $deck_category_id
     * @param int $allow_update_media 0:FALSE 1:TRUE
     *
     * @throws Exception
     * @throws Google_Exception_api
     */
    public function deck_question($folder = 1, $deck_package_id = 1, $deck_category_id = 1, $allow_update_media = 0)
    {
        // Get all deck package folder
        $this->load->model('deck_package_model');
        $this->load->model('deck_category_model');
        $this->load->model('deck_model');
        $this->load->model('stage_model');
        $this->load->model('question_model');
        $this->load->model('stage_question_inuse_model');
        $this->load->model('image_model');
        $this->load->model('deck_image_model');
        $this->load->model('file_model');
        $this->load->model('memorization_model');

        $deck_icon_types = [
            'small' => ['type' => 'max_width', 'max_width' => 120, 'quality' => 100],
            'original' => ['type' => 'max_width', 'max_width' => 210, 'quality' => 100]
        ];

        $deck_capture_image_types = [
            'original' => ['type' => 'max_width', 'max_width' => 270, 'quality' => 100]
        ];

        $drive_service = $this->google_spreadsheet->get_drive_instance();

        $results = $drive_service->files->listFiles([
            'q' => "'0B9NfzKsmp_uoenpjM0o1TXRXcWM' in parents"
        ]);

        foreach ($results->getItems() AS $res) {

            // This code will be ignore package doesn't need update
            if ($folder != (int) $res->getTitle()) continue;

            log_message('info', 'Import deck package ' . $res->getTitle() );

             // Import every deck stage in deck_package folder
            $package_folder_id = $res->id;

            // Find Drill_stage spread_sheet
            $spread_results = $drive_service->files->listFiles([
                'q' => "'$package_folder_id' in parents and mimeType='application/vnd.google-apps.spreadsheet'"
            ]);

            $spread_items = $spread_results->getItems();

            // Continue to next deck package if this package doesn't have content
            if (!count($spread_items)) {
                continue;
            }

            /**
             * Dump Deck and Stage
             */

            /**TODO Dump deckpakge and deckcategory later */
            $map_decks = [];
            $map_stages = [];

            // Find all relation image with this deck
            $package_images = [];

            $deck_package_folder_result = $drive_service->files->listFiles([
                'q' => "'$package_folder_id' in parents and mimeType='application/vnd.google-apps.folder' and title='Image'"
            ]);

            $deck_package_folder_items = $deck_package_folder_result->getItems();

            if (isset($deck_package_folder_items[0])) {
                $deck_package_folder_image_id = $deck_package_folder_items[0]->id;

                $images_results = $drive_service->files->listFiles([
                    'q' => "'$deck_package_folder_image_id' in parents"
                ]);

                foreach ($images_results->getItems() AS $image_item) {
                    $package_images[mb_trim($image_item->title)] =  $image_item;
                }
            }

            foreach ($spread_items AS $spread_item) {

                $spread = $this->google_spreadsheet->get_spreadsheet_by_id($spread_item->id);

                log_message('info', 'Import Deck Stage of spread ' . $spread_item->getTitle() );

                /** Import deck of sheet **/
                $deck_entries = $this->google_spreadsheet->get_list_entries_in_sheet($spread, 'Drill');
                unset($deck_entries[0]); // row 0 is english header

                foreach ($deck_entries as $deck_entry) {
                    $deck_key = "$deck_package_id-$deck_category_id-{$deck_entry['ドリルid']}";

                    $deck_data = [
                        'name' => $deck_entry['ドリル名'],
                        'package_id' => $deck_package_id,
                        'category_id' => $deck_category_id,
                        'order' => $deck_entry['ドリルオーダー'],
                        'coin' => $deck_entry['購入に必要なコイン'],
                        'description' => $deck_entry['説明文'],
                        'key' => $deck_key
                    ];

                    // Get Icon of Deck
                    $deck_image_res = null;

                    // Create or update deck icon image
                    if (!empty($deck_entry['アイコン'])) {
                        if (!isset($package_images[$deck_entry['アイコン']])) {
                            log_message('info', '[Import Deck] Image "' . $package_images[$deck_entry['アイコン']] . '" is not exist in folder Image');
                            continue;
                        }

                        $deck_image_res = $this->image_model
                            ->select('id, key, name')
                            ->where('holder_type', 'deck_icon')
                            ->where('name', $deck_entry['アイコン'])
                            ->first();

                        // Read image data from google drive
                        $request = new Google_Http_Request($package_images[$deck_entry['アイコン']]->downloadUrl, 'GET', null, null);

                        $httpRequest = $drive_service->getClient()->getAuth()->authenticatedRequest($request);

                        if (empty($deck_image_res)) {
                            $deck_image_res = $this->image_model->create_from_data([
                                'data' => $httpRequest->getResponseBody(),
                                'name' => $deck_entry['アイコン'],
                                'holder_type' => 'deck_icon'
                            ], [
                                'return' => TRUE,
                                'only_original' => FALSE
                            ], $deck_icon_types);
                        } else if ($allow_update_media) {
                            $this->image_model->update_from_data($deck_image_res->key, [
                                'data' => $httpRequest->getResponseBody(),
                                'name' => $deck_entry['アイコン'],
                                'holder_type' => 'deck_icon'
                            ], ['only_original' => FALSE], $deck_icon_types);
                        }
                    }

                    if (!empty($deck_image_res)) {
                        $deck_data['image_key'] = $deck_image_res->key;
                    }

                    $deck = $this->deck_model->find_by([
                        'key' => $deck_key
                    ]);

                    if (!empty($deck)) {
                        $deck_data['id'] = $deck->id;
                    }

                    $deck = $this->deck_model->create($deck_data, [
                        'mode' => 'replace',
                        'master' => TRUE,
                        'return' => TRUE
                    ]);

                    $deck_capture_res = $this->image_model
                        ->select('id, key, name')
                        ->where('holder_type', 'deck_capture')
                        ->where('holder_id', $deck->id)
                        ->all();

                    $deck_captures = [];

                    foreach ($deck_capture_res AS $deck_capture) {
                        $deck_captures[] = get_object_vars($deck_capture);
                    }

                    $deck_captures = array_column($deck_captures, 'key', 'name');

                    for ($i = 1; $i <= 5; ++$i) {

                        $capture_image_name = isset($deck_entry['キャプチャ' . $i]) ? mb_trim($deck_entry['キャプチャ' . $i]) : null;

                        if (!$capture_image_name) continue;

                        // Find google drive image
                        if (!isset($package_images[$capture_image_name])) {
                            log_message('info', '[Import Deck] Image "' . $deck_entry['キャプチャ' . $i] . '" is not exist in folder Image');
                            continue;
                        }

                        // Read image data from google drive
                        $request = new Google_Http_Request($package_images[$capture_image_name]->downloadUrl, 'GET', null, null);

                        $httpRequest = $drive_service->getClient()->getAuth()->authenticatedRequest($request);

                        $deck_capture_data = [
                            'data' => $httpRequest->getResponseBody(),
                            'name' => $capture_image_name,
                            'holder_type' => 'deck_capture',
                            'holder_id' => $deck->id
                        ];

                        if (!isset($deck_captures[$capture_image_name])) {
                            // Create deck capture
                            $image_res = $this->image_model->create_from_data($deck_capture_data, [
                                'return' => TRUE,
                                'only_original' => FALSE
                            ], $deck_capture_image_types);

                            $this->deck_image_model->create([
                                'deck_id' => $deck->id,
                                'image_key' => $image_res->key
                            ]);
                        } else if ($allow_update_media){
                            // Just update the deck capture image by image key
                            $this->image_model->update_from_data($deck_captures[$capture_image_name],
                                $deck_capture_data,
                                ['only_original' => FALSE],
                                $deck_capture_image_types
                            );
                        }
                    }

                    $map_decks[$deck_entry['ドリルid']] = $deck;
                }

                /** Import deck stage of sheet **/
                $stage_entries = $this->google_spreadsheet->get_list_entries_in_sheet($spread, 'Stage');

                unset($stage_entries[0]); //row 0 is english header

                foreach ($stage_entries AS $stage_entry_key => $stage_entry) {

                    if (!isset($stage_entry['ドリルid'])) {
                        $this->add_error_log('[Deck-package] ' . $stage_entry['ステージid'] );

                        continue;
                    }

                    if (empty($stage_entry['ステージ名'])) {
                        continue;
                    }

                    log_message('info', 'Import stage ' . $stage_entry_key . '  ' . $stage_entry['ステージ名']);

                    $stage_key = $map_decks[$stage_entry['ドリルid']]->key . '-' . $stage_entry['ステージid'];

                    $stage_data = [
                        'name' => $stage_entry['ステージ名'],
                        'order' => $stage_entry['ステージオーダー'],
                        'key' => $stage_key,
                        'deck_id' => $map_decks[$stage_entry['ドリルid']]->id
                    ];

                    $stage = $this->stage_model->find_by([
                        'key' => $stage_key
                    ]);

                    if (!empty($stage)) {
                        $stage_data['id'] = $stage->id;
                    }

                    $stage = $this->stage_model->create($stage_data, [
                        'mode' => 'replace',
                        'master' => TRUE,
                        'return' => TRUE
                    ]);

                    $map_stages[$stage_entry['ステージid']] = $stage;
                }

            }

            /**
             * DUMP Question For deck package
             */

            // Find folder contain question spreads
            $question_folder_result = $drive_service->files->listFiles([
                'q' => "'$package_folder_id' in parents and mimeType='application/vnd.google-apps.folder' and title='Question'"
            ]);

            $question_folder_items = $question_folder_result->getItems();

            if (!isset($question_folder_items[0])) {
                continue;
            }

            $question_folder_id = $question_folder_items[0]->id;

            // Get all spread question of this deck package
            $spread_results = $drive_service->files->listFiles([
                'q' => "'$question_folder_id' in parents and mimeType='application/vnd.google-apps.spreadsheet'"
            ]);

            $spread_question_items = $spread_results->getItems();

            if (!count($spread_question_items)) {
                continue;
            }

            foreach ($spread_question_items AS $question_spread_item) {
                
                $spread = $this->google_spreadsheet->get_spreadsheet_by_id($question_spread_item->id);

                $spread_title = $spread->getTitle();

                log_message('info', 'Import Deck question ' . $spread_title);

                // Get all sheet in this spread

                $sheet_titles = $this->google_spreadsheet->get_list_sheet_titles($spread);

                foreach ($sheet_titles AS $sheet_title) {

                    $question_entries = $this->google_spreadsheet->get_list_entries_in_sheet($spread, $sheet_title);

                    unset(
                        $question_entries[0],
                        $question_entries[1],
                        $question_entries[2]
                    );

                    // entry 3 is english row
                    $map_cols = [];
                    foreach ($question_entries[3] AS $col_key => $col_val) {
                        $map_cols[$col_val] = $col_key;
                    }

                    unset($question_entries[3]);

                    foreach($question_entries AS $question_entry_index => $question_entry) {

                        $question_type = isset($this->define_question_type[$question_entry[$map_cols['question_type']]]) ?
                            $this->define_question_type[$question_entry[$map_cols['question_type']]] : NULL;
                        // Log error
                        if(!$question_type) {
                            $this->add_error_log("[Deckpackage][Question] Can not import entry $question_entry_index of $spread_title | $sheet_title  : Wrong question type '" . $question_entry[$map_cols['question_type']]. "'");
                            continue;
                        }

                        $function_build_question = 'get_deck_question_data_answer_'.$question_type;

                        $stage = $map_stages[$question_entry[$map_cols['stage_id']]];

                        $question_data = [
                            'deck_id' => $stage->deck_id,
                            'type' => $question_type,
                            'question_key' => $stage->key . '-' . $question_entry[$map_cols['question_id']] ,
                            'data' => json_encode(
                                array_merge(
                                    $this->get_deck_question_data_common($question_entry, $map_cols),
                                    $this->$function_build_question($question_entry, $map_cols)
                                )
                            )
                        ];

                        $question = $this->question_model->find_by([
                            'question_key' => $question_data['question_key']
                        ]);

                        if (!empty($question)) {
                            log_message('info', 'Update question with question_key is ' . $question_data['question_key']);
                            $question_data['id'] = $question->id;
                        } else {
                            log_message('info', 'Create new question with question_key is ' . $question_data['question_key']);
                        }

                        $question = $this->question_model->create($question_data, [
                            'mode' => 'replace',
                            'return' => TRUE
                        ]);

                        // Set stage question inuse
                        $this->stage_question_inuse_model->create([
                            'stage_id' => $stage->id,
                            'question_id' => $question->id
                        ], [
                            'mode' => 'replace'
                        ]);
                    }

                }
            }

            /**
             * DUMP Memorization For deck package
             */

            // Find all sound with this deck package
            $package_sounds = [];
            $deck_package_folder_sound_result = $drive_service->files->listFiles([
                'q' => "'$package_folder_id' in parents and mimeType='application/vnd.google-apps.folder' and title='Sound'"
            ]);

            $deck_package_folder_sound_id = isset($deck_package_folder_sound_result[0]) ? $deck_package_folder_sound_result[0]->id : null;

            // Find folder contain memorization spreads
            $memorization_folder_result = $drive_service->files->listFiles([
                'q' => "'$package_folder_id' in parents and mimeType='application/vnd.google-apps.folder' and title='Memorization'"
            ]);

            $memorization_folder_items = $memorization_folder_result->getItems();

            if (!isset($memorization_folder_items[0])) {
                log_message('debug', '[Memorization] Can not find memorization google spread folder');
                return FALSE;
            }

            $memorization_folder_id = $memorization_folder_items[0]->id;

            // Get all spread question of this deckpackage
            $spread_results = $drive_service->files->listFiles([
                'q' => "'$memorization_folder_id' in parents and mimeType='application/vnd.google-apps.spreadsheet'"
            ]);

            $spread_memorization_items = $spread_results->getItems();

            if (!count($spread_memorization_items)) {
                log_message('debug', '[Memorization] Can not find memorization google spread file in folder Question');
                return FALSE;
            }

            foreach ($spread_memorization_items AS $memorization_spread_item) {

                $spread = $this->google_spreadsheet->get_spreadsheet_by_id($memorization_spread_item->id);

                log_message('info', '[Memorization] Import memorizations in google file ' . $memorization_spread_item->getTitle());

                $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet($spread, 'sheet1');

                $map_cols = [];

                foreach ($list_entries[3] AS $key => $name) {
                    $map_cols[$name] = $key;
                }

                // Unset unnecessary sheet row
                unset($list_entries[0], $list_entries[1], $list_entries[2], $list_entries[3]);

                $current_stage = 0;
                $order = 0;

                foreach ($list_entries AS $entry) {

                    if ($entry[$map_cols['stage_id']] != $current_stage) {
                        $current_stage = $entry[$map_cols['stage_id']];
                        $order = 0;
                    }

                    $stage = $map_stages[$entry[$map_cols['stage_id']]];

                    $memorization_key = $stage->key . '-' . $entry[$map_cols['question_id']];

                    $memorization_data = [
                        'stage_id' => $stage->id,
                        'key' => $memorization_key,
                        'question' => $entry[$map_cols['question']],
                        'answer' => $entry[$map_cols['answer']],
                        'order' => ++ $order,
                        'sound_key' => ''
                    ];

                    if (!empty($entry[$map_cols['sound_file']])) {
                        // Import sound to DB
                        $sound_key = $memorization_key . '-' . $entry[$map_cols['sound_file']];

                        // Find sound
                        $sound_res = $this->file_model->find_by([
                            'key' => $sound_key
                        ]);

                        // Get sound data content from google
                        if ((empty($sound_res) || (!empty($sound_res) && $allow_update_media))
                            && $deck_package_folder_sound_id
                        ) {
                            // Search sound file in sound package folder

                            $search_sound_file = $drive_service->files->listFiles([
                                'q' => "'$deck_package_folder_sound_id' in parents and title='{$entry[$map_cols['sound_file']]}'"
                            ]);

                            if (isset($search_sound_file[0])) {

                                log_message('info', sprintf('[Sound] %s %s', empty($sound_res) ? 'Import' : 'Update', $sound_key));

                                // Read sound data from google drive
                                $request = new Google_Http_Request($search_sound_file[0]->downloadUrl, 'GET', null, null);
                                $httpRequest = $drive_service->getClient()->getAuth()->authenticatedRequest($request);

                                $sound_data = [
                                    'key' => $sound_key,
                                    'name' => $entry[$map_cols['sound_file']],
                                    'content_type' => $search_sound_file[0]->mimeType,
                                    'size' => $search_sound_file[0]->fileSize,
                                    'data' => $httpRequest->getResponseBody(),
                                    'holder_type' => 'question_memorization_sound'
                                ];

                                if (!empty($sound_res)) {
                                    $sound_data['id'] = $sound_res->id;
                                }

                                $sound_res = $this->file_model->create($sound_data, [
                                    'return' => TRUE,
                                    'mode' => 'replace'
                                ]);
                            } else {
                                log_message('info', '[Sound] Can not get sound file ' . $entry[$map_cols['sound_file']] . ' in sound folder');
                            }
                        }

                        if (!empty($sound_res)) {
                            $memorization_data['sound_key'] = $sound_res->key;
                        }
                    }

                    //
                    $memorization_res = $this->memorization_model->find_by([
                        'key' => $memorization_key
                    ]);

                    if ($memorization_res) {
                        log_message('info', 'Update memorization with key is ' . $memorization_data['key']);
                        $memorization_data['id'] = $memorization_res->id;
                    } else {
                        log_message('info', 'Create new memorization with key is ' . $memorization_data['key']);
                    }

                    $this->memorization_model->create($memorization_data, [
                        'mode' => 'replace'
                    ]);
                }
            }

        }
    }

    /**
     * Build deck question data common for all type
     *
     * @param array $entry of row google sheet
     * @param array $col_maps
     *
     * @return array
     */
    private function get_deck_question_data_common($entry, $col_maps = [])
    {
        $data = [
            'correct_return_second' => null,
            'wrong_return_second' => null,
            'time_limit' => 30,
            'commentary' => '',
            'random' => (isset($entry[$col_maps['random']]) && strtoupper(trim($entry[$col_maps['random']])) == 'OFF') ? 'off' : 'on'
        ];

        $questions = [];
        if (!empty($entry[$col_maps['question1']])) {
            $questions[] = $this->escape_special_characters($entry[$col_maps['question1']]);
        }

        if (!empty($entry[$col_maps['question2']])) {
            $questions[] = $this->escape_special_characters($entry[$col_maps['question2']]);
        }

        if (!empty($entry[$col_maps['question3']])) {
            $questions[] = $this->escape_special_characters($entry[$col_maps['question3']]);
        }

        $data['question'] = implode("\\n", $questions);

        // Process question image
        if( !empty($entry[$col_maps['image_key']]) ) {
            $data['question_images'] = [];
            $images = explode(':', $entry[$col_maps['image_key']]);

            foreach($images as $order => $image) {
                $data['question_images'][] = [
                    'url' => $image,
                    'caption' => '',
                    'width' => null,
                    'height' => null,
                    'order' => $order + 1
                ];
            }
        }

        return $data;
    }

    /**
     * Clean all unnecessary, return array of answers. Ex: [1 => answer1, 2 => answer2 ..]
     *
     * @param array $entry of row google sheet
     * @param array $map_cols
     *
     * @return array
     */
    private function get_deck_question_answer_entry($entry, $map_cols = [])
    {
        unset(
            $entry[$map_cols['stage_id']],
            $entry[$map_cols['question_id']],
            $entry[$map_cols['question_type']],
            $entry[$map_cols['question1']],
            $entry[$map_cols['question2']],
            $entry[$map_cols['question3']],
            $entry[$map_cols['image_key']],
            $entry[$map_cols['correct']],
            $entry[$map_cols['random']]
        );

        $answers = [];
        $count = 0;
        foreach($entry as $value) {
            $answers[++$count] = $value;
        }

        return $answers;
    }

    /**
     * Build data answer for single question type
     *
     * @param array $entry of row google sheet
     * @param array $map_cols
     *
     * @return array
     */
    private function get_deck_question_data_answer_single($entry, $map_cols = [])
    {
        $data = [
            'answers' => []
        ];

        $right_answer = $entry[$map_cols['correct']];

        $entry = $this->get_deck_question_answer_entry($entry, $map_cols);

        foreach($entry as $answer_key => $answer) {

            if(!$answer) continue;

            // Split image and text in answer
            $image = null;
            $text = $answer;
            if( strpos($answer, 'image:') !== FALSE ) {
                $split_arr = explode(':', $answer);
                $image = $split_arr[1];
                $text = $split_arr[2];
            }

            $data['answers'][] = [
                'text' => $this->escape_special_characters($text),
                'image_url' => $image,
                'correct' => $answer_key == $right_answer ? TRUE : FALSE,
                'order' => null
            ];
        }

        return $data;
    }

    /**
     * Import active video learning
     *
     * @param string $google_spread_id
     *
     * @throws Exception
     * @throws Google_Exception_api
     */
    public function active_learning_video($google_spread_id = '')
    {
        $this->load->model('video_model');
        $this->load->library('brightcove_video');

        $google_spread_id = !empty($google_spread_id) ? $google_spread_id : '1naHNvNd8v6t72qc5ddyjhJhVBuKxN3B1pkK3wA43OHU';

        $spread = $this->google_spreadsheet->get_spreadsheet_by_id($google_spread_id);

        $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet($spread, 'Sheet1');

        foreach ($list_entries AS $key => $entry) {
            
            if (empty($entry['videoid'])) {
                $this->add_error_log('[Import][Active_learning] Cannot detect video id of row ' . ($key + 2));
                continue;
            }

            log_message('info', '[Import][Active_learning] '.$entry['videoid']);

            $brightcove_img = null;

            $brightcove_id = mb_trim($entry['brightcoveid']);

            if (!empty($brightcove_id)) {

                $brightcove_img = $this->get_brightcove_image_link($brightcove_id);
            }

            if (empty($brightcove_img)) {
                $this->add_error_log('[Import][Active_learning] Cannot detect brightcove id of row ' . ($key + 2));
                continue;
            }

            $video_data = [
                'name' => $entry['videoid'],
                'type' => Video_model::TYPE_ACTIVE_LEARNING_VIDEO,
                'description' => 'Acitve learning video description of ' . $entry['videoid'],
                'brightcove_id' => $brightcove_id,
                'brightcove_thumbnail_url' => !empty($brightcove_img) ? $brightcove_img : null
            ];

            $video = $this->video_model->find_by([
                'name' => $entry['videoid'],
                'type' => Video_model::TYPE_ACTIVE_LEARNING_VIDEO,
            ]);

            if (!empty($video)) {
                $video_data['id'] = $video->id;
            }

            $this->video_model->create($video_data, [
                'mode' => 'replace',
                'master' => TRUE
            ]);
        }

        //$this->show_error_log();
    }

    /**
     * Dummy user for playing stage score
     * @throws Google_Exception_api
     */
    public function dummy_user($from_row = 3, $to_row = 6)
    {
        $this->load->model('user_model');
        $this->load->model('user_profile_model');
        $this->load->model('stage_model');
        $this->load->model('user_playing_stage_model');

        $spread = $this->google_spreadsheet->get_spreadsheet_by_id('1fmijZ9WHMCi86QkoItJr7OTwvQC0jwDlim4m3SkyVis');

        $worksheet_feed = $spread->getWorksheets();

        $worksheet = $worksheet_feed->getByTitle('シンプルバージョン');

        $cell_feed = $worksheet->getCellFeed();

        $list_users = [];

        for ($i=0; $i<10; ++$i) {

            $user = $this->user_model->find_by([
                'login_id' => $cell_feed->getCell(2, 4 + $i)->getContent()
            ]);

            if (empty($user)) {
                $user = $this->user_model->create([
                    'login_id' =>  $cell_feed->getCell(2, 4 + $i)->getContent(),
                    'email' =>  generate_unique_key(30),
                    'password' =>  generate_unique_key(64),
                    'primary_type' =>  'student',
                    'nickname' =>  $cell_feed->getCell(3, 4 + $i)->getContent(),
                    'email_verified' => 1,
                    'status' => 'active'
                ], [
                    'return' => TRUE
                ]);

                $this->user_profile_model->create([
                    'user_id' => $user->id,
                    'avatar_id' => $cell_feed->getCell(4, 4 + $i)->getContent()
                ]);
            }
            $list_users[] = $user->id;
        }

        $worksheet = $worksheet_feed->getByTitle('冨永さん初期作成');

        $cell_feed = $worksheet->getCellFeed();

        $list_drill_keys = [];
        $list_drill_stage = [];

        for($i = $from_row; $i <= $to_row; ++$i) {
            $type = strpos($cell_feed->getCell($i, 4)->getContent(), 'odd') === 0 ? 'odd' : 'even';
            $drill_keys = explode(',', $cell_feed->getCell($i, 3)->getContent());

            foreach($drill_keys AS $drill_key) {

                $list_drill_keys[$drill_key][$type] = [];

                for ($j = 0; $j < 10; ++$j) {
                    $list_drill_keys[$drill_key][$type][$j] = $cell_feed->getCell($i, 5 +$j)->getContent();
                }

                if (!isset($list_drill_stage[$drill_key])) {
                    $list_drill_stage[$drill_key] = $this->stage_model
                        ->select('stage.id as id, stage.key as key')
                        ->join('deck', 'deck.id = stage.deck_id')
                        ->where('deck.key', $drill_key)
                        ->all();

                    foreach($list_drill_stage[$drill_key] AS $index => $stage) {
                        $list_drill_stage[$drill_key][$index] = get_object_vars($stage);
                    }
                }
            }
        }

        // Dummy record
        foreach ($list_drill_stage AS $drill_key => $list_stage) {

            if (empty($list_stage)) continue;

            log_message('info', 'Import Dummy Score of Deck' . $drill_key );

            foreach ($list_stage AS $stage) {
                // Import to user
                $key = $stage['key'];

                $stage_type = explode('-', $key);
                $stage_type = end($stage_type);
                $stage_type = $stage_type % 2 == 1 ? 'odd' : 'even';

                foreach($list_drill_keys[$drill_key][$stage_type] AS $user_index => $user_score) {

                    $data = [
                        'user_id' => $list_users[$user_index],
                        'stage_id' => $stage['id'],
                        'second' => 0,
                        'score' => $user_score,
                    ];

                    $play = $this->user_playing_stage_model->find_by([
                        'user_id' => $list_users[$user_index],
                        'stage_id' => $stage['id']
                    ]);

                    if (!empty($play)) {
                        $data['id'] = $play->id;
                    }

                    $this->user_playing_stage_model->create($data, [
                        'mode' => 'replace'
                    ]);
                }
            }
        }
    }

    /**
     * Fix question key
     */
    public function fix_question_key()
    {
        $this->load->model('question_model');

        $res = $this->question_model
            ->select('id, question_key')
            ->like('question_key', '2-1-')
            ->all();

        foreach ($res AS $question) {
            if (strpos($question->question_key, '2-1-') === 0) {

                log_message('info', '[FixQuestionKey] Question ID : ' . $question->id);
                $keys = explode('-', $question->question_key);

                $keys[1] = 2;

                $this->question_model->update($question->id, [
                    'question_key' => implode('-', $keys)
                ]);
            }
        }
    }

    /**
     * Get list master normal question from google sheet
     *
     * @return array
     * @throws Google_Exception_api
     */
    public function get_list_master_normal_quest()
    {
        // Get list normal quest entries
        $normal_quest_spread = $this->google_spreadsheet->get_spreadsheet_by_id('1qyFm3TPiJuhDMLGvk3c6fXBXUnDg6qkXnrZdD3h3aPI');

        $normal_quest_entries = $this->google_spreadsheet->get_list_entries_in_sheet($normal_quest_spread, 'sheet1');

        $quest_col_maps = [];

        foreach ($normal_quest_entries[0] AS $key => $val) {
            $quest_col_maps[$val] = $key;
        }

        // Unset $normal_quest_entries[0] because this is row of english header
        unset($normal_quest_entries[0]);

        $return = [];

        foreach ($normal_quest_entries AS $normal_quest_entry) {

            $return[$normal_quest_entry[$quest_col_maps['quest_master_id']]] = [
                'title' => $normal_quest_entry[$quest_col_maps['quest_title']],
                'description' => $normal_quest_entry[$quest_col_maps['quest_description']],
                'type' => $normal_quest_entry[$quest_col_maps['quest_type']],
                'clear_condition_1' => $normal_quest_entry[$quest_col_maps['clear_condition1']],
                'clear_condition_2' => $normal_quest_entry[$quest_col_maps['clear_condition2']],
                'drill_type' => $normal_quest_entry[$quest_col_maps['drill_type']]
            ];
        }

        return $return;

    }

    /**
     * Import normal quest master into DB
     * @param int $deck_package_id
     * @param int $deck_category_id
     */
    public function normal_quest($deck_package_id = 1, $deck_category_id = 1)
    {
        $this->load->model('stage_model');
        $this->load->model('quest_model');

        $normal_quest_entries = $this->get_list_master_normal_quest();

        $drive_service = $this->google_spreadsheet->get_drive_instance();

        // Get all Stage quest spread in folder 0B9kskUq4mKAoWjJ4bzdPdWRGUmc
        $results = $drive_service->files->listFiles([
            'q' => "'0B9kskUq4mKAoWjJ4bzdPdWRGUmc' in parents"
        ]);

        foreach ($results->getItems() AS $spread_file) {

            // This code will be igore package doesn't need update
            if ($deck_package_id != (int) $spread_file->getTitle()) continue;

            //
            $spread = $this->google_spreadsheet->get_spreadsheet_by_id($spread_file->id);

            $list_sheet_titles = $this->google_spreadsheet->get_list_sheet_titles($spread);

            foreach ($list_sheet_titles AS $sheet_title) {

                $list_entries = $this->google_spreadsheet->get_list_entries_in_sheet($spread, $sheet_title);

                $deck_key = $deck_package_id . '-' . $deck_category_id . '-' . (int) $sheet_title;

                foreach ($list_entries AS $entry) {
                    $stage_key = $deck_key . '-' . $entry['stageid'];

                    $stage = $this->stage_model->find_by([
                        'key' => $stage_key
                    ]);

                    if (empty($stage)) {
                        log_message('info', sprintf('Stage %s is not exist', $stage_key) );
                        continue;
                    }

                    $quest = $this->quest_model->find_by([
                        'stage_id' => $stage->id,
                        'key' => $entry['questmasterid']
                    ]);

                    $quest_data = array_merge($normal_quest_entries[$entry['questmasterid']], [
                        'key' => $entry['questmasterid'],
                        'stage_id' => $stage->id,
                        'order' => $entry['order']
                    ]);

                    if (!empty($quest)) {
                        $quest_data['id'] = $quest->id;
                    }

                    $this->quest_model->create($quest_data, [
                        'mode' => 'replace'
                    ]);

                }
            }
        }
    }

    /*
     * Check master textbook inuse
     */
    public function check_school_master_textbook_inuse()
    {
        $this->load->model('master_school_model');

        $spread_id = '1rHfNWR9_THTGHB86xLCJm7MA8gvMx7SQiTBhEnhS95o';

        $spread = $this->google_spreadsheet->get_spreadsheet_by_id($spread_id);

        $res = $this->master_school_model
            ->select('master_school.id, pt.postalcode, master_school.type, master_school.name, COUNT(textbook_id), group_concat(inuse.subject_id) AS inuse_subjects')
            ->join('master_textbook_inuse AS inuse', 'master_school.id = inuse.school_id', 'left')
            ->join('master_postalcode AS pt', 'pt.id = master_school.postalcode_id', 'left')
            ->group_by('master_school.id')
            ->all();

        $row = 1;

        $batch_datas = [];

        $total = count($res);

        foreach ($res AS $key => $school) {

            log_message('info', sprintf('Check master textbook inuse of school (%s/%s): %s %s', $key + 1, $total, $school->id, $school->name));
            // Check each school
            switch ($school->type) {
                case 'elementary':
                    $required_subjects = [12, 22, 32, 33, 34, 42, 43, 44, 52, 53, 54, 62, 63, 64];
                    break;

                default: // Case secondary or junior_hight
                    $required_subjects = [72, 73, 74, 75, 76, 82, 83, 84, 85, 86, 92, 93, 94, 95, 96, 97];
                    break;
            }

            $compare_subjects = array_diff($required_subjects, explode(',', $school->inuse_subjects));

            if (count($compare_subjects) == 0) {
                // If this school has enough required subject, ignore check
                continue;
            }

            ++$row;

            $batch_datas[] = [
                'row' => $row,
                'col' => 1,
                'value' => $school->id
            ];

            $batch_datas[] = [
                'row' => $row,
                'col' => 2,
                'value' => $school->name
            ];

            $batch_datas[] = [
                'row' => $row,
                'col' => 3,
                'value' => $school->postalcode
            ];

            $batch_datas[] = [
                'row' => $row,
                'col' => 4,
                'value' => $school->type
            ];

            $batch_datas[] = [
                'row' => $row,
                'col' => 5,
                'value' => $school->inuse_subjects
            ];

            $batch_datas[] = [
                'row' => $row,
                'col' => 6,
                'value' => implode(',', $compare_subjects)
            ];

            if (count($batch_datas) > 300) {
                $this->google_spreadsheet->update_sheet_by_batch($spread, ENVIRONMENT, $batch_datas);
                $batch_datas = [];
            }
        }

        if (count($batch_datas)) {
            $this->google_spreadsheet->update_sheet_by_batch($spread, ENVIRONMENT, $batch_datas);
        }
    }

    /**
     * Fix temporary textbook inuse for each school
     */
    public function fix_temp_school_master_textbook_inuse()
    {

        $this->load->model('master_textbook_inuse_model');

        $res = $this->master_school_model
            ->select('master_school.id, pt.postalcode, master_school.type, master_school.name, COUNT(textbook_id), group_concat(inuse.subject_id) AS inuse_subjects')
            ->join('master_textbook_inuse AS inuse', 'master_school.id = inuse.school_id', 'left')
            ->join('master_postalcode AS pt', 'pt.id = master_school.postalcode_id', 'left')
            ->group_by('master_school.id')
            ->all();

        $temporary = [];

        switch (ENVIRONMENT) {
            case 'development':
            case 'testing':
                $temporary['juniorhigh'] = $temporary['secondary'] = [
                    '74' => '17',
                    '84' => '23',
                    '75' => '150',
                    '85' => '155',
                    '95' => '160',
                    '76' => '165',
                    '86' => '173',
                    '96' => '181',
                    '97' => '189',
                    '72' => '198',
                    '82' => '210',
                    '92' => '218',
                    '73' => '221',
                    '83' => '226',
                    '93' => '231'
                ];

                $temporary['elementary'] = [
                    '34' => '65',
                    '44' => '70',
                    '54' => '75',
                    '64' => '80',
                    '12' => '84',
                    '22' => '91',
                    '32' => '98',
                    '42' => '105',
                    '52' => '112',
                    '62' => '119',
                    '33' => '126',
                    '43' => '132',
                    '53' => '138',
                    '63' => '144'
                ];
                break;

            case 'staging':
            case 'production':
                $temporary['juniorhigh'] = $temporary['secondary'] = [
                    '73' => '17',
                    '83' => '119',
                    '93' => '107',
                    '76' => '160',
                    '86' => '138',
                    '96' => '22',
                    '74' => '30',
                    '84' => '36',
                    '94' => '42',
                    '72' => '157',
                    '82' => '128',
                    '92' => '116',
                    '97' => '131',
                    '75' => '167',
                    '85' => '149',
                    '95' => '145'
                ];

                $temporary['elementary'] = [
                    '34' => '2',
                    '44' => '6',
                    '54' => '10',
                    '64' => '14',
                    '12' => '71',
                    '22' => '77',
                    '32' => '83',
                    '42' => '89',
                    '52' => '95',
                    '62' => '101',
                    '33' => '47',
                    '43' => '53',
                    '53' => '59',
                    '63' => '65'
                ];
                break;
        }



        $bulk_array = [];

        $count = 0;

        foreach ($res AS $school) {

            $subject_school_db = explode(',', $school->inuse_subjects);

            // For fix subject inuse temporary

            foreach ($temporary[$school->type] AS $req_subject_id => $req_textbook_id)
            {
                if (!in_array($req_subject_id, $subject_school_db)) {
                    $bulk_array[] = [
                        'school_id' => $school->id,
                        'year_id' => 1,
                        'subject_id' => (int) $req_subject_id,
                        'textbook_id' => (int) $req_textbook_id
                    ];
                }

                if (count($bulk_array) >= 500) {
                    ++$count;
                    log_message('info', 'Bulk_create ' . $count);

                    $this->master_textbook_inuse_model->bulk_create($bulk_array);
                    $bulk_array = [];
                }
            }
        }

        // Import the last one
        if (count($bulk_array) > 0) {
            $this->master_textbook_inuse_model->bulk_create($bulk_array);
        }

    }
}
