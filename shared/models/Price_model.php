<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';
/**
 * Class Price_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Price_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'price';
    public $primary_key = ['type', 'price'];

    /**
     * Return min exchange point
     */
    public function get_min_mile_exchange()
    {
        return $this
            ->select('type, price AS min_point, number AS min_mile')
            ->where('type', 'mile')
            ->order_by('price', 'asc')
            ->first();
    }
}
