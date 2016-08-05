<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_friend_api
 *
 * @property User_model user_model
 * @property User_friend_number_model user_friend_number_model
 * @property APP_Config config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_friend_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'User_friend_api_validator';

    /**
     * Get friend number Spec UF-010
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param bool $reset(TRUE/FALSE)
     *
     * @return array
     */
    public function get_number($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $this->load->model('user_friend_number_model');

        if($params['reset'] == TRUE) {
            $this->user_friend_number_model->where('user_id', $params['user_id'])
                ->update_all([
                    'register_at' => null,
                    'user_id' => null
                ]);
        }

        $res = $this->user_friend_number_model
            ->select('number, user_id, register_at')
            ->where('user_id', $params['user_id'])
            ->where('register_at >= ', business_date('Y-m-d H:i:s', strtotime('-15 minutes')))
            ->first();

        if (empty($res)) {
            $res = $this->user_friend_number_model
                ->update_get_number($params['user_id']);
        }

        $response = [
            'number' => $res->number,
            'user_id' => (int) $res->user_id,
            'expired_at' => business_date('Y-m-d H:i:s', strtotime('+15 minutes', strtotime($res->register_at)))
        ];

        return $this->true_json($response);

    }

    /**
     * Find user by friend number Spec UF-050
     *
     * @param array $params
     *
     * @internal param int $friend_number
     *
     * @return array
     */
    public function find_user_by_number($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('friend_number', '友達ナンバー', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('master_school_model');
        $convert_number = $this->master_school_model->comparison;

        $this->load->model('user_friend_number_model');

        $res = $this->user_friend_number_model
            ->where('number', str_replace(array_keys($convert_number), array_values($convert_number), $params['friend_number']))
            ->where('register_at >=', business_date('Y-m-d H:i:s', strtotime('-15 minutes')))
            ->first();

        if (!$res) {
            return $this->false_json(APP_Response::NOT_FOUND, 'ナンバーが違います');
        }

        if($res->user_id == $this->operator()->id) {
            return $this->false_json(self::BAD_REQUEST);
        }

        return $this->true_json($res->user_id);

    }

    /**
     * Create connection between 2 users Spec UF-020
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $target_id
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required|valid_user_id');
        $v->set_rules('target_id', 'ターゲットユーザーID', 'required|valid_user_id|valid_target_id[user_id]');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_friend_model');

        $this->user_friend_model->create([
            'user_id' => $params['user_id'],
            'target_id' => $params['target_id'],
            'status' => 'active'
        ], [
            'mode' => 'replace'
        ]);

        $this->user_friend_model->create([
            'user_id' => $params['target_id'],
            'target_id' => $params['user_id'],
            'status' => 'active'
        ], [
            'mode' => 'replace'
        ]);
        $this->load->model('timeline_model');
        $trophy = $this->timeline_model->create_timeline('make_friend', 'trophy');
        $this->timeline_model->create_timeline('make_friend_timeline', 'timeline', $params['target_id']);

        // Give rabipoint for every single one
        $this->load->model('user_rabipoint_model');
        $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
            'user_id' => $params['user_id'],
            'target_id' => $params['target_id'],
            'case' => 'become_friend',
            'modal_shown' => 1
        ]);

        $this->user_rabipoint_model->create_rabipoint([
            'user_id' => $params['target_id'],
            'target_id' => $params['user_id'],
            'case' => 'become_friend',
            'modal_shown' => 0
        ]);

        if ($res_rabipoint == FALSE) {
            // Give rabipoint when have too many friends
            $total = $this->user_rabipoint_model->check_more_friends($params['user_id']);
            $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $params['user_id'],
                'case' => 'more_friends',
                'condition' => $total,
                'modal_shown' => 1
            ]);
        }
        $total_target = $this->user_rabipoint_model->check_more_friends($params['target_id']);
        $this->user_rabipoint_model->create_rabipoint([
            'user_id' => $params['target_id'],
            'case' => 'more_friends',
            'condition' => $total_target,
            'modal_shown' => 0
        ]);

        return $this->true_json(['trophy' => $trophy, 'point' => $res_rabipoint]);

    }

    /**
     * Get list friends of user Spec UF-030
     *
     * @param array $params
     *
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $this->load->model('user_friend_model');

        if (isset($params['group_id'])) {
            $this->user_friend_model
                ->select('gr.user_id')
                ->join('(SELECT user_id FROM user_group WHERE group_id = '.$params['group_id'].') AS gr', 'gr.user_id = user_friend.target_id', 'left');
        }

        $res = $this->user_friend_model
            ->calc_found_rows()
            ->select('user.id, user.nickname, user.login_id, user_profile.avatar_id')
            ->with_profile()
            ->with_user()
            ->where('user_friend.user_id', $params['user_id'])
            ->where('user_friend.status', 'active')
            ->where('user.status', 'active')
            ->where('user.primary_type', 'student')
            ->order_by('user.nickname', 'asc')
            ->all();

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->user_friend_model->found_rows()
        ]);
    }

    /**
     * Delete connection between 2 users Spec UF-040
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $target_id
     *
     * @return array
     */
    public function delete($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required');
        $v->set_rules('target_id', 'ターゲットユーザーID', 'required|valid_user_id['.$params['user_id'].']');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('user_friend_model');

        $this->user_friend_model
            ->where('user_id', $params['user_id'])
            ->where('target_id', $params['target_id'])
            ->destroy_all();

        $this->user_friend_model
            ->where('user_id', $params['target_id'])
            ->where('target_id', $params['user_id'])
            ->destroy_all();

        return $this->true_json();

    }

    /**
     * Check friend connection between 2 users Spec UF-050
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $target_id
     *
     * @return array
     */
    public function check_friend($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required');
        $v->set_rules('target_id', 'ターゲットユーザーID', 'required|valid_user_id['.$params['user_id'].']');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('user_friend_model');

        $res = $this->user_friend_model->find_by([
            'user_id' => $params['user_id'],
            'target_id' => $params['target_id']
        ]);

        return $res ? $this->true_json(TRUE) : $this->true_json(FALSE);

    }
}
/**
 * Class User_friend_api_validator
 *
 * @property User_api $base
 */
class User_friend_api_validator extends Base_api_validation
{

    /**
     * Validate Target id
     *
     * @param int $target_id
     * @param string $field
     *
     * @return bool
     */
    function valid_target_id($target_id, $field)
    {

        if (isset($this->_field_data[$field], $this->_field_data[$field]['postdata']) && $target_id == $this->_field_data[$field]['postdata']) {
            $this->set_message('valid_user_id', 'ユーザーIDとターゲットIDは異なっている必要があります。');
            return FALSE;
        }

        return TRUE;
    }
}