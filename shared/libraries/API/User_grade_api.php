<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_grade_api
 *
 * @property User_model user_model
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_grade_api extends Base_api
{

    /**
     * User grade update information API Spec UGD-040
     *
     * @param array $params
     * @internal param int $id User ID
     * @internal param int $grade_id Grade ID
     *
     * @return array
     */
    public function update($params = [])
    {
        // Validate
        $params = array_map('trim', $params);
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'ユーザーID', 'required|is_natural_no_zero');
        $v->set_rules('grade_id', '学年ID', 'required|is_natural_no_zero|valid_grade_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');

        // If operator isn't admin, he can not update user detail who isn't available or other user.
        if (!$this->operator()->is_administrator()) {

            if ($this->operator()->id != $params['id']) {
                // Check same group and type is parent, can update grade of student
                if ($this->operator()->primary_type != 'parent') {
                    return $this->false_json(self::BAD_REQUEST);
                }
            }

            $this->user_model->available(TRUE);
        }

        // Get user info
        $user = $this->user_model->find($params['id']);

        // Return error if user does not exist
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        /** @var object $res Update grade */
        $res = $this->user_model->update_grade($user->id, $params['grade_id']);

        $response = [
            'user_id' => (int)$res->id
        ];

        if (!$this->operator()->is_administrator()) {
            $this->load->model('timeline_model');
            $trophy = $this->timeline_model->create_timeline('profile', 'trophy');

            $response['trophy'] = $trophy;

            $this->load->model('user_rabipoint_model');
            $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                'user_id' => $params['id'],
                'case' => 'register_profile',
                'modal_shown' => 1
            ]);
            $response['point'] = $res_rabipoint;
        }

        // Return
        return $this->true_json($response);
    }

}
