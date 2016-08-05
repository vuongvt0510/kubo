<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Deck_category_api
 *
 * @property Deck_category_model deck_category_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Deck_category_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Deck_category_api_validator';

    /**
     * Deck category get list DC-010
     *
     * @param array $params
     * @internal param int $package_id
     * @internal param int $offset Default: 0
     * @internal param int $limit Default: 20
     *
     * @return array
     */
    public function get_list($params = [])
    {
        $v = $this->validator($params);
        $v->set_rules('package_id', 'パッケージID', 'integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load Model
        $this->load->model('deck_category_model');

        // Build default query
        $this->deck_category_model
            ->calc_found_rows()
            ->select('id, title, package_id')
            ->limit($params['limit'])
            ->offset($params['offset']);

        // Filter by package ID
        if (!empty($params['package_id'])) {
            $this->deck_category_model
                ->where('package_id', $params['package_id']);
        }

        $res = $this->deck_category_model->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->deck_category_model->found_rows()
        ]);
    }
}


/**
 * Class Deck_category_api_validator
 *
 * @property Deck_category_api $base
 */
class Deck_category_api_validator extends Base_api_validation
{

}
