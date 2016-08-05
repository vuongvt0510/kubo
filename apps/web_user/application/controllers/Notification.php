<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Notification Controller
 *
 * @author DiepHQ
 */
class Notification extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['index', 'detail']
        ]);

        $this->_before_filter('_is_student', [
            'only' => ['friend']
        ]);
    }

    /**
     * Index Spec NT-10
     */
    public function index()
    {
        $view_data = [
            'notification_family' => $this->_internal_api('notification', 'get_list', [
                'group_type' => 'family',
                'user_id'    => $this->current_user->id
            ]),
        ];

        $view_data['is_student'] = $this->current_user->primary_type == 'student' ? TRUE : FALSE;
        return $this->_render($view_data);
    }

    /**
     * Notification friend NT-10
     */
    public function friend()
    {
        $view_data = [
            'notification_friend' => $this->_internal_api('notification', 'get_list', [
                'group_type' => 'friend',
                'user_id'    => $this->current_user->id
            ])
        ];

        return $this->_render($view_data);
    }

    /**
     * Notification from exchange point
     */
    public function point_exchange()
    {
        $view_data = [];
        $res = $this->_api('notification')->get_list_schooltv([
            'user_id' => $this->current_user->id
        ]);

        if (isset($res['result'])) {
            $view_data['notification'] = $res['result'];
        }

        $view_data['is_student'] = $this->current_user->primary_type == 'student' ? TRUE : FALSE;
        return $this->_render($view_data);
    }

    /**
     * count message and notification unread
     */
    public function notification_check()
    {
        $this->_keep_flash_message();

        $res = [
            'message' => $this->_internal_api('message', 'get_total_new_message'),
            'notification' => $this->_internal_api('notification', 'get_total_new_notification')
        ];

        $view_data['message'] = $res['message']['total'];
        $view_data['notification'] = $res['notification']['total'];

        return $this->_true_json($view_data);
    }
}
