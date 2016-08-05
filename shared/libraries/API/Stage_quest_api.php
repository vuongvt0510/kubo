<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Stage_quest_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Stage_quest_api extends Base_api
{

    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Stage_quest_api_validator';

    /**
     * Get list timelines of user Spec SE-010
     *
     * @param array $params
     *
     * @internal param int $stage_id
     * @internal param int $group_id
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
        $v->set_rules('stage_id', 'Stage ID', 'required|integer');
        $v->set_rules('group_id', 'タイムラインタイプ', 'required|integer|valid_group_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('quest_model');

        $res = $this->quest_model
            ->calc_found_rows()
            ->select('schooltv_main.group_playing.id as round_id, schooltv_main.group_playing.status, schooltv_main.group_playing.created_at')
            ->select('quest.title, quest.description, quest.order, quest.id as quest_id')
            ->join('schooltv_main.group_playing', "schooltv_main.group_playing.target_id = quest.id AND schooltv_main.group_playing.type = 'quest' AND schooltv_main.group_playing.group_id = ".$params['group_id'], 'left')
            ->join ("(SELECT MAX(created_at) AS latest FROM schooltv_main.group_playing WHERE group_id = ".$params['group_id']." AND type = 'quest' GROUP BY (target_id)) AS latest_date", '1=1', 'left')
            ->where("(schooltv_main.group_playing.created_at IS NULL OR schooltv_main.group_playing.created_at = latest_date.latest)",null)
            ->where('quest.stage_id', $params['stage_id'])
            ->group_by('quest_id')
            ->order_by('quest.order asc')
            ->all();

        if (empty($res[0]->status)) {
            $this->load->model('group_playing_model');

            $round_id = $this->group_playing_model
                ->create([
                    'type' => 'quest',
                    'target_id' => $res[0]->quest_id,
                    'group_id' => $params['group_id'],
                    'status' => 'open'
                ]);

            $res[0]->status = 'open';
            $res[0]->round_id = $round_id;
        }

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->quest_model->found_rows()
        ]);
    }

    /**
     * Get quest detail Spec SE-020
     *
     * @param array $params
     * @internal param int $round_id
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('round_id', 'Round ID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_playing_model');

        $res = $this->group_playing_model
            ->select('group_playing.status, group_playing.group_id, group_playing.id as round_id, group_playing.created_at, schooltv_content.quest.id as quest_id')
            ->select('schooltv_content.quest.title, schooltv_content.quest.description, schooltv_content.quest.order, schooltv_content.quest.type, schooltv_content.quest.drill_type')
            ->join('schooltv_content.quest', 'schooltv_content.quest.id = group_playing.target_id')
            ->where('group_playing.id', $params['round_id'])
            ->where('group_playing.type', 'quest')
            ->order_by('group_playing.created_at desc')
            ->first();

        if ($res) {
            $started_time = $this->group_playing_model
                ->select('MAX(created_at)')
                ->where('type', 'quest')
                ->where('target_id', $res->quest_id)
                ->where('group_id', $res->group_id)
                ->where('status', 'progress')
                ->first();

            $res->started_time = $started_time->created_at;
        }

        return $this->true_json($this->build_responses($res));
    }

    /**
     * Get quest detail Spec SE-030
     *
     * @param array $params
     * @internal param int $quest_id
     * @internal param int $group_id
     * @internal param int $stage_id
     *
     * @return array
     */
    public function start_quest($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('quest_id', 'Quest ID', 'required|integer');
        $v->set_rules('stage_id', 'Stage ID', 'required|integer');
        $v->set_rules('group_id', 'Group ID', 'required|integer|valid_group_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_playing_model');

        $round_id = $this->group_playing_model
            ->create([
                'type' => 'quest',
                'target_id' => $params['quest_id'],
                'group_id' => $params['group_id'],
                'status' => 'progress'
            ]);

        // Load model
        $this->load->model('group_model');
        $this->load->model('user_group_playing_model');

        // Get group info
        $members = $this->group_model->get_member($params['group_id']);

        foreach ($members as $member) {
            $this->user_group_playing_model
                ->create([
                    'group_playing_id' => $round_id,
                    'user_id' => $member->id,
                    'status' => 'progress',
                    'target_id' => $params['stage_id']
                ]);
        }

        return $this->true_json($round_id);
    }

    /**
     * Check clear Spec SE-040
     *
     * @param array $params
     * @internal param int $stage_id
     * @internal param int $quest_id
     * @internal param int $group_id
     * @internal param int $status
     *
     * @return array
     */
    public function check_clear($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('deck_id', 'Deck ID', 'required|integer');
        $v->set_rules('group_id', 'Group ID', 'required|integer|valid_group_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('group_playing_model');

        $rounds = $this->group_playing_model
            ->select('group_playing.status')
            ->join('schooltv_content.quest', 'group_playing.target_id = schooltv_content.quest.id')
            ->join('schooltv_content.stage', 'schooltv_content.quest.stage_id = schooltv_content.stage.id')
            ->where('schooltv_content.stage.deck_id', $params['deck_id'])
            ->where('group_playing.group_id', $params['group_id'])
            ->where('group_playing.status', 'clear')
            ->where('group_playing.type', 'quest')
            ->all();

        $res = !empty($rounds) ? TRUE : FALSE;

        return $this->true_json($res);
    }
}

/**
 * Class Stage_quest_api_validator
 *
 * @property Stage_quest_api $base
 */
class Stage_quest_api_validator extends Base_api_validation
{

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
            $this->set_message('valid_group_id', 'Group ID must be friend');
            return FALSE;
        }

        return TRUE;
    }
}