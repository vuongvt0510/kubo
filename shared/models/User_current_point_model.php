<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class User_current_point_model
 */
class User_current_point_model extends APP_model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_current_point';
    public $primary_key = 'id';
}
