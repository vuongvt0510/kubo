<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Class registry
*
* アプリケーション間で共有のファイルを読み込めるように修正
*
*/
if ( ! function_exists('load_class'))
{
    function &load_class($class, $directory = 'libraries', $param = NULL, $prefix = 'CI_')
    {
        static $_classes = array();

        // Does the class exist? If so, we're done...
        if (isset($_classes[$class]))
        {
            return $_classes[$class];
        }

        $name = FALSE;

        // Look for the class first in the local application/libraries folder
        // then in the native system/libraries folder
        foreach (array(APPPATH, SHAREDPATH, BASEPATH) as $path)
        {
            if (file_exists($path.$directory.'/'.$class.'.php'))
            {
                $name = $prefix.$class;

                if (class_exists($name, FALSE) === FALSE)
                {
                    require_once($path.$directory.'/'.$class.'.php');
                }

                break;
            }
        }

        // 共通系のクラスがある場合は事前に読み込む
        if (file_exists(SHAREDPATH.$directory.'/'.'APP_'.$class.'.php'))
        {
            $name = 'APP_'.$class;

            if (class_exists($name) === FALSE)
            {
                require(SHAREDPATH.$directory.'/'.'APP_'.$class.'.php');
            }
        }

        // Is the request a class extension? If so we load it too
        if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
        {
            $name = config_item('subclass_prefix').$class;

            if (class_exists($name, FALSE) === FALSE)
            {
                require_once(APPPATH.$directory.'/'.$name.'.php');
            }
        }

        // Did we find the class?
        if ($name === FALSE)
        {
            // Note: We use exit() rather than show_error() in order to avoid a
            // self-referencing loop with the Exceptions class
            set_status_header(503);
            echo 'Unable to locate the specified class: '.$class.'.php';
            exit(5); // EXIT_UNK_CLASS
        }

        // Keep track of what we just loaded
        is_loaded($class);

        $_classes[$class] = isset($param)
            ? new $name($param)
            : new $name();
        return $_classes[$class];
    }
}

if ( ! function_exists('get_config'))
{
    function &get_config($replace = array())
    {
        static $_config;

        if (isset($_config)) {
            return $_config[0];
        }

        $paths = array(SHAREDPATH, APPPATH);

        $exists_file = FALSE;

        foreach ($paths as $path) {
            // Is the config file in the environment folder?
            if ( ! defined('ENVIRONMENT') OR ! file_exists($file_path = $path.'config/'.ENVIRONMENT.'/config.php'))
            {
                $file_path = $path.'config/config.php';
            }

            if (file_exists($file_path)) {
                $exists_file = TRUE;
                require($file_path);
            }
        }

        if ( ! $exists_file) {
            exit('The configuration file does not exist.');
        }

        // Does the $config array exist in the file?
        if ( ! isset($config) OR ! is_array($config)) {
            exit('Your config file does not appear to be formatted correctly.');
        }

        // Are any values being dynamically replaced?
        if (count($replace) > 0) {
            foreach ($replace as $key => $val) {
                if (isset($config[$key])) {
                    $config[$key] = $val;
                }
            }
        }

        return $_config[0] =& $config;
    }
}

if ( ! function_exists('log_message'))
{
    /**
     * ログ出力
     *
     * @param string $level
     * @param string $message
     * @param bool $php_error
     * @return void
     */
    function log_message($level = 'error', $message, $php_error = FALSE)
    {
        static $_log;

        $_log =& load_class('Log');
        $_log->write_log($level, $message, $php_error);
    }
}

if ( ! function_exists('log_exception'))
{
    /**
     * 例外ログ出力
     *
     * @param string $log_level
     * @param Exception $e
     */
    function log_exception($log_level = 'error', Exception $e) 
    {
        $message = sprintf("Throw exception '%s' (%d) with message '%s' in %s:%d",
            get_class($e),
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine());

        log_message($log_level, $message);
    }
}

