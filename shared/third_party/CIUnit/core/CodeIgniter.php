<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2015, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	http://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
    defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System Initialization File
 *
 * Loads the base classes and executes the request.
 *
 * @package		CodeIgniter
 * @subpackage	CodeIgniter
 * @category	Front-controller
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/
 */

/**
 * CodeIgniter Version
 *
 * @var	string
 *
 */
    define('CI_VERSION', '3.0.3');

/*
 * ------------------------------------------------------
 *  Load the global functions
 * ------------------------------------------------------
 */

require(CIUPATH.'core/Common'.EXT);
require_once(SHAREDPATH.'core/APP_Common.php');
require_once(BASEPATH.'core/Common.php');


/*
 * ------------------------------------------------------
 *  Load the framework constants
 * ------------------------------------------------------
 */
/*
 * ------------------------------------------------------
 *  Load the framework constants
 * ------------------------------------------------------
 */
//Customized for IM
	if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/constants.php'))
	{
		require(APPPATH.'config/'.ENVIRONMENT.'/constants.php');
	}

	if (file_exists(APPPATH.'config/constants.php'))
	{
		require(APPPATH.'config/constants.php');
	}

	if (defined('ENVIRONMENT') AND file_exists(SHAREDPATH.'config/'.ENVIRONMENT.'/constants.php'))
	{
		require(SHAREDPATH.'config/'.ENVIRONMENT.'/constants.php');
	}

	require(SHAREDPATH.'config/constants.php');


/*
 * ------------------------------------------------------
 *  Define a custom error handler so we can log PHP errors
 * ------------------------------------------------------
 */
    set_error_handler('_exception_handler');

    if ( ! is_php('5.3'))
    {
        @set_magic_quotes_runtime(0); // Kill magic quotes
    }

    if ( ! is_php('5.4'))
    {
        ini_set('magic_quotes_runtime', 0);

        if ((bool) ini_get('register_globals'))
        {
            $_protected = array(
                '_SERVER',
                '_GET',
                '_POST',
                '_FILES',
                '_REQUEST',
                '_SESSION',
                '_ENV',
                '_COOKIE',
                'GLOBALS',
                'HTTP_RAW_POST_DATA',
                'system_path',
                'application_folder',
                'view_folder',
                '_protected',
                '_registered'
            );

            $_registered = ini_get('variables_order');
            foreach (array('E' => '_ENV', 'G' => '_GET', 'P' => '_POST', 'C' => '_COOKIE', 'S' => '_SERVER') as $key => $superglobal)
            {
                if (strpos($_registered, $key) === FALSE)
                {
                    continue;
                }

                foreach (array_keys($$superglobal) as $var)
                {
                    if (isset($GLOBALS[$var]) && ! in_array($var, $_protected, TRUE))
                    {
                        $GLOBALS[$var] = NULL;
                    }
                }
            }
        }
    }

/*
 * ------------------------------------------------------
 *  Set the subclass_prefix
 * ------------------------------------------------------
 *
 * Normally the "subclass_prefix" is set in the config file.
 * The subclass prefix allows CI to know if a core class is
 * being extended via a library in the local application
 * "libraries" folder. Since CI allows config items to be
 * overriden via data set in the main index. php file,
 * before proceeding we need to know if a subclass_prefix
 * override exists.  If so, we will set this value now,
 * before any classes are loaded
 * Note: Since the config file data is cached it doesn't
 * hurt to load it here.
 */
    if (isset($assign_to_config['subclass_prefix']) AND $assign_to_config['subclass_prefix'] != '')
    {
        get_config(array('subclass_prefix' => $assign_to_config['subclass_prefix']));
    }

    /*
    * ------------------------------------------------------
    *  Should we use a Composer autoloader?
    * ------------------------------------------------------
    */
    if ($composer_autoload = config_item('composer_autoload'))
    {
        if ($composer_autoload === TRUE)
        {
            file_exists(APPPATH.'vendor/autoload.php')
                ? require_once(APPPATH.'vendor/autoload.php')
                : log_message('error', '$config[\'composer_autoload\'] is set to TRUE but '.APPPATH.'vendor/autoload.php was not found.');
        }
        elseif (file_exists($composer_autoload))
        {
            require_once($composer_autoload);
        }
        else
        {
            log_message('error', 'Could not find the specified $config[\'composer_autoload\'] path: '.$composer_autoload);
        }
    }

/*
 * ------------------------------------------------------
 *  Set a liberal script execution time limit
 * ------------------------------------------------------
 */
    if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0)
    {
        @set_time_limit(300);
    }

/*
 * ------------------------------------------------------
 *  Start the timer... tick tock tick tock...
 * ------------------------------------------------------
 */
    $BM =& load_class('Benchmark', 'core');

    $GLOBALS['BM'] =& $BM;

    $BM->mark('total_execution_time_start');
    $BM->mark('loading_time:_base_classes_start');

