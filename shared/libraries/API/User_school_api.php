<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_school_api
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_school_api extends Base_api
{

    /**
     * User school update information API Spec US-40
     *
     * @param array $params
     * @internal param int $school_id identity user id
     *
     * @return array
     */
    public function update($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('school_id', '学校ID', 'required|integer|valid_school_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $user_id = isset($params['user_id']) ? $params['user_id'] : $this->operator()->id;
        // Load model
        $this->load->model('user_model');

        // Return error if user is not exist
        if (!$this->user_model->available(TRUE)->find($user_id)) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Update school
        $res = $this->user_model->update_school($user_id, $params['school_id']);

        $response = [
            'user_id' => (int) $res->id
        ];

        if (!$this->operator()->is_administrator() && $this->operator()->primary_type == 'student') {
            $this->load->model('timeline_model');
            $trophy = $this->timeline_model->create_timeline('profile', 'trophy');
            $response['trophy'] = $trophy;
        }

        // Return
        return $this->true_json($response);
    }

}
