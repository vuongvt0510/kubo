<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Timeline_good_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Timeline_good_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Timeline_good_api_validator';

    /**
     * Create a new good for an activities Spec TG-010
     *
     * @param array $params
     *
     * @internal param int $timeline_id
     * @internal param int $user_id
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('timeline_id', 'タイムラインID', 'required|valid_timeline_id');
        $v->set_rules('user_id', 'ユーザーID', 'required|valid_user_id');
        $v->set_rules('target_id', 'ユーザーID', 'required|valid_user_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('timeline_good_model');
        $this->load->model('notification_model');
        $this->load->model('timeline_model');

        // Create timeline good
        $this->timeline_good_model->create([
            'timeline_id' => $params['timeline_id'],
            'user_id' => $params['user_id']
        ], [
            'mode' => 'replace'
        ]);

        $res = $this->timeline_good_model
            ->select('COUNT(user_id) AS total')
            ->where('timeline_id', $params['timeline_id'])
            ->first();

        $trophy = FALSE;
        $res_rabipoint = FALSE;

        if ($this->operator()->primary_type == 'student') {
            $this->load->model('user_friend_model');
            $check_friend = $this->user_friend_model
                ->join('user', 'user.id = user_friend.target_id')
                ->where('user.status', 'active')
                ->where('user_friend.status', 'active')
                ->where('user_friend.user_id', $params['user_id'])
                ->where('user_friend.target_id', $params['target_id'])
                ->first();

            if (!empty($check_friend)) {
                $this->load->model('user_rabipoint_model');
                $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                    'target_id' => $params['target_id'],
                    'user_id' => $params['user_id'],
                    'case' => 'send_good'
                ]);
            }
        }

        // Create notification type is good
        if  ($params['user_id'] != $params['target_id']) {
            $trophy = $this->timeline_model->create_timeline('good', 'trophy');
            $this->notification_model->create_notification($params);
        }

        return $this->true_json([
            'total' => $res->total,
            'trophy' => $trophy,
            'point' => $res_rabipoint
        ]);
    }

    /**
     * Get list goods of timeline Spec TC-020
     *
     * @param array $params
     *
     * @internal param int $timeline_id
     * @internal param int $user_id
     * @internal param int $offset
     * @internal param int $limit
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'valid_user_id');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }



        // Load model
        $this->load->model('timeline_good_model');

        $this->timeline_good_model
            ->calc_found_rows()
            ->select('timeline_good.timeline_id, timeline_good.user_id, timeline_good.created_at, user.nickname, user.login_id')
            ->with_user()
            ->order_by('timeline_good.created_at desc');

        if (!empty($params['timeline_id'])) {
            $this->timeline_good_model->where('timeline_good.timeline_id', $params['timeline_id']);
        }

        if (!empty($params['user_id'])) {
            $this->timeline_good_model->join('(SELECT id FROM timeline WHERE user_id = '.$params['user_id'].') AS timeline_ids', 'timeline_ids.id = timeline_good.timeline_id');
        }

        if (isset($params['limit'])) {
            $this->timeline_good_model->limit($params['limit']);
        }

        if (isset($params['offset'])) {
            $this->timeline_good_model->offset($params['offset']);
        }

        $res = $this->timeline_good_model->all();

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->timeline_good_model->found_rows()
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

        return [
            'timeline_id'  => (int) $res->timeline_id,
            'user_id'  => (int) $res->user_id,
            'created_at' => $res->created_at,
            'nickname' => $res->nickname,
            'login_id' => $res->login_id
        ];
    }
}

/**
 * Class Timeline_good_api_validator
 *
 * @property Timeline_good_api $base
 */
class Timeline_good_api_validator extends Base_api_validation
{
    /**
     * Check timeline ID is exist
     *
     * @var int timeline_id
     *
     * @return bool
     */
    function valid_timeline_id($timeline_id)
    {
        // Load model
        $this->base->load->model('timeline_model');

        // Get the user ID
        $res = $this->base->timeline_model->find($timeline_id);

        // If existing return error
        if (empty($res)) {
            $this->set_message('valid_timeline_id', 'タイムラインIDの設定が間違っています');
            return FALSE;
        }

        return TRUE;
    }
}
