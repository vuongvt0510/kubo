<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_playing_api
 *
 * @property User_playing_stage_model user_playing_stage_model
 * @property User_group_playing_model user_group_playing_model
 * @property User_model user_model
 * @property User_power_model user_power_model
 * @property User_rabipoint_model user_rabipoint_model
 * @property Timeline_model timeline_model
 *
 * @version $id$
 *
 * @copyright 2016 Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_playing_api extends Base_api
{
    /**
     * Get detail of user with highest score API - Spec UPS-011
     * 
     * @param array $params
     * @internal param int $id Play ID
     * @internal param int $user_id User ID
     * @internal param int $stage_id
     * @internal param int $type
     * 
     * @return array
     */
    public function get_highest_score_user($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('id', 'プレーID', 'integer');
        $v->set_rules('user_id', 'ユーザーID', 'integer');
        $v->set_rules('stage_id', 'ターゲットID', 'integer');
        $v->set_rules('type', 'タイプ', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_playing_stage_model');

        // Set query
        $res = $this->user_playing_stage_model
            ->select('id, user_id, stage_id, second, MAX(score) as highest_score');

        // Filter by Play ID
        if (!empty($params['id'])) {
            $res->where('id', $params['id']);
        }

        // Filter by User ID
        if (!empty($params['user_id'])) {
            $res->where('user_id', $params['user_id']);
        }

        // Filter by Target ID
        if (!empty($params['stage_id'])) {
            $res->where('stage_id', $params['stage_id']);
        }

        // Filter by Play type
        if (!empty($params['type'])) {
            $res->where('type', $params['type']);
        }

        $res = $res->first();

        if (empty($res)) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Return
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Get result play - Spec UPS-012
     * 
     * @param array $params
     * @internal param int $play_id
     * @internal param int $stage_id
     * @internal param int $user_id
     * @internal param array $scores
     * @internal param int $speed milliSecond
     * @internal param int $type
     * @internal param int $round_id
     * 
     * @return array
     */
    public function get_result_play($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('play_id', 'プレイID', 'integer');
        $v->set_rules('stage_id', 'ステージID', 'required|integer');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('scores[]', 'スコア', 'required|is_natural');
        $v->set_rules('speed', 'スピードゲーム', 'required|integer');
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
        $this->load->model('user_playing_stage_model');
        $this->load->model('user_model');
        $this->load->model('user_power_model');
        $this->load->model('user_rabipoint_model');

        // Load library
        $this->load->library('session');

        $speed = $params['speed'];
        $score = (int) $this->user_playing_stage_model->get_total_score($params);
        $params['score'] = $score; // Add param to count rabipoint, lift power
        $question_total = count($params['scores']);
        $correct_number = count(array_filter($params['scores']));
        $correct_rate = round($correct_number/$question_total, 2) * 100; // 100%

        $good_score = [];
        $rabipoint = [];
        $lift_power = [];

        // Handle lift power of user
        $params['number_win'] = $this->session->userdata('number_win');
        if ($params['type'] != 'trial' && !isset($params['round_id']) && $params['operator_primary_type'] == 'student') {
            $lift_power = $this->user_power_model->do_lift_power($params);
        }

        // modify number_win session
        if (!empty($lift_power)) {
            $number_win = $lift_power['number_win'] < 4 ? $lift_power['number_win'] : 0;
            $this->session->set_userdata('number_win', $number_win);
        }

        // Handle user rabipoint
        if ($params['operator_primary_type'] == 'student') {
            $rabipoint = $this->user_rabipoint_model->creating($params);
        }

        // Save user_playing_stage
        $params['rabipoint'] = $rabipoint; // add to extra_data
        $params['lift_power'] = $lift_power; // add to extra_data

        // Create playing stage
        $user_playing = $this->user_playing_stage_model->create_playing($params);

        // Build response
        $result['score'] = $score;
        $result['speed']['second'] = round($speed / 1000);
        $result['speed']['milliSecond'] = substr(($speed % 1000), 0 , 2);
        $result['type'] = $params['type'];
        $result['question_total'] = $question_total; // to show result training
        $result['correct_number'] = $correct_number; // to show result training
        $result['correct_rate'] = $correct_rate; // to show result training

        // Add lift power params to response
        if (isset($lift_power['power_bonus']) && isset($lift_power['number_win'])) {
            $result['power_bonus'] = $lift_power['power_bonus'];
            $result['number_win'] = $lift_power['number_win'];
        }

        // Add rabipoint to response
        if (isset($rabipoint['rabipoint_bonus'])) {
            $result['rabipoint_bonus'] = $rabipoint['rabipoint_bonus'];

            // Create high score in battle to show
            if (in_array(User_rabipoint_model::RP_HIGH_SCORE_BATTLE, $rabipoint['cases'])) {
                $point_master = $this->user_rabipoint_model->get_rabipoint(User_rabipoint_model::RP_HIGH_SCORE_BATTLE);
                $good_score['high_score'] = [
                    'rabipoint_bonus' => $point_master->base_point * $point_master->campaign,
                    'type' => $params['type']
                ];
            }

            // Create higher in raking to show
            if (in_array(User_rabipoint_model::RP_HIGHER_RANKING, $rabipoint['cases'])) {
                // Get user ranking
                $user_ranking = $this->user_model->get_ranking_position('global', $params['user_id']);

                // Get opponent pass
                $opponent = $this->user_model
                    ->select('id')
                    ->where('highest_score <', $score)
                    ->order_by('highest_score', 'desc')
                    ->first();

                // Get opponent ranking
                $opponent_ranking = $this->user_model->get_ranking_position('global', $opponent->id);

                $point_master = $this->user_rabipoint_model->get_rabipoint(User_rabipoint_model::RP_HIGHER_RANKING);
                // Response higher ranking
                $good_score['higher_ranking'] = [
                    'score' => $score,
                    'rabipoint_bonus' => $point_master->base_point * $point_master->campaign,
                    'winner' => $user_ranking,
                    'loser' => $opponent_ranking
                ];
            }
            // Add length of array good_score
            $good_score['length'] = count($good_score);
        }

        // add good score
        $result['good_score'] = $good_score;

        // add rabipoint
        $result['rabipoint'] = $rabipoint;

        $trophy = [];
        // Create timeline and tropy
        if (in_array($params['type'], ['training', 'battle'])) {
            $this->load->model('timeline_model');

            if (!isset($params['round_id'])) {
                // Create timeline

                $this->timeline_model->create_timeline('play_deck', 'timeline', $params['stage_id'], null, $user_playing->id, 'individual_play');
                $this->timeline_model->create_timeline('achieve_ranking', 'timeline', $params['stage_id']);
                $this->timeline_model->create_timeline('play_score', 'timeline', $user_playing);

                if (isset($rabipoint['cases'])) {
                    if (in_array(User_rabipoint_model::RP_HIGHER_RANKING, $rabipoint['cases'])) {
                        $this->timeline_model->create_timeline('higher_ranking', 'timeline', $params['stage_id']);
                    }

                    if (in_array(User_rabipoint_model::RP_HIGH_SCORE_BATTLE, $rabipoint['cases'])) {
                        $this->timeline_model->create_timeline('higher_score', 'timeline', $user_playing);
                    }
                }
            }

            // Create trophy if any
            if ($params['type'] == 'battle') {
                $play_match = $this->timeline_model->create_timeline('play_match', 'trophy');

                if (is_array($play_match)) {
                    $trophy[] = $play_match;
                }
            }

            $play_day = $this->timeline_model->create_timeline('play_day', 'trophy');
            if (is_array($play_day)) {
                $trophy[] = $play_day;
            }

            $play_minute = $this->timeline_model->create_timeline('play_minute', 'trophy');
            if (is_array($play_minute)) {
                $trophy[] = $play_minute;
            }
        }

        // trophy
        $result['trophy'] = !empty($trophy) ? $trophy : [];

        // Return
        return $this->true_json($result);
    }

    /**
     * Get list of user playing API - Spec UPS-013
     *
     * @param array $params
     * @internal param int $id Play ID
     * @internal param int $user_id User ID
     * @internal param int $stage_id
     * @internal param int $type
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('stage_id', 'ターゲットID', 'required|integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_playing_stage_model');
        $this->load->model('user_group_playing_model');

        // Set default offset, limit
        $this->_set_default($params);

        $user_id = $params['user_id'];
        $stage_id = $params['stage_id'];
        $limit = $params['limit'];
        $offset = $params['offset'];

        $sql = "SELECT SQL_CALC_FOUND_ROWS
            id, second, question_answer_data, score, updated_at as created_at
        FROM
            schooltv_main.user_group_playing
        WHERE
            user_group_playing.user_id = $user_id
            AND user_group_playing.target_id = $stage_id
        UNION SELECT
            id, second, question_answer_data, score, created_at
        FROM
            schooltv_main.user_playing_stage
        WHERE
            user_playing_stage.user_id = $user_id
            AND user_playing_stage.stage_id = $stage_id
            AND user_playing_stage.type != 'memorize'
        ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

        $play_history = $this->db->query($sql)->result_array();

        $total = $this->db->query('SELECT FOUND_ROWS() count;')->row()->count;

        // Return
        return $this->true_json([
            'items' => $play_history,
            'total' => $total,
        ]);
    }

    /**
     * Get monthly report API
     *
     * @param array $params
     * @internal param string $user_id
     *
     * @return array
     */
    public function get_monthly_report($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');

        // Get user info
        $user = $this->user_model->available(TRUE)
            ->find($params['user_id']);

        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $user_id = $params['user_id'];

        $sql = "SELECT SQL_CALC_FOUND_ROWS
            second, updated_at as created_at
        FROM
            schooltv_main.user_group_playing
        WHERE
            user_group_playing.user_id = $user_id
        UNION SELECT
            second, created_at
        FROM
            schooltv_main.user_playing_stage
        WHERE
            user_playing_stage.user_id = $user_id
            AND user_playing_stage.type != 'memorize'
        ORDER BY created_at DESC";

        $res = $this->db->query($sql)->result_array();

        $return = [];

        $register_month = strtotime(business_date('Y-m', strtotime($user->created_at)));

        for ($i=0; ; $i+=30) {

            $count_month = business_date('Y-m', strtotime('-' . $i . 'days'));

            if (strtotime($count_month) >= $register_month) {
                $return[$count_month] = 0;
            } else {
                break;
            }
        }

        foreach($res as $item) {
            $month = business_date('Y-m', strtotime($item['created_at']));

            $return[$month] += $item['second'];
        }

        // Return
        return $this->true_json($return);
    }

    /**
     * Create memorization playing with highest score API - Spec UPS-011
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param int $stage_id
     * @internal param int $type
     *
     * @return array
     */
    public function create_memorization_playing($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'integer|required');
        $v->set_rules('stage_id', 'ターゲットID', 'integer|required');
        $v->set_rules('second', 'Second', 'integer|required');
        $v->set_rules('type', 'タイプ', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_playing_stage_model');

        // Set query
        $play_id = $this->user_playing_stage_model
            ->create($params);

        // Return
        return $this->true_json($play_id);
    }
}