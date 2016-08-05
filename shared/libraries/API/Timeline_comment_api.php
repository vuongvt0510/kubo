<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Timeline_comment_api
 *
 * @property
 * @property object config
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Timeline_comment_api extends Base_api
{

    /**
     * Create a new comment for an activities Spec TC-010
     *
     * @param array $params
     *
     * @internal param int $timeline_id
     * @internal param int $content
     * @internal param int $user_id
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('timeline_id', 'タイムラインID', 'required|integer');
        $v->set_rules('user_id', 'ユーザーID', 'required|integer');
        $v->set_rules('target_id', 'ユーザーID', 'required|integer');
        $v->set_rules('content', 'コメント内容', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if user is not exist
        $this->load->model('user_model');
        $user = $this->user_model->find($params['user_id']);
        if (!$user) {
            return $this->false_json(self::USER_NOT_FOUND);
        }

        // Load model
        $this->load->model('timeline_model');
        $this->load->model('notification_model');
        $this->load->model('timeline_comment_model');

        $timeline = $this->timeline_model->find($params['timeline_id']);

        // Return error if timeline is not exist
        if (!$timeline) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Create timeline comment
        $this->timeline_comment_model->create([
            'timeline_id' => $params['timeline_id'],
            'user_id' => $params['user_id'],
            'content' => $params['content']
        ]);

        // Create notification type is good
        if  ($params['user_id'] != $params['target_id']) {
            $this->notification_model->create_notification($params);
        }

        $res = $this->timeline_comment_model
            ->select('COUNT(id) AS total')
            ->where('timeline_id', $params['timeline_id'])
            ->first();

        return $this->true_json(['total' => $res->total]);
    }

    /**
     * Get list comments of timeline Spec TC-020
     *
     * @param array $params
     *
     * @internal param int $timeline_id
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
        $v->set_rules('timeline_id', 'タイムラインID', 'required|integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('time_request', 'Time when request', 'datetime_format');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Return error if timeline is not exist
        $this->load->model('timeline_model');

        $timeline = $this->timeline_model->find($params['timeline_id']);

        if (!$timeline) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Set default offset, limit
        $this->_set_default($params);

        $this->load->model('timeline_comment_model');

        // Prepare sql
        $this->timeline_comment_model
            ->calc_found_rows()
            ->select('timeline_comment.id, timeline_comment.timeline_id, timeline_comment.content, timeline_comment.user_id, timeline_comment.created_at')
            ->select('user.primary_type, user.nickname, user.login_id, user_profile.avatar_id')
            ->with_profile()
            ->with_user()
            ->where('timeline_comment.timeline_id', $params['timeline_id'])
            ->order_by('timeline_comment.created_at', 'desc');

        if (!isset($params['type']) || !isset($params['time_request'])) {

            $this->timeline_comment_model
                ->limit($params['limit']);

        } else {

            switch ($params['type']) {
                case 'new':
                    $this->timeline_comment_model->where('timeline_comment.created_at > ', $params['time_request']);
                    break;

                case 'old':
                    $this->timeline_comment_model
                        ->where('timeline_comment.created_at < ', $params['time_request'])
                        ->limit($params['limit']);

                    break;
            }
        }

        $res = $this->timeline_comment_model->all();

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->timeline_comment_model->found_rows()
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

        return [
            'id' => (int) $res->id,
            'content'  => $res->content,
            'timeline_id'  => (int) $res->timeline_id,
            'user_id'  => (int) $res->user_id,
            'created_at' => $res->created_at,
            'nickname' => $res->nickname,
            'login_id' => $res->login_id,
            'avatar_id' => (int) $res->avatar_id,
            'primary_type' => $res->primary_type
        ];
    }
}