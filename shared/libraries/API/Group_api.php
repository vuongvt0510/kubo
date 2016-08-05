<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Group_api
 * @property Group_model group_model
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Group_api extends Base_api
{

    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Group_api_validator';

    /**
     * Create group API Spec UG-030
     *
     * @param array $params
     * @internal param string $primary_type (family|friend)
     * @internal param string $group_name
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_name', 'グループ名', 'required|max_length[50]');
        $v->set_rules('primary_type', 'ユーザータイプ', 'required|valid_primary_type');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');
        $this->load->model('room_model');
        $this->load->model('room_user_model');

        // Operator current user
        $current_user_id = $this->operator()->id;

        /** @var object $res Create group */
        $res = $this->group_model->create([
            'primary_type' => $params['primary_type'],
            'name' => $params['group_name']
        ], [
            'return' => TRUE
        ]);

        // Return
        return $this->true_json([
            'group_id' => (int) $res->id
        ]);
    }

    /**
     * Get detail group API Spec UG-070
     *
     * @param array $params
     * @internal param int $group_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'グループID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');

        /** @var object $res Create group */
        $res = $this->group_model->find($params['group_id']);

        if (empty($res)) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Return
        return $this->true_json([
            'group_name' => $res->name,
            'type' => $res->primary_type,
            'created_at' => $res->created_at
        ]);
    }

    /**
     * Update name of group API Spec UG-060
     *
     * @param array $params
     * @internal param $group_id
     * @internal param $group_name
     *
     * @return array
     */
    public function update($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('group_id', 'グループID', 'required|valid_group_id');
        $v->set_rules('group_name', 'グループ名', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');

        /** @var object $res Update group */
        $res = $this->group_model->update($params['group_id'], [
            'name' => $params['group_name']
        ], [
            'return' => TRUE
        ]);

        // Return
        return $this->true_json([
            'group_id' => (int) $res->id
        ]);
    }

    /**
     * Search list group UG-090
     *
     * @param array $params
     * @internal param string $from_date filter
     * @internal param string $to_date filter
     * @internal param string $type of group (group|team)
     * @internal param int $limit
     * @internal param int $offset
     *
     * @return array
     */
    public function search_list($params = [])
    {
        // Set params default
        $params = array_merge([
            'from_date' => null,
            'to_date' => null
        ], $params);

        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('from_date', '掲載開始日', 'date_format');
        $v->set_rules('to_date', '検索終了日', 'date_format|date_larger['.$params['from_date'].']');
        $v->set_rules('primary_type', 'ユーザータイプ', 'valid_primary_type');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_model');

        // Prepare query
        $this->group_model
            ->calc_found_rows()
            ->select('id, primary_type, name, created_at');

        if (!empty($params['from_date'])) {
            $this->group_model->where('created_at >=', $params['from_date'] . ' 00:00:00');
        }

        if (!empty($params['to_date'])) {
            $this->group_model->where('created_at <=', $params['to_date'] . ' 23:59:59');
        }

        if (!empty($params['primary_type'])) {
            $this->group_model->where('primary_type', $params['primary_type']);
        }

        if (!empty($params['limit'])) {
            $this->group_model->limit((int) $params['limit'], isset($params['offset']) ? (int) $params['offset'] : 0);
        }

        $res = $this->group_model->all();

        // Return
        return $this->true_json([
            'total' => $this->group_model->found_rows(),
            'items' => $this->build_responses($res)
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
    protected function build_response($res, $options = []){

        if (empty($res)) {
            return [];
        }

        $result = get_object_vars($res);

        $result['id'] = isset($result['id'])? (int) $result['id'] : null;

        return $result;
    }

}

class Group_api_validator extends Base_api_validation {

    /**
     * Validate ended_date must to be larger than started_date
     *
     * @param string $ended_date
     * @param string $started_date
     *
     * @return bool
     */
    function date_larger($ended_date = NULL, $started_date) {

        // Ended date value is not required , only check when $ended_date has value
        if($ended_date && strtotime($ended_date) < strtotime($started_date)) {
            $this->set_message('date_larger', '終了日時は開始日時より後になるように設定してください。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate news status
     *
     * @param string $status
     *
     * @return bool
     */
    function valid_type($status) {

        if( !in_array($status, ['private', 'public'])) {
            $this->set_message('valid_status', 'ステータスは公開しないか公開するのどちらかです。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate news status
     *
     * @param string $status
     *
     * @return bool
     */
    function valid_status_for_list($status) {

        if( !in_array($status, ['private', 'public','all'])) {
            $this->set_message('valid_status_for_list', 'ステータスは公開しない、公開する、全てのどれかです。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate news status
     *
     * @param string $status
     *
     * @return bool
     */
    function valid_public_status($status) {

        if( !in_array($status, ['all', 'before_published', 'available', 'expired'])) {
            $this->set_message('valid_public_status', '公開ステータスは全て、発行前、利用可能、失効のいずれかです。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check permission to view private news
     *
     * @param string $status
     *
     * @return bool
     */
    function check_permission_status($status) {
        if($this->base->operator()->is_anonymous() == TRUE && ($status == 'private' || $status == 'all')) {
            $this->set_message('check_permission', '匿名では個人のニュースを閲覧できません。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check permission to view before-published and expired news
     *
     * @param $public_status
     * @return bool
     */
    function check_permission_public_status($public_status) {
        if($this->base->operator()->is_administrator() == FALSE && ($public_status == 'before_published' || $public_status == 'expired' || $public_status == 'all')) {
            $this->set_message('check_permission_public_status', '編集者のみが発行前と失効後のニュースを閲覧できます。');
            return FALSE;
        }

        return TRUE;
    }
}
