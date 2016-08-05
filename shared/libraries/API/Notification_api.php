<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Notification_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Notification_api extends Base_api
{
    private $notification_type_list = ['comment', 'good', 'fee_base', 'ask', 'constract'];

    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Notification_api_validator';

    /**
     * Get list notifications of user Spec NT-010
     *
     * @param array $params
     *
     * @internal param int $target_id
     * @internal param int $group_type (family/friend)
     * @internal param int $limit
     * @internal param int $offset
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('group_type', 'グループの種類', 'valid_group_type');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        $this->load->model('notification_model');

        $this->notification_model
            ->calc_found_rows()
            ->select('notification.id, notification.user_id, notification.target_id, notification.status')
            ->select('notification.type, notification.extra_data, notification.created_at, user.login_id, user.nickname, user.primary_type, user_profile.avatar_id')
            ->with_profile()
            ->with_user()
            ->where('notification.target_id', $params['user_id'])
            ->order_by('notification.created_at', 'desc')
            ->limit($params['limit'], $params['offset']);

        if(isset($params['group_type'])) {
            $this->notification_model->with_group_family($params['user_id']);

            switch($params['group_type']) {
                case 'family':
                    $this->notification_model
                        ->where('family.user_id is not null')
                        ->where_not_in('notification.type', [Notification_model::NT_POINT_EXCHANGE_SUCCESS, Notification_model::NT_POINT_EXCHANGE_REJECT]);
                    break;

                case 'friend':
                    $this->notification_model
                        ->where('family.user_id', null)
                        ->where_not_in('notification.type', [Notification_model::NT_POINT_EXCHANGE_SUCCESS, Notification_model::NT_POINT_EXCHANGE_REJECT]);
                    break;

            }
        }
        $res = $this->notification_model->all();

        if (!empty($res)) {
            foreach ($res as $key => $notify) {
                // Update unread notification to read
                $this->notification_model->update($notify->id, [
                    'status' => 1
                ]);
            }
        }

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->notification_model->found_rows()
        ]);
    }

    /**
     * Create notification - Spec NT-030
     * 
     * @param array $params
     * 
     * @internal param int $user_id User will receive notification
     * 
     * @return array
     */
    public function get_list_schooltv($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('notification_model');

        // Filter by sort
        if(empty($params['sort_by']) || !in_array($params['sort_by'], ['id', 'created_at'])) {
            $params['sort_by'] = 'created_at';
        }

        // Filter by sort position
        if(empty($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'desc';
        }

        // Allow type
        $allowed_type = [Notification_model::NT_POINT_EXCHANGE_SUCCESS, Notification_model::NT_POINT_EXCHANGE_REJECT];

        // Set query
        $res = $this->notification_model
            ->calc_found_rows()
            ->select('notification.id, notification.user_id, notification.target_id')
            ->select('notification.status, notification.type, notification.extra_data')
            ->select('notification.created_at')
            ->where_in('notification.type', $allowed_type)
            ->where('target_id', $params['user_id'])
            ->order_by($params['sort_by'], $params['sort_position'])
            ->all();

        if (!empty($res)) {
            foreach ($res as $key => $notify) {
                // Update unread notification to read
                $this->notification_model->update($notify->id, [
                    'status' => 1
                ]);
            }
        }

        // Return
        return $this->true_json([
            'total' => (int) $this->notification_model->found_rows(),
            'items' => $this->build_responses($res, ['schooltv'])
        ]);
    }

    /**
     * Create notification - Spec NT-030
     * 
     * @param array $params
     * 
     * @internal param string $type
     * @internal param int $user_id
     * @internal param int $target_id
     * @internal param string $content
     * @internal param int $timeline_id
     * 
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('type', '通知タイプ', 'required');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('target_id', 'ターゲットID', 'required|integer');
        $v->set_rules('timeline_id', 'タイムラインID', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Filter notification type
        if (!in_array($params['type'], $this->notification_type_list)) {
            return $this->false_json(self::BAD_REQUEST, '誤った通知タイプ');
        }

        // Load model
        $this->load->model('notification_model');

        // Set query
        // Create notification
        $notify = $this->notification_model->create_notification($params);

        // Return
        return $this->true_json();
    }

    /**
     * Update a notification status Spec NT-020
     *
     * @param array $params
     *
     * @internal param int $id
     *
     * @return array
     */
    public function update_status($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', '通知ID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('notification_model');

        $this->user_model->update($params['id'] , [
            'status' => 1
        ]);

        return $this->true_json();

    }

    /**
     * Get total new notifications of user
     *
     * @param array $params
     *
     * @return array
     */
    public function get_total_new_notification($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('notification_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        $res = $this->notification_model
            ->select('count(notification.id) as total')
            ->select('id, user_id, target_id, status')
            ->where('target_id', $current_user_id )
            ->where('status', 0)
            ->first();

        return $this->true_json([
            'total' => (int) $res->total
        ]);
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = []){

        if (empty($res)) {
            return [];
        }

        foreach (json_decode($res->extra_data) as $key => $value) {
            $extra_data[$key] = $value;
        }

        $result = [];

        if (empty($options)) {
            $result = [
                'id'  => (int) $res->id,
                'user_id'  => (int) $res->user_id,
                'type' => $res->type,
                'created_at' => $res->created_at,
                'target_id' => (int) $res->target_id,
                'status' => $res->status == 1 ? 'read' : 'unread',
                'extra_data' => $extra_data,
                'login_id' => $res->login_id,
                'nickname' => $res->nickname,
                'avatar_id' => (int) $res->avatar_id,
                'primary_type' => $res->primary_type
            ];
        }

        if (in_array('schooltv', $options)) {
            $result = [
                'id'  => (int) $res->id,
                'user_id'  => null,
                'type' => $res->type,
                'created_at' => $res->created_at,
                'target_id' => (int) $res->target_id,
                'status' => $res->status == 1 ? 'read' : 'unread',
                'extra_data' => $extra_data,
                'avatar_id' => 'rabi-hari',
            ];
        }

        return $result;
    }
}

class Notification_api_validator extends Base_api_validation
{
    /**
     * Validate group type
     *
     * @param string $group_type
     *
     * @return bool
     */
    function valid_group_type($group_type = NULL)
    {

        // Validate group type
        if (isset($group_type) && !in_array($group_type, ['family', 'friend'])) {
            $this->set_message('Group Type', 'グループタイプは家族か友達のどちらかです。');
            return FALSE;
        }

        return TRUE;
    }
}