/*
 * ------------------------------------------------------
 *  Instantiate the hooks class
 * ------------------------------------------------------
 */
    $EXT =& load_class('Hooks', 'core');

    $GLOBALS['EXT'] =& $EXT;

/*
 * ------------------------------------------------------
 *  Verification call_hooks() method
 *
 *  In Codeigniter 2 is callable "_call_hook()"
 *
 *  In Codeigniter 3 is callable "call_hook()"
 * ------------------------------------------------------
 */

    $call_hook_method = '_call_hook';

    if (is_callable(array($EXT, 'call_hook'))){
        $call_hook_method = 'call_hook';
    }
/*
 * ------------------------------------------------------
 *  Is there a "pre_system" hook?
 * ------------------------------------------------------
 */
    $EXT->$call_hook_method('pre_system');

/*
 * ------------------------------------------------------
 *  Instantiate the config class
 * ------------------------------------------------------
 */
    $CFG =& load_class('Config', 'core');

    $GLOBALS['CFG'] =& $CFG;

    // Do we have any manually set config items in the index.php file?
    if (isset($assign_to_config))
    {
        $CFG->_assign_to_config($assign_to_config);
    }

    /*
     * ------------------------------------------------------
     * Important charset-related stuff
     * ------------------------------------------------------
     *
     * Configure mbstring and/or iconv if they are enabled
     * and set MB_ENABLED and ICONV_ENABLED constants, so
     * that we don't repeatedly do extension_loaded() or
     * function_exists() calls.
     *
     * Note: UTF-8 class depends on this. It used to be done
     * in it's constructor, but it's _not_ class-specific.
     *
     */
    $charset = strtoupper(config_item('charset'));
    ini_set('default_charset', $charset);

    if (extension_loaded('mbstring'))
    {
        define('MB_ENABLED', TRUE);
        // mbstring.internal_encoding is deprecated starting with PHP 5.6
        // and it's usage triggers E_DEPRECATED messages.
        @ini_set('mbstring.internal_encoding', $charset);
        // This is required for mb_convert_encoding() to strip invalid characters.
        // That's utilized by CI_Utf8, but it's also done for consistency with iconv.
        mb_substitute_character('none');
    }
    else
    {
        define('MB_ENABLED', FALSE);
    }

    // There's an ICONV_IMPL constant, but the PHP manual says that using
    // iconv's predefined constants is "strongly discouraged".
    if (extension_loaded('iconv'))
    {
        define('ICONV_ENABLED', TRUE);
        // iconv.internal_encoding is deprecated starting with PHP 5.6
        // and it's usage triggers E_DEPRECATED messages.
        @ini_set('iconv.internal_encoding', $charset);
    }
    else
    {
        define('ICONV_ENABLED', FALSE);
    }

    if (is_php('5.6'))
    {
        ini_set('php.internal_encoding', $charset);
    }

    /*
     * ------------------------------------------------------
     *  Load compatibility features
     * ------------------------------------------------------
     */

    require_once(BASEPATH.'core/compat/mbstring.php');
    require_once(BASEPATH.'core/compat/hash.php');
    require_once(BASEPATH.'core/compat/password.php');
    require_once(BASEPATH.'core/compat/standard.php');

/*
 * ------------------------------------------------------
 *  Instantiate the UTF-8 class
 * ------------------------------------------------------
 *
 * Note: Order here is rather important as the UTF-8
 * class needs to be used very early on, but it cannot
 * properly determine if UTf-8 can be supported until
 * after the Config class is instantiated.
 *
 */
    $UNI =& load_class('Utf8', 'core');

    $GLOBALS['UNI'] =& $UNI;

/*
 * ------------------------------------------------------
 *  Instantiate the URI class
 * ------------------------------------------------------
 */
    $URI =& load_class('URI', 'core');
    $GLOBALS['URI'] =& $URI;

/*
 * ------------------------------------------------------
 *  Instantiate the routing class and set the routing
 * ------------------------------------------------------
 */
    $RTR =& load_class('Router', 'core');
    $GLOBALS['RTR'] =& $RTR;

//    $RTR->_set_routing();

    // Set any routing overrides that may exist in the main index file
    if (isset($routing))
    {
        $RTR->_set_overrides($routing);
    }

/*
 * ------------------------------------------------------
 *  Instantiate the output class
 * ------------------------------------------------------
 */
    $OUT =& load_class('Output', 'core');
    $GLOBALS['OUT'] =& $OUT;

/*
 * ------------------------------------------------------
 *  Is there a valid cache file?  If so, we're done...
 * ------------------------------------------------------
 */
    // I am not going to worry about a cache, right?
    /*
    if ($EXT->call_hook('cache_override') === FALSE)
    {
        if ($OUT->_display_cache($CFG, $URI) == TRUE)
        {
            exit;
        }
    }
    */

/*
 * ------------------------------------------------------
 *  Load the Input class and sanitize globals
 * ------------------------------------------------------
 */
    $IN =& load_class('Input', 'core');
    $GLOBALS['IN'] =& $IN;

