<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Purchase_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Purchase_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'purchase';
    public $primary_key = 'id';

    const PURCHASE_STATUS_PENDING = 'pending';
    const PURCHASE_STATUS_SUCCESS = 'success';

    const PURCHASE_TYPE_CONTRACT = 'contract';
    const PURCHASE_TYPE_COIN = 'coin';

    const PURCHASE_TYPE_CONTRACT_BATCH = 'batch_contract';

    /**
     * fetch with profile info
     *
     * @access public
     * @return Purchase_model
     */
    public function with_profile()
    {
        return $this->join('user_profile', 'credit_card.user_id = user_profile.user_id', 'left');
    }

    /**
     * fetch with user info
     *
     * @access public
     * @return Purchase_model
     */
    public function with_user()
    {
        return $this->join('user', 'credit_card.user_id = user.id', 'left');
    }

    /**
     * fetch with user info who is purchase
     *
     * @access public
     * @return Purchase_model
     */
    public function with_user_purchase()
    {
        return $this
            ->join('user', 'purchase.user_id = user.id', 'left')
            ->join('user_profile', 'purchase.user_id = user_profile.user_id', 'left');
    }

    /**
     * Generate order id to GMO
     *
     * @param int $from_user
     * @param int $to_user
     * @param string $type
     * @return string
     */
    public function generate_order_id($from_user = 0, $to_user = 0, $type = null)
    {
        // Auto generation order id for this tran
        $order_id = $from_user.'-'.$to_user;

        switch ($type) {
            case self::PURCHASE_TYPE_CONTRACT:
                $order_id .= '-MC-';
                break;

            case self::PURCHASE_TYPE_CONTRACT_BATCH:

                // Hack code to test fail purchase
//                switch (ENVIRONMENT) {
//                    case 'testing':
//                        if ($from_user == 875 && ($to_user == 876 || $to_user == 858) ) {
//                            $order_id .= date('Y-m-d Y-d-m Y-d-m H:i:s');
//                        }
//                        break;
//                }


                $order_id .= '-MA-';
                break;

            default:
                $order_id .= '-';
                break;
        }

        // 27 is limit length of order_id in GMO
        $order_id .= generate_unique_key(27 - strlen($order_id));

        return $order_id;
    }
}