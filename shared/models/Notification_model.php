<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Notification_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Notification_model extends APP_Model
{
    /**
     * Constants case notify
     */
    const NT_COMMENT = 'comment';
    const NT_GOOD = 'good';
    const NT_FEE_BASE = 'fee_base';
    const NT_ASK = 'ask';
    const NT_CONTRACT = 'contract';
    const NT_TEAM_BATTLE = 'team_battle'; // stage3
    const NT_NORMAL_QUEST = 'quest'; // stage3
    const NT_POINT_EXCHANGE_SUCCESS = 'point_exchange_success'; // stage3 - Point exchange success
    const NT_POINT_EXCHANGE_REJECT = 'point_exchange_reject'; // stage3 - Point exchange reject

    /**
     * Constants title for NT
     */
    const NT_TITLE_COMMENT = 'さんがコメントしました';
    const NT_TITLE_GOOD = 'さんがGoodしました';
    const NT_TITLE_FEE_BASE = 'さんが有料プランの申し込みを依頼しました';
    const NT_TITLE_ASK = 'さんがおねだりをしました';
    const NT_TITLE_CONTRACT = 'Plusの契約が更新停止';
    const NT_TITLE_TEAM_BATTLE = 'Title of team battle'; // stage3
    const NT_TITLE_NORMAL_QUEST = 'Title of normal quest'; // stage3
    const NT_TITLE_POINT_EXCHANGE_SUCCESS = 'ネットマイルへのポイント交換が完了しました'; // title point exchange successfully
    const NT_TITLE_POINT_EXCHANGE_REJECT = 'ネットマイルへのポイント交換が非承認となりました'; // title point exchange reject

    /**
     * Constants content for NT
     */
    const NT_CONTENT_COMMENT = '';
    const NT_CONTENT_GOOD = 'Goodが届きました';
    const NT_CONTENT_FEE_BASE = '有料プランで勉強したい！';
    const NT_CONTENT_ASK = 'コインの購入をお願いします';
    const NT_CONTENT_CONTRACT = '決済状況を確認してください';
    const NT_CONTENT_TEAM_BATTLE = 'Content of team battle'; // stage3
    const NT_CONTENT_NORMAL_QUEST = 'Content of normal quest'; // stage3
    const NT_CONTENT_POINT_EXCHANGE_SUCCESS = 'さんのポイント交換が完了しました'; // stage3 - content point exchange successfully
    const NT_CONTENT_POINT_EXCHANGE_REJECT = 'さんのポイント交換が非承認となりました'; // stage3 - content point exchange reject

    /**
     * Constants content for NT
     */
    const NT_DESTINATION_COMMENT = '/timeline/detail/';
    const NT_DESTINATION_GOOD = '/timeline/detail/';
    const NT_DESTINATION_FEE_BASE = '/pay_service/';
    const NT_DESTINATION_ASK = '/coin/';
    const NT_DESTINATION_CONTRACT = '/pay_service/';
    const NT_DESTINATION_TEAM_BATTLE = '/play/result_team_battle_notification/'; // stage3
    const NT_DESTINATION_NORMAL_QUEST = '/play/'; // stage3
    const NT_DESTINATION_POINT_EXCHANGE_SUCCESS = '/rabipoint/'; // stage - point exchange success
    const NT_DESTINATION_POINT_EXCHANGE_REJECT = '/rabipoint/'; // stage - point exchange reject

    /**
     * Constants content for transit destination
     * @var [type]
     */
    public $database_name = DB_MAIN;
    public $table_name = 'notification';
    public $primary_key = 'id';

    /**
     * fetch with group type
     *
     * @access public
     * @return User_group_model
     */
    public function with_group_family($target_id)
    {
        $sql = "(SELECT DISTINCT(user_group.user_id) as user_id
                FROM user_group
                JOIN (SELECT group_id
                    FROM user_group
                    JOIN `group` ON `group`.id = user_group.group_id AND `group`.`primary_type` = 'family'
                    WHERE user_id = $target_id) AS gr ON gr.group_id = user_group.group_id
                WHERE user_group.user_id != $target_id) AS family";

        return $this->join($sql, 'family.user_id = notification.user_id', 'left');
    }

    /**
     * fetch with profile info
     *
     * @access public
     * @return User_model
     */
    public function with_profile()
    {
        return $this->join('user_profile', 'notification.user_id = user_profile.user_id', 'left');
    }

    /**
     * fetch with user info
     *
     * @access public
     * @return User_model
     */
    public function with_user()
    {
        return $this->join('user', 'notification.user_id = user.id', 'left');
    }

    /**
     * Create notification
     * 
     * @access public
     * @return Notification_model
     */
    public function create_notification($attributes)
    {
        // Load user model
        $this->load->model('user_model');

        $user_id = $attributes['user_id'];
        if (in_array($attributes['type'], [self::NT_POINT_EXCHANGE_SUCCESS, self::NT_POINT_EXCHANGE_REJECT])) {
            $user_id = $attributes['student_id'];
        }

        $user = $this->user_model
            ->select('user.nickname')
            ->where('id', $user_id)
            ->first();

        $nickname = $user->nickname;
        $title = '';

        // Filter type
        switch ($attributes['type']) {
            case self::NT_COMMENT:
                $title = $nickname. self::NT_TITLE_COMMENT;
                if (!isset($attributes['content']) || empty($attributes['content'])) {
                    $attributes['content'] = "";
                }
                $attributes['destination'] = self::NT_DESTINATION_COMMENT. $attributes['timeline_id'];
                break;

            case self::NT_GOOD:
                $title = $nickname. self::NT_TITLE_GOOD;
                $attributes['content'] = self::NT_CONTENT_GOOD;
                $attributes['destination'] = self::NT_DESTINATION_GOOD. $attributes['timeline_id'];
                break;

            case self::NT_FEE_BASE:
                $title = $nickname. self::NT_TITLE_FEE_BASE;
                $attributes['content'] = self::NT_CONTENT_FEE_BASE;
                $attributes['destination'] = self::NT_DESTINATION_FEE_BASE. $attributes['user_id'];
                break;

            case self::NT_ASK:
                $title = $nickname. self::NT_TITLE_ASK;
                $attributes['content'] = self::NT_CONTENT_ASK;
                $attributes['destination'] = self::NT_DESTINATION_ASK. $attributes['user_id'];
                break;

            case self::NT_CONTRACT:
                $title = $nickname. self::NT_TITLE_CONTRACT;
                $attributes['content'] = self::NT_CONTENT_CONTRACT;
                $attributes['destination'] = self::NT_DESTINATION_CONTRACT. $attributes['user_id'];
                break;

            case self::NT_TEAM_BATTLE:
                $title = $nickname. self::NT_TITLE_TEAM_BATTLE;
                $attributes['content'] = self::NT_CONTENT_TEAM_BATTLE;
                $attributes['destination'] = self::NT_DESTINATION_TEAM_BATTLE. $attributes['battle_room_id'];
                break;

            // Case point exchange success
            case self::NT_POINT_EXCHANGE_SUCCESS:
                $title = self::NT_TITLE_POINT_EXCHANGE_SUCCESS;
                $attributes['content'] = $nickname. self::NT_CONTENT_POINT_EXCHANGE_SUCCESS;
                $attributes['destination'] = self::NT_DESTINATION_POINT_EXCHANGE_SUCCESS. $attributes['student_id'];
                break;

            // Case point exchange reject
            case self::NT_POINT_EXCHANGE_REJECT:
                $title = self::NT_TITLE_POINT_EXCHANGE_REJECT;
                $attributes['content'] = $nickname. self::NT_CONTENT_POINT_EXCHANGE_REJECT;
                $attributes['destination'] = self::NT_DESTINATION_POINT_EXCHANGE_REJECT. $attributes['student_id']; // link to student RP10
                break;
        }

        // setup params
        $data['user_id'] = $attributes['user_id'];
        $data['target_id'] = $attributes['target_id'];
        $data['type'] = $attributes['type'];
        $data['status'] = 0; // unread
        $data['extra_data'] = json_encode([
            'title' => $title,
            'content' => $attributes['content'],
            'destination' => $attributes['destination']
        ]);

        // Set query
        $res = $this->create($data, [
            'return' => TRUE
        ]);

        return $res;
    }
}
