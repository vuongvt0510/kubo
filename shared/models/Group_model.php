<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Group_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Group_model extends APP_Model
{
    /**
     * Constants
     */
    const GROUP_FRIEND_TYPE = 'friend';
    
    public $database_name = DB_MAIN;
    public $table_name = 'group';
    public $primary_key = 'id';

    /**
     * @var array Group name
     */
    public $_group_name = [
        'family' => 'の家族'
    ];

    /**
     * Return all members belong to target group
     *
     * @param int $group_id
     *
     * @return object
     */
    public function get_member($group_id)
    {
        return $this->select('group.id as group_id, group.name, user_group.role, user_group.user_id as id')
            ->join('user_group', 'user_group.group_id = group.id')
            ->join('user', 'user.id = user_group.user_id')
            ->where('group.id', (int) $group_id)
            ->where('user.deleted_at IS NULL')
            ->all(['master' => TRUE]);
    }

    /**
     * Return owner and admin of target group
     *
     * @param int $group_id
     */
    public function get_admin($group_id)
    {

    }

    /**
     * Return just one owner of target group
     *
     * @param int $group_id
     */
    public function get_owner($group_id)
    {

    }

    /**
     * Return group name and group id
     *
     * author : DiepHQ
     *
     * @param int $group_id
     *
     * @return object
     */
    public function get_group_name($group_id)
    {   
        return $this->select('id, name')
            ->where('id', (int) $group_id)
            ->first();
    }

    /**
     * Return all team of user
     *
     * @return User_group_model
     */
    public function with_team()
    {
        return $this->join('user_group', 'user_group.group_id = group.id')
            ->join('team_playing', 'team_playing.team_id = group.id', 'left');
    }

    /**
     * Get all user group
     *
     * @return User_group_model
     */
    public function get_user_group($attributes)
    {
        $res = $this->group_model
            ->select('group.id as group_id, group.name, group.created_at, user_group.role, group.primary_type as primary_type_group')
            ->select('user.id as user_id, user.login_id, user.email_verified, user.nickname, user.primary_type, user_profile.avatar_id, user.status')
            ->join('user_group', 'user_group.group_id = group.id')
            ->join('user_profile', 'user_group.user_id = user_profile.user_id')
            ->join('user', 'user.id = user_group.user_id')
            ->where('user.deleted_at IS NULL')
            ->where('group.id IN (SELECT group_id FROM user_group WHERE user_id = '.$attributes['user_id'].')')
            ->where('group.primary_type', $attributes['group_type'])
            ->all();

        /** @var array $list_groups information */
        $list_groups = [];

        foreach ($res as $record) {

            if (!isset($list_groups[$record->group_id])) {
                $list_groups[$record->group_id] = [
                    'id' => (int) $record->group_id,
                    'primary_type_group'=> $record->primary_type_group,
                    'name' => $record->name,
                    'created_at' => $record->created_at,
                    'owner' => [],
                    'members' => []
                ];
            }

            $user = [
                'user_id' => (int) $record->user_id,
                'login_id' => $record->login_id,
                'nickname' => $record->nickname,
                'primary_type' => $record->primary_type,
                'email_verified' => $record->email_verified,
                'avatar_id' => $record->avatar_id,
                'status' => $record->status
            ];

            if ($record->role == 'owner') {
                $list_groups[$record->group_id]['owner'] = $user;
            } else {
                $list_groups[$record->group_id]['members'][] = $user;
            }
        }
        return array_values($list_groups);
    }
}
