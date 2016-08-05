<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_contract_history_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_contract_history_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_contract_history';
    public $primary_key = 'id';

    const TYPE_FIRST_REGISTER = 'first_register';
    const TYPE_AUTO_PURCHASE_AT_26TH = 'auto_purchase_26th';
    const TYPE_TRYING_AUTO_PURCHASE_26TH = 'try_auto_purchase_26th';
    const TYPE_PENDING_AT_11TH = 'pending_11th';
    const TYPE_MANUALLY_PURCHASE_PENDING = 'manually_purchase_pending';
    const TYPE_CANCELED = 'cancel';
    const TYPE_TURN_OFF_PENDING_25TH = 'turn_off_25th';
    const TYPE_TURN_OFF_CANCEL = 'turn_off_cancel';

    const STATUS_FAIL = 'fail';
    const STATUS_SUCCESS = 'success';

}
