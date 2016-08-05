<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Contact_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Contact_api extends Base_api
{

    /**
     * Send question to admin site Spec CU-010
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param int $type
     * @internal param string $question
     *
     * @return array
     */
    public function send_question($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'integer');
        $v->set_rules('type', 'お問い合わせ項目', 'integer|required');
        $v->set_rules('question', 'お問い合わせ内容', 'required|max_length[500]');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        if(isset($params['user_id'])) {
            $this->load->model('user_model');
            $user = $this->user_model->find($params['user_id']);
            if (!$user) {
                return $this->false_json(self::USER_NOT_FOUND);
            }
            $data['user_id'] = $params['user_id'];
        }

        $data['type'] = $params['type'];
        $data['question'] = $params['question'];

        // Load model
        $this->load->model('contact_model');
        $this->load->model('user_promotion_code_model');

        $res = $this->contact_model->create($data);
        // Get infomation user promotion code
        $promotion_code = $this->user_promotion_code_model->find_by([
            'user_id' => (int) $params['user_id']
        ]);
        // Get infomation to send mail
        $data_user = $this->build_user_response($user, ['user_detail']);
        $data = array_merge($data, $data_user);
        $data['forceclub'] = isset($promotion_code->code) ? $promotion_code->code : null;

        // Send mail user
        $this->send_mail('mails/contact_user', [
            'to' => $data['email'],
            'subject' => $this->subject_email['contact_user']
        ], [
            'name' => $data['nickname']
        ]);

        // Send mail system
        $this->send_mail('mails/contact_us', [
            'to' => 'school-tv.jp@e-ll.co.jp',
            'subject' => $this->subject_email['contact_us']
        ], $data);


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
            'max_power' => $res->max_power,
            'current_power' => $res->current_power
        ];
    }
}