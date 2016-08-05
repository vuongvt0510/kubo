<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Invitation_api
 *
 * @version 0.1
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Invitation_api extends Base_api
{
    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Invitation_api_validator';

    /**
     * User all user is invited API Spec I-001
     *
     * @param array $params
     * @internal param datetime $from_date allow search user register after this day
     * @internal param datetime $to_date allow search user register before this day
     * @internal param string $login_id of inviter
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
        if(!$this->operator()->is_administrator()) {
            $this->user_model->available(TRUE);
        }

        if(!empty($params['from_date'])) {
            $this->user_model->where('user.created_at >=', business_date('Y-m-d H:i:s', strtotime($params['from_date'] . ' 00:00:00')) );
        }

        if(!empty($params['to_date'])) {
            $this->user_model->where('user.created_at <=', business_date('Y-m-d H:i:s', strtotime($params['to_date'] . ' 23:59:59')) );
        }

        // Fetch all records
        $this->user_model
            ->calc_found_rows()
            ->select('user.id, user.login_id, user.nickname, user.email, user.primary_type, user.created_at')
            ->select('user_profile.gender, user_profile.postalcode, user_profile.address, user_profile.phone')
            ->select('user_invite.id as user_invite_id, user_invite.login_id as from_login_id, user_invite.nickname as from_nickname, user_invite.primary_type as from_primary_type')
            ->with_profile()
            ->with_user_invite()
            ->where('user.invited_from_id IS NOT NULL')
            ->order_by('created_at', 'ASC');

        if (!empty($params['login_id'])) {
            $this->user_model->where('user.login_id', $params['login_id']);
        }

        $res = $this->user_model->all();
        // Return
        return $this->true_json([
            'total' => (int) $this->user_model->found_rows(),
            'users' => $this->build_responses($res),
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

        $result['id'] = isset($result['id'])? (int) $result['id'] : NULL;
        $result['user_invite_id'] = isset($result['user_invite_id'])? (int) $result['user_invite_id'] : NULL;


        return $result;
    }
}

class Invitation_api_validator extends Base_api_validation {

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
