<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_mile_model
 *
 */
class User_netmile_model extends APP_Paranoid_model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_netmile';
    public $primary_key = 'id';
}