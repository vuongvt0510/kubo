<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_group_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_group_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_group';
    public $primary_key = ['user_id', 'group_id'];

    /**
     * Return owner belong to target group
     *
     * @param array $group_id User group id of array
     * @return User_group_model
     */
    function get_user_owner($group_id = []) {
        return $this->select('user.id, user.nickname, user.email, user.login_id')
            ->join('user', 'user.id = user_group.user_id')
            ->where_in('group_id', $group_id)
            ->where('role', 'owner')
            ->all(['master' => true]);
    }

    /**
     * Return list of group belong to user
     *
     * @param int $user_id
     * @return array
     */
    function get_user_group_id($user_id) {
        $res = [];
        $group = $this
            ->where('user_id', $user_id)
            ->all([
                'master' => TRUE
            ]);

        if ($group) {
            foreach($group as $k) {
                $res[] = $k->group_id;
            }
        }

        return $res;
    }

    /**
     * Return list of group belong to user according to primary type
     *
     * @param int $user_id
     * @param int $group_type
     *
     * @return array
     */
    function get_user_group_id_by_type($user_id, $group_type) {
        $res = [];
        $group = $this
            ->join('group', 'user_group.group_id = group.id')
            ->where('user_group.user_id', $user_id)
            ->where('group.primary_type', $group_type)
            ->all([
                'master' => TRUE
            ]);

        if ($group) {
            foreach($group as $k) {
                $res[] = $k->group_id;
            }
        }

        return $res;
    }

    /**
     * Return user member belong to target group
     *
     * @param array $group_id User group id of array
     * @return User_group_model
     */
    function get_user_member_group($group_id = []) {
        $res = $this->select('user.id')
            ->join('user', 'user.id = user_group.user_id')
            ->where_in('group_id', $group_id)
            ->where('role', 'member')
            ->all(['master' => true]);
        $data = [];
        if (!empty($res)){
            foreach ($res as $re) {
                $data[] = $re->id;
            }
        }
        return $data;
    }

    /**
     * Return all parents email of user
     *
     * @param int $user_id
     *
     * @return User_group_model
     */
    function get_all_parent_emails($user_id = null) {
        $sql = '(SELECT group_id FROM user_group WHERE user_id ='.$user_id.') AS gr';

        $res = $this
            ->select('distinct(user.email)')
            ->join($sql, 'gr.group_id = user_group.group_id')
            ->join('user', 'user.id = user_group.user_id')
            ->where('user.primary_type', 'parent')
            ->where('user.status', 'active')
            ->all();

        return $res;
    }
}
