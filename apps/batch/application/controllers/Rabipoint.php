<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(SHAREDPATH . 'core/APP_Batch_controller.php');

/**
 * Class Rabipoint batch
 *
 * @property User_rabipoint_model user_rabipoint_model
 * @property User_playing_stage_model user_playing_stage_model
 * @property User_promotion_code_model user_promotion_code_model
 * @property Notification_model notification_model
 * @property User_buying_model user_buying_model
 * @property User_friend_model user_friend_model
 * @property User_textbook_inuse_model user_textbook_inuse_model
 * @property User_trophy_model user_trophy_model
 * @property Timeline_good_model timeline_good_model
 * @property User_group_model user_group_model
 * @property Timeline_model timeline_model
 * @property Point_model point_model
 * @property Purchase_model purchase_model
 * @property User_model user_model
 * @property User_profile_model user_profile_model
 * @property Point_exchange_model point_exchange_model
 *
 * @package Controller
 *
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Rabipoint extends APP_Batch_controller
{
    /**
     * Rabipoint constructor
     */
    public function __construct()
    {
        parent::__construct();

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        // Load Model
        $this->load->model('user_rabipoint_model');
        $this->load->model('user_playing_stage_model');
        $this->load->model('user_promotion_code_model');
        $this->load->model('notification_model');
        $this->load->model('user_buying_model');
        $this->load->model('user_friend_model');
        $this->load->model('user_model');
        $this->load->model('user_profile_model');
        $this->load->model('user_textbook_inuse_model');
        $this->load->model('user_trophy_model');
        $this->load->model('timeline_good_model');
        $this->load->model('user_group_model');
        $this->load->model('point_model');
        $this->load->model('purchase_model');
        $this->load->model('point_exchange_model');
        $this->load->model('timeline_model');
    }

    /**
     * Execute batch for version 3.0.0
     */
    public function execute_batches_update_rabipoint()
    {

        // Change type to point master id
        $this->add_point_master();

        // Add point for 3.0.0
        $this->update_forceclub(); // #2
        $this->update_ask_coin(); // #16
        $this->update_deck_downloads(); // #49
        $this->update_become_friend(); // #10
        $this->update_edit_profile(); // #18
        $this->update_dashboard(); // #19
        $this->update_registration(); // #1
        $this->update_more_friends(); // #11
        $this->update_join_family(); // #7
        $this->update_invite_user(); // #9
        $this->update_watch_video(); // #23 #24
        $this->update_monthly_contract(); // #5 #6
        $this->update_send_message(); // #21
        $this->update_send_good(); // #22

        $this->update_trial_play();// #15

        // No need to create this point
        //$this->update_case_first_time();// #36

        $this->update_case_win_battle();// #42

        // Remove all rabipoint for dummy user
        $this->remove_all_dummy_user_rabipoint();

        // Update point remain
        $this->update_point_remain();

        // Update modal shown
        $this->update_modal_shown();
    }

    /**
     * Update first time for playing each stage - new case
     */
    private function update_case_first_time()
    {
        log_message('info', '[Reimport Rabipoint] User play first time on a stage');

        // Set condition
        $cond = [
            'select' => [
                'id, user_id, stage_id, score'
            ],
            'where' => [
                'type' => User_playing_stage_model::MOD_PLAY_BATTLE
            ],
            'group_by' => [
                'user_id, stage_id'
            ]
        ];

        // call back sql
        $this->call_callback_to($this->user_playing_stage_model, 'all', $cond, function ($res) {
            // Check duplicate
            $data = [
                'user_id' => $res->user_id,
                'point.case' => User_rabipoint_model::RP_PLAY_FIRST_TIME,
                'target_id' => $res->stage_id
            ];

            $duplicate = $this->user_rabipoint_model
                ->select('user_rabipoint.id')
                ->join('point', 'point.id = user_rabipoint.point_id')
                ->where($data)
                ->first();

            if (!empty($duplicate)) {
                log_message('Info', sprintf('Ignore create data for %s', $res->user_id));
            } else {
                log_message('Info', sprintf('Create data for %s', $res->user_id));

                // do insert
                $this->user_rabipoint_model->create_rabipoint([
                    'case' => User_rabipoint_model::RP_PLAY_FIRST_TIME,
                    'play_id' => $res->id,
                    'score' => $res->score,
                    'stage_id' => $res->stage_id,
                    'user_id' => $res->user_id
                ]);
            }
        });
    }

    /**
     * Update point bonus of win a battle
     */
    public function update_case_win_battle()
    {
        log_message('info', '[Reimport Rabipoint] User play win battle on a stage');

        // Set condition
        $cond = [
            'select' => [
                'id, user_id, rabipoint, type, target_id, extra_data'
            ],
            'where' => [
                'target_id IS NULL' => null,
                'type' => 'win_battle'
            ]
        ];

        // Call back sql
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {
            // echo json_encode($res);// die;
            // Extra_data
            $extra_data = json_decode($res->extra_data);

            // Get case point
            $point = $this->point_model
                ->select('id, case, base_point, campaign')
                ->where('case', User_rabipoint_model::RP_WIN_BATTLE)
                ->first();

            // New data
            $update_data = [
                'rabipoint' => $point->base_point,
                'point_remain' => $point->base_point,
                'point_id' => $point->id,
                'type' => User_rabipoint_model::RP_WIN_BATTLE,
                'target_id' => isset($extra_data->stage_id) ? $extra_data->stage_id : null,
                'extra_data' => json_encode([
                    'play_id' => isset($extra_data->play_id) ? $extra_data->play_id : null,
                    'score' => isset($extra_data->score) ? $extra_data->score: null
                ])
            ];

            $this->user_rabipoint_model->update($res->id, $update_data);
        });
    }

    /**
     * Update point force club user
     */
    public function update_forceclub()
    {
        log_message('info', '[update_forceclub] Starting recreate point');

        $users = $this->user_promotion_code_model
            ->select('user_promotion_code.user_id')
            ->join('user', 'user_promotion_code.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'new_registration_by_forceclub'
            ]);
        }
    }

    /**
     * Update point for ask coin
     */
    public function update_ask_coin()
    {
        log_message('info', '[update_ask_coin] Starting recreate point');

        $users = $this->notification_model
            ->select('DISTINCT(notification.user_id)')
            ->join('user', 'notification.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('notification.type', 'ask')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'ask_coin',
                'modal_shown' => 0
            ]);
        }
    }

    /**
     * Update point for deck downloads
     */
    public function update_deck_downloads()
    {
        log_message('info', '[update_deck_downloads] Starting recreate point');

        $users = $this->user_buying_model
            ->select('user_buying.user_id, count(user_buying.target_id) as total_decks')
            ->join('user', 'user_buying.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('user_buying.type', 'deck')
            ->group_by('user_buying.user_id')
            ->all();

        foreach ($users AS $user) {

            if ($user->total_decks > 9) {

                log_message('info', 'Create point for user_id: ' . $user->user_id . ' - Download 10 times');

                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'download_decks',
                    'condition' => 10,
                    'modal_shown' => 0
                ]);
            }

            if ($user->total_decks > 19) {

                log_message('info', 'Create point for user_id: ' . $user->user_id . ' - Download 20 times');

                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'download_decks',
                    'condition' => 20,
                    'modal_shown' => 0
                ]);
            }

            if ($user->total_decks > 29) {

                log_message('info', 'Create point for user_id: ' . $user->user_id . ' - Download 30 times');

                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'download_decks',
                    'condition' => 30,
                    'modal_shown' => 0
                ]);
            }
        }
    }

    /**
     * Update point for becoming friend
     */
    public function update_become_friend()
    {
        log_message('info', '[update_become_friend] Starting recreate point');

        $users = $this->user_friend_model
            ->select('DISTINCT(user_friend.user_id)')
            ->join('user', 'user_friend.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('user_friend.status', 'active')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'become_friend',
                'modal_shown' => 0
            ]);
        }
    }

    /**
     * Update point for updating profile
     */
    public function update_edit_profile()
    {
        log_message('info', '[update_edit_profile] Starting recreate point');

        $users = $this->user_model
            ->select('id AS user_id')
            ->where('status', 'active')
            ->where('primary_type', 'student')
            ->where('current_school is not null', null)
            ->all();

        foreach ($users AS $user) {
            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'register_profile',
                'modal_shown' => 0
            ]);
        }

        $users = $this->user_profile_model
            ->select('user_profile.user_id')
            ->join('user', 'user_profile.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('(user_profile.avatar_id > 0  OR user_profile.gender is not null)', null)
            ->all();

        foreach ($users AS $user) {
            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'register_profile',
                'modal_shown' => 0
            ]);
        }
    }

    /**
     * Update point for becoming friend
     */
    public function update_dashboard()
    {
        log_message('info', '[update_dashboard] Starting recreate point');

        $users = $this->user_trophy_model
            ->select('user_trophy.user_id')
            ->join('user', 'user_trophy.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('user_trophy.trophy_id', 2)
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'dashboard',
                'modal_shown' => 0
            ]);
        }
    }

    /**
     * Update point for new registration
     */
    public function update_registration()
    {
        log_message('info', '[update_registration] Starting recreate point');

        $users = $this->user_model
            ->select('id AS user_id')
            ->where('primary_type', 'student')
            ->where('status', 'active')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'new_registration'
            ]);
        }
    }

    /**
     * Update point for more friends
     */
    public function update_more_friends()
    {
        log_message('info', '[update_more_friends] Starting recreate point');

        $users = $this->user_friend_model
            ->select('user_friend.user_id, count(user_friend.target_id) as total_friends')
            ->join('user', 'user_friend.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('user_friend.status', 'active')
            ->group_by('user_friend.user_id')
            ->all();

        foreach ($users AS $user) {
            if ($user->total_friends > 9) {
                log_message('info', 'Create point for user_id: ' . $user->user_id . ' - 10 friends');
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'more_friends',
                    'condition' => 10,
                    'modal_shown' => 0
                ]);
            }

            if ($user->total_friends > 19) {
                log_message('info', 'Create point for user_id: ' . $user->user_id . ' - 20 friends');
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'more_friends',
                    'condition' => 20,
                    'modal_shown' => 0
                ]);
            }

            if ($user->total_friends > 29) {
                log_message('info', 'Create point for user_id: ' . $user->user_id . ' - 30 friends');
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'more_friends',
                    'condition' => 30,
                    'modal_shown' => 0
                ]);
            }

            if ($user->total_friends > 49) {
                log_message('info', 'Create point for user_id: ' . $user->user_id . ' - 50 friends');
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'more_friends',
                    'condition' => 50,
                    'modal_shown' => 0
                ]);
            }

            if ($user->total_friends > 99) {
                log_message('info', 'Create point for user_id: ' . $user->user_id . ' - 100 friends');
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'more_friends',
                    'condition' => 100,
                    'modal_shown' => 0
                ]);
            }
        }
    }

    /**
     * Update point for joining family group
     */
    public function update_join_family()
    {
        log_message('info', '[update_join_family] Starting recreate point');

        $users = $this->user_group_model
            ->select('DISTINCT(user_group.user_id)')
            ->join('user', 'user_group.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->join('group', 'user_group.group_id = group.id')
            ->where('group.primary_type', 'family')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'join_family'
            ]);
        }
    }

    /**
     * Update user current point remain
     */
    public function update_point_remain()
    {
        log_message('info', '[Import Rabipoint] User point remain');

        $query = "UPDATE {$this->user_rabipoint_model->database_name}.{$this->user_rabipoint_model->table_name} 
            SET point_remain = rabipoint 
            WHERE type != '". User_rabipoint_model::RP_EXPIRED_POINT. "'";

        $this->user_rabipoint_model->master->query($query);
    }

    /**
     * Update point for friend invitation
     */
    public function update_invite_user()
    {

        log_message('info', '[update_invite_user] Starting recreate point');

        $users = $this->user_model
            ->select('user_invite.id AS user_id')
            ->join('user as user_invite', 'user_invite.id = user.invited_from_id')
            ->where('user.primary_type', 'student')
            ->where('user_invite.primary_type', 'student')
            ->where('user.invited_from_id is not null', null)
            ->where('user.status !=', 'unauth')
            ->where('user.status IS NOT NULL')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'invite_friend'
            ]);
        }
    }

    /**
     * Update point for watching video
     */
    public function update_watch_video()
    {

        log_message('info', '[update_watch_video] Starting recreate point');

        $users = $this->timeline_model
            ->select('timeline.user_id, COUNT(timeline.id) AS total_view')
            ->join('user', 'timeline.user_id = user.id')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('timeline.type', 'timeline')
            ->where("extra_data LIKE '%video_timeline%'", null)
            ->group_by('user_id')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'watch_video',
                'modal_shown' => 0
            ]);

            for ($i = 0; $i < $user->total_view - 1; $i++) {
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'watch_video_every_time'
                ]);
            }
        }
    }

    /**
     * Update point for monthly payment contract
     */
    public function update_monthly_contract()
    {

        log_message('info', '[update_monthly_contract] Starting recreate point');

        $users = $this->purchase_model
            ->select('purchase.target_id as user_id, purchase.created_at as purchase_date, user_contract.created_at as trial_date')
            ->join('user', 'purchase.target_id = user.id')
            ->join("(SELECT MIN(created_at) as oldest FROM purchase WHERE type = 'contract' and status = 'success' group by target_id) AS oldest_date", 'purchase.created_at = oldest_date.oldest')
            ->join('user_contract', 'purchase.target_id = user_contract.user_id', 'left')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('purchase.status', 'success')
            ->where('purchase.type', 'contract')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'monthly_payment'
            ]);

            if (!empty($user->purchase_date) && !empty($user->trial_date) && strtotime($user->purchase_date) < strtotime($user->trial_date) + 7 * 86400) {
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'monthly_payment_1week'
                ]);
            }
        }
    }

    /**
     * Update point for send message
     */
    public function update_send_message()
    {

        log_message('info', '[update_send_message] Starting recreate point');

        $users = $this->user_trophy_model
            ->select('user_trophy.user_id')
            ->join('user', 'user_trophy.user_id = user.id')
            ->join('trophy', 'user_trophy.trophy_id = trophy.id')
            ->where('trophy.category', 'message')
            ->where('trophy.type', 'tutorial')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $user->user_id,
                'case' => 'send_message',
                'modal_shown' => 0
            ]);
        }
    }

    /**
     * Update point for sending good
     */
    public function update_send_good()
    {

        log_message('info', '[update_send_good] Starting recreate point');

        $users = $this->timeline_good_model
            ->select('timeline_good.user_id, timeline.user_id as target_id')
            ->join('user', 'timeline_good.user_id = user.id')
            ->join('timeline', 'timeline_good.timeline_id = timeline.id', 'left')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->where('timeline_good.user_id != timeline.user_id', null)
            ->all();

        foreach ($users AS $user) {

            log_message('info', 'Create point for user_id: ' . $user->user_id);

            $check_friend = $this->user_friend_model
                ->join('user', 'user.id = user_friend.target_id')
                ->where('user.status', 'active')
                ->where('user_friend.status', 'active')
                ->where('user_friend.user_id', $user->user_id)
                ->where('user_friend.target_id', $user->target_id)
                ->first();

            if (!empty($check_friend)) {
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $user->user_id,
                    'case' => 'send_good',
                    'modal_shown' => 0
                ]);
            }
        }
    }

    /**
     * Update point for trial play
     *
     * @author: nguyentc@nal.vn
     */
    public function update_trial_play()
    {
        log_message('info', '[Reimport Rabipoint] User trial play');

        // 16 is master point_id for case trial_play
        $trial_play_point_id = 16;

        $res = $this->user_playing_stage_model
            ->select('user_playing_stage.user_id, user_playing_stage.stage_id, stage.deck_id')
            ->join(DB_CONTENT. '.stage AS stage', 'stage.id = user_playing_stage.stage_id')
            ->join('user', 'user.id = user_playing_stage.user_id', 'left')
            ->where('type', User_playing_stage_model::MOD_PLAY_TRIAL)
            ->where('user.status', 'active')
            ->group_by('user_playing_stage.user_id, user_playing_stage.stage_id')
            ->all();

        foreach ($res AS $item) {
            log_message('Info', sprintf('Update data for %s', $item->user_id));

            // Check and ignore insert if this rabipoint is already inserted
            $user_rabipoint = $this->user_rabipoint_model
                ->select('id')
                ->where('user_id', $item->user_id)
                ->where('target_id', $item->deck_id)
                ->where('point_id', $trial_play_point_id)
                ->first();

            // Update user rabipoint trial play
            if (empty($user_rabipoint)) {
                $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $item->user_id,
                    'case' => User_rabipoint_model::RP_TRIAL_PLAY,
                    'stage_id' => $item->stage_id
                ]);
            }
        }
    }

    /**
     * Expired rabipoint after one year
     * Run in first day in month
     */
    public function expired_rabipoint()
    {
        log_message('info', '[Reimport Rabipoint] Expired rabipoint of user');

        $begin_date = business_date('Y-m-01 00:00:00', strtotime('-1 year'));

        // The last day of month
        $end_date = business_date('Y-m-t 23:59:59', strtotime('-1 year'));

        // Set condition
        $cond = [
            'select' => [
                'id, user_id, rabipoint, point_remain, point_id, type, created_at, extra_data'
            ],
            'where' => [
                'created_at <' => $begin_date,
                'point_remain >' => 0,
            ],
        ];
        // call back sql to expired point
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {

            log_message('Info', sprintf('Expired data for %s', $res->id));

            // expired point
            $data = [
                'case' => User_rabipoint_model::RP_EXPIRED_POINT,
                'point' => $res->point_remain,
                'expired_date' => business_date('Y-m-d H:i:s')
            ];

            $extra_data = (array)json_decode($res->extra_data, TRUE);
            $extra_data = array_merge($extra_data, $data);

            // Update user rabipoint
            $this->user_rabipoint_model->update($res->id, [
                'point_remain' => 0,
                'type' => User_rabipoint_model::RP_EXPIRED_POINT,
                'extra_data' => json_encode($extra_data)
            ]);

            // Create point exchange
            $this->point_exchange_model->create([
                'user_id' => null,
                'target_id' => $res->user_id,
                'ip_address' => $this->input->ip_address(),
                'point' => $res->point_remain,
                'mile' => 0,
                'publish_id' => null,
                'status' => Point_exchange_model::PX_EXPIRED_STATUS,
                'extra_data' => json_encode([
                    'id' => $res->id,
                    'rabipoint' => $res->rabipoint,
                    'point_remain' => $res->point_remain,
                    'expired_date' => business_date('Y-m-d H:i:s')
                ])
            ]);
        });

        log_message('info', '[Reimport Rabipoint] Send mail to notify expired rabipoint');
        $this->send_mail_expired_point($begin_date, $end_date);

    }

    /**
     * Send mail to notify expired point
     *
     * @param string $begin_date
     * @param string $end_date
     */
    public function send_mail_expired_point($begin_date = null, $end_date = null)
    {
        $this->load->library('schooltv_email');
        // Set condition
        $cond = [
            'select' => [
                'user_rabipoint.user_id, SUM(point_remain) AS current_rabipoint,
                DATE(user_rabipoint.created_at) AS created_at,
                user.login_id, user.nickname, user.email'
            ],

            'with' => [
                'user'
            ],

            'where' => [
                'user_rabipoint.created_at >=' => $begin_date,
                'user_rabipoint.created_at <=' => $end_date,
                'user_rabipoint.type !=' => User_rabipoint_model::RP_EXPIRED_POINT,
                'user.deleted_at IS null' => null,
                'user.status' => 'active'
            ],

            'group_by' => [
                'user_rabipoint.user_id'
            ]
        ];

        // call back sql to expired point
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {
            if ($res->current_rabipoint > 0) {
                log_message('Info', sprintf('Send mail for %s', $res->user_id));

                // Get all email of parent
                $parent_emails = $this->user_group_model->get_all_parent_emails($res->user_id);

                //List emails for sending
                $list_emails = [];

                // Add email of student
                $list_emails[] = $res->email;

                if (!empty($parent_emails)) {
                    foreach ($parent_emails AS $parent_email) {
                        if (!empty($parent_email->email)) {
                            $list_emails[] = $parent_email->email;
                        }
                    }
                }

                $list_emails = array_unique($list_emails);

                // Notice date that rapipoint will be expired
                $notice_date = strtotime(business_date('Y-m-t', strtotime('-1 year')));
                $notice_date = business_date('Y-m-d H:i:s', strtotime('+1 day', $notice_date));

                // Send email both parent and student - all parent in group
                foreach ($list_emails AS $email) {
                    // Also send email to student
                    $this->schooltv_email->send('expire_rabipoint', $email, [
                        'login_id' => isset($res->login_id) ? $res->login_id : '',
                        'nickname' => isset($res->nickname) ? $res->nickname : '',
                        'date_expired' => $notice_date,
                        'current_rabipoint' => isset($res->current_rabipoint) ? $res->current_rabipoint : 0
                    ], ['queuing' => TRUE]);
                }
            }
        });

        // send mail to notify expired rabipoint
        try {
            $this->schooltv_email->send_from_all_queue();
        } catch (Exception $e) {
            sleep(10);
            // Try to send email again
            $this->schooltv_email->send_from_all_queue();
        }

    }

    /**
     * Change history point
     *
     * ('playing', 'first_time', 'win_battle', 'everyday', 'high_score_battle', 'higher_ranking', 'highest_ranking')
     */
    public function add_point_master()
    {
        $this->change_case_first_time();
        $this->change_case_win_battle();
        $this->change_case_everyday();
        $this->change_case_high_score_battle();
        $this->change_case_higher_ranking();
        $this->change_case_highest_ranking();
    }

    /**
     * Change history case first time - first_time
     */
    public function change_case_first_time()
    {
        log_message('info', '[Reimport Rabipoint] Change user rabipoint with case first time play');
        // Set condition
        $cond = [
            'select' => [
                'id, type, created_at, extra_data'
            ],
            'where' => [
                'type' => 'first_time'
            ]
        ];
        // call back sql to expired point
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {
            log_message('Info', sprintf('Change data for %s', $res->id));

            $this->user_rabipoint_model->update_point_master([
                'case' => User_rabipoint_model::RP_PLAY_FIRST_TIME, // new case
                'old_type' => 'first_time',
                'id' => $res->id,
                'extra_data' => !empty($res->extra_data) ? $res->extra_data : []
            ]);
        });
    }

    /**
     * Change history case win in battle - win_battle
     */
    public function change_case_win_battle()
    {
        log_message('info', '[Reimport Rabipoint] Change user rabipoint with case win battle');
        // Set condition
        $cond = [
            'select' => [
                'id, type, created_at, extra_data'
            ],
            'where' => [
                'type' => 'win_battle'
            ]
        ];
        // call back sql to expired point
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {
            log_message('Info', sprintf('Change data for %s', $res->id));

            $this->user_rabipoint_model->update_point_master([
                'case' => User_rabipoint_model::RP_WIN_BATTLE, // new case
                'old_type' => 'win_battle',
                'id' => $res->id,
                'extra_data' => !empty($res->extra_data) ? $res->extra_data : []
            ]);
        });
    }

    /**
     * Change history case everyday - everyday
     */
    public function change_case_everyday()
    {
        log_message('info', '[Reimport Rabipoint] Change user rabipoint with case everyday');
        // Set condition
        $cond = [
            'select' => [
                'id, type, created_at, extra_data'
            ],
            'where' => [
                'type' => 'everyday'
            ]
        ];
        // call back sql to expired point
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {
            log_message('Info', sprintf('Change data for %s', $res->id));

            $this->user_rabipoint_model->update_point_master([
                'case' => User_rabipoint_model::RP_PLAY_EVERYDAY, // new case
                'old_type' => 'everyday',
                'id' => $res->id,
                'extra_data' => !empty($res->extra_data) ? $res->extra_data : []
            ]);
        });
    }

    /**
     * Change history case higher score - high_score_battle
     */
    public function change_case_high_score_battle()
    {
        log_message('info', '[Reimport Rabipoint] Change user rabipoint with case high_score_battle');
        // Set condition
        $cond = [
            'select' => [
                'id, type, created_at, extra_data'
            ],
            'where' => [
                'type' => 'high_score_battle'
            ]
        ];
        // call back sql to expired point
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {
            log_message('Info', sprintf('Change data for %s', $res->id));

            $this->user_rabipoint_model->update_point_master([
                'case' => User_rabipoint_model::RP_HIGH_SCORE_BATTLE, // new case
                'old_type' => 'high_score_battle',
                'id' => $res->id,
                'extra_data' => !empty($res->extra_data) ? $res->extra_data : []
            ]);
        });
    }

    /**
     * Change history case change position in ranking - higher_ranking
     */
    public function change_case_higher_ranking()
    {
        log_message('info', '[Reimport Rabipoint] Change user rabipoint with case higher_ranking');
        // Set condition
        $cond = [
            'select' => [
                'id, type, created_at, extra_data'
            ],
            'where' => [
                'type' => 'higher_ranking'
            ]
        ];
        // call back sql to expired point
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {
            log_message('Info', sprintf('Change data for %s', $res->id));

            $this->user_rabipoint_model->update_point_master([
                'case' => User_rabipoint_model::RP_HIGHER_RANKING, // new case
                'old_type' => 'higher_ranking',
                'id' => $res->id,
                'extra_data' => !empty($res->extra_data) ? $res->extra_data : []
            ]);
        });
    }

    /**
     * Change history case get 1st in ranking - highest_ranking
     */
    public function change_case_highest_ranking()
    {
        log_message('info', '[Reimport Rabipoint] Change user rabipoint with case highest_ranking');
        // Set condition
        $cond = [
            'select' => [
                'id, type, created_at, extra_data'
            ],
            'where' => [
                'type' => 'highest_ranking'
            ]
        ];
        // call back sql to expired point
        $this->call_callback_to($this->user_rabipoint_model, 'all', $cond, function ($res) {
            log_message('Info', sprintf('Change data for %s', $res->id));

            $this->user_rabipoint_model->update_point_master([
                'case' => User_rabipoint_model::RP_HIGHEST_RANKING, // new case
                'old_type' => 'highest_ranking',
                'id' => $res->id,
                'extra_data' => !empty($res->extra_data) ? $res->extra_data : []
            ]);
        });
    }

    /**
     * Update is_shown_modal to 1
     */
    public function update_modal_shown()
    {
        log_message('info', '[Update Rabipoint] Update is_shown_modal to 1');

        $this->user_rabipoint_model->update_all([
            'is_modal_shown' => 1
        ]);
    }

    /**
     * Remove all rapipoint of dummy user
     */
    public function remove_all_dummy_user_rabipoint()
    {
        $this->load->model('user_model');

        $dummy_users_res = $this->user_model
            ->select('id')
            ->not_like('email', '@')
            ->all();

        $dummy_ids = [];

        foreach ($dummy_users_res AS $user) {
            $dummy_ids[] = $user->id;
        }

        $this->user_rabipoint_model
            ->where_in('user_id', $dummy_ids)
            ->real_destroy_all();
    }

}
