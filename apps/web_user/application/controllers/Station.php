<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Station Controller
 *
 * @author Tran Nguyen <nguyentc@nal.vn>
 */
class Station extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'only' => ['purchased']
        ]);
    }

    /**
     * Index Station Spec SA10
     * 
     */
    public function index()
    {
        $view_data = [];

        $drills = $this->_api('deck')->get_related_subject(['deck_id' => '']);

        if(isset($drills['result'])) {
            $view_data['subjects'] = $drills['result']['items'];
        }

        $view_data['is_login'] = FALSE;
        if(isset($this->current_user->primary_type)) {
            $view_data['is_login'] = isset($this->current_user->login_id) ? TRUE : FALSE;
            $view_data['user_id'] = $this->session->userdata('switch_student_id') ? $this->session->userdata('switch_student_id') : $this->current_user->id;
        }

        $view_data['meta'] = [
            'title' => "スクールTVドリル | 無料の動画で授業の予習・復習をするならスクールTV"
        ];

        return $this->_render($view_data);
    }

    /**
     * Purchased drill Station SA20
     */
    public function purchased($user_id = null)
    {
        $view_data = [];

        if($this->current_user->primary_type == 'parent') {
            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $params['user_id'] = $user_id == null ? $this->session->userdata['switch_student_id'] : $user_id;
        } else {
            $params['user_id'] = $user_id == null ? $this->current_user->id : $user_id;
        }

        $purchases = $this->_api('user_deck')->get_list($params);

        if (isset($purchases['result'])) {
            $view_data['purchases'] = $purchases['result']['items'];
        }

        $user_info = $this->_api('user')->get_detail(['id' => $params['user_id']]);

        $view_data['nickname'] = $user_info['result']['nickname'];

        return $this->_render($view_data);
    }
}
