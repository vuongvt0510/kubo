<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';
require_once SHAREDPATH . 'libraries/STV_Action_type.php';

/**
 * Class User_action_api
 *
 * Do not action user has value of deleted_by or status is not active
 */
class User_action_api extends Base_api
{
    use STV_Action_type;

    /**
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param string $object
     * @internal param string $action
     */
    public function add_action($params = [])
    {
        /**
         * Upload information to cloud search
         */
    }

    /**
     * Get timeline of target user from cloud search
     *
     * @param array $params
     *
     * @internal param int $user_id
     * @internal param string $object
     */
    public function get_timeline($params = [])
    {
    }
}
