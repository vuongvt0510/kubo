<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Grade_api
 *
 * @property Master_grade_model master_grade_model
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Grade_api extends Base_api
{
    /**
     * Get list of grade API Spec GD-010
     *
     * @param array $params
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('master_grade_model');

        // Get list
        $res = $this
            ->master_grade_model
            ->calc_found_rows()
            ->order_by('id', 'asc')
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->master_grade_model->found_rows()
        ]);
    }

    /**
     * Get Grade detail
     *
     * @param array $params
     * @internal param $id of grade
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate params
        $v = $this->validator($params);
        $v->set_rules('id', 'ニュースID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('master_grade_model');

        // Get news detail
        $res = $this->master_grade_model
            ->select('id, name')
            ->find($params['id']);

        if (empty($res)) {
            return $this->false_json(self::NOT_FOUND);
        }

        return $this->true_json($this->build_response($res));
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
            'name'  => $res->name
        ];
    }

}

