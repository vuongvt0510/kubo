<?php defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('get_mimes'))
{
    /**
     * Returns the MIME types array from config/mimes.php
     *
     * @return	array
     */
    function &get_mimes()
    {
        static $_mimes;

        $_mimes = include SHAREDPATH.'config/mimes.php';

        if (defined('ENVIRONMENT') AND file_exists(SHAREDPATH.'config/'.ENVIRONMENT.'/mimes.php'))
        {
            $_mimes = include SHAREDPATH.'config/'.ENVIRONMENT.'/mimes.php';
        }

        if (file_exists(APPPATH.'config/mimes.php'))
        {
            $_mimes = include APPPATH.'config/mimes.php';
        }

        if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'))
        {
            $_mimes = include APPPATH.'config/'.ENVIRONMENT.'/mimes.php';
        }

        return $_mimes;
    }
}