/*
 * ------------------------------------------------------
 *  Load the Language class
 * ------------------------------------------------------
 */
    $LANG =& load_class('Lang', 'core');

/*
 * ------------------------------------------------------
 *  Load the app controller and local controller
 * ------------------------------------------------------
 *
 */
    // Load the base controller class
    require BASEPATH.'core/Controller'.EXT;

    function &get_instance()
    {
	    return CI_Controller::get_instance();
    }

    //Customized for IM
    if (defined('SHAREDPATH') && file_exists(SHAREDPATH.'core/APP_Controller.php'))
    {
	require SHAREDPATH.'core/APP_Controller.php';
    }

    if (file_exists(APPPATH.'core/'.$CFG->config['subclass_prefix'].'Controller'.EXT))
    {
        require APPPATH.'core/'.$CFG->config['subclass_prefix'].'Controller'.EXT;
    }

    if (defined('CIUnit_Version') === false) {

        // Load the local application controller
        // Note: The Router class automatically validates the controller path using the router->_validate_request().
        // If this include fails it means that the default controller in the Routes.php file is not resolving to something valid.
        if ( file_exists(APPPATH.'controllers/'.$RTR->fetch_directory().$RTR->fetch_class().EXT))
        {
	    include(APPPATH.'controllers/'.$RTR->fetch_directory().$RTR->fetch_class().EXT);
        }
        //Customized for IM
	else if (file_exists(SHAREDPATH.'controllers/'.$RTR->fetch_directory().$RTR->fetch_class().'.php'))
	{
            include(SHAREDPATH.'controllers/'.$RTR->fetch_directory().$RTR->fetch_class().'.php');
	}
        else {
            show_error('Unable to load your default controller. Please make sure the controller specified in your Routes.php file is valid.');
        }

        // Set a mark point for benchmarking
        $BM->mark('loading_time:_base_classes_end');

        /*
         * ------------------------------------------------------
         *  Security check
         * ------------------------------------------------------
         *
         *  None of the functions in the app controller or the
         *  loader class can be called via the URI, nor can
         *  controller functions that begin with an underscore
         */
        $class  = $RTR->fetch_class();
        $method = $RTR->fetch_method();

        if ( ! class_exists($class)
            OR strncmp($method, '_', 1) == 0
            OR in_array(strtolower($method), array_map('strtolower', get_class_methods('CI_Controller')))
            )
        {
            show_404("{$class}/{$method}");
        }

        /*
         * ------------------------------------------------------
         *  Is there a "pre_controller" hook?
         * ------------------------------------------------------
         */
        $EXT->$call_hook_method('pre_controller');

        /*
         * ------------------------------------------------------
         *  Instantiate the requested controller
         * ------------------------------------------------------
         */
        // Mark a start point so we can benchmark the controller
        $BM->mark('controller_execution_time_( '.$class.' / '.$method.' )_start');

        $CI = new $class();

        $GLOBALS['CI'] =& $CI;

        /*
         * ------------------------------------------------------
         *  Is there a "post_controller_constructor" hook?
         * ------------------------------------------------------
         */
        $EXT->$call_hook_method('post_controller_constructor');

        /*
         * ------------------------------------------------------
         *  Call the requested method
         * ------------------------------------------------------
         */
        // Is there a "remap" function? If so, we call it instead
        if (method_exists($CI, '_remap'))
        {
            $CI->_remap($method, array_slice($URI->rsegments, 2));
        }
        else
        {
            // is_callable() returns TRUE on some versions of PHP 5 for private and protected
            // methods, so we'll use this workaround for consistent behavior
            if ( ! in_array(strtolower($method), array_map('strtolower', get_class_methods($CI))))
            {
                show_404("{$class}/{$method}");
            }

            // Call the requested method.
            // Any URI segments present (besides the class/function) will be passed to the method for convenience
            call_user_func_array(array(&$CI, $method), array_slice($URI->rsegments, 2));
        }


        // Mark a benchmark end point
        $BM->mark('controller_execution_time_( '.$class.' / '.$method.' )_end');

        /*
         * ------------------------------------------------------
         *  Is there a "post_controller" hook?
         * ------------------------------------------------------
         */
        $EXT->$call_hook_method('post_controller');

        /*
         * ------------------------------------------------------
         *  Send the final rendered output to the browser
         * ------------------------------------------------------
         */
        if ($EXT->$call_hook_method('display_override') === FALSE)
        {
            $OUT->_display();
        }

        /*
         * ------------------------------------------------------
         *  Is there a "post_system" hook?
         * ------------------------------------------------------
         */
        $EXT->$call_hook_method('post_system');

    }

    /*
     * ------------------------------------------------------
     *  Close the DB connection if one exists
     * ------------------------------------------------------
     */
    if (class_exists('CI_DB') AND isset($CI->db))
    {
        $CI->db->close();
    }

/* End of file CodeIgniter.php */
/* Location: ./system/core/CodeIgniter.php */
