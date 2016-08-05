<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_promotion_code
 */
class User_promotion_code_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_promotion_code';
    public $primary_key = 'id';

    /**
     * array $type
     * 1 => forceclub
     */
    public $type = [
        1 => 'forceclub'
    ];

    const TYPE_CAMPAIGN = 'campaign';
    const TYPE_FORCECLUB = 'forceclub';
}
