<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_netmile_api
 *
 * @property User_netmile_model user_netmile_model
 * @property Netmile_exchange netmile_exchange
 *
 * @version $id$
 *
 * @copyright 2016 - Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author trannguyen <nguyentc@nal.vn>
 */
class User_netmile_api extends Base_api
{
    /**
     * Get detail netmile user - NM-010
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
        $this->load->model('user_netmile_model');

        // Get query
        $res = $this->user_netmile_model
            ->select('user_id, netmile_user_id, enc_user_id, created_at')
            ->where('user_id', $params['user_id'])
            ->first();

        // Return
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Update netmile user - NM-020
     *
     * @param array $params
     * @internal param string $enc_user_id encrypt netmile user id
     * @internal param int $user_id of user
     * @internal param string $token
     *
     * @return array
     */
    public function update($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('enc_user_id', '暗号化されたNetMile会員ID', 'required');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer|valid_user_id');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load library and model
        $this->load->library('netmile_exchange');
        $this->load->library('session');
        $this->load->model('user_netmile_model');

        if (empty($params['token']) || $params['token'] != $this->session->userdata('request_netmile_token')) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Check netmile user is exist
        $netmile_user = $this->netmile_exchange->check_enc_user_id_exist([
            'enc_user_id' => $params['enc_user_id']
        ]);

        if (empty($netmile_user['userId'])) {
            return $this->false_json(self::BAD_REQUEST, $netmile_user['message']);
        }

        // Save uid of user
        $this->user_netmile_model->create([
            'user_id' => $params['user_id'],
            'netmile_user_id' => $netmile_user['userId'],
            'enc_user_id' => $params['enc_user_id']
        ], [
            'mode' => 'replace'
        ]);

        $this->session->unset_userdata('request_netmile_token');

        // Return
        return $this->true_json();
    }
}
