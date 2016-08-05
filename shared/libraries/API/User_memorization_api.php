<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_memorization_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_memorization_api extends Base_api
{

    /**
     * Create status of memorization for user Spec UM-020
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $memorization_id
     * @internal param int $status
     *
     * @return array
     */
    public function update_status($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'アカウントID', 'required|valid_user_id');
        $v->set_rules('memorization_id', 'Memorization ID', 'required');
        $v->set_rules('status', 'Status', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_memorization_model');

        $this->user_memorization_model->create([
            'user_id' => $params['user_id'],
            'memorization_id' => $params['memorization_id'],
            'status' => $params['status'] == 'not_checked' ? null : $params['status']
        ], [
            'mode' => 'replace'
        ]);

        return $this->true_json();

    }

    /**
     * Get list of user memorization Spec UM-010
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
        $v->set_rules('user_id', 'アカウントID', 'required|valid_user_id');
        $v->set_rules('stage_id', 'Stage ID', 'required');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        $this->load->model('memorization_model');

        $res = $this->memorization_model
            ->calc_found_rows()
            ->select('memorization.id, memorization.key, memorization.answer, memorization.question, memorization.sound_key, memorization.order, memorization.stage_id')
            ->select('schooltv_main.user_memorization.user_id, schooltv_main.user_memorization.status, schooltv_main.user_memorization.created_at')
            ->join('schooltv_main.user_memorization', 'schooltv_main.user_memorization.memorization_id = memorization.id AND schooltv_main.user_memorization.user_id = '.$params['user_id'], 'left')
            ->where('memorization.stage_id', $params['stage_id'])
            ->limit($params['limit'], $params['offset'])
            ->all();

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->memorization_model->found_rows()
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
            'id' => (int) $res->id,
            'key'  => $res->key,
            'answer'  => $res->answer,
            'question'  => $res->question,
            'sound_key'  => $res->sound_key,
            'order'  => $res->order,
            'stage_id'  => (int) $res->stage_id,
            'user_id' => (int) $res->user_id,
            'status' => $res->status,
            'created_at' => $res->created_at
        ];
    }
}