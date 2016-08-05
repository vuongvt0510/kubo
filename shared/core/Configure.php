<?php

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */
if (defined('CIUnit_Version')) { 
    define('ENVIRONMENT', 'auto_test');
} else {
    define('ENVIRONMENT', isset($_SERVER['CODEIGNITER_ENV']) ? $_SERVER['CODEIGNITER_ENV'] : 'development');
}

/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */

if (defined('ENVIRONMENT'))
{
    switch (ENVIRONMENT)
    {
        case 'development':
        case 'auto_test':
            error_reporting(E_ALL);
        break;
    
        case 'testing':
        case 'staging':
        case 'production':
            error_reporting(0);
        break;

        default:
            exit('The application environment is not set correctly.');
    }
}

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */
    if (realpath($system_path) !== FALSE)
    {
        $system_path = realpath($system_path).'/';
    }

    // ensure there's a trailing slash
    $system_path = rtrim($system_path, '/').'/';

    // Is the system path correct?
    if ( ! is_dir($system_path))
    {
        exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(__FILE__, PATHINFO_BASENAME));
    }

    if (realpath($shared_path) !== FALSE)
    {
        $shared_path = realpath($shared_path).'/';
    }

    // ensure there's a trailing slash
    $shared_path = rtrim($shared_path, '/').'/';

    // View folder
    $view_folder = '';

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
    // The name of THIS file
    define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

    // The PHP file extension
    // this global constant is deprecated.
    define('EXT', '.php');

    // Path to the system folder
    define('BASEPATH', str_replace("\\", "/", $system_path));

    // Path to the shared folder
    define('SHAREDPATH', str_replace("\\", "/", $shared_path));

    // Path to the front controller (this file)
    define('FCPATH', str_replace(SELF, '', __FILE__));

    // Name of the "system folder"
    define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));


    // The path to the "application" folder
    if (is_dir($application_folder))
    {
        define('APPPATH', $application_folder.'/');
    }
    else
    {
        if ( ! is_dir(BASEPATH.$application_folder.'/'))
        {
            exit("Your application folder path does not appear to be set correctly. Please open the following file and correct this: ".SELF);
        }

        define('APPPATH', BASEPATH.$application_folder.'/');
    }

    // The path to the "views" folder
    if ( ! is_dir($view_folder))
    {
        if ( ! empty($view_folder) && is_dir(APPPATH.$view_folder.DIRECTORY_SEPARATOR))
        {
            $view_folder = APPPATH.$view_folder;
        }
        elseif ( ! is_dir(APPPATH.'views'.DIRECTORY_SEPARATOR))
        {
            header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
            echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
            exit(3); // EXIT_CONFIG
        }
        else
        {
            $view_folder = APPPATH.'views';
        }
    }

    if (($_temp = realpath($view_folder)) !== FALSE)
    {
        $view_folder = $_temp.DIRECTORY_SEPARATOR;
    }
    else
    {
        $view_folder = rtrim($view_folder, '/\\').DIRECTORY_SEPARATOR;
    }

    define('VIEWPATH', $view_folder);

/*
 *---------------------------------------------------------------
 * PHP SETTING
 *---------------------------------------------------------------
 *
 * Load extra php.ini
 */
    $__extra_ini = array();
    if (file_exists(SHAREDPATH . "config/php.ini")) {
        $__extra_ini = parse_ini_file(SHAREDPATH . "config/php.ini");
    }
    if (file_exists(APPPATH . "config/php.ini")) {
        $__extra_ini = array_merge($__extra_ini, parse_ini_file(SHAREDPATH . "config/php.ini"));
    }
    if (!empty($__extra_ini)) {
        foreach ($__extra_ini as $__ini_name => $__ini_value) {
            ini_set($__ini_name, $__ini_value);
        }
        unset($__ini_name);
        unset($__ini_value);
    }
    unset($__extra_ini);

