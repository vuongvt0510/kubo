<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(SHAREDPATH . 'core/APP_Batch_controller.php');

/**
 * Class User_playing_stage batch
 *
 * @property user_playing_stage_model
 *
 * @package Controller
 *
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_playing_stage extends APP_Batch_controller
{
    public function __construct()
    {
        parent::__construct();

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        $this->load->model('user_playing_stage_model');
        $this->load->model('question_model');
    }

    /**
     * Change format question answer data of User playing stage
     */
    public function change_format_question_answer_data()
    {
        log_message('info', '[History] Change format question_answer_data');

        // Set condition
        $cond = [
            'select' => [
                'id, stage_id, second, question_answer_data'
            ],
            'where' => [
                'question_answer_data LIKE' => '%scores%'
            ]
        ];

        // call back sql
        $this->call_callback_to($this->user_playing_stage_model, 'all', $cond, function ($res) {
            log_message('Info', sprintf('Update data for %s', $res->id));

            // do update
            $data = get_object_vars(json_decode($res->question_answer_data));

            $scores = $data['scores'];
            $question_data = [];

            $data = $this->generate_case_1($res->stage_id, $res->second, $data, $scores);

            $question_answer_data = json_encode($data);

            // Update data
            $this->user_playing_stage_model->update($res->id, [
                'question_answer_data' => $question_answer_data
            ]);
        });

        log_message("Info", sprintf("Update question_answer_data successfully !!!"));
    }

    /**
     * Case only have scores
     */
    protected function generate_case_1($stage_id = NULL, $second = NULL, $data = [], $scores = [])
    {
        // Get list question of stage
        $list_questions = $this->question_model
            ->select('question.id, question.data')
            ->with_stage_question_inuse()
            ->where('stage_question_inuse.stage_id', $stage_id)
            ->all();

        // Create questions data
        if (!empty($list_questions)) {
            $speed = round(($second/count($list_questions)), 2);

            foreach ($list_questions as $key => $value) {
                $question_unit = get_object_vars(json_decode($value->data));

                $question_data[] = [
                    'id' => $value->id,
                    'question' => $question_unit['question'],
                    'score' => (int) $scores[$key],
                    'speed' => $speed
                ];
            }
        }

        // Create milisecond data
        $milisecond = 0;
        if ($data['speed']) {
            $milisecond = explode('.', "{$data['speed']}");
            $milisecond = ((int) $milisecond[1]) > 9 ? $milisecond[1] : 10* ((int) $milisecond[1]);
        }

        // Remove scores data
        unset($data['scores']);

        // Update question and milisecond data
        $data['milisecond'] = $milisecond;
        $data['questions'] = $question_data;

        return $data;
    }
}
