<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('get_mime_by_extension'))
{
    function get_mime_by_extension($file)
    {
        $extension = strtolower(substr(strrchr($file, '.'), 1));

        global $mimes;

        if ( ! is_array($mimes)) {
            $paths = array(
                SHAREDPATH.'config/mimes.php',
                SHAREDPATH.'config/'.ENVIRONMENT.'/mimes.php',
                APPPATH.'config/mimes.php',
                APPPATH.'config/'.ENVIRONMENT.'/mimes.php'
            );

            foreach ($paths as $f) {
                if (is_file($f)) {
                    include $f;
                }
            }

            if ( ! is_array($mimes)) {
                return FALSE;
            }
        }

        if (array_key_exists($extension, $mimes)) {
            if (is_array($mimes[$extension])) {
                // Multiple mime types, just give the first one
                return current($mimes[$extension]);
            } else {
                return $mimes[$extension];
            }
        } else {
            return FALSE;
        }
    }
}

