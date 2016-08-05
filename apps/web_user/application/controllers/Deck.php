<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Deck Controller
 *
 * @author Tran Nguyen
 */
class Deck extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'only' => ['buying']
        ]);
    }

    /**
     * Detail Spec D-010
     * 
     * @param string $id of deck
     */
    public function detail($id = NULL)
    {
        $view_data = [];

        // get information of deck
        $deck = $this->_api('deck')->get_infor(['deck_id' => $id]);

        if(empty($deck['result']['items'])) {
            return $this->_render_404();
        }

        if ($this->current_user->is_login() && $this->current_user->primary_type == 'student') {
            // Create timeline and trophy when the first check
            $trophy = $this->_api('timeline')->create([
                'timeline_key' => 'deck_detail',
                'type' => 'trophy'
            ]);

            $view_data['get_trophy'] = $trophy['result'];
        }

        if(isset($deck['result'])) {
            $res = $deck['result']['items'];

            $view_data['deck'] = $res;
            $view_data['meta'] = [
                'title' => $res['name'] . '- '. $res['subject']['short_name'],
                'description' => $res['description']
            ];
        }

        // get deck related by category
        $related_category = $this->_api('deck')->get_related_category(['deck_id' => $id]);
        if(isset($related_category['result'])) {
            $view_data['categories'] = $related_category['result']['items'];
        }

        // get deck related by subject
        $related_subject = $this->_api('deck')->get_related_subject([
            'deck_id' => $id,
            'category_id' => $deck['result']['items']['category']['id'],
        ]);

        if(isset($related_subject['result'])) {
            $view_data['subjects'] = $related_subject['result']['items'];
        }

        $view_data['is_purchased'] = FALSE;

        if ($this->current_user->is_login()) {
            $user_id = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : ($this->current_user->primary_type == 'student' ? $this->current_user->id : 0);

            // check purchased deck
            $is_purchased = $this->_api('user_deck')->get_detail([
                'user_id' => $user_id,
                'deck_id' => $id
            ]);

            if(!empty($is_purchased['result'])) {
                $view_data['is_purchased'] = TRUE;
            }
        }

        $view_data['is_student'] = ($this->current_user->is_login() && $this->current_user->primary_type == 'student') ? TRUE : FALSE;

        // Get deck id
        $this->session->set_userdata('current_deck', $id);

        return $this->_render($view_data);
    }

    /**
     * Buying deck
     * 
     * @return array
     */
    public function buying()
    {
        if (!$this->input->post() || !$this->input->is_ajax_request()) {
            return $this->_render_404();
        }

        // setup params
        $params['deck_id'] = (int) $this->input->post('deck_id');
        $params['user_id'] = $this->session->userdata('switch_student_id') ?
            $this->session->userdata('switch_student_id') :
            ($this->current_user->primary_type == 'student' ? $this->current_user->id : 0);

        // call API
        $res = $this->_api('user_deck')->create($params);

        // result array to return
        $result = [
            'submit' => FALSE
        ];

        // success
        if (isset($res['result']['items'])) {
            $result['submit'] = TRUE;
            $this->_flash_message('ドリルを入手しました');
            $this->session->set_flashdata('get_trophy', $res['result']['trophy']);
            $this->session->set_flashdata('get_point', $res['result']['point']);
        }

        return $this->_true_json($result);
    }
}
