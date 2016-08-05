<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Stage_api
 *
 * @property Stage_model stage_model
 * @property User_playing_stage_model user_playing_stage_model
 * @property User_group_playing_model user_group_playing_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Stage_api extends Base_api
{
    /**
     * Const
     */
    const SUM_SCORE = 100;
    const TOTAL_SCORE = 20;
    const RATE_COMBO_SCORE = 40;

    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Stage_api_validator';

    /**
     * Get list stage of deck - Spec S-010
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
        $v->set_rules('deck_id', 'デッキID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('stage_model');

        // Set query
        $res = $this->stage_model
            ->calc_found_rows()
            ->select('stage.id, stage.name, stage.order, 
                MAX(user_playing_stage.score) as highest_score, user_playing_stage.created_at')
            ->with_play()
            ->where('deck.id', $params['deck_id'])
            ->group_by('stage.id')
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->stage_model->found_rows()
        ]);
    }

    /**
     * Get detail of stage - Spec S-020
     * 
     * @param array $params
     * 
     * @internal param int $user_id
     * @internal param int $stage_id
     * @internal param string $stage_type
     * @internal param string $type
     * @internal param int $battle_room_id
     * 
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('stage_id', 'ステージID', 'required|integer');
        $v->set_rules('battle_room_id', 'battle room ID', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Filter type
        if (empty($params['type'])) {
            $params['type'] = 'battle';
        }

        // Load model
        $this->load->model('stage_model');
        $this->load->model('user_group_playing_model');

        // Get detail stage with mode play team_battle
        if (isset($params['stage_type']) && $params['stage_type'] == 'team_battle') {
            // Set query
            $this->stage_model
                ->select('deck.id as deck_id, deck.name as deck_name, user_group_playing.id,
                    stage.id, stage.name, stage.order,
                    MAX(user_group_playing.score) as highest_score, MAX(user_group_playing.created_at) as created_at')
                ->with_group_play()
                ->where('user_group_playing.user_id', $params['user_id'])
                ->where('stage.id', $params['stage_id'])
                ->where('group_playing.target_id', $params['battle_room_id']);

        } else {
            // Set query
            $this->stage_model
                ->select('deck.id as deck_id, deck.name as deck_name, 
                    stage.id, stage.name, stage.order,
                    MAX(user_playing_stage.score) as highest_score, MAX(user_playing_stage.created_at) as created_at')
                ->with_play()
                ->where('user_playing_stage.user_id', $params['user_id'])
                ->where('stage.id', $params['stage_id']);

            // Filter by type
            if (!empty($params['type'])) {
                $this->stage_model->where('type', $params['type']);
            }
        }

        $res = $this->stage_model->first();

        // Return
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Calculate score for question - Spec S-060
     * 
     * @param array $params
     * @internal param int $second
     * @internal param int $limit
     *
     * @return array
     */
    public function calculate($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('second', '秒', 'required|integer');
        $v->set_rules('limit', '制限時間', 'required|integer|is_natural_no_zero');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $total = (self::SUM_SCORE - ($params['second'] / $params['limit'] ) * self::SUM_SCORE) * self::TOTAL_SCORE;

        // Return
        return $this->true_json(['total' => round($total)]);
    }

    /**
     * Get total of score - Spec S-070
     * 
     * @param array $params
     * 
     * @internal param array $scores
     *
     * @return array
     */
    public function get_total_score($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('scores[]', 'スコア', 'required|integer');

        // Run Validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_playing_stage_model');

        $total = $this->user_playing_stage_model->get_total_score($params);

        // Return
        return $this->true_json(['total_score' => round($total) ]);
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
        if (empty($res)) {
            return [];
        }

        return [
            'deck_id' => $res->deck_id,
            'deck_name' => $res->deck_name,
            'id' => (int) $res->id,
            'name' => $res->name,
            'oder' => (int) $res->order,
            'score' => [
                'highest_score' => $res->highest_score,
                'created_at' => $res->created_at
            ]
        ];
    }

}

/**
 * Class Stage_api_validator
 *
 * @property Stage_api $base
 */
class Stage_api_validator extends Base_api_validation
{

}
