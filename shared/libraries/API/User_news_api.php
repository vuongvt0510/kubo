<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class New_api
 *
 * @property object html_purifier
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_news_api extends Base_api
{
    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'User_news_api_validator';

    /**
     * News get list
     *
     * @param array $params
     * @internal param $status (all|private|public)
     * @internal param $offset Default: 0
     * @internal param $limit Default: 20
     * @internal param $sort_by (id|started_at|title) Default id
     * @internal param $sort_position (asc|desc) Default desc
     * @internal param $public_date
     * @internal param $public_status (all|before_published|available|expired)
     *
     * @return array
     */
    public function get_list_unread($params = [])
    {
        // Validate params
        $v = $this->validator($params);
        $v->set_rules('public_date', '掲載開始日', 'datetime_format');
        $v->set_rules('status', 'ステータス', 'required|valid_status_for_list|check_permission_status');
        $v->set_rules('public_status', '公開ステータス', 'required|valid_public_status|check_permission_public_status');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            // Return errors
            return $v->error_json();
        }

        // Set default for params
        if (!isset($params['sort_by']) || !in_array($params['sort_by'], ['id', 'title', 'started_at'])) {
            $params['sort_by'] = 'id';
        }

        // Set default for param sort position
        if (!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'desc';
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Load model
        $this->load->model('news_model');

        $sql_user = $this->operator()->_operator_id() ? ' AND user_news.user_id = '.$this->operator()->_operator_id() : '';

        // Build default query
        $this->news_model
            ->calc_found_rows()
            ->select('news.id, news.title, news.status, news.started_at, news.ended_at, news.created_at')
            ->join('user_news', 'news.id = user_news.news_id'.$sql_user, 'left')
            ->where('user_news.is_read = 0 or user_news.is_read = ', null)
            ->order_by('news.' . $params['sort_by'], $params['sort_position'])
            ->limit($params['limit'])
            ->offset($params['offset']);

        // Filter by public date
        if (FALSE === empty($params['public_date']) && isset($params['public_date'])) {
            $this->news_model
                ->where('started_at <=', $params['public_date'])
                ->where('ended_at >=', $params['public_date'])
                ->where(['status' => 'public']);
        }

        // Filter by public status
        switch ($params['public_status']) {
            case 'before_published':
                $this->news_model->where('started_at >=', business_date('Y-m-d H:i:s'));
                break;

            case 'available':
                $this->news_model->with_available();
                break;

            case 'expired':
                $this->news_model->where('ended_at <=', business_date('Y-m-d H:i:s'));
                break;

            case 'all':
            default:
                // No process code
                break;
        }

        // Filter by news status
        switch ($params['status']) {
            case 'public':
                $this->news_model->with_public();
                break;

            case 'private':
                $this->news_model->with_private();
                break;

            case 'all':
            default:
                // No process code
                break;
        }

        // Return
        return $this->true_json([
            'items' => $this->build_responses($this->news_model->all(), ['public_status']),
            'total' => (int)$this->news_model->found_rows()
        ]);
    }

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
        $v = $this->validator($params);
        //$v->require_login();
        $v->set_rules('news_id', 'ニュースID', 'required|integer|valid_news_id');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer|valid_user_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');
        $this->load->model('user_news_model');

        // If operator isn't admin, he can not update user detail who isn't available or other user.
        if (!$this->operator()->is_administrator()) {

            if ($this->operator()->id != $params['user_id']) {
                return $this->false_json(self::BAD_REQUEST);
            }

            $this->user_model->available(TRUE);
        }

        // Get user info
        $user_news = $this->user_news_model
            ->for_update()
            ->select('user_news.id, user_news.is_read')
            ->join('user', 'user.id = user_news.user_id')
            ->join('news', 'news.id = user_news.news_id')
            ->where('news_id', $params['news_id'])
            ->where('user_id', $params['user_id'])
            ->first([
                'master' => TRUE
            ]);

        $result = [];
        // Start transaction
        $this->user_news_model->transaction(function () use (&$result, $user_news, $params) {
            if (!empty($user_news)) {
                // Update data
                $result = $this->user_news_model->update($user_news->id, $params);
            } else {
                // Insert data
                $result = $this->user_news_model->create($params);
            }
        });


        // Return error if user does not exist
        if (!$result) {
            return $this->false_json([]);
        }

        // Return
        return $this->true_json([
            'submit' => TRUE
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
    protected function build_response($res, $options = [])
    {

        if (!$res) {
            return [];
        }

        $details = [];
        if (in_array('detail', $options)) {
            $details = [
                'content' => $res->content,
                'status' => $res->status
            ];
        }

        if (in_array('public_status', $options)) {

            if (isset($res->started_at) && $res->started_at > business_date('Y-m-d H:i:s')) {
                $details['public_status'] = 'before_published';
            }

            if ($res->started_at <= business_date('Y-m-d H:i:s') && (business_date('Y-m-d H:i:s') <= $res->ended_at || $res->ended_at == null)) {
                $details['public_status'] = 'available';
            }

            if (isset($res->ended_at) && business_date('Y-m-d H:i:s') > $res->ended_at) {
                $details['public_status'] = 'expired';
            }
        }

        return array_merge([
            'id' => isset($res->id) ? (int)$res->id : NULL,
            'title' => $res->title,
            'created_at' => $res->created_at,
            'started_at' => $res->started_at,
            'ended_at' => $res->ended_at
        ], $details);
    }
}

class User_news_api_validator extends Base_api_validation
{

    /**
     * Validate ended_date must to be larger than started_date
     *
     * @param string $ended_date
     * @param string $started_date
     *
     * @return bool
     */
    function date_larger($ended_date = NULL, $started_date)
    {

        // Ended date value is not required , only check when $ended_date has value
        if ($ended_date && strtotime($ended_date) < strtotime($started_date)) {
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
    function valid_status($status)
    {

        if (!in_array($status, ['private', 'public'])) {
            $this->set_message('valid_status', 'ステータスは公開しないか公開するのどちらかです。');
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
    function valid_status_for_list($status)
    {

        if (!in_array($status, ['private', 'public', 'all'])) {
            $this->set_message('valid_status_for_list', 'ステータスは公開しない、公開する、全てのどれかです。');
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
    function valid_public_status($status)
    {

        if (!in_array($status, ['all', 'before_published', 'available', 'expired'])) {
            $this->set_message('valid_public_status', '公開ステータスは全て、発行前、利用可能、失効のいずれかです。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check permission to view private news
     *
     * @param string $status
     *
     * @return bool
     */
    function check_permission_status($status)
    {
        if ($this->base->operator()->is_anonymous() == TRUE && ($status == 'private' || $status == 'all')) {
            $this->set_message('check_permission', '匿名では個人のニュースを閲覧できません。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check permission to view before-published and expired news
     *
     * @param $public_status
     * @return bool
     */
    function check_permission_public_status($public_status)
    {
        if ($this->base->operator()->is_administrator() == FALSE && ($public_status == 'before_published' || $public_status == 'expired' || $public_status == 'all')) {
            $this->set_message('check_permission_public_status', '編集者のみが発行前と失効後のニュースを閲覧できます。');
            return FALSE;
        }

        return TRUE;
    }
}
