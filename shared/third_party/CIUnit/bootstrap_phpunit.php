<?php

if ( ! defined('CIUnit_Version') ) {
	define('CIUnit_Version', 0.17);
}

/*
 *---------------------------------------------------------------
 * PHP ERROR REPORTING LEVEL
 *---------------------------------------------------------------
 *
 * By default CI runs with error reporting set to ALL.  For security
 * reasons you are encouraged to change this to 0 when your site goes live.
 * For more info visit:  http://www.php.net/error_reporting
 *
 */
    error_reporting(0);

/**
 * --------------------------------------------------------------
 * CIUNIT FOLDER NAME
 * --------------------------------------------------------------
 * 
 * Typically this folder will be within the application's third-party
 * folder.  However, you can place the folder in any directory.  Just
 * be sure to update this path.
 *
 * NO TRAILING SLASH!
 *
 */
    $ciunit_folder = dirname(__FILE__);
 
    // The path to CIUnit
    if (is_dir($ciunit_folder))
    {
        define('CIUPATH', $ciunit_folder . '/');
    }
    else
    {
        if ( ! is_dir(APPPATH . 'third_party/' . $ciunit_folder))
        {
            exit("Your CIUnit folder path does not appear to be set correctly. Please open the following file and correct this: ".SELF);
        }
        
        define ('CIUPATH', APPPATH . 'third_party/' . $ciunit_folder);
    }
    
    
    // The path to the Tests folder
    define('TESTSPATH', $tests_folder . '/');

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILES
 * --------------------------------------------------------------------
 */

// Load the CIUnit CodeIgniter Core
require_once CIUPATH . 'core/CodeIgniter' . EXT;

// Autoload the PHPUnit Framework
require_once ('PHPUnit/Autoload.php');

// Load the CIUnit Framework
require_once CIUPATH. 'libraries/CIUnit.php';
