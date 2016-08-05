<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Deck_package_api
 *
 * @property Deck_package_model deck_package_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Deck_package_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Deck_package_api_validator';

    /**
     * Deck package get list DP-010
     *
     * @param array $params
     * @internal param int $grade_id
     * @internal param int $subject_id
     * @internal param int $offset Default: 0
     * @internal param int $limit Default: 20
     *
     * @return array
     */
    public function get_list($params = [])
    {
        $v = $this->validator($params);
        $v->set_rules('subject_id', '教科ID', 'integer');
        $v->set_rules('grade_id', '学年ID', 'integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load Model
        $this->load->model('deck_package_model');

        // Build default query
        $this->deck_package_model
            ->calc_found_rows()
            ->select('id, title')
            ->limit($params['limit'], $params['offset']);

        // Filter by subject ID
        if (!empty($params['subject_id'])) {
            $this->deck_package_model
                ->where('subject_id', $params['subject_id']);
        }

        // Filter by grade ID
        if (!empty($params['grade_id'])) {
            $this->deck_package_model
                ->where('grade_id', $params['grade_id']);
        }

        $res = $this->deck_package_model->all();
        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->deck_package_model->found_rows()
        ]);
    }
}


/**
 * Class Deck_package_api_validator
 *
 * @property Deck_package_api $base
 */
class Deck_package_api_validator extends Base_api_validation
{

}