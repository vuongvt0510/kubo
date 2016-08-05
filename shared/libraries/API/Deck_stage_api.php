<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Deck_stage_api
 *
 * @property Deck_model deck_model
 * @property Memorization_model memorization_model
 * @property User_playing_stage_model user_playing_stage_model
 * @property User_group_playing_model user_group_playing_model
 * @property Group_playing_model group_playing_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Deck_stage_api extends Base_api
{

    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Deck_stage_api_validator';

    /**
     * Get list stage of deck - Spec DS-010
     * 
     * @param array $params
     * 
     * @internal param int $user_id
     * @internal param int $deck_id
     * @internal param int $offset Default: 0
     * @internal param int $limit Default: 20
     * @internal param string $stage_type
     * 
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'integer');
        $v->set_rules('deck_id', 'デッキID', 'required|integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Filter type
        if (empty($params['type'])) {
            $params['type'] = 'battle';
        }

        // Filter type
        if (empty($params['user_id'])) {
            $params['user_id'] = $this->operator()->id;
        }

        // Load model
        $this->load->model('deck_model');
        $this->load->model('user_playing_stage_model');
        $this->load->model('user_group_playing_model');

        if (isset($params['stage_id'])) {
            $this->deck_model->where('stage.id', $params['stage_id']);
        }
        // Set query
        $stages = $this->deck_model
            ->select('stage.id, stage.name, stage.order')
            ->with_stage()
            ->where('deck.id', $params['deck_id']);

        if (!empty($params['limit'])) {
            $stages = $stages->limit($params['limit']);
        }

        if (!empty($params['offset'])) {
            $stages = $stages->offset($params['offset']);
        }

        $stages = $stages->all();

        $result = [];
        if (!empty($stages)) {
            foreach ($stages as $key => $stage) {
                $res = $stage;

                if (!isset($params['stage_type'])) {
                    $score = $this->user_playing_stage_model
                        ->select('MAX(score) as highest_score, MAX(created_at) as created_at')
                        ->where('user_id', $params['user_id'])
                        ->where('stage_id', $stage->id)
                        ->where('type', $params['type'])
                        ->first();

                    $res->highest_score = null;
                    $res->created_at = null;

                    if (isset($score->highest_score)) {
                        $res->highest_score = $score->highest_score;
                        $res->created_at = $score->created_at;
                    }
                    $return_option = ['list'];
                } else {
                    switch ($params['stage_type']) {
                        case 'memorize':
                            $this->load->model('memorization_model');

                            $memorization =[
                                'remember' => 0,
                                'consider' => 0,
                                'forget' => 0,
                                'not_checked' => 0
                            ];

                            $memorization_statues = $this->memorization_model
                                ->select('schooltv_main.user_memorization.user_id, schooltv_main.user_memorization.status, schooltv_main.user_memorization.created_at')
                                ->join('schooltv_main.user_memorization', 'schooltv_main.user_memorization.memorization_id = memorization.id AND schooltv_main.user_memorization.user_id = '.$params['user_id'], 'left')
                                ->where('memorization.stage_id', $stage->id)
                                ->all();

                            foreach ($memorization_statues as $status) {
                                if (empty($status->status)) {
                                    $memorization['not_checked'] +=1;
                                } else {
                                    $memorization[$status->status] += 1;
                                }
                            }
                            $res->memorization = $memorization;
                            $return_option = [];
                            break;

                        // Select stage with mode quest
                        case 'quest' :
                            $this->load->model('group_playing_model');
                            $quest_status = $this->group_playing_model
                                ->select('COUNT(group_playing.id) as total_clear')
                                ->join('schooltv_content.quest', 'group_playing.target_id = schooltv_content.quest.id')
                                ->join('schooltv_content.stage', 'schooltv_content.quest.stage_id = schooltv_content.stage.id')
                                ->where('schooltv_content.stage.deck_id', $params['deck_id'])
                                ->where('group_playing.group_id', $params['group_id'])
                                ->where('group_playing.status', 'clear')
                                ->where('group_playing.type', 'quest')
                                ->first();

                            $res->total_clear = $quest_status->total_clear;
                            $return_option = [];
                            break;

                        // Select stage with mode team_battle
                        case 'team_battle':
                            $this->load->model('user_group_playing_model');
                            $score = $this->user_group_playing_model
                                ->select('MAX(user_group_playing.score) AS highest_score, MAX(user_group_playing.created_at) AS created_at')
                                ->join('group_playing', 'group_playing.id = user_group_playing.group_playing_id')
                                ->where('user_group_playing.user_id', $params['user_id'])
                                ->where('user_group_playing.target_id', $stage->id)
                                ->where('group_playing.group_id', $params['group_id'])
                                ->where('group_playing.target_id', $params['battle_room_id'])
                                ->first();

                            $res->highest_score = null;
                            $res->created_at = null;

                            if (isset($score->highest_score)) {
                                $res->highest_score = $score->highest_score;
                                $res->created_at = $score->created_at;
                            }

                            // check another user played stage
                            $played = $this->user_group_playing_model
                                ->select('user_group_playing.id')
                                ->join('group_playing', 'group_playing.id = user_group_playing.group_playing_id')
                                ->where('user_group_playing.target_id', $stage->id)
                                ->where('group_playing.group_id', $params['group_id'])
                                ->where('group_playing.target_id', $params['battle_room_id'])
                                ->first();

                            // Check curent user played
                            $current_user = $this->user_group_playing_model
                                ->select('user_group_playing.id')
                                ->join('group_playing', 'group_playing.id = user_group_playing.group_playing_id')
                                ->where('user_group_playing.user_id', $params['user_id'])
                                ->where('group_playing.group_id', $params['group_id'])
                                ->where('group_playing.target_id', $params['battle_room_id'])
                                ->first();

                            $res->group_played = !empty($current_user) ? TRUE : (!empty($played) ? TRUE : FALSE);

                            $return_option = ['list'];
                            break;

                        case 'status':

                            $user_id = $params['user_id'];
                            $stage_id = $stage->id;
                            $score_group = $this->user_group_playing_model
                                ->select('score as highest_score, question_answer_data')
                                ->join("(SELECT MAX(score) AS highest_score FROM user_group_playing WHERE user_id = $user_id AND target_id = $stage_id) AS highest_score", 'highest_score.highest_score = user_group_playing.score')
                                ->where('user_id', $user_id)
                                ->where('target_id', $stage_id)
                                ->first();

                            $time_group = $this->user_group_playing_model
                                ->select('SUM(second) as total_time, COUNT(id) as playing_time')
                                ->where('user_id', $user_id)
                                ->where('target_id', $stage_id)
                                ->first();

                            $score_individual = $this->user_playing_stage_model
                                ->select('score as highest_score, question_answer_data')
                                ->join("(SELECT MAX(score) AS highest_score FROM user_playing_stage WHERE user_id = $user_id AND stage_id = $stage_id) AS highest_score", 'highest_score.highest_score = user_playing_stage.score')
                                ->where('user_id', $user_id)
                                ->where('stage_id', $stage_id)
                                ->where('type !=', 'memorize')
                                ->first();

                            $time_individual = $this->user_playing_stage_model
                                ->select('SUM(second) as total_time, COUNT(id) as playing_time')
                                ->where('user_id', $user_id)
                                ->where('stage_id', $stage_id)
                                ->where('type !=', 'memorize')
                                ->first();

                            $res->total_second = $time_group->total_time + $time_individual->total_time;
                            $res->total_time = $time_group->playing_time + $time_individual->playing_time;

                            if (!empty($score_group) && !empty($score_individual)) {
                                $res->highest_score = $score_group->highest_score > $score_individual->highest_score ? $score_group->highest_score : $score_individual->highest_score;
                                $res->highest_score_data = $score_group->highest_score > $score_individual->highest_score ? json_decode($score_group->question_answer_data, TRUE) : json_decode($score_individual->question_answer_data, TRUE);
                            }

                            if (!empty($score_group) && empty($score_individual)) {
                                $res->highest_score = $score_group->highest_score;
                                $res->highest_score_data = json_decode($score_group->question_answer_data, TRUE);
                            }

                            if (empty($score_group) && !empty($score_individual)) {
                                $res->highest_score = $score_individual->highest_score;
                                $res->highest_score_data = json_decode($score_individual->question_answer_data, TRUE);
                            }

                            $return_option = [];
                            break;
                    }
                }

                $result[] = $res;
            }
        }

        // Return
        return $this->true_json([
            'items' => $this->build_responses($result, $return_option)
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

        $result = [];

        // Build the list deck stage response
        if (in_array('list', $options)) {

            $result['id'] = $res->id;
            $result['name'] = $res->name;
            $result['order'] = $res->order;
            $result['score']['highest_score'] = $res->highest_score;
            $result['score']['created_at'] = $res->created_at;
            $result['group_played'] = isset($res->group_played) ? $res->group_played : null;
        }
        
        // Build the list response
        if (empty($options) && !empty($res)) {
            $result = get_object_vars($res);
        }

        return $result;
    }
}

/**
 * Class Deck_stage_api_validator
 *
 * @property Deck_stage_api $base
 */
class Deck_stage_api_validator extends Base_api_validation
{

}
