<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_rabipoint_api
 *
 * @property User_rabipoint_model user_rabipoint_model
 * @property User_model user_model
 * @property Point_model point_model
 *
 * @version $id$
 *
 * @copyright 2016 - Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_rabipoint_api extends Base_api
{
    /**
     * Get list rabipoint of User - Spec URB-030
     * 
     * @param  array $params
     * @internal param int $user_id
     * @internal param int $offset of query Default:0
     * @internal param int $limit of query Default:20
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('user_rabipoint_model');

        if (isset($params['modal_not_shown'])) {
            $this->user_rabipoint_model->where('user_rabipoint.is_modal_shown', 0);
        }

        if (isset($params['explanation']) && $params['explanation'] == TRUE) {
            // Filter by sort
            if(empty($params['sort_by']) || !in_array($params['sort_by'], ['id', 'created_at'])) {
                $params['sort_by'] = 'created_at';
            }

            // Filter by sort position
            if(empty($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
                $params['sort_position'] = 'desc';
            }

            $this->user_rabipoint_model->order_by($params['sort_by'], $params['sort_position']);
        }

        // Set query
        $res = $this->user_rabipoint_model
            ->calc_found_rows()
            ->select('user_rabipoint.id, user_rabipoint.rabipoint, user_rabipoint.type, user_rabipoint.created_at')
            ->select('user_rabipoint.point_id, point.case, point.title_modal, point.base_point, point.campaign')
            ->join('point', 'user_rabipoint.point_id = point.id', 'left')
            ->where('user_rabipoint.user_id', $params['user_id'])
            ->where('user_rabipoint.type !=', User_rabipoint_model::RP_EXPIRED_POINT)
            ->limit($params['limit'], $params['offset'])
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->user_rabipoint_model->found_rows()
        ]);
    }

    /**
     * Get user rabipoint
     * @param array $params
     * @return array
     */
    public function get_user_infor($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_rabipoint_model');

        // Set query
        $res = $this->user_model
            ->select('user.id, user.nickname, user.login_id')
            ->select('(SELECT SUM(user_rabipoint.point_remain)
                FROM user_rabipoint
                WHERE type != "'. User_rabipoint_model::RP_EXPIRED_POINT. '" 
                    AND user_id = '. $params["user_id"]. ') AS current_point')
            ->join('user_rabipoint', 'user_rabipoint.user_id = user.id', 'left')
            ->where('user.id', $params['user_id'])
            ->first();

        // Return
        return $this->true_json([
            'id' => (int) $res->id,
            'nickname' => $res->nickname,
            'login_id' => $res->login_id,
            'current_point' => (int) $res->current_point
        ]);
    }

    /**
     * Get total rabipoint of User - Spec RB-010
     * 
     * @param array $params
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_rabipoint_model');

        // Set query
        $res = $this->user_model
            ->select('user.id AS user_id, user.nickname, user.login_id, SUM(user_rabipoint.rabipoint) AS total_point')
            ->select('(SELECT SUM(user_rabipoint.point_remain)
                FROM user_rabipoint
                WHERE type != "'. User_rabipoint_model::RP_EXPIRED_POINT. '" 
                    AND user_id = '. $params["user_id"]. ') AS current_point')
            ->join('user_rabipoint', 'user_rabipoint.user_id = user.id', 'left')
            ->where('user.id', $params['user_id'])
            ->first();

        // Return
        return $this->true_json([
            'user_id' => (int) $res->user_id,
            'nickname' => $res->nickname,
            'login_id' => $res->login_id,
            'point' => (int) $res->current_point,
            'total_point' => (int) $res->total_point
        ]);
    }

    /**
     * Get detail user rabipoint of user
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param int $target_id
     *
     * @return array
     */
    public function get_detail_point($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('case', '場合', 'required');
        $v->set_rules('target_id', 'ターゲットID', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_rabipoint_model');


        // Set query
        $this->user_rabipoint_model
            ->select('user_rabipoint.id, user_rabipoint.user_id, user_rabipoint.rabipoint')
            ->select('user_rabipoint.point_remain, user_rabipoint.point_id, user_rabipoint.is_modal_shown, user_rabipoint.target_id')
            ->select('point.title_modal, point.base_point, point.campaign')
            ->join('point', 'point.id = user_rabipoint.point_id')
            ->where('user_id', $params['user_id'])
            ->where('point.case', $params['case']);

        // Filter target_id param
        if (isset($params['target_id']) && !empty($params['target_id'])) {
            $this->user_rabipoint_model->where('target_id', $params['target_id']);
        }

        $res = $this->user_rabipoint_model->first();

        if (empty($res)) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Return
        return $this->true_json($this->build_response($res));
    }

    /**
     * Get all cases will update rabipoint
     * 
     * @param array $params
     * 
     * @internal param int $play_id
     * @internal param int $stage_id
     * @internal param int $user_id
     * @internal param int $type
     * 
     * @return array
     */
    public function get_cases($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('play_id', 'プレイID', 'integer');
        $v->set_rules('stage_id', 'ステージID', 'required|integer');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('type', 'タイプ', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_rabipoint_model');

        // Set query
        $res = $this->user_rabipoint_model->get_cases_update($params);

        // Return
        return $this->true_json($res);
    }

    /**
     * Get all cases will update rabipoint
     *
     * @param array $params
     *
     * @internal param int $target_id
     * @internal param int $user_id
     * @internal param int $type
     *
     * @return array
     */
    public function create_rp($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('target_id', 'ユーザーID', 'integer');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('type', 'タイプ', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_rabipoint_model');

        $rabipoint_data = [
            'user_id' => $params['user_id'],
            'case' => $params['type']
        ];

        if (isset($params['target_id'])) {
            $rabipoint_data['target_id'] = $params['target_id'];
        }

        // Set query
        $res = $this->user_rabipoint_model->create_rabipoint($rabipoint_data);

        // Return
        return $this->true_json($res);
    }

    /**
     * Create rabipoint by admin
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $rabipoint
     *
     * @return array
     */
    public function create_rp_admin($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('RABIPOINT_CREATE');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('rabipoint', 'Rabipoint', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Only administrator can edit news
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load model
        $this->load->model('user_rabipoint_model');
        $this->load->model('point_model');

        $point = $this->point_model
            ->select('case, id')
            ->where('case', 'admin_creation')
            ->first();

        $rabipoint_data = [
            'user_id' => $params['user_id'],
            'type' => $point->case,
            'rabipoint' => $params['rabipoint'],
            'point_remain' => $params['rabipoint'],
            'point_id' => $point->id,
            'is_modal_shown' => 1,
            'target_id' => $this->operator()->_operator_id()
        ];

        // Set query
        $this->user_rabipoint_model->create($rabipoint_data);

        // Return
        return $this->true_json();
    }

    /**
     * Change status of modal
     *
     * @param array $params
     *
     * @internal param int $id
     *
     * @return array
     */
    public function change_modal_shown($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('id', 'ID', 'integer|required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('user_rabipoint_model');
        $this->user_rabipoint_model->update($params['id'], ['is_modal_shown' => 1]);

        // Return
        return $this->true_json();
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

        return $result;
    }
}
