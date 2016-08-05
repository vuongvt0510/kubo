<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Question Control API
 *
 * @property Question_model question_model
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 *
 * @author Akiyuki Nomura
 * @author IMVN Team
 */
class Question_api extends Base_api
{

    /**
     * Get questions related to Specific Video Spec NONE
     *
     * @param array $params
     * @param array $options
     *
     * @internal param int $video_id Video ID
     *
     * @return array
     */
    public function get_video_question($params, /** @noinspection PhpUnusedParameterInspection */
                                       $options = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('video_id', '動画ID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('question_model');
        $res = $this->question_model
            ->with_video_id((int) $params['video_id'])
            ->all();

        return $this->true_json([
            'total' => COUNT($res),
            'items' => $this->build_responses($res)
        ]);
    }

    /**
     * Question get list API Spec Q-020
     *
     * @param array $params
     * @internal param int $deck_id
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('deck_id', 'デッキID', 'required|integer|valid_deck_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load Model
        $this->load->model('question_model');

        // Set default query
        $res = $this->question_model
            ->calc_found_rows()
            ->select('question.id, question.type, question.data')
            ->where('deck.id', $params['deck_id'])
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->deck_model->found_rows()
        ]);
    }

    /**
     * Building response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = [])
    {
        $data = json_decode($res->data, TRUE);

        return array_merge($data, [
            'id' => (int) $res->id,
            'type' => $res->type,
            'second' => (float) $res->second,
            'deck_id' => (int) $res->deck_id
        ]);
    }

}
