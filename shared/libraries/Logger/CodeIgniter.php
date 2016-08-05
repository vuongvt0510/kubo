<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('CI_Log')) {
    require dirname(__FILE__) . "/../../../system/core/Log.php";
}

/**
 * ログドライバ - CodeIgniter
 *
 * @author Yoshikazu Ozawa
 */
class APP_Log_driver_codeigniter extends CI_Log {
}

