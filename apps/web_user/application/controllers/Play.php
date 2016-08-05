<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Profile controller
 *
 * @author Duy Phan <yoshikawa@interest-marketing.net>
 */
class Play extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_is_student', [
            'only' => ['match', 'select_player', 'result_match', 'memorize_result']
        ]);

    }

    /**
     * Play index screen VS10
     * @param int $drill_id
     * @param string $type
     */
    public function index($drill_id = null, $type = null)
    {
        // Check session redirect to VS230 - team_battle
        if ($this->session->userdata('team_battle_playing') == TRUE) {
            return redirect('/play/team/battle/room');
        }

        $user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id;

        // Check already buy deck
        $already_buy = FALSE;
        $purchases = $this->_api('user_deck')->get_list(['user_id' => $user_id]);
        foreach($purchases['result']['items'] AS $purchase) {
            foreach($purchase['decks'] AS $deck) {
                if($deck['id'] == $drill_id) {
                    $already_buy = TRUE;
                }
            }
        }

        if($drill_id && $already_buy == FALSE && (empty($type) || $type != 'trial')) {
            return redirect('deck/'.$drill_id);
        }

        // route /play/{drill_id}/trial - redirect to VS25
        if($drill_id && $type == 'trial' && $already_buy == FALSE) {
            // If it dont buy - transit to trial play
            $this->session->set_userdata('type', $type);

            // Update session drill_id
            $this->session->set_userdata('drill_id', $drill_id);
            redirect('play/select_stage/');
        }

        if (!empty($drill_id)) {
            $this->session->set_userdata('drill_id', $drill_id);
            $redirect = '/play/select_stage/';
        } else {
            $redirect = '/play/select_drill';
        }

        // Check type of play
        if($this->input->get('type') ) {
            $this->session->set_userdata('type', $this->input->get('type'));

            return redirect($redirect);
        }

        $power = $this->_api('user_power')->get_detail([
            'user_id' => $user_id
        ]);

        $rabipoint = $this->_api('user_rabipoint')->get_detail([
            'user_id' => $user_id
        ]);
        
        $view_data= [
            'avatar' => $this->current_user->avatar_id,
            'nickname' => $this->current_user->nickname,
            'login_id' => $this->current_user->login_id,
            'is_student' => $this->current_user->primary_type == 'student' ? TRUE : FALSE,
            'power' => $power['result'],
            'rabi_point' => isset($rabipoint['result']['point']) ? $rabipoint['result']['point'] : 0,
            'deck_id' => $drill_id
        ];

        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $drill_id,
        ]);

        if (isset($deck['result']['items'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        return $this->_render($view_data);
    }

    /**
     * Play select Drill screen VS20
     * @param int $drill_id
     */
    public function select_drill($drill_id = null)
    {
        // Execute from link URL email
        if ($this->input->get('user_id')) {
            if (isset($this->students[$this->input->get('user_id')])) {
                $this->session->set_userdata('type', 'status');
                $this->session->set_userdata('switch_student_id', $this->input->get('user_id'));
            }
            redirect('play/select_drill');
            return;
        }

        if (!$this->session->has_userdata('type') && !$this->session->has_userdata('group_id')) {
            return redirect('/play');
        }

        if ($this->session->userdata('type') == 'quest' && !empty($drill_id)) {
            $members = $this->_api('user_deck')->check_members(['deck_id' => $drill_id, 'group_id' => $this->session->userdata('group_id')]);

            $team_buy = TRUE;
            foreach ($members['result']['items'] AS $member) {
                if (empty($member['id'])) {
                    $team_buy = FALSE;
                    break;
                }
            }

            $team_clear = $this->_api('stage_quest')->check_clear(['deck_id' => $drill_id, 'group_id' => $this->session->userdata('group_id')])['result'];

            $this->session->set_userdata('drill_id', $drill_id);
            return redirect(($team_buy || $team_clear) ? '/play/select_stage' : '/play/drill_owners');
        }

        // Check play team_battle
        if ($this->session->userdata('type') == 'team_battle') {

        }

        // Check drill bought
        if (!empty($drill_id) && $this->session->has_userdata('type')) {
            if($this->current_user->primary_type == 'student') {
                $already_buy = FALSE;
                $purchases = $this->_api('user_deck')->get_list(['user_id' => $this->current_user->id]);
                // Filter result
                foreach ($purchases['result']['items'] AS $purchase) {
                    foreach ($purchase['decks'] as $deck) {
                        if ($deck['id'] == $drill_id) {
                            $already_buy = TRUE;
                        }
                    }
                }
                if ($already_buy == FALSE) {
                    return redirect('deck/'.$drill_id);
                }
            }

            $this->session->set_userdata('drill_id', $drill_id);
            return redirect('/play/select_stage');
        }

        $view_data = [];

        // Get team info
        if (in_array($this->session->userdata('type'), ['quest', 'team_battle'])) {
            $drills = $this->_api('deck')->get_related_subject(['deck_id' => '']);
            $group_name = $this->_api('group')->get_detail([
                'group_id' => $this->session->userdata('group_id')
            ]);
            if (isset($group_name['result'])) {
                $view_data['group_name'] = $group_name['result']['group_name'];
            }
        }

        if ($this->session->userdata('type') == 'status') {
            $user = $this->_api('user')->get_detail([
                'id' => $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id
            ]);
            $view_data['user_nickname'] = $user['result']['nickname'];
            $view_data['user_id'] = $user['result']['id'];

            $this_month = business_date('Y-m');
            $last_month = business_date('Y-m', strtotime('-30 days'));

            $playing_monthly_report = $this->_api('user_playing')->get_monthly_report([
                'user_id' => $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id
            ]);

            $view_data['playing_monthly_report']['this_month'] = isset($playing_monthly_report['result'][$this_month]) ? $playing_monthly_report['result'][$this_month] : 0;
            $view_data['playing_monthly_report']['last_month'] = isset($playing_monthly_report['result'][$last_month]) ? $playing_monthly_report['result'][$last_month] : 0;
        }

        if ($this->session->has_userdata('type')) {
            $user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id;
            $drills = $this->_api('user_deck')->get_list([
                'user_id' => $user_id
            ]);
        }

        if (isset($drills['result'])) {
            $view_data['subjects'] = $drills['result']['items'];
        }

        $view_data['parent_check_status'] = $this->session->userdata('type') == 'status' ? TRUE : FALSE;
        return $this->_render($view_data);
    }

    /**
     * Play select Stage screen VS25
     * @param int $stage_id
     */
    public function select_stage($stage_id = null)
    {
        if (!$this->session->has_userdata('drill_id')) {
            return redirect('/play');
        }

        $user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id;

        // Check stage
        if(!empty($stage_id)) {
            $this->session->set_userdata('stage_id', $stage_id);
            switch($this->session->userdata('type')) {
                case 'quest': $redirect = '/play/select_quest';
                    break;

                case 'memorize': $redirect = '/play/memorize';
                    break;

                case 'status': $redirect = '/play/view_status';
                    break;

                case 'battle' : $redirect = '/play/select_player';
                    break;

                case 'team_battle': 
                    $params['group_id'] = $this->session->userdata('group_id');
                    $params['battle_room_id'] = $this->session->userdata('battle_room_id');
                    $params['stage_id'] = $stage_id;

                    $res = $this->_api('user_group_playing')->check_team_played_stage($params);
                    $redirect = '/play/team_battle_play';

                    if (isset($res['result'])) {
                        // If one member in group played stage - redirect to select_stage
                        if ($res['result']['is_played']) {
                            $redirect = '/play/select_stage';
                            // destroy session stage with team_battle mod
                            $this->session->unset_userdata('stage_id');
                        }
                    }

                    break;

                // limit stage for trial mode
                case 'trial':
                    // Get list stage in drill
                    $stage = $this->_api('deck_stage')->get_list([
                        'user_id' => $user_id,
                        'deck_id' => $this->session->userdata('drill_id'),
                        'limit' => 1
                    ]);

                    // Add session stage_id
                    if (isset($stage['result'])) {
                        $this->session->set_userdata('stage_id', $stage['result']['items'][0]['id']);
                    }
                    $redirect = '/play/training';
                    break;
                    
                default:
                    $redirect = '/play/training';
                    break;
            }

            return redirect($redirect);
        }

        $view_data = [];

        // Get deck infor
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        $type = $this->session->userdata('type');

        // Check mode play
        switch($type) {
            case 'quest':
                $view_data['group_name'] = $this->_api('group')->get_detail(['group_id' => $this->session->userdata('group_id')])['result']['group_name'];
                $stages = $this->_api('deck_stage')->get_list([
                    'deck_id' => $this->session->userdata('drill_id'),
                    'stage_type' => 'quest'
                ]);
                break;

            // List stage with mode team_battle
            case 'team_battle':
                $group_name = $this->_api('group')->get_detail([
                    'group_id' => $this->session->userdata('group_id')
                ]);
                if (isset($group_name['result'])) {
                    $view_data['group_name'] = $group_name['result']['group_name'];
                }

                // Get stage with mode team_battle
                $stages = $this->_api('deck_stage')->get_list([
                    'user_id' => $user_id,
                    'deck_id' => $this->session->userdata('drill_id'),
                    'group_id' => $this->session->userdata('group_id'),
                    'battle_room_id' => $this->session->userdata('battle_room_id'),
                    'stage_type' => $type
                ]);

                break;
            case 'trial':
                $view_data['deck_id'] = $this->session->userdata('drill_id');

                // Get list stage in drill
                $stages = $this->_api('deck_stage')->get_list([
                    'user_id' => $user_id,
                    'deck_id' => $this->session->userdata('drill_id'),
                    'limit' => 1
                ]);
                break;

            case 'memorize':
                $stages = $this->_api('deck_stage')->get_list([
                    'user_id' => $user_id,
                    'deck_id' => $this->session->userdata('drill_id'),
                    'stage_type' => 'memorize'
                ]);
                break;

            case 'status':
                $view_data['user_nickname'] = $this->_api('user')->get_detail([
                    'id' => $user_id
                ])['result']['nickname'];

                $stages = $this->_api('deck_stage')->get_list([
                    'user_id' => $user_id,
                    'deck_id' => $this->session->userdata('drill_id'),
                    'stage_type' => $this->session->userdata('type')
                ]);

                foreach($stages['result']['items'] as $key => $item) {
                    if (isset($item['highest_score_data'])) {
                        $data = $item['highest_score_data'];

                        $stages['result']['items'][$key]['total_questions'] = 0;
                        $stages['result']['items'][$key]['correct_answer'] = 0;
                        foreach ($data['questions'] as $item1) {
                            $stages['result']['items'][$key]['total_questions']++;

                            if ($item1['score'] > 0) {
                                $stages['result']['items'][$key]['correct_answer']++;
                            }
                        }
                    }
                }

                break;

            default:
                $stages = $this->_api('deck_stage')->get_list([
                    'user_id' => $user_id,
                    'deck_id' => $this->session->userdata('drill_id'),
                ]);
                break;
        }

        if (isset($stages['result'])) {
            $view_data['stages'] = $stages['result']['items'];
        }

        $view_data['is_trial'] = $this->session->userdata('type') == 'trial' ? TRUE : FALSE;
        $view_data['is_team_battle_mode'] = $this->session->userdata('type') == 'team_battle' ? TRUE : FALSE;
        $view_data['parent_check_status'] = $this->session->userdata('type') == 'status' ? TRUE : FALSE;
        return $this->_render($view_data);
    }

    /**
     * Play select person screen VS30
     * @param int $play_id
     */
    public function select_player($play_id = null)
    {
        if (!$this->session->has_userdata('stage_id') || !in_array($this->session->userdata('type'), ['battle', 'quest'])) {
            return redirect('/play');
        }

        if(!empty($play_id)) {
            $this->session->set_userdata('play_id', $play_id);
            return redirect('/play/match');
        }

        $view_data['from_quest'] = FALSE;
        if ($this->session->userdata('type') == 'quest') {
            $view_data['from_quest'] = TRUE;
        }

        // Get player who played stage
        $players = $this->_api('user')->get_players([
            'stage_id' => $this->session->userdata('stage_id'),
            'user_id' => $this->current_user->id,
        ]);
        $view_data['players'] = $players['result'];

        // Get stage detail
        $stage = $this->_api('stage')->get_detail([
            'user_id' => $this->current_user->id,
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        if (isset($stage['result'])) {
            $view_data['stage'][] = $stage['result'];
        }

        // Get detail of deck
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        if ($this->session->has_userdata('index_stage')) {
            $view_data['index_stage'] = $this->session->userdata('index_stage');
        }
        $view_data['is_trial'] = $this->session->userdata('type') == 'trial' ? TRUE : FALSE;
        return $this->_render($view_data);
    }

    /**
     * Play training screen VC10
     */
    public function training()
    {
        if (!$this->session->has_userdata('stage_id') || !in_array($this->session->userdata('type'), ['trial', 'training', 'quest'])) {
            return redirect('/play');
        }
        $view_data = [];

        if ($this->session->userdata('type') == 'quest') {
            $view_data['quest_desciption'] = $this->session->userdata('quest_description');
        }

        // Get question in stage
        $res = $this->_api('stage_question')->get_list([
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        $view_data['questions'] = $res['result']['items'];

        $view_data['operator_primary_type'] = $this->current_user->primary_type;

        // Get detail of deck
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        // Check type of play
        if ($this->session->userdata('type') == 'trial') {
            $view_data['deck_id'] = $this->session->userdata('drill_id');
        }
        
        // Get detail of stage
        $stage = $this->_api('stage')->get_detail([
            'stage_id' => $this->session->userdata('stage_id'),
            'user_id' => $this->current_user->id
        ]);

        if (isset($stage['result'])) {
            $view_data['stage'][] = $stage['result'];
        }

        $view_data['is_trial'] = $this->session->userdata('type') == 'trial' ? TRUE : FALSE;
        $view_data['from_quest'] = $this->session->userdata('type') == 'quest' ? TRUE : FALSE;
        $view_data['not_show_get_trophy_script'] = TRUE;

        return $this->_render($view_data);
    }

    /**
     * Check duplicated stage
     */
    public function check_duplicate_stage()
    {
        // Check ajax
        if (!$this->input->post() || !$this->input->is_ajax_request()) {
           // return $this->_render_404();
        }

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle' && $this->session->has_userdata('drill_id')) {
            return $this->_render_404();
        }

        // Check duplicate stage
        $res = $this->_api('group_playing')->check_duplicate_stage([
            'battle_room_id' => $this->session->userdata('battle_room_id'),
            'group_id' => $this->session->userdata('group_id'),
            'user_id' => $this->current_user->id,
            'stage_id' => $this->input->post('stage_id')
        ]);

        // create session stage_id
        $this->session->set_userdata('stage_id', $this->input->post('stage_id'));

        return $this->_true_json($res['result']);
    }

    /**
     * Team battle play
     */
    public function team_battle_play()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Check session
        if (!$this->session->has_userdata('stage_id') || !in_array($this->session->userdata('type'), ['team_battle'])) {
            return redirect('/play');
        }

        // Get question in stage
        $questions = $this->_api('stage_question')->get_list([
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        // Get detail of deck
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        // Get detail of stage
        $stage = $this->_api('stage')->get_detail([
            'stage_id' => $this->session->userdata('stage_id'),
            'battle_room_id' => $this->session->userdata('battle_room_id'),
            'user_id' => $this->current_user->id,
            'stage_type' => $this->session->userdata('type')
        ]);

        // Render value
        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        if (isset($stage['result'])) {
            $view_data['stage'][] = $stage['result'];
        }

        if (isset($questions['result'])) {
            $view_data['questions'] = $questions['result']['items'];
        }

        $view_data['operator_primary_type'] = $this->current_user->primary_type;
        $view_data['is_trial'] = $this->session->userdata('type') == 'trial' ? TRUE : FALSE;
        $view_data['not_show_get_trophy_script'] = TRUE;
        $view_data['type_play'] = $this->session->userdata('type');

        return $this->_render($view_data);
    }

    /**
     * Check power before run match
     */
    public function check_power()
    {
        if (!$this->input->is_post() || !$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        // Check user power before play battle
        $res = $this->_api('user_power')->update([
            'user_id' => $this->current_user->id,
            'type' => $this->session->userdata('type')
        ]);

        if (!$res['submit']) {
            $this->_flash_message('パワーが足りないためプレイできません!!');
        }
        return $this->_true_json($res);
    }

    /**
     * Play match screen VC20
     */
    public function match()
    {
        if (!$this->session->has_userdata('play_id') || !in_array($this->session->userdata('type'), ['battle', 'quest'])) {
            return redirect('/play');
        }

        $view_data = [];
        $view_data['from_quest'] = FALSE;
        if ($this->session->userdata('type') == 'quest') {
            $view_data['from_quest'] = TRUE;
            $view_data['quest_desciption'] = $this->session->userdata('quest_description');
        }

        // Get question data
        $res = $this->_api('stage_question')->get_list([
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        $view_data['questions'] = $res['result']['items'];

        // Get information player
        $res = $this->_api('user')->get_play([
            'play_id' => $this->session->userdata('play_id'),
        ]);
        $view_data['player'][] = $res['result'];

        // Get detail of stage
        $stage = $this->_api('stage')->get_detail([
            'user_id' => $this->current_user->id,
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        if (isset($stage['result'])) {
            $view_data['stage'][] = $stage['result'];
        }

        // Get detail of deck
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        // Get power of user
        $power = $this->_api('user_power')->get_detail([
            'user_id' => $this->current_user->id
        ]);
        $view_data['power'] = $power['result'];

        // Get cases of rabipoint bonus
        $rabipoint = $this->_api('user_rabipoint')->get_cases([
            'user_id' => $this->current_user->id,
            'play_id' => $this->session->has_userdata('play_id'),
            'stage_id' => $this->session->userdata('stage_id'),
            'type' => $this->session->userdata('type')
        ]);

        if (isset($rabipoint['result'])) {
            $view_data['rabipoint_bonus'] = $rabipoint['result']['rabipoint_bonus'];
        }
        $view_data['is_trial'] = $this->session->userdata('type') == 'trial' ? TRUE : FALSE;
        $view_data['not_show_get_trophy_script'] = TRUE;

        return $this->_render($view_data);
    }

    /**
     * Get questions
     */
    public function get_questions()
    {
        $res = $this->_api('stage_question')->get_list([
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        return $this->_build_json($res['result']['items']);
    }

    /**
     * Calculate the answer
     */
    public function get_score()
    {
        if (!$this->input->post() || !$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        $res = $this->_api('stage')->calculate($this->input->param());

        return $this->_true_json($res['result']);
    }

    /**
     * Get Result of Play
     */
    public function result()
    {
        if (!$this->input->post() || !$this->input->is_ajax_request() || !$this->session->has_userdata('stage_id')) {
            return $this->_render_404();
        }

        $params = $this->input->param();

        // Update params to count score
        $params['play_id'] = $this->session->userdata('play_id');
        $params['stage_id'] = $this->session->userdata('stage_id');
        $params['user_id'] = $this->current_user->id;
        $params['operator_primary_type'] = $this->current_user->primary_type;

        if ($this->session->userdata('type') == 'quest') {
            $params['type'] = $this->session->userdata('drill_type');
            $params['round_id'] = $this->session->userdata('round_id');
        } else {
            $params['type'] = $this->session->userdata('type');
        }

        $score = $this->_api('stage')->get_total_score($params);
        $result = [];

        if (isset($score['result']['total_score'])) {
            // Count
            $params['score'] = (int) $score['result']['total_score'];
            $type = $this->session->userdata('type');

            if ($type == 'team_battle') {
                // add param to get group_playing_id
                $params['group_id'] = $this->session->userdata('group_id');
                $params['target_group_id'] = $this->session->userdata('target_group_id');
                $params['battle_room_id'] = $this->session->userdata('battle_room_id');

                // get result
                $result = $this->_api('user_group_playing')->get_result_play($params);
            } else {
                $result = $this->_api('user_playing')->get_result_play($params);
            }
        }

        if (!isset($result['result'])) {
            return $this->_render_404();
        }

        // Response to view
        $res = $result['result'];
        $res['show_result'] = TRUE;

        if (in_array($res['type'], ['trial', 'training'])) {
            $this->session->set_userdata('result_training', $res);
            $res['href'] = '/play/result_training';

        } else if ($res['type'] == 'team_battle') {
            $this->session->set_userdata('result_team_battle', $res);
            $res['href'] = '/play/result_team_battle';

        } else {
            $this->session->set_userdata('result_match', $res);
            $res['href'] = '/play/result_match';
        }

        return $this->_true_json($res);
    }

    /**
     * Get Result training
     */
    public function result_training()
    {
        if (!$this->session->has_userdata('result_training')) {
            return redirect('/play');
        }
        $view_data = [];

        $view_data['from_quest'] = FALSE;
        if ($this->session->userdata('type') == 'quest') {
            $view_data['from_quest'] = TRUE;
        }

        $res = $this->session->userdata('result_training');

        // Response
        if ($res['show_result']) {
            $view_data['is_trial'] = FALSE;
            $view_data['deck_id'] = $this->session->userdata('drill_id');
            $view_data['result'] = $res;

            if ($this->session->userdata('type') == 'trial') {
                $view_data['is_trial'] = TRUE;
                $view_data['get_point'] = isset($res['rabipoint']['result_trial_play']) ? $res['rabipoint']['result_trial_play'] : [];
            }

            // $this->session->unset_userdata('result_training');
            return $this->_render($view_data);

        } else {
            return redirect('/play?type=training');
        }
    }

    /**
     * Get Result match
     */
    public function result_match()
    {
        if (!$this->session->has_userdata('result_match')) {
            return redirect('/play');
        }

        $view_data = [];
        $view_data['from_quest'] = FALSE;
        if ($this->session->userdata('type') == 'quest') {
            $view_data['from_quest'] = TRUE;
        }

        $res = $this->session->userdata('result_match');

        // Get user power
        $power = $this->_api('user_power')->get_detail([
            'user_id' => $this->current_user->id
        ]);

        $can_battle = $power['result']['current_power'] > 0 ? TRUE : FALSE;

        // Show result of play
        if ($res['show_result']) {
            $view_data['result'] = $res;
            $this->session->unset_userdata('result_match');

            // Get information player
            $opponent = $this->_api('user')->get_play([
                'play_id' => $this->session->userdata('play_id'),
            ]);

            $view_data['can_battle'] = $can_battle;

            // Check draw play
            if ($res['score'] == $opponent['result']['score']) {
                $view_data['is_draw'] = TRUE;
            } else {
                $view_data['is_draw'] = FALSE;
                $view_data['is_win'] = $res['score'] > $opponent['result']['score'] ? TRUE : FALSE;
            }

            $view_data['opponent'][] = $opponent['result'];
            $this->session->unset_userdata('play_id');
            return $this->_render($view_data);

        } else {
            return redirect($can_battle ? '/play?type=battle' : '/play?type=training');
        }
    }

    /**
     * Memorize questions
     */
    public function memorize()
    {
        if (!$this->session->has_userdata('stage_id') || $this->session->userdata('type') != 'memorize') {
            return redirect('/play');
        }

        $user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id;

        $view_data = [];

        $view_data['is_student'] = $this->session->userdata('switch_student_id') ? FALSE : TRUE;

        // Get deck infor
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        // Get stage detail
        $stage = $this->_api('stage')->get_detail([
            'user_id' => $user_id,
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        if (isset($stage['result'])) {
            $view_data['stage'][] = $stage['result'];
        }

        // Get memorization status
        $memorization_status = $this->_api('user_memorization')->get_list([
            'user_id' => $user_id,
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        $statuses = [
            'remember' => 0,
            'consider' => 0,
            'forget' => 0,
            'not_checked' => 0
        ];

        $view_data['questions'] = $memorization_status['result']['items'];

        $incorrect_questions = 0;
        foreach ($memorization_status['result']['items'] AS $key => $question) {
            if (empty($question['status'])) {
                ++$statuses['not_checked'];
            } else {
                ++$statuses[$question['status']];
            }

            if ($question['status'] != 'remember') {
                ++$incorrect_questions;
            }
        }
        $view_data['disable_button'] = FALSE;
        if ($incorrect_questions == 0) {
            $view_data['disable_button'] = TRUE;
        }

        $view_data['stage'][0]['memorization'] = $statuses;

        return $this->_render($view_data);
    }

    /**
     * Change memorization
     */
    function change_memorization()
    {
        $return = FALSE;
        if ($this->input->is_post()) {
            // Update status
            $res = $this->_api('user_memorization')->update_status($this->input->post());
            if (isset($res['submit'])) {
                $return = $res['submit'];
            }
        }

        return $this->_build_json($return);
    }

    /**
     * Memorize questions
     */
    public function memorize_result($type = null)
    {
        if (!$this->session->has_userdata('stage_id') || $this->session->userdata('type') != 'memorize') {
            return redirect('/play');
        }

        $view_data = [];
        // Get memorization status
        $memorization_status = $this->_api('user_memorization')->get_list([
            'user_id' => $this->current_user->id,
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        $all_questions = $memorization_status['result']['items'];

        foreach ($all_questions AS $key => $question) {
            if ($question['status'] != 'remember') {
                $incorrect_questions[] = $all_questions[$key];
            }
        }

        $view_data['questions'] = $type == 'all' ? $all_questions : $incorrect_questions;

        $view_data['remove_question'] = $type == 'all' ? FALSE : TRUE;

        return $this->_render($view_data);
    }

    /**
     * Get memorize result
     */
    public function get_memorize_result()
    {
        // Get memorization status
        $memorization_status = $this->_api('user_memorization')->get_list([
            'user_id' => $this->current_user->id,
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        $statuses = [
            'remember' => 0,
            'consider' => 0,
            'forget' => 0,
            'not_checked' => 0
        ];

        $question_answer_data['questions'] = [];
        foreach ($memorization_status['result']['items'] AS $key => $question) {

            $question_answer_data['questions'][$key]['question_id'] = $question['id'];
            $question_answer_data['questions'][$key]['status'] = $question['status'];
            $question_answer_data['questions'][$key]['question'] = $question['question'];

            if (empty($question['status'])) {
                ++$statuses['not_checked'];
            } else {
                ++$statuses[$question['status']];
            }
        }
        $text_condition = $statuses['remember'] / ($statuses['remember'] + $statuses['consider'] + $statuses['forget'] + $statuses['not_checked']);

        switch(TRUE) {
            case $text_condition <= 0.4:
                $return['text1'] = '焦らないで！';
                $return['text2'] = '少しずつ頑張ろう！';
                break;

            case $text_condition > 0.4 && $text_condition <= 0.7:
                $return['text1'] = '調子が上がって来たみたいだね！';
                $return['text2'] = '頑張って！';
                break;

            case $text_condition == 1:
                $return['text1'] = '全部覚えたね！';
                $return['text2'] = 'おめでとう！';

                $this->_api('timeline')->create([
                    'timeline_key' => 'memorization_done',
                    'type' => 'timeline',
                    'target_id' => $this->session->userdata('stage_id')
                ]);

                break;

            case $text_condition > 0.7 && $text_condition < 1:
                $return['text1'] = 'あとすこし！';
                $return['text2'] = 'ゴールが見えて来たね';
                break;
        }

        // Get memorization status
        $memorize_play = $this->_api('user_playing')->create_memorization_playing([
            'user_id' => $this->current_user->id,
            'stage_id' => $this->session->userdata('stage_id'),
            'type' => 'memorize',
            'second' => $this->input->post('total_second'),
            'question_answer_data' => json_encode($question_answer_data)
        ]);

        $this->_api('timeline')->create([
            'timeline_key' => 'memorization',
            'type' => 'timeline',
            'target_id' => $this->session->userdata('stage_id'),
            'play_id' => $memorize_play['result'],
            'play_type' => 'individual_play'
        ]);

        $return['memorization'] = $statuses;

        return $this->_build_json($return);
    }

    /**
     * Team play top - VS100
     */
    public function team()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        return $this->_render();
    }

    /**
     * Team battle Top - VS200
     */
    public function team_battle()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Create type battle
        if ($this->session->userdata('type') != 'team_battle') {
            $this->session->set_userdata('type', 'team_battle');
        }
        
        // Get team battle rooms are playing of user
        $rooms = $this->_api('battle_room')->get_room_playing([
            'user_id' => $this->current_user->id
        ]);

        // Set data to view
        if (isset($rooms['result'])) {
            $view_data['rooms'] = $rooms['result'];
        }

        // render view
        return $this->_render($view_data);
    }

    /**
     * Team battle select my team - VS210
     * @param int $group_id
     */
    public function my_team($group_id = null)
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle') {
            return redirect('/play/team/');
        }

        // Check select team
        if (is_numeric($group_id)) {
            $this->session->set_userdata('group_id', $group_id);
            // redirect to VS220
            return redirect('/play/team/battle/opponent');
        }

        // Get team of user
        $groups = $this->_api('group_playing')->get_group_user([
            'user_id' => $this->current_user->id
        ]);

        // Set data to view
        if (isset($groups['result'])) {
            $view_data['groups'] = $groups['result'];
        }

        // render view
        return $this->_render($view_data);
    }

    /**
     * Team battle select opponent's team - VS220
     * @param int $target_group_id
     */
    public function opponent($target_group_id = null)
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle') {
            return redirect('/play/team/');
        }

        // Check team_opponent select
        if (is_numeric($target_group_id)) {
            $this->session->set_userdata('target_group_id', $target_group_id);
            // redirect to VS230
            return redirect('/play/team/battle/room');
        }

        // Get infor current team
        $group = $this->_api('group_playing')->get_infor_current_group([
            'group_id' => $this->session->userdata('group_id')
        ]);

        // Get list opponent
        $target_groups = $this->_api('group_playing')->get_target_group([
            'group_id' => $this->session->userdata('group_id')
        ]);

        // Set data to view
        if (isset($group['result'])) {
            $view_data['group'] = $group['result'];
        }
        if (isset($target_groups['result'])) {
            $view_data['target_groups'] = $target_groups['result'];
        }

        // render view
        return $this->_render($view_data);
    }

    /**
     * Return business date
     */
    public function get_current_business_time()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Check ajax
        if (!$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle') {
            return $this->_render_404();
        }

        return $this->_true_json(business_date('Y-m-d H:i:s'));
    }

    /**
     * Check target_group
     */
    public function check_target_group()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Check ajax
        if (!$this->input->post() || !$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle') {
            return $this->_render_404();
        }

        $res = $this->_api('group_playing')->check_target_group([
            'group_id' => $this->session->userdata('group_id'),
            'target_group_id' => $this->input->post('target_group_id')
        ]);
        return $this->_true_json($res['result']);
    }

    /**
     * Team battle search opponent's team - VS223
     */
    public function search_opponent()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle') {
            return redirect('/play/team/');
        }

        // Check  input post
        if ($this->input->post()) {
            $target_groups = $this->_api('group_playing')->search_group([
                'group_param' => $this->input->post('group_param'),
                'group_id' => $this->session->userdata('group_id')
            ]);
        }

        $view_data = [];

        // Get current team info
        $group = $this->_api('group_playing')->get_infor_current_group([
            'group_id' => $this->session->userdata('group_id')
        ]);

        // Set data to view
        if (isset($group['result'])) {
            $view_data['group'] = $group['result'];
        }

        if (isset($target_groups['result'])) {
            $view_data['target_groups'] = $target_groups['result'];
        }

        // render view
        return $this->_render($view_data);
    }

    /**
     * Team battle room - VS223
     * @param int $room_id
     */
    public function room($room_id = null)
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle') {
            return redirect('/play/team/');
        }

        // Check exist room
        if (!$room_id) {

            $group_id = $this->session->userdata('group_id');
            $target_group_id = $this->session->userdata('target_group_id');

            // Check my team
            if (!$group_id) {
                // redirect to VS210
                return redirect('/play/team/battle/my_team');
            }

            // Check team's opponent
            if (!$target_group_id) {
                // redirect to VS220
                return redirect('/play/team/battle/opponent');
            }

            // Check exist room - if not exist then create
            $res = $this->_api('group_playing')->check_exist_room([
                'group_id' => $group_id,
                'target_group_id' => $target_group_id
            ]);

            if (isset($res['result'])) {
                $room_id = $res['result']['id'];
                return redirect('/play/team/battle/room/'. $room_id);
            } else {
                return $this->_false_json(APP_Response::BAD_REQUEST);
            }
        }

        // Set session battle_room
        $this->session->set_userdata('battle_room_id', $room_id);

        // Get detail of room
        $res = $this->_api('battle_room')->get_room_detail([
            'user_id' => $this->current_user->id,
            'room_id' => $room_id
        ]);

        // Set data to view
        if (isset($res['result']['room'])) {
            // Set session group
            $this->session->set_userdata('group_id', $res['result']['room']['group_id']);
            $this->session->set_userdata('target_group_id', $res['result']['room']['target_group_id']);

            $view_data['room'] = $res['result']['room'];
        }

        if (isset($res['result']['members'])) {
            $view_data['members'] = $res['result']['members'];
        }

        // render view
        return $this->_render($view_data);
    }

    /**
     * Team battle history - VS240
     */
    public function history()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle') {
            return redirect('/play/team/');
        }

        // Get list history play team battle
        $histories = $this->_api('battle_room')->get_history([
            'user_id' => $this->current_user->id
        ]);

        // Set data to view
        if (isset($histories['result'])) {
            $view_data['histories'] = $histories['result'];
        }

        // render view
        return $this->_render($view_data);
    }

    /**
     * Team battle result - NT40
     */
    public function result_team_battle()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        $view_data = [];

        // Check session mode play team_battle
        if ($this->session->userdata('type') != 'team_battle') {
            return $this->_render_404();
        }

        $res = $this->session->userdata('result_team_battle');

        // Check result
        if (!$res) {
            return redirect('/play');
        }

        // Response
        if (!$res['show_result']) {
            return redirect('/play/team/battle');
        }

        $view_data['result'] = $res;

        // destroy session result
        $this->session->unset_userdata('result_team_battle');

        // Set room battle
        $view_data['battle_room_id'] = $this->session->userdata('battle_room_id');

        return $this->_render($view_data);
    }

    /**
     * Team battle result - NT40
     */
    public function result_team_battle_notification($battle_room_id = null)
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        // Get result team battle 
        if (!is_numeric($battle_room_id)) {
            return $this->_render_404();
        }

        $view_data = [];
        $res = $this->_api('user_group_playing')->get_data_result([
            'battle_room_id' => $battle_room_id
        ]);

        if (isset($res['result'])) {
            $view_data['result'] = $res['result'];
        }

        // return data
        return $this->_render($view_data);
    }

    /**
     * Team battle Event quest top - VS300
     */
    public function event_quest_battle()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        return $this->_render();
    }

    /**
     * Team battle Event quest top detail Team - VS310
     */
    public function detail_team_battle()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        return $this->_render();
    }

    /**
     * Select team quest - VS110
     * @param int $group_id
     */
    public function select_team($group_id = null)
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        if($group_id) {
            // Update session group_id
            $this->session->set_userdata('group_id', $group_id);
            $this->session->set_userdata('type', 'quest');
            return redirect('play/select_drill');
        }

        $res = $this->_internal_api('user_group', 'get_list', [
            'user_id' => (int) $this->current_user->id,
            'group_type' => 'friend'
        ]);

        $view_data = [
            'list_groups' => $res['items']
        ];

        $this->_render($view_data);
    }

    /**
     * Select team quest - VS131
     */
    public function drill_owners()
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        if (!$this->session->has_userdata('drill_id') || $this->session->userdata('type') != 'quest') {
            return redirect('/play');
        }

        $view_data = [];
        $members = $this->_api('user_deck')->check_members(['deck_id' => $this->session->userdata('drill_id'), 'group_id' => $this->session->userdata('group_id')]);

        $view_data['members'] = $members['result']['items'];
        $view_data['deck_id'] = $this->session->userdata('drill_id');

        return $this->_render($view_data);
    }

    /**
     * Select team quest - VS140
     * @param int $round_id
     */
    public function select_quest($round_id = null)
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        if (!$this->session->has_userdata('stage_id') || $this->session->userdata('type') != 'quest') {
            return redirect('/play');
        }

        if($round_id) {
            // Update session group_id
            $this->session->set_userdata('round_id', $round_id);
            redirect('play/quest_detail');
        }

        $view_data = [];

        // Group name
        $view_data['group_name'] = $this->_api('group')->get_detail(['group_id' => $this->session->userdata('group_id')])['result']['group_name'];

        // Get stage detail
        $stage = $this->_api('stage')->get_detail([
            'user_id' => $this->current_user->id,
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        if (isset($stage['result'])) {
            $view_data['stage'][] = $stage['result'];
        }

        // Get detail of deck
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        $quests = $this->_api('stage_quest')->get_list(['stage_id' => $this->session->userdata('stage_id'), 'group_id' => $this->session->userdata('group_id')]);

        if (isset($quests['result'])) {
            $view_data['quests'] = $quests['result']['items'];
        }

        return $this->_render($view_data);
    }

    /**
     * Select team quest - VS150
     * @param int $drill_type
     */
    public function quest_detail($drill_type = null)
    {
        // Ignore from 3.0.0
        return $this->_render_404();

        if (!$this->session->has_userdata('round_id') || $this->session->userdata('type') != 'quest') {
            return redirect('/play');
        }

        $view_data = [];

        $quest = $this->_api('stage_quest')->get_detail(['round_id' => $this->session->userdata('round_id')]);

        if (isset($quest['result'])) {
            $view_data['quest'] = $quest['result'];
        }

        if (!empty($drill_type)) {
            $this->session->set_userdata('quest_description', $quest['result']['description']);
            $this->session->set_userdata('drill_type', $drill_type);
            $redirect = $drill_type == 'training' ? 'play/training' : 'play/select_player';
            return redirect($redirect);
        }

        // Group name
        $view_data['group_name'] = $this->_api('group')->get_detail(['group_id' => $this->session->userdata('group_id')])['result']['group_name'];

        // Get stage detail
        $stage = $this->_api('stage')->get_detail([
            'user_id' => $this->current_user->id,
            'stage_id' => $this->session->userdata('stage_id')
        ]);

        if (isset($stage['result'])) {
            $view_data['stage'][] = $stage['result'];
        }

        // Get detail of deck
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        // Get detail of deck
        $res = $this->_api('user_group_playing')->get_list([
            'round_id' => $this->session->userdata('round_id'),
        ]);

        $members = $res['result']['items'];

        $view_data['current_user_done'] = FALSE;
        $total_score = 0;

        foreach ($members AS $key => $member) {
            if ($member['user_id'] == $this->current_user->id && $member['status'] != 'progress') {
                $view_data['current_user_done'] = TRUE;
            }

            if ($member['user_id'] == $this->current_user->id) {
                $members[$key]['current_user'] = TRUE;
            }
            $total_score += $member['score'];
        }
        $view_data['average'] = (int) ($total_score/count($members));

        $view_data['members'] = $members;


        return $this->_render($view_data);
    }

    public function start_quest()
    {
        if (!$this->session->has_userdata('round_id') || $this->session->userdata('type') != 'quest') {
            return redirect('/play');
        }

        $round_id = $this->_api('stage_quest')->start_quest([
            'quest_id' => $this->input->post('quest_id'),
            'group_id' => $this->session->userdata('group_id'),
            'stage_id' => $this->session->userdata('stage_id')
        ])['result'];

        $this->session->set_userdata('round_id', $round_id);
    }

    public function view_status()
    {
        $view_data = [];

        // Get deck infor
        $deck = $this->_api('deck')->get_infor([
            'deck_id' => $this->session->userdata('drill_id'),
        ]);

        if (isset($deck['result'])) {
            $view_data['deck'][] = $deck['result']['items'];
        }

        $user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id;

        $stage = $this->_api('deck_stage')->get_list([
            'user_id' => $user_id,
            'deck_id' => $this->session->userdata('drill_id'),
            'stage_id' => $this->session->userdata('stage_id'),
            'stage_type' => 'status'
        ]);

        if (isset($stage['result'])) {

            foreach($stage['result']['items'] as $key => $item) {
                $data = $item['highest_score_data'];

                $stage['result']['items'][$key]['total_questions'] = 0;
                $stage['result']['items'][$key]['correct_answer'] = 0;
                foreach ($data['questions'] as $item1) {
                    $stage['result']['items'][$key]['total_questions'] ++;

                    if ($item1['score'] > 0) {
                        $stage['result']['items'][$key]['correct_answer'] ++;
                    }
                }
            }

            $view_data['stage'] = $stage['result']['items'];
        }

        $view_data['user_nickname'] = $this->_api('user')->get_detail([
            'id' => $user_id
        ])['result']['nickname'];

        // Get play history
        $learning_history = $this->_api('user_playing')->get_list([
            'user_id' => $user_id,
            'stage_id' => $this->session->userdata('stage_id'),
            'limit' => 2,
            'offset' => 0
        ]);

        $items = $learning_history['result']['items'];

        foreach($items as $key => $item) {
            $data = json_decode($item['question_answer_data'], TRUE);

            $items[$key]['correct_answer'] = 0;
            $items[$key]['total_questions'] = 0;
            foreach ($data['questions'] as $item1) {
                $items[$key]['total_questions'] ++;

                if ($item1['score'] > 0) {
                    $items[$key]['correct_answer'] ++;
                }
            }
        }

        if (isset($learning_history['result'])) {
            $view_data['learning_history'] = $items;
            $view_data['total'] = $learning_history['result']['total'];
        }

        return $this->_render($view_data);
    }

    public function monthly_report($user_id = null)
    {
        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            } else {
                redirect('dashboard/'.$user_id);
            }
        } else {
            $user_id = $this->current_user->id;
        }

        $view_data = [];
        $view_data['user_nickname'] = $this->_api('user')->get_detail([
            'id' => $user_id
        ])['result']['nickname'];

        $view_data['monthly_report'] = $this->_api('user_playing')->get_monthly_report([
            'user_id' => $user_id
        ])['result'];

        return $this->_render($view_data);
    }
}
