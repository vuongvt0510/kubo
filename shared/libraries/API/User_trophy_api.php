<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_trophy_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_trophy_api extends Base_api
{

    /**
     * Get list trophies which user gained UT-010
     *
     * @param array $params
     *
     * @internal param int $user_id
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
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Set default offset, limit
        $this->_set_default($params);

        $this->load->model('user_trophy_model');

        $this->user_trophy_model
            ->calc_found_rows()
            ->select('user_trophy.created_at, user_trophy.trophy_id, trophy.image_key, trophy.target_id, trophy.category, trophy.type, trophy.name, trophy.description')
            ->join('trophy', 'trophy.id = user_trophy.trophy_id')
            ->where('user_trophy.user_id', $params['user_id'])
            ->order_by('user_trophy.created_at', 'desc')
            ->limit($params['limit'])
            ->offset($params['offset']);

        // Progress trophies only show on operator page
        if($this->operator()->id != $params['user_id']) {
            $this->user_trophy_model->where('trophy.type !=', 'progress');
        }

        $res = $this->user_trophy_model->all();

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->user_trophy_model->found_rows()
        ]);
    }

    /**
     * Get detail a trophy which user gained UT-020
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $trophy_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('trophy_id', 'トロフィーID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Return error if trophy is not exist
        $this->load->model('trophy_model');
        $trophy = $this->trophy_model->find($params['trophy_id']);
        if (!$trophy) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Progress trophies only show on operator page
        $this->load->model('trophy_model');

        $trophy = $this->trophy_model->find($params['trophy_id']);
        if($this->operator()->id != $params['user_id'] && $trophy->type == 'progress') {
            return $this->false_json(self::NOT_FOUND);
        }

        $this->load->model('user_trophy_model');

        $res = $this->user_trophy_model
            ->calc_found_rows()
            ->select('user_trophy.created_at, user_trophy.trophy_id, trophy.image_key, trophy.target_id, trophy.category, trophy.type, trophy.name, trophy.description')
            ->join('trophy', 'trophy.id = user_trophy.trophy_id')
            ->where('user_trophy.user_id', $params['user_id'])
            ->where('user_trophy.trophy_id', $params['trophy_id'])
            ->first();

        return $this->true_json($this->build_responses($res));
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
            'trophy_id'  => (int) $res->trophy_id,
            'created_at'  => $res->created_at,
            'image_key' => $res->image_key,
            'category' => $res->category,
            'target_id' => (int) $res->target_id,
            'name' => $res->name,
            'type' => $res->type,
            'description' => $res->description
        ];
    }
}