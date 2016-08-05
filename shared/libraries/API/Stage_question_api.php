<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Stage_question API
 *
 * @property Question_model question_model
 * @property Stage_model stage_model
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Stage_question_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Stage_question_api_validator';

    /**
     * Get questions for stage SQ-010
     *
     * @param array $params
     * @internal int $stage_id of Deck stage
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('stage_id', 'ステージID', 'required|valid_stage_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('question_model');
        $res = $this->question_model
            ->select('question.id, question.type, question.data')
            ->calc_found_rows()
            ->with_stage_question_inuse()
            ->where('stage_question_inuse.stage_id', $params['stage_id'])
            ->all();

        return $this->true_json([
            'total' => $this->question_model->found_rows(),
            'items' => $this->build_responses($res)
        ]);
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
        $result = [
            'id' => isset($res->id) ? (int) $res->id : null,
            'second' => isset($res->second) ? (float) $res->second : 600,
            'type' => isset($res->type) ? $res->type : null
        ];

        $answer_data = json_decode($res->data, TRUE);

        if (!empty($answer_data['answers'])) {
            if ((isset($answer_data['random']) && $answer_data['random'] == 'on') || !isset($answer_data['random'])) {
                shuffle($answer_data['answers']);
            }
        }

        $result = array_merge($result, $answer_data);

        return $result;
    }
}

/**
 * Class Stage_question_api_validator
 *
 * @property Stage_api base
 * @property Stage_model stage_model
 */
class Stage_question_api_validator extends Base_api_validation
{
    /**
     * Check valid deck stage Id
     *
     * @var string
     *
     * @return bool
     */
    function valid_stage_id($id)
    {
        // Load model
        $this->base->load->model('stage_model');

        $res = $this->base->stage_model->find($id);

        // If existing return error
        if (empty($res)) {
            $this->set_message('valid_stage_id', 'ステージが存在しません');
            return FALSE;
        }

        return TRUE;
    }
}
