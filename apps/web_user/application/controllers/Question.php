<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Question page controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class Question extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['get_score', 'get_ranking', 'create_score']
        ]);
    }

//    public function index()
//    {
//        $params = $this->input->param();
//
//        $res = $this->_internal_api('question', 'get_video_question', [
//            'video_id' => $params['id']
//        ]);
//
//        $this->_render([
//            'questions' => !empty($res['items']) ? $res['items'] : []
//        ]);
//    }
//
//    /*DG10*/
//    public function dg10()
//    {
//        $this->_render();
//    }

    /*
     * Calculate the answer
     * */
    public function get_score()
    {
        if (!$this->input->post() || !$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        $params = $this->input->param();

        if($params['second'] == 0) $params['second'] = 1;

        $res = $this->_api('score')->calculate($params);

        return $this->_true_json($res['result']);
    }

    /*
    * Create the score
    * */
    public function create_score()
    {
        if (!$this->input->post() || !$this->input->is_ajax_request()) {
            return $this->_render_404();
       }

        $params = $this->input->param();

        $res = $this->_api('score')->create($params);

        if($res['result'] && $this->current_user->is_login()) {
            $params['score'] = (int) $res['result']['total_score'];
            $params['type'] = 'score';
            $params['user_id'] = $this->current_user->id;
            $ranking = $this->_api('ranking')->get_detail($params);

            if(empty($ranking['result']) && $this->current_user->primary_type != 'parent') {
                // Create new ranking
                $this->_api('ranking')->create($params);
            } else {
                // Update ranking
                if(($params['score'] > (int) $ranking['result']['score']) && $this->current_user->primary_type != 'parent') {
                    $this->_api('ranking')->update($params);
                }
            }
        }

        $score = isset($res['result']['total_score']) ? $res['result']['total_score'] : NULL;

        // Save the session
        $this->session->set_userdata('user_score', [
            'target_id' => $params['target_id'],
            'score' => $score
        ]);

        return $this->_true_json($score);
    }

    /*
     * Get the question ranking
     * */
    public function get_ranking()
    {
        if (!$this->input->get() || !$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        $params = $this->input->get();
        $params['type'] = 'score';
        $params['limit'] = 3;

        $res = $this->_api('ranking')->get_ranking_list($params);
        $ranking = $this->_api('ranking')->get_detail($params);

        // Put the fake ranking for non login user
        if(!$this->current_user->is_login() || $this->current_user->primary_type == 'parent') {
            $user_score = $this->session->userdata('user_score') ? $this->session->userdata('user_score') : [];
            $params = array_merge($params, $user_score);

            $current_position = $this->_api('ranking')->get_ranking_position($params);


            // Set the default avatar for parent
            $avatar_id = ($this->current_user->is_login()) ?
                !empty(($this->current_user->avatar_id)) ? $this->current_user->avatar_id : 12 : '';

            $items[] = [
                'id' => null ,
                'user_id' => null,
                'type' => null,
                'target_id'  => null,
                'score' => $this->session->userdata('user_score')['score'],
                'user' => [
                    'name' => ($this->current_user->is_login()) ? $this->current_user->nickname : '',
                    'avatar_id' => $avatar_id
                ],
                'current_position' => isset($current_position['result']) ? $current_position['result'] + 1 : 0,
                'show_class' => TRUE
            ] ;

            $res['result']['items'] = !empty($res['result']['items']) ? $res['result']['items'] : [];

            if(isset($current_position['result']) && ($current_position['result'] >= 3 && $current_position['result'] != 0)) {
                $ranking['result'] = $items[0];
            } else {
                $res['result']['items'] = array_merge($items, $res['result']['items']) ;

                usort($res['result']['items'], function($a, $b) {
                   return $b['score'] - $a['score'];
                });

                $res['result']['items'] = array_slice($res['result']['items'],0 , 3);
            }
        }

        // Get ranking position
        $ranking_tmp = [];
        if (isset($res['result']['items']) && !empty(isset($res['result']['items']))) {
            foreach ($res['result']['items'] as $key => $item_tmp) {
                $score_pre = isset($res['result']['items'][$key - 1]['score']) ? $res['result']['items'][$key - 1]['score'] : null;
                $item_tmp['position'] = $key + 1;
                if (isset($item_tmp['score']) && $item_tmp['score'] == $score_pre) {
                    $item_tmp['position'] = $key;
                }
                $ranking_tmp[] = $item_tmp;
            }
            $res['result']['items'] = $ranking_tmp;
        }

        $this->_render([
            'ranking' => isset($res['result']['items']) ? $res['result']['items'] : [],
            'detail' => isset($ranking['result']) ? $ranking['result'] : [],
            'score' => isset($this->session->userdata('user_score')['score'])
                ? $this->session->userdata('user_score')['score'] : 0,
            'is_login' => $this->current_user->is_login()
        ],
            'partial/modal/ranking',
            FALSE
        );
    }
}