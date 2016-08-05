<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class User_deck_api
 *
 * @property Deck_model deck_model
 * @property User_buying_model user_buying_model
 * @property User_model user_model
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_deck_api extends Base_api
{
    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'User_deck_api_validator';

    /**
     * Return purchased deck of User Spec UD-070
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param string $sort_by
     * @internal param string $sort_position
     * @internal param int $offset Default: 0
     * @internal param int $limit Default: 20
     * 
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset, limit
        $this->_set_default($params);

        // Param sort by
        if (empty($params['sort_by'])) {
            $params['sort_by'] = 'deck.order';
        }

        // Param sort by
        if (empty($params['sort_position']) OR !in_array(strtolower($params['sort_position']), ['desc', 'asc'])) {
            $params['sort_position'] = 'asc';
        }

        // Load model
        $this->load->model('deck_model');

        // Set default query
        $res = $this->deck_model
            ->calc_found_rows()
            ->select('deck.id, deck.name, deck.image_key, deck.category_id, deck_category.title, master_subject.short_name, master_subject.color, user_buying.created_at')
            ->with_buying()
            ->with_category()
            ->with_subject()
            ->limit($params['limit'])
            ->offset($params['offset'])
            ->order_by($params['sort_by'], $params['sort_position'])
            ->where('user_id', $params['user_id'])
            ->all();

        // Build response
        $result = [];
        foreach ($res AS $key => $value) {
            $decks = [];
            $flag = TRUE;

            foreach ($result AS $k1 => $v1) {
                if ($v1['category']['id'] == $value->category_id) {
                    $flag = FALSE;
                    break;
                }
            }

            if ($flag) {
                foreach ($res AS $k => $v) {
                    // Merge decks if deck have same category_id
                    if ($v->category_id == $value->category_id) {
                        $decks[] = [
                            'id' => (int) $v->id,
                            'name' => $v->name,
                            'image_key' => $v->image_key,
                            'buy' => [
                                'created_at' => $v->created_at
                            ],
                            'subject' => [
                                'short_name' => $v->short_name,
                                'color' => $v->color,
                            ]
                        ];
                    }
                }

                $result[] = [
                    'category' => [
                        'id' => $value->category_id,
                        'title' => $value->title,
                    ],
                    'decks' => $decks
                ];
            }
        }

        // Return
        return $this->true_json([
            'items' => $result,
            'total' => (int) $this->deck_model->found_rows()
        ]);
    }

    /**
     * Purchase deck API Spec UD-010
     *
     * @param array $params
     * @internal param int $user_id
     * @internal param int $deck_id
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('deck_id', 'デッキID', 'required|integer|valid_deck_id_exist|valid_deck_id_purchased['.$params['user_id'].']');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_buying_model');
        $this->load->model('user_model');
        $this->load->model('deck_model');

        // Get total coin of decks need to purchase
        $pay_coin = $this->deck_model
            ->select('SUM(deck.coin) as pay_coin')
            ->where('deck.id', $params['deck_id'])
            ->first()
            ->pay_coin;

        // Check current Coin of user
        $current_coin = $this->user_model
            ->select('current_coin')
            ->where('id', $params['user_id'])
            ->first();

        if ((int) $current_coin->current_coin >= (int) $pay_coin) {
            $this->user_model->update($params['user_id'], ['current_coin' => (int) ($current_coin->current_coin - $pay_coin)]);
        } else {
            return $this->false_json(self::BAD_REQUEST, "コインが足りないため入手できませんでした");
        }

        // load model user
        $this->load->model('user_group_model');

        // Insert buying
        $decks = $this->deck_model
            ->select('id, coin')
            ->where('id', $params['deck_id'])
            ->all();

        foreach ($decks AS $key => $deck) {
            $data = array(
                'user_id' => $params['user_id'],
                'type' => 'deck',
                'target_id' => $deck->id,
                'coin' => $deck->coin,
            );
            $this->user_buying_model->create($data);
        }

        // Get deck were purchase
        $res = $this->user_buying_model
            ->calc_found_rows()
            ->select('user_buying.user_id, user_buying.target_id as deck_id')
            ->where('user_id', $params['user_id'])
            ->where('target_id', $params['deck_id'])
            ->all();

        $this->load->model('timeline_model');
        $this->timeline_model->create_timeline('deck_download_timeline', 'timeline', $params['deck_id']);
        $trophy = $this->timeline_model->create_timeline('deck_download', 'trophy');

        $res_rabipoint = FALSE;

        if ($trophy == FALSE) {
            $trophy = $this->timeline_model->create_timeline('deck_downloads', 'trophy');

            $total_res = $this->user_buying_model
                ->select('id')
                ->where('user_id', $params['user_id'])
                ->where('type', 'deck')
                ->all();

            $total = empty($total_res) ? 0 : (int) count($total_res);

            if ($total) {
                $total = $total - ($total % 10);
            }

            if ($this->operator()->primary_type == 'student') {
                $this->load->model('user_rabipoint_model');
                $res_rabipoint = $this->user_rabipoint_model->create_rabipoint([
                    'user_id' => $params['user_id'],
                    'case' => 'download_decks',
                    'condition' => $total,
                    'modal_shown' => 1
                ]);
            }
        }

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->user_buying_model->found_rows(),
            'trophy' => $trophy,
            'point' => $res_rabipoint
        ]);
    }

    /**
     * Detail purchased deck API Spec UD-020
     * 
     * @param array $params
     * @internal param int user_id
     * @internal param int deck_id
     * 
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('deck_id', 'デッキID', 'required|integer');
        
        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('deck_model');

        // Query
        $res = $this->deck_model
            ->with_buying()
            ->select('user_buying.id, user_id, target_id, user_buying.coin')
            ->where('type', 'deck')
            ->where('user_id', $params['user_id'])
            ->where('target_id', $params['deck_id'])
            ->first();

        // Return
        return $this->true_json($res);
    }

    /**
     * Check members in a group owning deck API Spec UD-030
     *
     * @param  array  $params
     * @internal  param int $group_id
     * @internal  param int $deck_id
     *
     * @return array
     */
    public function check_members($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('group_id', 'Group ID', 'required|integer|valid_group_id');
        $v->set_rules('deck_id', 'デッキID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('user_group_model');

        // Query
        $res = $this->user_group_model
            ->calc_found_rows()
            ->select('user_group.user_id, user_buying.id, user.nickname, user_profile.avatar_id')
            ->join('user_buying', "user_buying.user_id = user_group.user_id AND user_buying.type = 'deck' AND user_buying.target_id = ".$params['deck_id'], 'left')
            ->join('user', 'user.id = user_group.user_id', 'left')
            ->join('user_profile', 'user_profile.user_id = user_group.user_id', 'left')
            ->where('user_group.group_id', $params['group_id'])
            ->where('user.primary_type', 'student')
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->user_group_model->found_rows()
        ]);
    }
}

