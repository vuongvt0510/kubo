<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_playing_stage_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_playing_stage_model extends APP_model
{
    /**
     * Const
     */
    const SUM_SCORE = 100;
    const TOTAL_SCORE = 20;
    const RATE_COMBO_SCORE = 40;

    const RESULT_WIN = 'win';
    const RESULT_DRAW = 'draw';
    const RESULT_LOSE = 'lose';

    const MOD_PLAY_TRIAL = 'trial';
    const MOD_PLAY_TRAINING = 'training';
    const MOD_PLAY_BATTLE = 'battle';

    public $database_name = DB_MAIN;
    public $table_name = 'user_playing_stage';
    public $primary_key = 'id';

    /**
     * fetch with profile info
     *
     * @access public
     * @return User_model
     */
    public function with_profile()
    {
        return $this->join('user_profile', 'user_playing_stage.user_id = user_profile.user_id', 'left');
    }

    /**
     * fetch with user info
     *
     * @access public
     * @return User_model
     */
    public function with_user()
    {
        return $this->join('user', "user_playing_stage.user_id = user.id", 'left');
    }

    /**
     * Count total score
     * 
     * @param  array $scores list scores
     * @return int $score score from list scores
     */
    public function get_total_score($attributes)
    {
        // Check combo
        $list_score = $attributes['scores'];
        $is_added = FALSE;
        $combo = 0;
        $correct_number = count(array_filter($list_score)); 

        // Count combo
        foreach ($list_score as $key => $score) {
            // convert score to numeric
            if (!is_numeric($score)) {
                $score = 0;
            }

            // check combo
            if ($score) {
                if($is_added) {
                    $combo += 1;
                } else {
                    $is_added = TRUE;
                }
            } else {
                $is_added = FALSE;
            }
        }

        // Sum the total score
        $total = array_sum($list_score);

        // If all answer is correct add more bonus
        $total += (int) (($correct_number/ count($list_score)) * self::SUM_SCORE) * self::TOTAL_SCORE;

        // Combo
        $total += (int) (($combo/ count($list_score)) * self::SUM_SCORE * self::RATE_COMBO_SCORE);

        return round($total);
    }

    /**
     * Save playing stage of user
     *
     * @access public
     * @return User_playing_stage_model
     */
    public function create_playing($attributes)
    {
        // Load model
        $this->load->model('user_model');

        // Check primary type
        $user = $this->user_model
            ->select('primary_type')
            ->where('id', $attributes['user_id'])
            ->first();

        if (!in_array($attributes['type'], [self::MOD_PLAY_TRIAL, self::MOD_PLAY_TRAINING, self::MOD_PLAY_BATTLE]) || !$user->primary_type === 'student') {
            return [];
        }

        // Update score when type is trial-training-battle
        // Add result
        $result = null;
        if ($attributes['type'] == self::MOD_PLAY_BATTLE) {
            // Get score of play_id
            $play = $this
                ->select('score')
                ->where('id', $attributes['play_id'])
                ->first();

            // Set result
            $result = $attributes['score'] > $play->score ? self::RESULT_WIN : ($attributes['score'] == $play->score ? self::RESULT_DRAW : self::RESULT_LOSE);
        }

        $question_data = [];
        // Create list question answer
        if (isset($attributes['question_list_ids']) && isset($attributes['question_lists']) && isset($attributes['second_lists'])) {
            foreach ($attributes['question_list_ids'] as $key => $value) {
                $question_data[] = [
                    'id' => $value,
                    'question' => $attributes['question_lists'][$key],
                    'score' => (int) $attributes['scores'][$key],
                    'speed' => round(($attributes['second_lists'][$key] / 1000), 2)
                ];
            }
        }

        if ($attributes['type'] != self::MOD_PLAY_TRIAL) {
            // Generate question_answer_data
            $question_answer_data = json_encode([
                'speed' => round(($attributes['speed'] / 1000), 2),
                'milliSecond' => substr(($attributes['speed'] % 1000), 0 , 2),
                'questions' => $question_data, // list array score
                'play_id' => $attributes['play_id'],
                'result' => $result,
                'rabipoint' => $attributes['rabipoint'],
                'lift_power' => $attributes['lift_power']
            ]);
        } else {
            // Generate question_answer_data
            $question_answer_data = json_encode([
                'speed' => (int) round(($attributes['speed'] / 1000), 2),
                'milliSecond' => (int) substr(($attributes['speed'] % 1000), 0 , 2),
                'questions' => $question_data, // list array score
            ]);
        }

        if (isset($attributes['round_id'])) {
            $this->load->model('user_group_playing_model');

            $this->user_group_playing_model->succeed_quest(
                $attributes['round_id'],
                $attributes['user_id'],
                $attributes['score'],
                $attributes['speed'],
                $question_answer_data
            );
        }

        // Insert user_playing_stage
        $res = $this->user_playing_stage_model
            ->create([
                'user_id' => $attributes['user_id'],
                'stage_id' => $attributes['stage_id'],
                'second' => round(($attributes['speed'] / 1000), 2),
                'score' => $attributes['score'],
                'type' => $attributes['type'],
                'question_answer_data' => $question_answer_data
            ], ['return' => TRUE]);

        return $res;
    }

    /**
     * Join with schooltv_content Stage
     *
     * @access public
     * @return User_playing_stage_model
     */
    public function with_stage()
    {
        return $this->join(DB_CONTENT. '.stage', DB_CONTENT. '.stage.id = user_playing_stage.stage_id');
    }
}
