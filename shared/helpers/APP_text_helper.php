<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Convert Accented Foreign Characters to ASCII
 *
 * @access public
 * @param string the text string
 * @return string
 */
if ( ! function_exists('convert_accented_characters'))
{
    function convert_accented_characters($str)
    {
        if (defined('ENVIRONMENT') AND is_file(SHAREDPATH.'config/'.ENVIRONMENT.'/foreign_chars.php'))
        {
            include(SHAREDPATH.'config/'.ENVIRONMENT.'/foreign_chars.php');
        }
        elseif (is_file(SHAREDPATH.'config/foreign_chars.php'))
        {
            include(SHAREDPATH.'config/foreign_chars.php');
        }

        if ( ! isset($foreign_characters))
        {
            return $str;
        }

        return preg_replace(array_keys($foreign_characters), array_values($foreign_characters), $str);
    }
}