/**
 * Class User_deck_api_validator
 *
 * @property User_deck_api $base
 */
class User_deck_api_validator extends Base_api_validation
{
    /**
     * Validate deck is purchased
     * 
     * @param int $deck_id of deck
     *
     * @return bool
     */
    public function valid_deck_id_exist($deck_id)
    {
        // Load model
        $this->base->load->model('deck_model');

        // Check exist deck
        $is_exist = $this->base->deck_model->find($deck_id);

        if (!$is_exist) {
            $this->set_message('デッキID', 'デッキIDが存在しません');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate deck is purchased
     * 
     * @param int $deck_id of deck
     * @param int $user_id
     *
     * @return bool
     */
    public function valid_deck_id_purchased($deck_id, $user_id)
    {
        // Load model
        $this->base->load->model('user_buying_model');

        // Check deck were purchased
        $is_purchased = $this->base->user_buying_model
            ->find_by([
                'user_id' => $user_id,
                'target_id' => $deck_id,
                'type' => 'deck'
            ]);

        if ($is_purchased) {
            $this->set_message('デッキID', 'このデッキは既に購入済です');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate group id
     *
     * @param  $group_id
     * @return bool
     */
    public function valid_group_id($group_id)
    {
        // Load model
        $this->base->load->model('group_model');

        // Check exist deck
        $res = $this->base->group_model->find($group_id);


        if (!$res) {
            $this->set_message('valid_group_id', 'Group ID is not exist');
            return FALSE;
        }

        if ($res->primary_type == 'family') {
            $this->set_message('valid_group_id', 'Group Type must be friend');
            return FALSE;
        }

        return TRUE;
    }
}
