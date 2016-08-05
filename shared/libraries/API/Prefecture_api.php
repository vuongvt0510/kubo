<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Prefecture_api
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Prefecture_api extends Base_api
{
    /**
     * Get list of pref API Spec PRE_020
     *
     * @param array $params
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('master_area_pref_model');

        // Set default offset, limit
        $this->_set_default($params);

        // Get list
        $res = $this
            ->master_area_pref_model
            ->calc_found_rows()
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->master_area_pref_model->found_rows()
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

