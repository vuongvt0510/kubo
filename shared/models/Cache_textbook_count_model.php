<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Cache_textbook_count_model
 */
class Cache_textbook_count_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'cache_textbook_count';
}
