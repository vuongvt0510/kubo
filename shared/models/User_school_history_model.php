<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class User_school_history_model
 */
class User_school_history_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'user_school_history';
    public $primary_key = 'id';
}
