<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_power_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_power_model extends APP_Paranoid_model
{
    /**
     * Define lift power
     */
    const LF_HIGH_SCORE_TRAINING = 'high_score_training';
    const LF_HIGH_SCORE_BATTLE = 'high_score_battle';
    const LF_HIGHEST_SCORE_RANKING = 'highest_score_ranking';
    const LF_WIN_4_TIMES_IN_RAW = 'win_4_times_raw';
    const LF_TRAINING = 'training';
    const LF_BATTLE = 'battle';

    /**
     * Define bonus lift power
     */
    const LF_BONUS_HIGH_SCORE_TRAINING = 2;
    const LF_BONUS_HIGH_SCORE_BATTLE = 3;
    const LF_BONUS_HIGHEST_SCORE_RANKING = 3;
    const LF_BONUS_WIN_4_TIMES_IN_RAW = 5;
    const LF_BONUS_TRAINING = 10;
    const LF_BONUS_BATTLE = -1;

    const MAX_POWER_LIMIT = 100;
    const MAX_POWER_DEFAULT = 40;

    public $database_name = DB_MAIN;
    public $table_name = 'user_power';
    public $primary_key = 'user_id';

    /**
     * Do update max power user - lift power for user
     */
    public function lift_power($attributes)
    {
        // Set query
        $user = $this->user_power_model
            ->select('max_power, current_power')
            ->where('user_id', $attributes['user_id'])
            ->first();

        $case = $attributes['case'];
        switch ($case) {
            case self::LF_TRAINING:
                $power = self::LF_BONUS_TRAINING;
                break;

            case self::LF_BATTLE:
                $power = self::LF_BONUS_BATTLE;
                break;

            case self::LF_HIGH_SCORE_TRAINING:
                $power = self::LF_BONUS_HIGH_SCORE_TRAINING;
                break;

            case self::LF_HIGH_SCORE_BATTLE:
                $power = self::LF_BONUS_HIGH_SCORE_BATTLE;
                break;

            case self::LF_HIGHEST_SCORE_RANKING:
                $power = self::LF_BONUS_HIGHEST_SCORE_RANKING;
                break;

            case self::LF_WIN_4_TIMES_IN_RAW:
                $power = self::LF_BONUS_WIN_4_TIMES_IN_RAW;
                break;
        }

        // Limit power to 100
        if (in_array($case, [self::LF_TRAINING, self::LF_BATTLE])) {
            $max_power = $user->max_power;

            // Revert current power with mode battle
            if ($case == self::LF_BATTLE) {
                $user->current_power += 1;
            }

        } else {
            $max_power = ($user->max_power + $power < self::MAX_POWER_LIMIT) ? ($user->max_power + $power) : self::MAX_POWER_LIMIT;
        }

        // Update current_power
        $current_power = ($user->current_power + $power < self::MAX_POWER_LIMIT) ? ($user->current_power + $power) : self::MAX_POWER_LIMIT;

        $this->user_power_model
            ->update($attributes['user_id'], [
                'max_power' => $max_power,
                'current_power' => $current_power > $max_power ? $max_power  : $current_power
        ]);

        return $power;
    }

    /**
     * Handle lift power user
     *
     * @param array $attributes
     * @param array $options
     *
     * @internal param int $user_id
     * @internal param int $score
     * @internal param int $play_id
     * @internal param int $stage_id
     * @internal param int $number_win
     * @internal param int $type
     *
     * @return  array
     */
    public function do_lift_power($attributes, $options = [])
    {
        $result = [];
        $cases = [];
        $number_win = 0;
        $power_bonus = 0;

        // Check type of play
        $cases[] = $this->check_type_play($attributes);

        // Check high score
        $cases[] = $this->check_high_score($attributes);

        // Check highest score in ranking
        $cases[] = $this->check_highest_score($attributes);

        // Check win in a raw
        $res = $this->check_win_in_raw($attributes);
        if (!empty($res) && !is_numeric($res)) {
            $cases[] = $res;
        }

        if (!empty($res) && is_numeric($res)) {
            $number_win += $res;
        }

        // Filter case
        $cases = array_values(array_filter($cases));

        foreach ($cases as $key => $case) {
            $attributes['case'] = $case;
            $power_bonus += $this->lift_power($attributes);
        }

        // Response
        $result = [
            'number_win' => $number_win,
            'power_bonus' => $power_bonus,
            'cases' => $cases
        ];

        return $result;
    }

    /**
     * Check type of play
     */
    public function check_type_play($attributes, $options = [])
    {
        if ($attributes['type'] == 'training') {
            return self::LF_TRAINING;
        }

        return self::LF_BATTLE;
    }

    /**
     * Check score user
     */
    public function check_high_score($attributes, $options = [])
    {
        // Load model
        $this->load->model('user_playing_stage_model');

        // Check high score
        $user_playing = $this->user_playing_stage_model
            ->select('MAX(score) as highest_score')
            ->where('user_id', $attributes['user_id'])
            ->where('stage_id', $attributes['stage_id'])
            ->where('type', $attributes['type'])
            ->first([
                'master' => TRUE
            ]);

        // Update lift power - case high score 
        // Bonus 2 power in mod training - 3 power in mod battle
        if (!empty($user_playing)) {
            if ($attributes['score'] > $user_playing->highest_score) {
                $case_lift_power = $attributes['type'] == 'training' ? self::LF_HIGH_SCORE_TRAINING : self::LF_HIGH_SCORE_BATTLE;
                return $case_lift_power;
            }
        }
        return;
    }

    /**
     * Check highest_score in ranking
     */
    public function check_highest_score($attributes, $options = [])
    {
        // Load model
        $this->load->model('user_model');

        // Check 1st ranking
        // Get highest score
        $user = $this->user_model
            ->select('MAX(highest_score) as highest_score')
            ->where('primary_type', 'student')
            ->first();

        // Update lift power - case highest score in ranking - bonus 3 power
        if ($attributes['type'] == 'battle' && $attributes['score'] > $user->highest_score) {
            return self::LF_HIGHEST_SCORE_RANKING;
        }
        return;
    }

    /**
     * Check win 4 times in a raw
     */
    public function check_win_in_raw($attributes, $options = [])
    {
        // Check win 4 times
        if (!empty($attributes['play_id']) && $attributes['type'] == "battle") {
            // Get information player
            $opponent = $this->user_playing_stage_model
                ->select('score')
                ->where('id', $attributes['play_id'])
                ->first([
                    'master' => TRUE
                ]);

            // Only increase number_win when player get victory in a raw
            $number_win = $attributes['score'] > $opponent->score ? $attributes['number_win'] + 1 : 0;

            // If number_win is 4 - Do lift power for user
            // Bonus 5 power
            if ($number_win >= 4) {
                return self::LF_WIN_4_TIMES_IN_RAW;
            } else {
                return $number_win;
            }
        }
        return;
    }
}
