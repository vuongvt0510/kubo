<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('CI_Profiler')) {
    require_once BASEPATH . "libraries/Profiler.php";
}

class APP_Profiler extends CI_Profiler {

    public function run()
    {
        if ( ! ((php_sapi_name() == 'cli') or defined('STDIN'))) {
            return parent::run();
        }
    }
}
