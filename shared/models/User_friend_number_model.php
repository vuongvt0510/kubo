<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_friend_number_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_friend_number_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_friend_number';
    public $primary_key = 'number';

    /**
     * @param array $user_id of user
     *
     * @return bool|object
     */
    public function update_get_number($user_id)
    {

        /** @var string $expired */
        $expired = business_date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $friend_number_data = [
            'user_id' => $user_id,
            'register_at' => business_date('Y-m-d H:i:s')
        ];

        // Create new friend number for user
        $this->where('register_at', NULL)
            ->or_where("register_at < '$expired'", NULL, FALSE )
            ->order_by('number', 'RANDOM')
            ->limit(1)
            ->update_all($friend_number_data);

        // Get friend number
        $res = $this->find_by($friend_number_data);

        return $res;
    }
}