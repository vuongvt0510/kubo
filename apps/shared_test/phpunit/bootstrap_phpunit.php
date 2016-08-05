<?php
/*
 * Memory limit
 */
ini_set('memory_limit', '2G');

/*
 *---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not in the same  directory
 * as this file.
 *
 */
    $system_path = '../../../system';

/*
 *---------------------------------------------------------------
 * SHARED FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "shared" folder.
 * Include the path if the folder is not in the same  directory
 * as this file.
 *
 */
    $shared_path = '../../../shared';

/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder then the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.  If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 * 
 * The tests should be run from inside the tests folder.  The assumption
 * is that the tests folder is in the same directory as the application
 * folder.  If it is not, update the path accordingly.
 */
    $application_folder = dirname(__FILE__) . "/../application";

/*
 * --------------------------------------------------------------------
 * LOAD CONFIGURATION FILE
 * --------------------------------------------------------------------
 */
require_once "../../../shared/core/Configure.php";



/**
 * --------------------------------------------------------------
 * UNIT TESTS FOLDER NAME
 * --------------------------------------------------------------
 *
 * This is the path to the tests folder.
 */
//Customized for IM
$tests_folder = dirname(__FILE__); 

/*
 * --------------------------------------------------------------------
 * LOAD THE CIUnit BOOTSTRAP FILE
 * --------------------------------------------------------------------
 */
require_once '../../../shared/third_party/CIUnit/bootstrap_phpunit.php';
