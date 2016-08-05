<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Get Smiley Array
 *
 * Fetches the config/smiley.php file
 *
 * @access private
 * @return mixed
 */
if ( ! function_exists('_get_smiley_array'))
{
    function _get_smiley_array()
    {
        if (defined('ENVIRONMENT') AND file_exists(SHAREDPATH.'config/'.ENVIRONMENT.'/smileys.php'))
        {
            include(APPPATH.'config/'.ENVIRONMENT.'/smileys.php');
        }
        elseif (file_exists(SHAREDPATH.'config/smileys.php'))
        {
            include(SHAREDPATH.'config/smileys.php');
        }
        
        if (isset($smileys) AND is_array($smileys))
        {
            return $smileys;
        }

        return FALSE;
    }
}
