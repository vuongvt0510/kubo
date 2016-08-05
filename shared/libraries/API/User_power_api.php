<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_power_api
 *
 * @property User_model user_model
 * @property User_power_model user_power_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_power_api extends Base_api
{

    /**
     * Get current power and max power of user Spec UP-010
     *
     * @param array $params
     *
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_power_model');

        // Call user
        $user = $this->user_model->find($params['user_id']);

        // Return error if user is not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $res = $this->user_power_model->find($params['user_id']);
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Create power for new user Spec UP-030
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param string $type
     *
     * @return array
     */
    public function update($params =[])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('type', 'タイプ', 'required');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default type
        if (!in_array($params['type'], ['trial', 'training', 'battle'])) {
            $params['type'] = 'trial';
        }

        // Load model
        $this->load->model('user_power_model');

        // Set query
        $user = $this->user_power_model
            ->select('max_power, current_power')
            ->where('user_id', $params['user_id'])
            ->first();

        // Power bonus after play
        $power_bonus = $params['type'] == 'battle' ? -1 : 10;

        // update when type is battle
        if ($params['type'] != 'trial') {
            if (!$user->current_power) {
                return $this->false_json(self::BAD_REQUEST);
            }

            $power_bonus_update = $user->current_power + $power_bonus;
            $power_bonus_update = $user->max_power < $power_bonus_update ? $user->max_power : $power_bonus_update;

            // Update user power
            $this->user_power_model ->update($params['user_id'], [
                'current_power' => $power_bonus_update
            ]);
        }

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
    public function build_response($res, $options = []){

        if (empty($res)) {
            return [];
        }

        // Max power >=0
        if ($res->max_power < 0) {
            $res->max_power = 0;
        }

        // Current power cant > max power
        if ($res->current_power > $res->max_power) {
            $res->current_power = $res->max_power;
        }

        return [
            'max_power' => $res->max_power,
            'current_power' => $res->current_power
        ];
    }
}
