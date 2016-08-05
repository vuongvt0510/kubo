<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Dashboard controller
 *
 * @author IMV
 */
class Dashboard extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * User Dashboard DB-10
     */
    public function index($user_id = null)
    {
        $view_data = [];
        // Check user primary type
        if($this->current_user->primary_type == 'parent' && empty($user_id) && empty($this->students)) {
            $view_data['no_child'] = TRUE;
            $view_data['dashboard_page'] = TRUE;
            $this->_render($view_data);
            return;
        }

        // Check user primary type
        if($this->current_user->primary_type == 'parent' ) {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }

            $user_id = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;

        } else {
            $user_id = $user_id == null ? $this->current_user->id : $user_id;
        }

        // Set link of station
        $view_data['play_URL'] = ($this->current_user->primary_type == 'student' && $user_id == $this->current_user->id) || ($this->current_user->primary_type == 'parent' && isset($this->students[$user_id])) ?
            'play/' : 'deck/';

        $user_info = $this->_api('user')->get_detail([
            'id' => $user_id
        ]);
        if ($user_info['result']) {
            $view_data['user_info'] = $user_info['result'];
        }

        if ($user_id == $this->current_user->id) {
            // Create timeline and trophy when the first check
            $trophy = $this->_api('timeline')->create([
                'timeline_key' => 'dashboard',
                'type' => 'trophy'
            ]);

            // Give rabipoint when student browse dashboard
            $res_rabipoint = $this->_api('user_rabipoint')->create_rp([
                'user_id' => $user_id,
                'type' => 'dashboard',
                'modal_shown' => 1
            ]);

            $view_data['get_trophy'] = $trophy['result'];
            $view_data['get_point'] = $res_rabipoint['result'];
        }

        // Get trophy of user
        $trophies = $this->_api('user_trophy')->get_list([
            'user_id' => $user_id
        ]);
        if (isset($trophies['result'])) {
            $view_data['trophy_total'] = $trophies['result']['total'];
            $view_data['trophy_items'] = $trophies['result']['items'];
        }

        if($user_info['result']['primary_type'] == 'parent') {
            redirect('/');
            return;
        }

        // Get friend list
        $friends = $this->_api('user_friend')->get_list([
            'user_id' => $user_id
        ]);
        if (isset($friends['result'])) {
            $view_data['friends'] = $friends['result']['total'];
        }

        // Get rabipoint of user
        $rabipoints = $this->_api('user_rabipoint')->get_detail([
            'user_id' => $user_id
        ]);
        if (isset($rabipoints['result'])) {
            $view_data['rabipoints'] = $rabipoints['result']['point'];
        }

        // Get good of user
        $goods = $this->_api('timeline_good')->get_list([
            'user_id' => $user_id
        ]);
        if (isset($goods['result'])) {
            $view_data['goods'] = $goods['result']['total'];
        }

        // Get contract of user
        if ( ($this->current_user->primary_type == 'student' && $user_id == $this->current_user->id)
            || ($this->current_user->primary_type == 'parent' && isset($this->students[$user_id]))
        ) {
            $contract = $this->_api('user_contract')->get_detail([
                'user_id' => $user_id
            ]);
            if (isset($contract['result'])) {
                switch ($contract['result']['status']) {
                    case 'under_contract':
                        $view_data['contract'] = '契約中';
                        break;

                    case 'pending':
                        $view_data['contract'] = '更新停止';
                        break;

                    case 'canceling':
                        $view_data['contract'] = '解約';
                        break;

                    default:
                        $view_data['contract'] = '未契約';
                }
            }
        }

        // Get timelines of user
        $timelines = $this->_api('timeline')->get_list([
            'user_id' => $user_id,
            'limit' => 4,
            'offset' => 0
        ]);
        if (isset($timelines['result'])) {
            $view_data['timelines'] = $timelines['result']['items'];
        }

        // Get timelines of everyone
        $timelines_everyone = $this->_api('timeline')->get_list([
            'get_friend' => TRUE,
            'user_id' => $user_id,
            'type' => 'trophy',
            'limit' => 4,
            'offset' => 0
        ]);
        if (isset($timelines_everyone['result'])) {
            $view_data['timelines_everyone'] = $timelines_everyone['result']['items'];
        }

        // Get ranking of user
        $user_ranking = $this->_api('ranking')->get_user_rank([
            'user_id' => $user_id,
            'ranking_type' => 'global'
        ]);
        $view_data['user_ranking'] = $user_ranking['result']['rank'] == 0 || $user_ranking['result']['rank'] >= 101 ? null : $user_ranking['result']['rank'];

        // Get coin of user
        $remaining_coin = $this->_api('coin')->get_user_coin([
            'user_id' => $user_id
        ]);
        $view_data['remaining_coin'] = $remaining_coin['result']['current_coin'];

        // Get purchases deck of user
        $purchased_decks = $this->_api('user_deck')->get_list([
            'user_id' => $user_id,
            'limit' => 6,
            'sort_position' => 'desc',
            'sort_by' => 'user_buying.created_at'
        ]);

        if (isset($purchased_decks['result'])) {
            $temp = [];

            // Order purchased deck follow created_at
            foreach ($purchased_decks['result']['items'] as $key => $value) {
                foreach ($value['decks'] as $k => $v) {
                    $temp[] = $v;
                }
            }

            // Order buying created at
            for ($i=0; $i < count($temp); $i++) { 
                for ($j=$i+1; $j < count($temp); $j++) { 
                    if ($temp[$i]['buy']['created_at'] < $temp[$j]['buy']['created_at']) {
                        $t = $temp[$j];
                        $temp[$j] = $temp[$i];
                        $temp[$i] = $t;
                    }
                }
            }

            $purchased_decks['result']['items'] = $temp;
            $view_data['purchased_decks'] = $purchased_decks['result'];
        }

        if($user_id != $this->current_user->id) {

            if ($this->current_user->primary_type == 'parent') {
                if(!isset($this->students[$user_id])) {
                    $view_data['check_friend_from_parent'] = TRUE;
                }
            } else {
                $check_friend = $this->_api('user_friend')->check_friend([
                    'user_id' => $user_id,
                    'target_id' => $this->current_user->id
                ]);
                $view_data['check_friend'] = $check_friend['result'];
            }
        } else {
            $view_data['only_myself'] = TRUE;
        }

        $this_month = business_date('Y-m');
        $last_month = business_date('Y-m', strtotime('-30 days'));

        $playing_monthly_report = $this->_api('user_playing')->get_monthly_report([
            'user_id' => $user_id
        ]);

        $view_data['playing_monthly_report']['this_month'] = $playing_monthly_report['result'][$this_month];

        if (isset($playing_monthly_report['result'][$last_month])) {
            $view_data['playing_monthly_report']['last_month'] = $playing_monthly_report['result'][$last_month];
        }

        $watching_monthly_report = $this->_api('learning_history')->get_monthly_report([
            'user_id' => $user_id
        ]);

        $view_data['watching_monthly_report']['this_month'] = $watching_monthly_report['result'][$this_month];

        if (isset($watching_monthly_report['result'][$last_month])) {
            $view_data['watching_monthly_report']['last_month'] = $watching_monthly_report['result'][$last_month];
        }

        $view_data['check_parent'] = $this->current_user->primary_type == 'parent' ? TRUE : FALSE;
        $view_data['dashboard_page'] = TRUE;
        $view_data['user_id'] = $user_id;
        $this->_render($view_data);
    }
}