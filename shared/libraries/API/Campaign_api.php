<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Campaign_api
 *
 * @property object html_purifier
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Campaign_api extends Base_api
{
    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Campaign_api_validator';

    /**
     * News get list API Spec C-010
     *
     * @param array $params
     * @internal param $offset Default: 0
     * @internal param $limit Default: 20
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate params
        $v = $this->validator($params);
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            // Return errors
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('campaign_model');

        // Build default query
        $this->campaign_model
            ->calc_found_rows()
            ->select('id, name, code, status, started_at, ended_at, created_at')
            ->order_by('id', 'desc')
            ->limit($params['limit'])
            ->offset($params['offset']);

        // Return
        return $this->true_json([
            'items' => $this->build_responses($this->campaign_model->all()),
            'total' => (int) $this->campaign_model->found_rows()
        ]);
    }

    /**
     * Get campaign detail API Spec C-020
     *
     * @param array $params
     * @internal param $id of campaign
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
        $this->load->model('campaign_model');

        // Only administrator can view campaign detail
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Get news detail
        $res = $this->campaign_model->find($params['id']);

        if (empty($res)) {
            return $this->false_json(self::NOT_FOUND);
        }

        return $this->true_json($this->build_responses($res));
    }

    /**
     * Create campaign API Spec C-110
     *
     * @param array $params
     * @internal param string $name of campaign
     * @internal param string $code of campaign
     * @internal param datetime $started_at of campaign
     * @internal param datetime $ended_at of campaign
     * @internal param string $status of campaign (active|suspended)
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('CAMPAIGN_CREATE');
        $v->set_rules('name', 'キャンペーン名', 'required|max_length[110]');
        $v->set_rules('code', 'キャンペーンコード', 'required|alpha_numeric|max_length[110]|check_duplicate');
        $v->set_rules('started_at', 'キャンペーン期間（開始）', 'required|datetime_format');
        $v->set_rules('ended_at', '掲載終了日', 'datetime_format|date_larger['.$params['started_at'].']');
        $v->set_rules('status', '掲載ステータス', 'required|valid_status');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Only administrator can create news
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load model
        $this->load->model('campaign_model');

        /** @var object $res Create user */
        $res = $this->campaign_model->create($params, [
            'return' => TRUE
        ]);

        // Return
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Edit campaign API Spec N-111
     *
     * @param array $params
     * @internal param $id of campaign
     * @internal param $name of campaign
     * @internal param $code of campaign
     * @internal param $started_at of campaign
     * @internal param $ended_at of campaign
     * @internal param $status of campaign (active|suspended)
     *
     * @return array
     */
    public function edit($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('CAMPAIGN_UPDATE');
        $v->set_rules('id', 'ニュースID', 'required|integer');
        $v->set_rules('name', 'キャンペーン名', 'required|max_length[110]');
        $v->set_rules('code', 'キャンペーンコード', 'required|alpha_numeric|max_length[110]|check_duplicate_for_edit['.$params['id'].']');
        $v->set_rules('started_at', '掲載開始日', 'required|datetime_format');
        $v->set_rules('ended_at', '掲載終了日', 'datetime_format|date_larger['.$params['started_at'].']');
        $v->set_rules('status', 'ステータス', 'required|valid_status');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Only administrator can edit news
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load model
        $this->load->model('campaign_model');

        /** @var object $res Update news */
        $res = $this->campaign_model->update($params['id'], $params, [
            'return' => TRUE
        ]);

        // Return
        return $this->true_json($this->build_responses($res));
    }

    /**
     * Campaign delete API Spec C-100
     *
     * @param array $params
     * @internal param $id of campaign
     *
     * @return array
     */
    public function delete($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('CAMPAIGN_DELETE');
        $v->set_rules('id', 'ニュースID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Only administrator can delete news
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load model
        $this->load->model('campaign_model');

        /** @var object $news Find news */
        $campaign = $this->campaign_model->find($params['id']);

        // If news is not exist return error
        if (!$campaign) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Remove news
        $this->campaign_model->destroy($campaign->id);

        return $this->true_json();
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    protected function build_response($res, $options = []){

        if(!$res) {
            return [];
        }

        return [
            'id' => isset($res->id) ? (int) $res->id : NULL,
            'name'  => $res->name,
            'code'  => $res->code,
            'status'  => $res->status,
            'created_at'  => $res->created_at,
            'started_at' => $res->started_at,
            'ended_at' => $res->ended_at
        ];
    }
}

class Campaign_api_validator extends Base_api_validation {

    /**
     * Validate ended_date must to be larger than started_date
     *
     * @param string $ended_date
     * @param string $started_date
     *
     * @return bool
     */
    function date_larger($ended_date = NULL, $started_date) {

        // Ended date value is not required , only check when $ended_date has value
        if($ended_date && strtotime($ended_date) < strtotime($started_date)) {
            $this->set_message('date_larger', '終了日は開始日よりも後です。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate news status
     *
     * @param string $status
     *
     * @return bool
     */
    function valid_status($status) {

        if( !in_array($status, ['active', 'suspended'])) {
            $this->set_message('valid_status', 'ステータスは公開しないか公開するのどちらかです。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate duplicate code
     *
     * @param string $status
     *
     * @return bool
     */
    function check_duplicate($code)
    {
        // Load model
        $this->base->load->model('campaign_model');

        $campaign = $this->base->campaign_model->where([
            'code' => $code
        ])->first();

        if ($campaign) {
            $this->set_message('check_duplicate', 'キャンペーンコードが重複しています。');
            return FALSE;
        }
    }

    /**
     * Validate duplicate code for edit form
     *
     * @param string $status
     *
     * @return bool
     */
    function check_duplicate_for_edit($code, $id)
    {
        // Load model
        $this->base->load->model('campaign_model');

        $campaign = $this->base->campaign_model->where([
            'id !=' => $id,
            'code' => $code
        ])->first();

        if ($campaign) {
            $this->set_message('check_duplicate_for_edit', 'キャンペーンコードが重複しています。');
            return FALSE;
        }
    }
}
