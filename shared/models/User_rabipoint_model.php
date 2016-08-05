<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_rabipoint_model
 *
 * @property User_friend_model user_friend_model
 * @property Point_model point_model
 * @property Learning_history_model learning_history_model
 * @property APP_Loader load
 * @property User_playing_stage_model user_playing_stage_model
 * @property Stage_model stage_model
 * @property User_model user_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_rabipoint_model extends APP_Paranoid_model
{
    /**
     * Constants case create rabipoint
     */
    const RP_PLAYING                = 'playing';
    const RP_PLAY_EVERYDAY          = 'play_battle_continuously';
    const RP_PLAY_FIRST_TIME        = 'play_battle_1st';
    const RP_WIN_BATTLE             = 'play_battle_win';
    const RP_HIGH_SCORE_BATTLE      = 'play_battle_high_score';
    const RP_HIGH_SCORE_TRAINING    = 'high_score_training'; // remove logic in 2.2.0 stage
    const RP_HIGHER_RANKING         = 'play_battle_rank_up';
    const RP_HIGHEST_RANKING        = '1st_ranking';

    // Add new case in stage 3
    const RP_VIDEO_FIRST_SCORE          = 'video_score';
    const RP_NEW_REGISTRATION           = 'new_registration';
    const RP_CONTRACT_APPLICATION       = 'monthly_payment';
    const RP_JOIN_FAMILY_GROUP          = 'join_family';
    const RP_JOIN_TEAM                  = 'join_team';
    const RP_BOTH_REGISTER              = 'both_register';
    const RP_SEND_INVITATION            = 'invite_friend';
    const RP_EDIT_PROFILE               = 'register_profile';
    const RP_DASHBOARD                  = 'dashboard';
    const RP_ASK_COIN                   = 'ask_coin';
    const RP_GOOD                       = 'send_good';
    const RP_MESSAGE                    = 'send_message';
    const RP_2ND_10TH_RANKING           = '2nd_10th_ranking';
    const RP_11TH_50TH_RANKING          = '11th_50th_ranking';
    const RP_51TH_100TH_RANKING         = '51th_100th_ranking';
    const RP_TRIAL_PLAY                 = 'trial_play';
    const RP_All_CORRECT                = 'play_all_answers_correct';
    const RP_EXPIRED_POINT              = 'expired_point'; // Expired point - to run batch
    // refactor
    const RP_WATCH_TUTORIAL                 = 'watch_tutorial';
    const RP_WATCH_2ND_VIDEO                = 'watch_2nd_video';
    const RP_SCORE_VIDEO_2ND                = 'score_video_2nd';
    const RP_FIRST_LOGIN                    = 'first_login';
    const RP_VIDEO_CORRECT_ANSWER           = 'video_correct_answer';
    const RP_VIDEO_SCORE_EVERY_TIME         = 'video_score_every_time';
    const RP_MONTHLY_PAYMENT_ONE_WEEK       = 'monthly_payment_1week';
    const RP_NEW_REGISTRATION_BY_FORCECLUB  = 'new_registration_by_forceclub';
    const RP_TEAM_CREATE                    = 'create_team';
    const RP_TEAM_INVITE                    = 'invite_team';
    const RP_DOWNLOAD_DECKS                 = 'download_decks';
    const RP_BECOME_FRIEND                  = 'become_friend';
    const RP_WATCH_VIDEO                    = 'watch_video';
    const RP_WATCH_VIDEO_CONTINUOUSLY       = 'watch_video_continuously';
    const RP_WATCH_VIDEO_EVERY_TIME         = 'watch_video_every_time';
    const RP_MORE_FRIENDS                   = 'more_friends';

    public $database_name = DB_MAIN;
    public $table_name = 'user_rabipoint';
    public $primary_key = 'id';

    /**
     * Join with user table
     */
    public function with_user()
    {
        return $this->join('user', 'user.id = user_rabipoint.user_id');
    }

    /**
     * Join with point master table
     */
    public function with_point()
    {
        return $this->join('point', 'point.id = user_rabipoint.point_id');
    }

    /**
     * Add point master to old database
     *
     * @param array $attributes
     *
     * @return null
     */
    public function update_point_master($attributes = [])
    {
        // Get rabipoint data of point master
        $rabipoint_data = $this->get_rabipoint($attributes['case']);

        if (empty($rabipoint_data)) {
            return null;
        }

        // expired point
        $data = [
            'changed' => TRUE,
            'type' => $attributes['old_type'], // old type
            'change_date' => business_date('Y-m-d H:i:s')
        ];

        $extra_data = json_decode($attributes['extra_data'], TRUE);
        $extra_data = array_merge($extra_data, $data);

        $this->update($attributes['id'], [
            'point_id' => $rabipoint_data->id,
            'rabipoint' => $rabipoint_data->base_point * $rabipoint_data->campaign,
            'point_remain' => $rabipoint_data->base_point * $rabipoint_data->campaign,
            'type' => $attributes['case'],
            'extra_data' => json_encode($extra_data)
        ]);
    }

    /**
     * Create user rabipoint
     *
     * @param array $attributes
     * @param array $options
     *
     * @return bool|array
     */
    public function create_rabipoint($attributes, $options = [])
    {
        $rabipoint_data = $this->get_rabipoint($attributes['case'], isset($attributes['condition']) ? $attributes['condition'] : null);

        if (empty($rabipoint_data)) {
            return FALSE;
        }

        // Switch case
        switch ($attributes['case']) {

            case self::RP_WATCH_2ND_VIDEO: // Watch video every time
            case self::RP_WATCH_VIDEO_CONTINUOUSLY: // Watch video continuously
            case self::RP_SCORE_VIDEO_2ND: // Score 2nd time
                $res = $this->check_daily($attributes['user_id'], $rabipoint_data->id);

                if (!empty($res)) {
                    return FALSE;
                }
                break;

            case self::RP_FIRST_LOGIN: // Give point for the first login in a day
                // Check limit one time
                $res = $this->check_daily($attributes['user_id'], $rabipoint_data->id);

                if (!empty($res)) {
                    return FALSE;
                }
                $modal_shown = 0;
                break;

            case self::RP_VIDEO_CORRECT_ANSWER: // Watch video continuously
            case self::RP_WATCH_VIDEO_EVERY_TIME: // Watch video every time
            case self::RP_VIDEO_SCORE_EVERY_TIME: // Watch video continuously

                break;

            case self::RP_NEW_REGISTRATION: // Get point for new registration
            case self::RP_CONTRACT_APPLICATION: // Get point for user due to new contract
            case self::RP_BOTH_REGISTER: // Get point for parent and student register in the same time
            case self::RP_MONTHLY_PAYMENT_ONE_WEEK: // Get point for user due to new contract in 1 week after expire free time
            case self::RP_JOIN_FAMILY_GROUP: // Get point for user join family group in the first time
            case self::RP_SEND_INVITATION: // Get point when user send invitation to friends
            case self::RP_NEW_REGISTRATION_BY_FORCECLUB: // Get point when user register by forceclub
            case self::RP_WATCH_TUTORIAL: // Get point when user watch introduction video
            case self::RP_TEAM_CREATE: // Give point when user create team
            case self::RP_TEAM_INVITE: // Give point when user invite team
                // Check limit one time
                $res = $this->check_single($attributes['user_id'], $rabipoint_data->id);

                if (!empty($res)) {
                    return FALSE;
                }
                $modal_shown = 0;
                break;

            case self::RP_EDIT_PROFILE: // Get point when user edit profile
            case self::RP_DASHBOARD: // Get point when user edit profile
            case self::RP_DOWNLOAD_DECKS: // Get point when user buy deck
            case self::RP_BECOME_FRIEND: // Get point when user become friend in the first time
            case self::RP_WATCH_VIDEO: // Watch video in the first time
            case self::RP_MESSAGE: // Get point when user add good
            case self::RP_GOOD: // Get point when user add good
            case self::RP_MORE_FRIENDS: // Give point when user have many friends
                // Check limit one time
                $res = $this->check_single($attributes['user_id'], $rabipoint_data->id);

                if (!empty($res)) {
                    return FALSE;
                }
                $modal_shown = isset($attributes['modal_shown']) ? $attributes['modal_shown'] : 1;
                break;

            // Check single case
            case self::RP_VIDEO_FIRST_SCORE: // Get point by video in the first time
            case self::RP_JOIN_TEAM:  // Get point for user join team in the first time
            case self::RP_2ND_10TH_RANKING: // Check rank 2-10
            case self::RP_11TH_50TH_RANKING: // Check rank 11-50
            case self::RP_51TH_100TH_RANKING: // Check rank 51-100
                // Check limit one time
                $res = $this->check_single($attributes['user_id'], $rabipoint_data->id);

                if (!empty($res)) {
                    return FALSE;
                }
                break;

            // Get point when user ask coin
            case self::RP_ASK_COIN:
                // Check limit one time
                $res = $this->check_monthly($attributes['user_id'], $rabipoint_data->id);

                if (!empty($res)) {
                    return FALSE;
                }
                $modal_shown = isset($attributes['modal_shown']) ? $attributes['modal_shown'] : 1;
                break;

            // Give point when play trial
            case self::RP_TRIAL_PLAY:
                if (!isset($attributes['stage_id'])) {
                    return null;
                }
                $this->load->model('stage_model');
                // Get deck_id
                $deck = $this->stage_model
                    ->select('deck_id')
                    ->where('stage.id', $attributes['stage_id'])
                    ->first();

                if (!empty($deck)) {
                    $attributes['target_id'] = isset($deck->deck_id) ? $deck->deck_id : null;
                }

                break;

            // default;
            default:
                // 
                break;
        }

        $extra_data = null;
        // create extra_data
        if (isset($attributes['play_id']) && isset($attributes['score']) && isset($attributes['stage_id'])) {
            $extra_data = [
                'play_id' => isset($attributes['play_id']) ? $attributes['play_id'] : null,
                'score' => isset($attributes['score']) ? $attributes['score'] : null
            ];
            $attributes['target_id'] = isset($attributes['stage_id']) ? $attributes['stage_id'] : null;
        }

        if (isset($attributes['extra_data'])) {
            $extra_data = array_merge($extra_data, $attributes['extra_data']);
        }

        // Record
        $data = [
            'user_id' => $attributes['user_id'],
            'point_id' => $rabipoint_data->id,
            'rabipoint' => $rabipoint_data->base_point * $rabipoint_data->campaign,
            'point_remain' => $rabipoint_data->base_point * $rabipoint_data->campaign,
            'type' => $attributes['case'],
            'target_id' => isset($attributes['target_id']) ? $attributes['target_id'] : null,
            'extra_data' => isset($extra_data) ? json_encode($extra_data) : null,
            'is_modal_shown' => isset($modal_shown) ? $modal_shown : 1
        ];

        // Set query create
        $res = $this->create($data, [
            'return' => TRUE
        ]);

        $result = get_object_vars($rabipoint_data);

        if (in_array(self::RP_TRIAL_PLAY, $options)) {
            unset($result['id']); // remove duplicate id
            $result = array_merge($result, get_object_vars($res));
        }

        // Return
        return $result;
    }

    /**
     * Creating user rabipoint
     * @param array $attributes
     * @return array
     */
    public function creating($attributes)
    {
        $this->load->model('user_playing_stage_model');
        $cases = [];
        $rabipoints = 0;
        $attributes['_only_show_case'] = FALSE;

        if ($attributes['type'] == User_playing_stage_model::MOD_PLAY_TRIAL) {
            // Check trial play
            $cases[] = $this->check_trial_play($attributes);

        } else {
            // Check first time
            $cases[] = $this->check_first_time($attributes);

            // Check high score
            $cases[] = $this->check_high_score($attributes);

            // Check win in battle
            $cases[] = $this->check_win_in_battle($attributes);

            // Check higher in ranking
            $list_case = $this->check_higher_in_ranking($attributes);
            if (!empty($list_case)) {
                foreach ($list_case as $key => $value) {
                    $cases[] = $value;
                }
            }

            // Check highest in ranking
            $cases[] = $this->check_highest_score_in_ranking($attributes);

            // Check play_everyday
            $cases[] = $this->check_play_everyday($attributes);

            // Check all correct answer
            $cases[] = $this->check_all_answer_correct($attributes);
        }

        // Filter case
        $cases = array_values(array_filter($cases));

        // Add explain to result
        $responses = [];

        // List result
        $result_trial_play = [];

        // Switch cases
        foreach ($cases AS $key => $case) {
            $attributes['case'] = $case;
            $rabipoint_data = $this->create_rabipoint($attributes, [$case]);

            // only show case 1st at result
            if (!in_array($case, [self::RP_2ND_10TH_RANKING, self::RP_11TH_50TH_RANKING, self::RP_51TH_100TH_RANKING])) {
                $bonus = $rabipoint_data['base_point'] * $rabipoint_data['campaign'];
                $rabipoints += $bonus;

                // To explain result
                $responses[] = [
                    'label' => $rabipoint_data['title_modal'],
                    'bonus' => $bonus
                ];
            }

            // Check trial_play
            if ($case == self::RP_TRIAL_PLAY) {
                $result_trial_play = $rabipoint_data;
            }
        }

        // Response
        $result = [
            'rabipoint_bonus' => $rabipoints,
            'result_trial_play' => $result_trial_play,
            'cases' => $cases,
            'responses' => $responses
        ];

        return $result;
    }

    /**
     * Get all cases will update rabipoint
     *
     * @param array $attributes
     * @return array
     */
    public function get_cases_update($attributes)
    {
        $cases = [];
        $rabipoints = 0;
        $attributes['_only_show_case'] = TRUE;

        // Check first time
        $cases[] = $this->check_first_time($attributes);

        // Check win in battle
        $cases[] = $this->check_win_in_battle($attributes);

        // Check play_everyday
        $cases[] = $this->check_play_everyday($attributes);

        // Filter case
        $cases = array_values(array_filter($cases));

        // Switch cases
        foreach ($cases as $key => $case) {
            // Get point master of case
            $rabipoint_data = $this->get_rabipoint($case);
            $rabipoint_data = get_object_vars($rabipoint_data);

            $rabipoints += $rabipoint_data['base_point'] * $rabipoint_data['campaign'];
        }

        // Response
        $result = [
            'rabipoint_bonus' => $rabipoints,
            'cases' => $cases
        ];

        return $result;
    }

    /**
     * Check correct answer
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_all_answer_correct($attributes)
    {
        // Filter play type
        if ($attributes['type'] != 'battle') {
            return null;
        }

        // RP_All_CORRECT
        $question_total = count($attributes['scores']);
        $correct_number = count(array_filter($attributes['scores']));

        if ($question_total == $correct_number) {
            // Check only once for each stage
            $res = $this
                ->select('user_rabipoint.id')
                ->join('point', 'point.id = user_rabipoint.point_id')
                ->where('point.case', self::RP_All_CORRECT)
                ->where('user_rabipoint.target_id', $attributes['stage_id'])
                ->where('user_rabipoint.user_id', $attributes['user_id'])
                ->first();

            if (empty($res)) {
                return self::RP_All_CORRECT;
            }
        }

        return null;
    }

    /**
     * Check trial play bonus
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_trial_play($attributes)
    {
        $this->load->model('user_playing_stage_model');

        // Filter play type
        if ($attributes['type'] != User_playing_stage_model::MOD_PLAY_TRIAL) {
            return null;
        }

        // Load model
        $this->load->model('user_rabipoint_model');
        $this->load->model('stage_model');

        // Get deck_id
        $deck = $this->stage_model
            ->select('deck_id')
            ->where('stage.id', $attributes['stage_id'])
            ->first();

        if (empty($deck)) {
            return null;
        }

        // Limited once foreach one drill
        $user_rabipoint = $this
            ->select('user_rabipoint.id')
            ->join('point', 'point.id = user_rabipoint.point_id')
            ->where('user_rabipoint.target_id', $deck->deck_id)
            ->where('user_rabipoint.user_id', $attributes['user_id'])
            ->where('point.case', self::RP_TRIAL_PLAY)
            ->first();

        if (!empty($user_rabipoint)) {
            return null;
        }

        // Return
        return self::RP_TRIAL_PLAY;
    }

    /**
     * Check frist time play stage
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_first_time($attributes)
    {
        // Filter play type
        if ($attributes['type'] != 'battle') {
            return null;
        }

        // Load model
        $this->load->model('user_playing_stage_model');

        // Check first playing
        $play = $this->user_playing_stage_model
            ->select('id')
            ->where('user_id', $attributes['user_id'])
            ->where('stage_id', $attributes['stage_id'])
            ->where('type', $attributes['type'])
            ->first();

        // response
        if (empty($play)) {
            return self::RP_PLAY_FIRST_TIME;
        }

        return null;
    }

    /**
     * Check high score
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_high_score($attributes)
    {
        // Filter play type
        if ($attributes['type'] != 'battle') {
            return null;
        }

        // Load model
        $this->load->model('user_playing_stage_model');

        // Get score user
        $user_playing = $this->user_playing_stage_model
            ->select('MAX(score) AS score')
            ->where('user_id', $attributes['user_id'])
            ->where('stage_id', $attributes['stage_id'])
            ->where('type', $attributes['type'])
            ->first();

        // Get high score battle - bonus 20
        if (!empty($user_playing) && $attributes['score'] > $user_playing->score) {
            return self::RP_HIGH_SCORE_BATTLE;
        }

        return null;
    }

    /**
     * Check user got winner in battle
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_win_in_battle($attributes)
    {
        // Filter play type
        if ($attributes['type'] != 'battle') {
            return null;
        }

        if ($attributes['_only_show_case'] === TRUE) {
            // Get case win in battle
            return self::RP_WIN_BATTLE;
        }

        // Load model
        $this->load->model('user_playing_stage_model');

        if (!empty($attributes['play_id'])) {
            // Get score of opponent
            $battle = $this->user_playing_stage_model
                ->select('score')
                ->where('id', $attributes['play_id'])
                ->first();

            // Bonus 50
            if ($attributes['score'] > $battle->score) {
                return self::RP_WIN_BATTLE;
            }
        }

        return null;
    }

    /**
     * Check user got higher in ranking
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_higher_in_ranking($attributes)
    {
        // Filter play type
        if ($attributes['type'] != 'battle') {
            return null;
        }

        // Load user model
        $this->load->model('user_model');

        // Get position current user - before update highest score for user
        $ranking_past = $this->user_model->get_ranking_position('global', $attributes['user_id']);

        // Update highest score for user
        $this->user_model->update_highest_score($attributes);

        // Get position current user - before update highest score for user
        $ranking_current = $this->user_model->get_ranking_position('global', $attributes['user_id']);

        // Check empty ranking
        if (!empty($ranking_past) && empty($ranking_past->rank)) {
            $ranking_past->rank = 101; // Only check in 100 position
        }

        // Check ranking current
        if ($ranking_current->rank > 100) {
            return null;
        }

        $result = [];
        // Return list has change ranking
        if (($ranking_current->rank <= 10) && ($ranking_current->rank >= 1)) {
            $result[] = self::RP_2ND_10TH_RANKING;
            $result[] = self::RP_11TH_50TH_RANKING;
            $result[] = self::RP_51TH_100TH_RANKING;
        } else if (($ranking_current->rank <= 50) && ($ranking_current->rank >= 11)) {
            $result[] = self::RP_11TH_50TH_RANKING;
            $result[] = self::RP_51TH_100TH_RANKING;
        } else if (($ranking_current->rank <= 100) && ($ranking_current->rank >= 51)) {
            $result[] = self::RP_51TH_100TH_RANKING;
        }

        // Return response
        if (!empty($ranking_past) && !empty($ranking_current) && ($ranking_past->rank > $ranking_current->rank)) {
            $result[] = self::RP_HIGHER_RANKING;
            return $result;
        }

        return null;
    }

    /**
     * Check user got highest in ranking
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_highest_score_in_ranking($attributes)
    {
        // Filter play type
        if ($attributes['type'] != 'battle') {
            return null;
        }

        // Load model
        $this->load->model('user_model');

        // Check only once get bonus when at 1st Ranking
        $rank = $this
            ->select('user_rabipoint.id')
            ->join('point', 'point.id = user_rabipoint.point_id')
            ->where('user_rabipoint.user_id', $attributes['user_id'])
            ->where('point.case', self::RP_HIGHEST_RANKING)
            ->first();

        if (!empty($rank)) {
            return null;
        }

        // Get 1st in ranking - bonus 1500
        $user = $this->user_model
            ->select('MAX(highest_score) as highest_score')
            ->where('primary_type', 'student')
            ->first();

        // Bonus 1500
        if ($attributes['score'] >= $user->highest_score) {
            return self::RP_HIGHEST_RANKING;
        }

        return null;
    }

    /**
     * Check user play drill
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_playing($attributes)
    {
        // Filter play type
        if ($attributes['type'] != 'battle') {
            return null;
        }

        // Check first time in day
        return self::RP_PLAYING;
    }

    /**
     * Check user play drill everyday
     *
     * @param array $attributes
     * @return null|string
     */
    public function check_play_everyday($attributes)
    {
        // Filter type of play
        if ($attributes['type'] != 'battle') {
            return null;
        }

        // Load model
        $this->load->model('user_playing_stage_model');

        // Check once in day
        $current = $this
            ->select('user_rabipoint.id')
            ->join('point', 'point.id = user_rabipoint.point_id')
            ->where('user_rabipoint.user_id', $attributes['user_id'])
            ->where('point.case', self::RP_PLAY_EVERYDAY)
            ->where('DATE(user_rabipoint.created_at) = CURDATE()')
            ->first();

        // Filter one times in day
        if (!empty($current)) {
            return null;
        }

        // Check have play in yesterday
        $yesterday = $this->user_playing_stage_model
            ->select('id')
            ->where('user_id', $attributes['user_id'])
            ->where('DATE(created_at)', business_date('Y-m-d', strtotime(business_date('Y-m-d')) - 86400)) // Get yesterday
            ->where('type', $attributes['type'])
            ->first();

        // Check have play in current
        $today = $this->user_playing_stage_model
            ->select('id')
            ->where('user_id', $attributes['user_id'])
            ->where('DATE(created_at)', business_date('Y-m-d')) // Get today
            ->where('type', $attributes['type'])
            ->first();

        // Play in every - bonus 25
        // Bonus 25
        if (!empty($yesterday) && empty($today)) {
            return self::RP_PLAY_EVERYDAY;
        }

        return null;
    }

    /**
     * Check to give one time for single
     *
     * @param int $user_id
     * @param int $point_id
     *
     * @return object
     */
    public function check_single($user_id, $point_id)
    {
        // Check first time
        return $this
            ->where('user_id', $user_id)
            ->where('point_id', $point_id)
            ->first();
    }

    /**
     * Check to give one time for 1 month
     *
     * @param int $user_id
     * @param int $point_id
     *
     * @return object
     */
    public function check_monthly($user_id, $point_id)
    {
        // Check monthly
        return $this
            ->where('user_id', $user_id)
            ->where('point_id', $point_id)
            ->where('created_at > ', business_date('Y-m-d H:i:s', strtotime('-30 days')))
            ->first();
    }

    /**
     * Get rabipoint data
     *
     * @param string $case
     * @param string $condition
     *
     * @return object
     */
    public function get_rabipoint($case, $condition = null)
    {
        $this->load->model('point_model');

        return $this->point_model
            ->where('case', $case)
            ->where('condition', $condition)
            ->first();
    }

    /**
     * Check to give one time for daily
     *
     * @param int $user_id
     * @param int $point_id
     *
     * @return object
     */
    public function check_daily($user_id, $point_id)
    {
        // Check daily
        return $this
            ->where('user_id', $user_id)
            ->where('point_id', $point_id)
            ->where('created_at > ', business_date('Y-m-d H:i:s', strtotime('-1 day')))
            ->first();
    }

    /**
     * Check more friend
     *
     * @param int $user_id
     *
     * @return int
     */
    public function check_more_friends($user_id)
    {
        $this->load->model('user_friend_model');

        $total_res = $this->user_friend_model
            ->select('target_id')
            ->where('user_id', $user_id)
            ->where('status', 'active')
            ->all();

        $total = empty($total_res) ? 0 : (int) count($total_res);

        if ($total < 40) {
            $total = $total - ($total % 10);
        } else {
            $total = $total - ($total % 50);
        }

        return $total;
    }

    /**
     * Check watch 2 videos on 1 day
     *
     * @param int $user_id
     * @param string $date
     *
     * @return int
     */
    public function check_watch_2nd_in_aday($user_id, $date = null)
    {
        $this->load->model('learning_history_model');

        if (!$date) {
            $date = business_date('Y-m-d');
        }

        $views = $this->learning_history_model
            ->select('learning_history.id')
            ->join('schooltv_content.video', 'learning_history.video_id = schooltv_content.video.id')
            ->where('learning_history.user_id', $user_id)
            ->where('learning_history.status', 1)
            ->where('learning_history.created_at >= ', $date . ' 00:00:00')
            ->where('learning_history.created_at <= ', $date . ' 23:59:59')
            ->where('learning_history.second > (0.5 * (schooltv_content.video.duration / 1000))', null)
            ->all();

        return count($views);
    }

    /**
     * Check watch video continuously for date
     *
     * @param int $user_id
     * @param string $date
     *
     * @return bool
     */
    public function check_watch_video_continuously($user_id, $date = null)
    {
        $this->load->model('learning_history_model');

        $time = business_time();

        if ($date) {
            $time = strtotime($date);
        }

        $last_day_timestamp = strtotime('-1 day', $time);

        $last_watching = $this->learning_history_model
            ->select('learning_history.id')
            ->join('schooltv_content.video', 'learning_history.video_id = schooltv_content.video.id')
            ->where('learning_history.user_id', $user_id)
            ->where('learning_history.status', 1)
            ->where('learning_history.created_at >= ', business_date('Y-m-d 00:00:00', $last_day_timestamp))
            ->where('learning_history.created_at <= ', business_date('Y-m-d 23:59:59', $last_day_timestamp))
            ->where('learning_history.second > (0.5 * (schooltv_content.video.duration / 1000))', null)
            ->first();

        return !empty($last_watching) ? TRUE : FALSE;
    }

    /**
     * Check has 2nd score in a day
     *
     * @param int $user_id
     * @param string $date
     *
     * @return int
     */
    public function check_score_2nd_in_aday($user_id, $date = null)
    {
        $this->load->model('learning_history_model');

        if (!$date) {
            $date = business_date('Y-m-d');
        }

        $score_times = $this->learning_history_model
            ->select('id')
            ->where('user_id', $user_id)
            ->where("question_answer_data like '%point%'", null)
            ->where('learning_history.created_at >= ', $date . ' 00:00:00')
            ->where('learning_history.created_at <= ', $date . ' 23:59:59')
            ->all();

        return count($score_times);
    }
}
