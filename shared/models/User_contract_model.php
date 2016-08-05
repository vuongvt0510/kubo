<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_contract_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_contract_model extends APP_Paranoid_model
{
    /**
     * Constants status
     */ 
    const UC_STATUS_FREE = 'free';
    const UC_STATUS_PENDING = 'pending';
    const UC_STATUS_CANCELING = 'canceling';
    const UC_STATUS_NOT_CONTRACT = 'not_contract';
    const UC_STATUS_UNDER_CONTRACT = 'under_contract';

    const UC_LABEL_ADMIN_FREE = '未契約';
    const UC_LABEL_ADMIN_PENDING = '更新停止';
    const UC_LABEL_ADMIN_CANCELING = '解約中';
    const UC_LABEL_ADMIN_NOT_CONTRACT = '解約済';
    const UC_LABEL_ADMIN_UNDER_CONTRACT = '契約中';

    public $database_name = DB_MAIN;
    public $table_name = 'user_contract';
    public $primary_key = ['user_id'];

    public function get_list_status_admin()
    {
        return [
            self::UC_STATUS_FREE => self::UC_LABEL_ADMIN_FREE,
            self::UC_STATUS_PENDING => self::UC_LABEL_ADMIN_PENDING,
            self::UC_STATUS_CANCELING => self::UC_LABEL_ADMIN_CANCELING,
            self::UC_STATUS_NOT_CONTRACT => self::UC_LABEL_ADMIN_NOT_CONTRACT,
            self::UC_STATUS_UNDER_CONTRACT => self::UC_LABEL_ADMIN_UNDER_CONTRACT
        ];
    }

    /**
     * fetch with user info
     *
     * @access public
     * @return User_contract_model
     */
    public function with_user()
    {
        return $this->join('user', 'user.id = user_contract.user_id');
    }

    /**
     * fetch with purchase
     *
     * @access public
     * @return User_model
     */
    public function with_purchase()
    {
        return $this->join('purchase', 'purchase.id = user_contract.purchase_id', 'LEFT');
    }
}
