<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Point_exchange_api
 *
 * @property Point_exchange_model point_exchange_model
 * @property Notification_model notification_model
 * @property User_netmile_model user_netmile_model
 * @property User_rabipoint_model user_rabipoint_model
 * @property User_contract_model user_contract_model
 *
 * @version $id$
 *
 * @copyright 2016 - Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author trannguyen <nguyentc@nal.vn>
 */
class Point_exchange_api extends Base_api
{
    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Point_exchange_api_validator';

    /**
     * Get list history point exchange of user - PX-010
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param string $sort_by
     * @internal param string $sort_position
     * @internal param array $status
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
        $v->set_rules('user_id', 'ユーザーID', 'required|integer|valid_user_id');
        $v->set_rules('status[]', '状態', 'trim');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Filter by sort
        if(empty($params['sort_by']) || !in_array($params['sort_by'], ['id', 'created_at'])) {
            $params['sort_by'] = 'created_at';
        }

        // Filter by sort position
        if(empty($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'desc';
        }

        // Load model
        $this->load->model('point_exchange_model');

        $status_default = array_keys($this->point_exchange_model->get_all_status());

        // Filter status point
        if (!isset($params['status']) || empty($params['status'] || !in_array($params['status'], $status_default))) {
            $params['status'] = $status_default;
        }

        // Set query
        $res = $this->point_exchange_model
            ->calc_found_rows()
            ->select('id, user_id, target_id, point, mile, status, created_at')
            ->where('target_id', $params['user_id'])
            ->where_in('status', $params['status'])
            ->order_by($params['sort_by'], $params['sort_position'])
            ->limit($params['limit'], $params['offset'])
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['list']),
            'total' => (int) $this->point_exchange_model->found_rows()
        ]);
    }

    /**
     * Get list request exchange point - PX-011
     *
     * @param array $params
     * @internal param array $status
     * @internal param string $from_date
     * @internal param string $to_date
     * @internal param string $sort_by
     * @internal param string $sort_position
     * @internal param string $user_id
     * @internal param string $login_id
     * @internal param string $enc_user_id
     * @internal param string $ip_address
     * @internal param int $limit
     * @internal param int $offset
     *
     * @return array
     */
    public function get_list_point($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('POINT_EXCHANGE_LIST');
        $v->set_rules('status[]', 'ポイントステータス', 'trim|required');
        $v->set_rules('from_date', '掲載開始日', 'trim|date_format');
        $v->set_rules('to_date', '掲載終了日', 'trim|date_format|date_larger['.$params['from_date'].']');
        $v->set_rules('user_id', 'ツーザーID', 'trim|integer');
        $v->set_rules('login_id', 'ログインID', 'trim');
        $v->set_rules('enc_user_id', 'ネットマイルID', 'trim');
        $v->set_rules('ip_address', 'IPアドレス', 'trim');
        $v->set_rules('limit', '取得件数', 'trim|integer');
        $v->set_rules('offset', '取得開始', 'trim|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Filter sort_by
        if (!isset($params['sort_by']) || empty($params['sort_by'])) {
            $params['sort_by'] = 'id';
        }

        // Filter sort_position
        if (!isset($params['sort_position']) || empty($params['sort_position'])) {
            $params['sort_position'] = 'desc';
        }

        // Filter from_date
        if (empty($params['from_date'])) {
            $params['from_date'] = business_date('Y-m-d H:i:s', 0);
        }

        // Filter to_date
        if (empty($params['to_date'])) {
            $params['to_date'] = business_date('Y-m-d H:i:s');
        }

        // Load model
        $this->load->model('point_exchange_model');

        if (empty($params['status'])) {
            $params['status'] = [Point_exchange_model::PX_WAIT_STATUS];
        }

        // Set query
        $this->point_exchange_model
            ->calc_found_rows()
            ->select('a.login_id AS user_login_id, b.login_id AS target_login_id,
                point_exchange.user_id, point_exchange.target_id, point_exchange.status,
                point_exchange.id, point_exchange.ip_address, point_exchange.point, 
                point_exchange.mile, point_exchange.created_at, point_exchange.updated_at,
                point_exchange.publish_id, user_contract.status as contract,
                group.id AS group_id, group.name AS group_name, point_exchange.extra_data')
            ->with_user()
            ->with_target()
            ->with_group()
            ->with_user_netmile()
            ->with_user_contract()
            ->where_in('point_exchange.status', $params['status'])
            ->where('ug.group_id = tg.group_id');

        // Filter user_id
        if (!empty($params['user_id'])) {
            $this->point_exchange_model->where("(
                point_exchange.user_id = '". $params['user_id']. 
                "' OR point_exchange.target_id = '". $params['user_id']. "'
                )"
            );
        }

        // Filter login_id
        if (!empty($params['login_id'])) {
            $this->point_exchange_model->where("(
                a.login_id = '". $params['login_id']. 
                "' OR b.login_id = '". $params['login_id']. "'
                )"
            );
        }

        // Filter enc_user_id
        if (!empty($params['enc_user_id'])) {
            $this->point_exchange_model->like('point_exchange.extra_data', $params['enc_user_id']);
        }

        // Filter ip_address
        if (!empty($params['ip_address'])) {
            $this->point_exchange_model->where('point_exchange.ip_address', $params['ip_address']);
        }

        $params['to_date'] = business_date('Y-m-d', strtotime($params['to_date']) + 24 * 60 * 60);
        // Filter time
        $this->point_exchange_model->where("(point_exchange.created_at BETWEEN '". $params['from_date']. "' AND '". $params['to_date']. "')");

        // Sort
        $this->point_exchange_model->order_by(trim($params['sort_by']), trim($params['sort_position']));

        // Set limit offset
        if (!empty($params['limit'])) {
            $this->point_exchange_model->limit($params['limit'], isset($params['offset']) ? (int) $params['offset'] : 0);
        }

        // Get result
        $res = $this->point_exchange_model->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['list_admin']), 
            'total' => (int) $this->point_exchange_model->found_rows(),
            'wait_number' => $this->point_exchange_model->count_status($params, Point_exchange_model::PX_WAIT_STATUS),
            'error_number' => $this->point_exchange_model->count_status($params, Point_exchange_model::PX_ERROR_STATUS)
        ]);
    }

    /**
     * Reject exchange point for admin - PX-051
     * 
     * @param array $params
     * @internal param array $list_point_ids
     *
     * @return array
     */
    public function reject_exchange($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('POINT_EXCHANGE_REJECT');
        $v->set_rules('list_point_ids[]', 'リストポイントID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('point_exchange_model');
        $this->load->model('notification_model');

        foreach ($params['list_point_ids'] as $key => $point_id) {
            $point = $this->point_exchange_model->update($point_id, [
                'status' => Point_exchange_model::PX_REJECT_STATUS
            ], [
                'return' => TRUE
            ]);

            // Get data point exchange
            if (!empty($point)) {
                // Create notification to student account
                $this->notification_model->create_notification([
                    'user_id' => NULL, // Schooltv send
                    'target_id' => $point->target_id, // Student account
                    'student_id' => $point->target_id, // Student account
                    'type' => Notification_model::NT_POINT_EXCHANGE_REJECT
                ]);

                // Create notification to parent account
                $this->notification_model->create_notification([
                    'user_id' => NULL, // Schooltv send
                    'target_id' => $point->user_id, // Parent account
                    'student_id' => $point->target_id, // Parent account
                    'type' => Notification_model::NT_POINT_EXCHANGE_REJECT
                ]);
            }
        }

        // Return
        return $this->true_json(['is_reject' => TRUE]);
    }

    /**
     * Accept exchange for admin - PX-052
     *
     * @param array $params
     * @internal param array $list_point_ids
     *
     * @return array
     */
    public function accept_exchange($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('POINT_EXCHANGE_ACCEPT');
        $v->set_rules('list_point_ids[]', 'リストポイントID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load library
        $this->load->library('netmile_exchange');

        // Load model
        $this->load->model('point_exchange_model');
        $this->load->model('user_netmile_model');
        $this->load->model('notification_model');

        // Loop list array
        foreach ($params['list_point_ids'] as $key => $point_id) {
            // Check current status
            $point = $this->point_exchange_model
                ->select('id, user_id, point, mile, status, extra_data')
                ->where('id', $point_id)
                ->first();

            if ($point->status == Point_exchange_model::PX_DONE_STATUS) {
                return $this->false_json(self::BAD_REQUEST, 'ポイントが交換されました');
            }

            $user_netmile = $this->user_netmile_model
                ->select('user.login_id, user_netmile.user_id, netmile_user_id, enc_user_id')
                ->join('user', 'user.id = user_netmile.user_id')
                ->where('user.deleted_at IS NULL')
                ->where('user_netmile.user_id', $point->user_id)
                ->first();

            if (empty($user_netmile)) {
                return $this->false_json(self::BAD_REQUEST, 'ユーザーは撤回します');
            }

            $display_name = $user_netmile->login_id;
            $specific_key = $display_name;

            $data = json_decode($point->extra_data, TRUE);
            $enc_user_id = isset($data['enc_user_id_request']) ? $data['enc_user_id_request'] : $user_netmile->enc_user_id; // with old data

            // Call API service
            $res = $this->netmile_exchange->get_mile_publish_encId([
                'enc_user_id' => $enc_user_id,
                'mile' => $point->mile,
                'specific_key' => $specific_key, // need change
                'display_name' => $display_name // need change
            ]);

            if (!is_array($data)) {
                $data = get_object_vars($data);
            }

            // Check service error
            if (empty($res['type']) || $res['type'] == 'L') {
                $data['error'][] = [
                    $res[0] => business_date('Y-m-d H:i:s')
                ];

                // Update status error
                $this->point_exchange_model->update($point_id, [
                    'status' => Point_exchange_model::PX_ERROR_STATUS,
                    'extra_data' => json_encode($data)
                ]);

                return $this->false_json(self::BAD_REQUEST, $res[0]);
            }

            // Check error
            if (!empty($res['errorCode'])) {
                // Update extra_data error
                $data['error'][] = [
                    $res['errorCode'] => business_date('Y-m-d H:i:s')
                ];

                // Update status error
                $this->point_exchange_model->update($point_id, [
                    'status' => Point_exchange_model::PX_ERROR_STATUS,
                    'extra_data' => json_encode($data)
                ]);

                return $this->false_json(self::BAD_REQUEST, $res['message']);
            }

            // Save publishId and change status done
            // Update point exchange data
            $point = $this->point_exchange_model->update($point_id, [
                'publish_id' => isset($res['publishId']) ? $res['publishId'] : null,
                'status' => Point_exchange_model::PX_DONE_STATUS,
            ], [
                'return' => TRUE
            ]);

            // Get data point exchange
            if (!empty($point)) {
                // Create notification to student account
                $this->notification_model->create_notification([
                    'user_id' => NULL, // Schooltv send
                    'target_id' => $point->target_id, // Student account
                    'student_id' => $point->target_id, // Student account
                    'type' => Notification_model::NT_POINT_EXCHANGE_SUCCESS
                ]);

                // Create notification to parent account
                $this->notification_model->create_notification([
                    'user_id' => NULL, // Schooltv send
                    'target_id' => $point->user_id, // Parent account
                    'student_id' => $point->target_id, // Parent account
                    'type' => Notification_model::NT_POINT_EXCHANGE_SUCCESS
                ]);
            }
        }

        // Return
        return $this->true_json(['is_exchanged' => TRUE]);
    }

    /**
     * Get netmile oauth and get encrypt User's Netmile - PX-020
     *
     * @param array $params
     * @internal param string $url_redirect
     *
     * @return array
     */
    public function get_redirect_link($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('url_redirect', 'URL', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load library
        $this->load->library('netmile_exchange');
        $this->load->library('session');

        // Load helper
        $this->load->helper('string');

        // Generate token
        $token = random_string('alnum', 32);
        $this->session->set_userdata('request_netmile_token', $token);

        // Call API service
        $res = $this->netmile_exchange->get_redirect_link([
            'rd' => $this->config->item('site_url'). $params['url_redirect'] . "?token=". $token
        ]);

        // Return
        return $this->true_json(['url' => $res]);
    }

    /**
     * Check point is enough for exchanging - PX-030
     * 
     * @param array $params
     * @internal param int $user_id
     * @internal param string $pack
     *
     * @return array
     */
    public function check_enough_point($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('pack', 'パック', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load library
        $this->load->model('user_rabipoint_model');
        $this->load->model('point_exchange_model');

        // Get list pack
        $list_package = $this->point_exchange_model->get_packs();

        if (!array_key_exists($params['pack'], $list_package)) {
            return $this->false_json(self::BAD_REQUEST, 'それはないリストのパッケージで');
        }

        // Select current_point
        $user = $this->user_rabipoint_model
            ->select('SUM(point_remain) AS current_point')
            ->where('user_id', $params['user_id'])
            ->first();

        if (empty($user)) {
            return $this->true_json(['is_enough' => FALSE]);
        }

        $pack = $list_package[$params['pack']];

        // Return
        return $this->true_json([
            'is_enough' => $user->current_point >= $pack['point']
        ]);
    }

    /**
     * Confirm exchange - PX-031
     *
     * @param array $params
     * @internal param int $user_id of parent
     * @internal param int $target_id user id of student
     * @internal param string $pack
     *
     * @return array
     */
    public function confirm_exchange($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer|valid_user_id');
        $v->set_rules('target_id', 'ターゲットID', 'required|integer|valid_user_id');
        $v->set_rules('pack', 'パック', 'required');
        $v->set_rules('ip_address', 'IIPアドレス', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load models
        $this->load->model('user_rabipoint_model');
        $this->load->model('point_exchange_model');

        // Get list pack
        $list_package = $this->point_exchange_model->get_packs();

        if (!array_key_exists($params['pack'], $list_package)) {
            return $this->false_json(self::BAD_REQUEST, 'それはないリストのパッケージで');
        }

        // Select current point of student user
        $user = $this->user_rabipoint_model
            ->select('SUM(point_remain) AS current_point')
            ->where('user_id', $params['target_id'])
            ->first();

        if (empty($user)) {
            return $this->false_json(self::BAD_REQUEST);
        }

        $pack = $list_package[$params['pack']];

        // Check point exchange
        if ($user->current_point < $pack['point']) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Save record point exchange
        $res = $this->point_exchange_model->create_point_exchange([
            'user_id' => $params['user_id'],
            'ip_address' => $params['ip_address'],
            'target_id' => $params['target_id'],
            'point' => $pack['point'],
            'mile' => $pack['mile'],
            'status' => Point_exchange_model::PX_WAIT_STATUS,
        ]);

        return $this->true_json($this->build_responses($res));
    }

    /**
     * Check limit times exchange of user - PX-032
     * 
     * @param array $params
     * @internal param int $user_id
     * 
     * @return bool
     */
    public function check_limit_times_exchange($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer|valid_user_id');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('point_exchange_model');

        // Set query
        $res = $this->point_exchange_model
            ->select('COUNT(id) AS total')
            ->like('created_at', business_date('Y-m-d'))
            ->where('user_id', $params['user_id'])
            ->first();

        // Return
        return $this->true_json(['is_limited' => ($res->total >=10)]); // limit 10 times
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = [])
    {
        if (empty($res)) {
            return [];
        }

        $result = get_object_vars($res);

        // Build the list response
        if (in_array('list', $options)) {
            $result['id'] = (int) $res->id;
            $result['user_id'] = (int) $res->user_id;
            $result['target_id'] = (int) $res->target_id;
            $result['point'] = (int) $res->point;
            $result['mile'] = (int) $res->mile;

            switch ($res->status) {
                case Point_exchange_model::PX_WAIT_STATUS:
                    $result['status'] = Point_exchange_model::PX_WAIT_LABEL;
                    $result['color'] = 'text-blue';
                    break;

                case Point_exchange_model::PX_REJECT_STATUS:
                    $result['status'] = Point_exchange_model::PX_REJECT_LABEL;
                    $result['color'] = 'text-red';
                    break;

                case Point_exchange_model::PX_DONE_STATUS:
                    $result['status'] = Point_exchange_model::PX_DONE_LABEL;
                    $result['color'] = 'text-green';
                    break;

                case Point_exchange_model::PX_ERROR_STATUS:
                    $result['status'] = Point_exchange_model::PX_ERROR_LABEL;
                    $result['color'] = 'text-blue';
                    break;

                case Point_exchange_model::PX_EXPIRED_STATUS:
                    $result['status'] = Point_exchange_model::PX_EXPIRED_LABEL;
                    $result['color'] = 'text-red';
                    $result['is_expired'] = TRUE;

                default:
                    # code...
                    break;
            }

            $result['created_at'] = $res->created_at;
        }

        // Build the list admin response
        if (in_array('list_admin', $options)) {
            $result['id'] = (int) $res->id;
            $result['user_id'] = (int) $res->user_id;
            $result['target_id'] = (int) $res->target_id;
            $result['user_login_id'] = $res->user_login_id;
            $result['target_login_id'] = $res->target_login_id;
            $result['point'] = (int) $res->point;
            $result['mile'] = (int) $res->mile;
            $result['publish_id'] = isset($res->publish_id) ? (int) $res->publish_id : null;
            $result['created_at'] = $res->created_at;
            $result['updated_at'] = $res->updated_at;

            // Load model
            $this->load->model('point_exchange_model');
            $list_status = $this->point_exchange_model->get_list_status_admin();

            // Load model
            $this->load->model('user_contract_model');
            $list_contract = $this->user_contract_model->get_list_status_admin();

            $extra_data = json_decode($res->extra_data);
            $result['status'] = $list_status[$res->status];
            $result['contract'] = $list_contract[$res->contract];
            $result['enc_user_id'] = isset($extra_data->enc_user_id_request) ? $extra_data->enc_user_id_request : '';
        }

        return $result;
    }
}

class Point_exchange_api_validator extends Base_api_validation
{
    /**
     * Validate ended_date must to be larger than started_date
     *
     * @param string $ended_date
     * @param string $started_date
     *
     * @return bool
     */
    function date_larger($ended_date = null, $started_date)
    {

        // Ended date value is not required , only check when $ended_date has value
        if($ended_date && (strtotime($ended_date) < strtotime($started_date))) {
            $this->set_message('date_larger', '終了日時は開始日時より後になるように設定してください。');
            return FALSE;
        }

        return TRUE;
    }
}
