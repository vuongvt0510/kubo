<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_group_playing_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_group_playing_model extends APP_model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_group_playing';
    public $primary_key = 'id';

    /**
     * Process quest when add new member to group
     *
     * @access public
     * @return Group_playing_model
     */
    public function add_member($user_id, $group_id)
    {
        // Load model
        $this->load->model('group_playing_model');
        $rounds = $this->group_playing_model
            ->select('group_playing.id as round_id, group_playing.status')
            ->join ("(SELECT MAX(created_at) AS latest FROM group_playing WHERE group_id = ".$group_id." AND type = 'quest' GROUP BY (target_id)) AS latest_date", 'group_playing.created_at = latest_date.latest')
            ->all();

        foreach ($rounds as $round) {
            if ($round->status == 'progress') {
                $this->create([
                    'group_playing_id' => $round->round_id,
                    'user_id' => $user_id,
                    'status' => 'progress'
                ]);
            }
        }
        return TRUE;
    }

    /**
     * Save group playing of user
     *
     * @access public
     * @return User_group_playing_model
     */
    public function create_playing($attributes)
    {
        // Load model
        $this->load->model('user_model');
        $this->load->model('group_model');
        $this->load->model('group_playing_model');
        $this->load->model('battle_room_model');

        // Check primary type
        $user = $this->user_model
            ->select('primary_type')
            ->where('id', $attributes['user_id'])
            ->first();

        // Check play mode
        if (!in_array($attributes['type'], ['team_battle', 'quest', 'event']) || !$user->primary_type == 'student') {
            return FALSE;
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

        // Generate question_answer_data
        $question_answer_data = json_encode([
            'speed' => round(($attributes['speed'] / 1000), 2),
            'milliSecond' => substr(($attributes['speed'] % 1000), 0 , 2),
            'questions' => $question_data, // list question data
            'type' => $attributes['type']
        ]);

        // Get group playing id
        $group_playing = $this->group_playing_model
            ->select('id')
            ->where('group_id', $attributes['group_id'])
            ->where('target_id', $attributes['battle_room_id'])
            ->first();

        // Insert user_playing_stage
        $res = $this->user_group_playing_model
            ->create([
                'user_id' => $attributes['user_id'],
                'group_playing_id' => $group_playing->id,
                'target_id' => $attributes['stage_id'],
                'second' => round(($attributes['speed'] / 1000), 2),
                'score' => $attributes['score'],
                'question_answer_data' => $question_answer_data
            ], ['return' => TRUE]);

        // After insert - update highest score of group if exist
        $this->update_highest_score_group($attributes);

        // When the end team battle - create notification for group user
        if ($this->check_the_end_battle_room($attributes)) {
            // Send notification to member
            // Then change status of battle room
            $this->create_notification_result_team_battle($attributes);

            // Created extra_data to team battle
            // Change status of battle_room
            $this->create_result_data($attributes);
        }

        return $res;
    }

    /**
     * Update highest score group
     */
    protected function update_highest_score_group($attributes = [])
    {
        // Check current score of group
        $group = $this->group_model
            ->select('highest_score')
            ->where('id', $attributes['group_id'])
            ->first();

        // Get highest score of all recored of current group
        $score = $this->group_playing_model
            ->select("MAX(gp.group_score) as group_highest_score")
            ->join ("(
                    SELECT group_playing.id, SUM(user_group_playing.score) as group_score
                    FROM group_playing
                    JOIN user_group_playing ON user_group_playing.group_playing_id = group_playing.id
                    WHERE type = 'battle'
                        AND group_playing.group_id = ".$attributes['group_id'] ."
                    GROUP BY group_playing.target_id
                ) AS gp", "gp.id = group_playing.id")
            ->first();

        // Update highest score of group
        if ($score->group_highest_score > $group->highest_score) {
            $this->group_model->update($attributes['group_id'], [
                'highest_score' => $score->group_highest_score
            ]);
        }
        return;
    }

    /**
     * Create result data
     */
    public function create_result_data($attributes = [])
    {
        // Load model
        $this->load->model('user_group_playing_model');
        $this->load->model('group_playing_model');
        $this->load->model('battle_room_model');

        $extra_data = [];

        // Get data group
        $group = $this->group_playing_model
            ->select('group.name, SUM(user_group_playing.score) as score,
                battle_room.time_limit, battle_room.created_at')
            ->join('battle_room', 'battle_room.id = group_playing.target_id')
            ->join('group', 'group.id = group_playing.group_id')
            ->join('user_group_playing', 'user_group_playing.group_playing_id = group_playing.id')
            ->where('group_playing.target_id', $attributes['battle_room_id'])
            ->where('group_playing.group_id', $attributes['group_id'])
            ->group_by('user_group_playing.group_playing_id')
            ->first();

        // Get data target group
        $target_group = $this->group_playing_model
            ->select('group.name, SUM(user_group_playing.score) as score,
                battle_room.time_limit, battle_room.created_at')
            ->join('battle_room', 'battle_room.id = group_playing.target_id')
            ->join('group', 'group.id = group_playing.group_id')
            ->join('user_group_playing', 'user_group_playing.group_playing_id = group_playing.id')
            ->where('group_playing.target_id', $attributes['battle_room_id'])
            ->where('group_playing.group_id', $attributes['target_group_id'])
            ->group_by('user_group_playing.group_playing_id')
            ->first();

        $extra_data = json_encode([
            'group_score' =>  isset($group->score) ? $group->score : 0,
            'target_group_score' => isset($target_group->score) ? $target_group->score : 0,
            'end_time' => business_date('Y-m-d H:i:s')
        ]);

        // Change status battle room
        // Update extra_data
        $this->battle_room_model
            ->update($attributes['battle_room_id'], [
                'status' => Battle_room_model::BATTLE_ROOM_CLOSE_STATUS,
                'extra_data' => $extra_data
            ]);
        return;
    }

    /**
     * Create notification to all user in group when end team battle
     */
    public function create_notification_result_team_battle($attributes = [])
    {
        // Load model
        $this->load->model('battle_room_model');
        $this->load->model('user_group_model');
        $this->load->model('notification_model');

        // Check status of battle room
        $room = $this->battle_room_model
            ->select('status')
            ->where('id', $attributes['battle_room_id'])
            ->first();

        // Check status of room
        if (isset($room) && $room->status == Battle_room_model::BATTLE_ROOM_OPEN_STATUS) {
            // Get all members in group
            $members = $this->user_group_model
                ->select('user_id')
                ->where('user_group.group_id', $attributes['group_id'])
                ->all();

            // Create notification
            foreach ($members as $key => $user) {
                $params = $attributes;
                $params['target_id'] = $user->user_id;

                $this->notification_model->create_notification($params);
            }
        }
        return;
    }

    /**
     * Check the end battle team
     */
    protected function check_the_end_battle_room($attributes = [])
    {
        // condition the end
        if ($this->check_end_time_battle_room($attributes) || $this->check_all_user_played($attributes)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Check end time of team battle
     */
    protected function check_end_time_battle_room($attributes = [])
    {
        // Load model
        $this->load->model('battle_room_model');

        // Get detail of battle room
        $room = $this->battle_room_model
            ->select('id, time_limit, created_at')
            ->where('id', $attributes['battle_room_id'])
            ->first();

        // Check end time
        $room->end_time = date('Y-m-d H:i:s', strtotime($room->created_at) + $room->time_limit);

        // Change to unixtime
        $end_time_room = strtotime($room->created_at) + $room->time_limit;
        $current_time = strtotime(date('Y-m-d H:i:s'));

        if ($current_time > $end_time_room) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Check all user played
     */
    protected function check_all_user_played($attributes = [])
    {
        // Load model
        $this->load->model('user_group_model');
        $this->load->model('user_group_playing_model');

        // Get number member in group
        $users = $this->user_group_model
            ->select('COUNT(user_id) AS number')
            ->where('user_group.group_id', $attributes['group_id'])
            ->first();

        // Get member played
        $played = $this->user_group_playing_model
            ->select('COUNT(user_group_playing.id) AS number')
            ->join('group_playing', 'group_playing.id = user_group_playing.group_playing_id')
            ->where('user_group_playing.user_id', $attributes['user_id'])
            ->where('group_playing.group_id', $attributes['group_id'])
            ->where('group_playing.target_id', $attributes['battle_room_id'])
            ->first();

        // Return
        if ($played->number >= $users->number) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Return the status of quest
     *
     * @access public
     * @return Group_playing_model
     */
    public function succeed_quest($group_playing_id, $user_id, $scores, $speed, $question_answer_data)
    {
        // Load model
        $this->load->model('group_playing_model');
        $this->load->model('user_group_playing_model');

        $quest = $this->group_playing_model
            ->select('group_playing.group_id, schooltv_content.quest.id as quest_id, schooltv_content.quest.stage_id, schooltv_content.quest.order, schooltv_content.quest.type, schooltv_content.quest.drill_type, schooltv_content.quest.clear_condition_1, schooltv_content.quest.clear_condition_2')
            ->join('schooltv_content.quest', 'group_playing.target_id = schooltv_content.quest.id')
            ->where('group_playing.id', $group_playing_id)
            ->first();

        $users_playing = $this->user_group_playing_model
            ->select('id, user_id, second, score, question_answer_data, status')
            ->where('group_playing_id', $group_playing_id)
            ->all();

        $playing_result = json_decode($question_answer_data, true);

        $last_play = 0;
        $total_score = 0;
        $success_play = 0;
        foreach ($users_playing as $user) {
            if ($user->score == 0) {
                $last_play += 1;
            }
            $total_score += $user->score;

            if ($user->status == 'success') {
                $success_play += 1;
            }
        }
        if ($quest->drill_type == 'battle') {
            $correct_answers = 0;
            foreach ($playing_result['scores'] as $score) {
                if ($score > 0) {
                    $correct_answers += 1;
                }
            }
        }

        switch($quest->type) {
            case 'average':
                $this->user_group_playing_model
                    ->where('user_id', $user_id)
                    ->where('group_playing_id', $group_playing_id)
                    ->update_all([
                        'second' => round(($speed / 1000), 2),
                        'score' => $scores,
                        'question_answer_data' => $question_answer_data,
                        'status' => 'success'
                    ]);

                if ($last_play <= 1) {
                    $average_score = ($total_score + $scores)/(count($users_playing) + 1);
                    $group_status = $average_score > $quest->clear_condition_1 ? 'success' : 'failed';

                    $this->group_playing_model
                        ->update($group_playing_id,[
                            'status' => $group_status
                        ]);
                }
                break;

            case 'win':
                $user_status = $playing_result['result'] == 'win' ? 'success' : 'failed';
                $this->user_group_playing_model
                    ->where('user_id', $user_id)
                    ->where('group_playing_id', $group_playing_id)
                    ->update_all([
                        'second' => round(($speed / 1000), 2),
                        'score' => $scores,
                        'question_answer_data' => $question_answer_data,
                        'status' => $user_status
                    ]);

                if ($last_play <= 1 ) {

                    $group_status = $success_play + 1 == count($users_playing) ? $user_status : 'failed';
                    $this->group_playing_model
                        ->update($group_playing_id, [
                            'status' => $group_status
                        ]);
                }

                break;

            case 'accuracy':
                $percentage_correct = $correct_answers / count($playing_result['scores']) * 100;
                $user_status = $percentage_correct > $quest->clear_condition_1 ? 'success' : 'failed';
                $this->user_group_playing_model
                    ->where('user_id', $user_id)
                    ->where('group_playing_id', $group_playing_id)
                    ->update_all([
                        'second' => round(($speed / 1000), 2),
                        'score' => $scores,
                        'question_answer_data' => $question_answer_data,
                        'status' => $user_status
                    ]);

                if ($last_play <= 1 ) {

                    $group_status = $success_play + 1 == count($users_playing) ? $user_status : 'failed';
                    $this->group_playing_model
                        ->update($group_playing_id, [
                            'status' => $group_status
                        ]);
                }

                break;

            case 'combo':
                $user_status = $playing_result['result'] == 'win' && $correct_answers > $quest->clear_condition_1 ? 'success' : 'failed';

                $this->user_group_playing_model
                    ->where('user_id', $user_id)
                    ->where('group_playing_id', $group_playing_id)
                    ->update_all([
                        'second' => round(($speed / 1000), 2),
                        'score' => $scores,
                        'question_answer_data' => $question_answer_data,
                        'status' => $user_status
                    ]);

                if ($last_play <= 1 ) {

                    $group_status = $success_play + 1 == count($users_playing) ? $user_status : 'failed';
                    $this->group_playing_model
                        ->update($group_playing_id, [
                            'status' => $group_status
                        ]);
                }
                break;
        }

        if (isset($group_status) and $group_status == 'success') {

            $this->load->model('quest_model');
            $new_quest = $this->quest_model
                ->select('id')
                ->where('order', $quest->order + 1)
                ->where('stage_id', $quest->stage_id)
                ->first();

            $this->group_playing_model
                ->create([
                    'type' => 'quest',
                    'target_id' => $new_quest->id,
                    'group_id' => $quest->group_id,
                    'status' => 'open'
                ]);
        }


        return TRUE;
    }
}