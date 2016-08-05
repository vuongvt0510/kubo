<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Promotion API
 *
 * @property User_model user_model
 *
 * @version 0.1
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Promotion_api extends Base_api
{
    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Promotion_api_validator';

    /**
     * User all user is register with promotion code API Spec P-001
     *
     * @param array $params
     * @internal param string $type of promotion (Default: forceclub)
     * @internal param string $except_type condition allow search all type without this except type
     * @internal param string $code of promotion
     * @internal param datetime $from_date allow search user register after this day
     * @internal param datetime $to_date allow search user register before this day
     * @internal param int $limit
     * @internal param int $offset
     *
     * @return array
     */
    public function search_users($params = [])
    {
        if( !isset($params['from_date']) ) {
            $params['from_date'] = NULL;
        }

        if( !isset($params['to_date']) ) {
            $params['to_date'] = NULL;
        }

        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('from_date', '検索開始日', 'date_format');
        $v->set_rules('to_date', '検索終了日', 'date_format|date_greater['.$params['from_date'].']');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('user_model');

        // If operator isn't admin, he can not get user detail who isn't available
        if (!$this->operator()->is_administrator()) {
            $this->user_model->available(TRUE);
        }

        if (isset($params['type']) && !empty($params['type'])) {
            $this->user_model->where('user_promotion_code.type', $params['type']);
        }

        if (isset($params['except_type']) && !empty($params['except_type'])) {
            $this->user_model->where('user_promotion_code.type != ', $params['type']);
        }

        if (isset($params['code']) && !empty($params['code'])) {
            $this->user_model->where('user_promotion_code.code', $params['code']);
        }

        if (!empty($params['from_date'])) {
            $this->user_model->where('user.created_at >=', business_date('Y-m-d H:i:s', strtotime($params['from_date'] . ' 00:00:00')));
        }

        if (!empty($params['to_date'])) {
            $this->user_model->where('user.created_at <=', business_date('Y-m-d H:i:s', strtotime($params['to_date'] . ' 23:59:59')));
        }

        if (!empty($params['limit'])) {
            $this->user_model->limit($params['limit'], isset($params['offset']) ? (int) $params['offset'] : 0);
        }

        // Fetch all records
        $res = $this->user_model
            ->calc_found_rows()
            ->select('user.id, user.login_id, user.nickname, user.email, user.primary_type, user.created_at')
            ->select('user_profile.gender, user_profile.postalcode, user_profile.address, user_profile.phone')
            ->select('user_promotion_code.type as promotion_type, user_promotion_code.code as promotion_code')
            ->with_profile()
            ->with_promotion_code()
            ->where('user_promotion_code.id IS NOT NULL')
            ->order_by('created_at', 'ASC')
            ->all();

        // Return
        return $this->true_json([
            'total' => (int) $this->user_model->found_rows(),
            'users' => $this->build_responses($res)
        ]);
    }

    /**
     * User with promotion code API
     *
     * @param array $params
     * @internal param int $user_id
     *
     * @param array $options
     *
     * @return array
     */
    public function get_detail($params = [], $options = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load user model
        $this->load->model('user_model');

        if (!$this->operator()->is_administrator()) {
            $this->user_model->available(TRUE);
        }

        $res = $this->user_model
            ->select('user.id, user.login_id, user.nickname')
            ->select('user_promotion_code.code, user_promotion_code.type')
            ->join('user_promotion_code', 'user_promotion_code.user_id = user.id', 'left')
            ->where('user.id', $params['user_id'])
            ->all($options);

        $return = [
            'user_id' => (int) $params['user_id']
        ];

        foreach($res AS $item) {
            if ($item->type == 'forceclub') {
                $return['promotion_code'] = $item->code;
            } else {
                $return['campaign_code'] = $item->code;
            }
        }

        // Return
        return $this->true_json($return);
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

        $result['id'] = isset($result['id'])? (int) $result['id'] : NULL;


        return $result;
    }
}

class Promotion_api_validator extends Base_api_validation {

    /**
     * Validate to date must to be greater than from date
     *
     * @param string $from_date
     * @param string $to_date
     *
     * @return bool
     */
    function date_greater($to_date = NULL, $from_date = NULL) {

        // Ended date value is not required , only check when $ended_date has value
        if($to_date && strtotime($to_date) < strtotime($from_date)) {
            $this->set_message('date_large', '今日以降の日付です。');
            return FALSE;
        }

        return TRUE;
    }
}
