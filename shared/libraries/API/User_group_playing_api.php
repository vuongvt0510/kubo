<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_group_playing_api
 *
 * @property Notification_model notification_model
 * @property Battle_room_model battle_room_model
 * @property Group__model group__model
 * @property User_playing_stage_model user_playing_stage_model
 * @property User_group_playing_model user_group_playing_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_group_playing_api extends Base_api
{

    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'User_group_playing_api_validator';

    /**
     * Get list timelines of user Spec UPR-010
     *
     * @param array $params
     *
     * @internal param int $round_id
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('round_id', 'Round ID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_group_playing_model');

        $res = $this->user_group_playing_model
            ->calc_found_rows()
            ->select('user.nickname, user_profile.avatar_id, user_group_playing.created_at, user_group_playing.updated_at, user_group_playing.status')
            ->select('user_group_playing.user_id, user_group_playing.score, user_group_playing.second, user_group_playing.question_answer_data')
            ->join('user', 'user.id = user_group_playing.user_id', 'left')
            ->join('user_profile', 'user_profile.user_id = user_group_playing.user_id', 'left')
            ->where('user_group_playing.group_playing_id', $params['round_id'])
            ->all();

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->user_group_playing_model->found_rows()
        ]);
    }

        /**
     * Get result play - Spec UPS-012
     * 
     * @param array $params
     * @internal param int $stage_id
     * @internal param int $user_id
     * @internal param array $scores
     * @internal param int $speed milliSecond
     * @internal param int $type
     * 
     * @return array
     */
    public function get_result_play($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('stage_id', 'ステージID', 'required|integer');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('scores[]', 'スコア', 'required|is_natural');
        $v->set_rules('speed', 'スピードゲーム', 'required|integer');
        $v->set_rules('group_id', 'group ID', 'required|integer');
        $v->set_rules('battle_room_id', 'battle room id', 'required|integer');
        $v->set_rules('type', 'タイプ', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Filter params play_id
        if (empty($params['play_id'])) {
            $params['play_id'] = NULL;
        }

        // Load model
        $this->load->model('user_group_playing_model');
        $this->load->model('user_playing_stage_model');
        $this->load->model('notification_model');

        $speed = $params['speed'];
        $score = (int) $this->user_playing_stage_model->get_total_score($params);
        $params['score'] = $score; // to save highest score group
        $question_total = count($params['scores']);
        $correct_number = count(array_filter($params['scores']));
        $correct_rate = round($correct_number/$question_total, 2) * 100; // 100%

        // Create playing stage
        $user_playing = $this->user_group_playing_model->create_playing($params);

        // Build response
        $result['score'] = $score;
        $result['speed']['second'] = round($speed / 1000);
        $result['speed']['milliSecond'] = substr(($speed % 1000), 0 , 2);
        $result['type'] = $params['type'];
        $result['question_total'] = $question_total; // to show result training
        $result['correct_number'] = $correct_number; // to show result training
        $result['correct_rate'] = $correct_rate; // to show result training

        // add good score
        $result['good_score'] = []; // add good score
        $result['rabipoint'] = []; // add rabipoint

        // Return
        return $this->true_json($result);
    }

    /**
     * Get result data team battle
     */
    public function get_data_result($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('battle_room_id', 'battle room id', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Filter params play_id
        if (empty($params['play_id'])) {
            $params['play_id'] = NULL;
        }

        // Load model
        $this->load->model('battle_room_model');
        $this->load->model('group_model');

        // Create playing stage
        $res = $this->battle_room_model
            ->select('id, group_id, target_group_id, time_limit, created_at, extra_data')
            ->where('id', $params['battle_room_id'])
            ->where('extra_data !=', '')
            ->first();

        if (empty($res)) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // build array member
        $extra_data = get_object_vars(json_decode($res->extra_data));
        $res->extra_data = $extra_data;

        // Set score 
        $res->group_score = $extra_data['group_score'];
        $res->target_group_score = $extra_data['target_group_score'];

        // Get name of group
        $group_name = $this->group_model
            ->select('name')
            ->where('id', $res->group_id)
            ->first();

        // Set group_name
        $res->group_name = $group_name->name;

        // Get name of target_group
        $target_group_name = $this->group_model
            ->select('name')
            ->where('id', $res->target_group_id)
            ->first();
        
        // Set target_group_name
        $res->target_group_name = $target_group_name->name;

        // Return
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Check stage team played
     */
    public function check_team_played_stage($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_id', 'グループID', 'required|integer');
        $v->set_rules('battle_room_id', 'battle room id', 'required|integer');
        $v->set_rules('stage_id', 'ステージID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_group_playing_model');

        // check another user played stage
        $played = $this->user_group_playing_model
            ->select('user_group_playing.id')
            ->join('group_playing', 'group_playing.id = user_group_playing.group_playing_id')
            ->where('user_group_playing.target_id', $params['stage_id'])
            ->where('group_playing.group_id', $params['group_id'])
            ->where('group_playing.target_id', $params['battle_room_id'])
            ->first();

        $res = [];
        $res['is_played'] = !empty($played) ? TRUE : FALSE;

        // Return true
        return $this->true_json($res);
    }
}

/**
 * Class User_group_playing_api_validator
 *
 * @property User_group_playing_api $base
 */
class User_group_playing_api_validator extends Base_api_validation
{
    /**
     * Validate group id
     *
     * @param  $group_id
     * @return bool
     */
    public function valid_group_id($group_id)
    {
        // Load model
        $this->base->load->model('group_model');

        // Check exist deck
        $res = $this->base->group_model->find($group_id);


        if (!$res) {
            $this->set_message('valid_group_id', 'Group ID is not exist');
            return FALSE;
        }

        if ($res->primary_type == 'family') {
            $this->set_message('valid_group_id', 'Group ID must be friend');
            return FALSE;
        }

        return TRUE;
    }
}