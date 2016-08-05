<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Ranking_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Ranking_api extends Base_api
{

    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Ranking_api_validator';


    /**
     * @param array $params
     *
     * @internal param string $score
     * @internal param string $type
     * @internal param int $target_id
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('score', 'スコア', 'required');
        $v->set_rules('type', 'タイプ', 'required');
        $v->set_rules('target_id', 'ターゲットID', 'required');
        $v->set_rules('user_id', 'ユーザーID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('ranking_model');

        // Create user group
        $this->ranking_model->create([
            'user_id' => $params['user_id'],
            'type' => $params['type'],
            'score' => $params['score'],
            'target_id' => $params['target_id']
        ]);

        // Return
        return $this->true_json();
    }

    /**
     * @param array $params
     *
     * @internal param string $score
     * @internal param string $type
     * @internal param int $target_id
     *
     * @return array
     */
    public function update($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('score', 'スコア', 'required');
        $v->set_rules('type', 'タイプ', 'required');
        $v->set_rules('target_id', 'ターゲットID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('ranking_model');

        // Get ranking
        $res = $this->ranking_model->find_by([
            'user_id' => $this->operator->_operator_id(),
            'type' => $params['type'],
            'target_id' => $params['target_id']
        ]);

        if(!$res) {
            // Return
            return $this->false_json(self::NOT_FOUND, '見つからないのランキング');
        }

        // Create user group
        $this->ranking_model->update($res->id, [
            'score' => $params['score'],
        ]);

        // Return
        return $this->true_json();
    }

    /**
     * @param array $params
     *
     * @internal param string $type
     * @internal param int $target_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('type', 'タイプ', 'required');
        $v->set_rules('target_id', 'ターゲットID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('ranking_model');

        // Get ranking
        $res = $this->ranking_model->find_by([
            'user_id' => $this->operator->_operator_id(),
            'type' => $params['type'],
            'target_id' => $params['target_id']
        ]);

        // Return
        return $this->true_json($this->build_responses($res, ['current_position', 'ranking_list']));
    }

    /**
     * @param array $params
     *
     * @internal param string $type
     * @internal param int $target_id
     *
     * @return array
     */
    public function get_ranking_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('type', 'タイプ', 'required');
        $v->set_rules('target_id', 'ターゲットID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset , limit
        $this->_set_default($params);

        // Load model
        $this->load->model('ranking_model');

        // Get ranking
        $res = $this->ranking_model
            ->calc_found_rows()
            ->select('user.id as user_id, user.nickname, user_profile.avatar_id')
            ->select('ranking.id, ranking.score, ranking.target_id, ranking.type')
            ->join('user', 'user.id = ranking.user_id')
            ->join('user_profile', 'user.id = user_profile.user_id')
            ->where([
                'ranking.type' => $params['type'],
                'ranking.target_id' => $params['target_id'],
                'user.status' => 'active',
                'user.deleted_at is null'
            ])
            ->order_by('score', 'desc')
            ->offset($params['offset'])
            ->limit($params['limit'])
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['user_detail', 'ranking_list']),
            'total' => (int) $this->ranking_model->found_rows()
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
    private function build_ranking_response($res, $options = []){

        if(!$res) {
            return [];
        }

        $ranking = [
            'id' => (int) $res->id,
            'user_id' => !empty($res->user_id) ? $res->user_id : null,
            'type' => $res->type,
            'target_id'  => !empty($res->target_id) ? $res->target_id : null,
            'score' => !empty($res->score) ? $res->score : null,
        ];

        $user = [];

        if(in_array('user_detail', $options)) {
            unset($ranking['user_id']);
            $user['user'] = [
                'id' => $res->user_id,
                'name' => $res->nickname,
                'avatar_id' => $res->avatar_id,
            ];
        }

        if(in_array('current_position', $options)) {

            $crr_position = $this->get_ranking_position([
                'type' => $res->type,
                'target_id' => $res->target_id,
                'score' => $res->score
            ]);

            $ranking['current_position'] = $crr_position['result'];
        }

        return array_merge($ranking, $user);
    }

    /**
     * Get rank of user Spec RK-010
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param string $ranking_type
     *
     * @return array
     */
    public function get_user_rank($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('ranking_type', 'ランキングタイプ', 'required|valid_ranking_type');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_model');

        $user = $this->user_model->find($params['user_id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        $res = $this->user_model->get_ranking_position($params['ranking_type'], $params['user_id']);

        return $this->true_json($this->build_responses($res));

    }

    /**
     * Get ranking position of video Spec RK-012
     *
     * @param array $params
     *
     * @internal param int $type
     * @internal param string $target_id
     * @internal param float $score
     *
     * @return array
     */
    public function get_ranking_position($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('target_id', 'ターゲットID', 'required|integer');
        $v->set_rules('type', 'ランキングタイプ', 'required|valid_ranking_type');
        $v->set_rules('score', 'スコア', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('ranking_model');

        // Get current position
        $this->ranking_model
            ->calc_found_rows()
            ->where([
                'type' => $params['type'],
                'target_id' => $params['target_id']
            ])
            ->limit(1)
            ->all();

        $total_number = $this->ranking_model->found_rows();

        $current_position = $this->ranking_model
            ->select('count(ranking.id) as position')
            ->where([
                'type' => $params['type'],
                'target_id' => $params['target_id']
            ])
            ->where('score < ', $params['score'])
            ->first();

        $current_position = $total_number - $current_position->position;

        return $this->true_json($current_position);

    }

    /**
     * Get list order by rank of score Spec RK-020
     *
     * @param array $params
     *
     * @internal param string $ranking_type
     * @internal param int $user_id
     * @internal param int $limit
     * @internal param int $offset
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('user_id', 'ユーザーID', 'integer|valid_user_id');
        $v->set_rules('ranking_type', 'ランキングタイプ', 'required|valid_ranking_type_user_id['.$params['user_id'].']|valid_ranking_type');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        $personal_rank = '';

        if ($params['ranking_type'] == 'personal' && isset($params['user_id'])) {
            $personal_rank = "JOIN (
                SELECT target_id
                FROM user_friend
                WHERE status = 'active' AND user_id = ".$params['user_id']."
                UNION SELECT ".$params['user_id']."
            ) AS friend
            ON friend.target_id = user_rank.id";
        }

        $rank_sql = "FIND_IN_SET(
            user.highest_score, (
                SELECT GROUP_CONCAT(user_rank.highest_score ORDER BY user_rank.highest_score DESC)
                FROM user AS user_rank $personal_rank WHERE user_rank.primary_type = 'student' AND user_rank.status = 'active'
            )
        ) AS rank";

        $join_type = $params['ranking_type'] == 'global' ? 'left' : null;

        $res = $this->user_model->select('user.id as user_id, user.highest_score, user_profile.avatar_id, user.nickname, user.login_id, fr.target_id')
            ->select($rank_sql)
            ->calc_found_rows()
            ->with_profile()
            ->join("(SELECT target_id FROM user_friend WHERE status = 'active' AND user_id =".$params['user_id']." UNION SELECT ".$params['user_id'].") AS fr", 'fr.target_id = user.id', $join_type)
            ->where('user.highest_score IS NOT NULL')
            ->where('user.primary_type', 'student')
            ->where('user.status', 'active')
            ->limit($params['limit'], $params['offset'])
            ->order_by('rank', 'asc')
            ->all();

        return $this->true_json([
            'items' => $this->build_responses($res, ['detail']),
            'total' => (int) $this->user_model->found_rows()
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

        if (empty($res)) {
            return [];
        }

        if(in_array('ranking_list', $options)) {
            return $this->build_ranking_response($res, $options);
        }

        $details = [];
        if(in_array('detail', $options)) {
            $details = [
                'nickname'  => $res->nickname,
                'login_id'  => $res->login_id,
                'is_friend'  => $res->target_id == null ? 0 : 1
            ];
        }

        return array_merge([
            'user_id' => (int) $res->user_id,
            'highest_score'  => (int) $res->highest_score,
            'rank'  => (int) $res->rank,
            'avatar_id'  => (int) $res->avatar_id
        ], $details);
    }
}
class Ranking_api_validator extends Base_api_validation
{

    /**
     * Validate ranking type
     *
     * @param string $ranking_type
     *
     * @return bool
     */
    function valid_ranking_type($ranking_type = null)
    {

        // Validate timeline type
        if (!in_array($ranking_type, ['score', 'global', 'personal'])) {
            $this->set_message('ランキングタイプ', '無効なランキングタイプです。');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate ranking type
     *
     * @param string $ranking_type
     *
     * @return bool
     */
    function valid_ranking_type_user_id($ranking_type = null, $user_id = null)
    {

        // Validate timeline type
        if (empty($user_id) && $ranking_type == 'personal') {
            $this->set_message('ユーザーID', 'ランキングタイプが個人の場合、ユーザーIDが必要です。');
            return FALSE;
        }

        return TRUE;
    }
}