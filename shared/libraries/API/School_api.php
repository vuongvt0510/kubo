<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class School_api
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class School_api extends Base_api
{

    /**
     * Textbook search API Spec TB-010
     *
     * @param array $params
     * @internal param string $postal_code search keyword
     * @internal param string $school_name search keyword
     * @internal param int $area_id search keyword
     * @internal param int $pref_id search keyword
     * @internal param int $offset number of record
     * @internal param int $limit number of max record
     *
     * @return array
     */
    public function search($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        // Check required postal_code from link search/postal_code
        if(isset($params['postal_code'])) {
            $v->set_rules('postal_code', '郵便番号', 'required');
        }

        if(isset($params['area_id'])) {
            $v->set_rules('area_id', '市区町村', 'required');
        }

        if(isset($params['school_name'])) {
            $v->set_rules('school_name', '学校名', 'max_length[128]');
        }

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset , limit
        $this->_set_default($params);

        // Load model
        $this->load->model('master_school_model');
        $this->load->model('master_postalcode_model');

        // Process the postal_code
        if(isset($params['postal_code'])) {
            $params['postal_code'] = $this->master_school_model->sanitize_word($params['postal_code']);

            $this->master_school_model
                ->join('master_postalcode', 'master_school.postalcode_id = master_postalcode.id')
                // ->like('master_postalcode.postalcode', $params['postal_code'], 'after');
                // TODO: This time just fetch by equal condition
                ->where('master_postalcode.postalcode', $params['postal_code']);
        }

        // Process school_name
        if(isset($params['school_name'])) {
            // Add search condition
            $this->master_school_model->like('master_school.name', $params['school_name']);
        }

        // Process the area_id
        if(isset($params['area_id'])) {
            // Add search condition
            $this->master_school_model->where('master_school.area_id', $params['area_id']);
        }

        // Process the school type
        if(isset($params['type'])) {
            // Add search condition
            $this->master_school_model->where_in('master_school.type', $params['type']);
        }

        // Process the pref_id
        if(isset($params['pref_id'])) {
            // Add search condition
            $this->master_school_model
                ->join('master_area', 'master_school.area_id = master_area.id')
                ->where('master_area.pref_id', $params['pref_id']);
        }

        // Fetch School records
        $res = $this->master_school_model
            ->calc_found_rows()
            ->select('master_school.id, master_school.name, master_school.address')
            ->order_by('master_school.postalcode_id', 'ASC')
            //->offset($params['offset'])
            //->limit($params['limit'])
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->master_school_model->found_rows()
        ]);
    }

    /**
     * School search API Spec S-010
     *
     * @param array $params
     * @internal param int $id school id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', 'スクールID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('master_school_model');

        // Get school info
        $school = $this->master_school_model->find($params['id']);

        // Return error if school does not exist
        if (!$school) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Return
        return $this->true_json($this->build_responses($school));
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
            'id' => isset($res->id) ? (int) $res->id : null,
            'name'=> isset($res->name) ? $res->name : null,
            'address'=> isset($res->address) ? $res->address : null,
            'type'=> isset($res->type) ? $res->type : null
        ];
    }

}
