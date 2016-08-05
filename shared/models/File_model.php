<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . '/core/APP_Paranoid_model.php';

/**
 * Class File_model
 *
 * Manipulate file binary
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class File_model extends APP_Paranoid_model
{
    public $database_name = DB_IMAGE;
    public $table_name = 'file';
    public $primary_key = 'id';

}
