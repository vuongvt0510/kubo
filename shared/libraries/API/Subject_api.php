<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Subject_api
 *
 * @property Master_subject_model master_subject_model
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Subject_api extends Base_api
{

    /* array */
    private $subject_order = [
        'english' => 0,
        'math' => 1,
        'japanese-language' => 2,
        'science' => 3,
        'geography' => 4,
        'history' => 5,
        'civics' => 6
    ];

    /**
     * Get list of subject API Spec TB-030
     *
     * @param array $params
     * @internal param int $grade_id Grade ID
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('grade_id', '学年ID', 'required|integer|valid_grade_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('master_subject_model');

        // Get list
        $res = $this
            ->master_subject_model
            ->calc_found_rows()
            ->where('grade_id', (int) $params['grade_id'])
            ->where('display_flag', 1) // always get the subject with display_flag = 1
            ->order_by('id', 'asc')
            ->all();

        // Sort subject
        $subject = [];
        if($res) {
            foreach($res as $k) {
                $subject[$this->subject_order[$k->type]] = $k;
            }
            ksort($subject);
        }

        // Return
        return $this->true_json([
            'items' => $this->build_responses($subject),
            'total' => (int) $this->master_subject_model->found_rows()
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

        if(!$res) {
            return [];
        }

        return [
            'id' => (int) $res->id,
            'type' => !empty($res->type) ? $res->type : null,
            'name' => $res->name,
            'short_name' => !empty($res->short_name) ? $res->short_name : null,
            'rubi' => !empty($res->rubi) ? $res->rubi : null,
            'color' => !empty($res->color) ? $res->color : null
        ];
    }

}
