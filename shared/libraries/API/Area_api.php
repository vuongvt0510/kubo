<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Area_api
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Area_api extends Base_api
{
    /**
     * Get list of area API Spec AR-020
     *
     * @param array $params
     * @internal param int $pref_id
     * @internal param int $offset
     * @internal param int $limit
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('pref_id', '都道府県', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('master_area_model');

        // Get list
        $res = $this
            ->master_area_model
            ->calc_found_rows()
            ->where('pref_id', $params['pref_id'])
            ->order_by('pref_id', 'ASC')
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->master_area_model->found_rows()
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
            'name'  => $res->name
        ];
    }

}
