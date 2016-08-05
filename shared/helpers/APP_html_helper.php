<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Doctype
 *
 * Generates a page document type declaration
 *
 * Valid options are xhtml-11, xhtml-strict, xhtml-trans, xhtml-frame,
 * html4-strict, html4-trans, and html4-frame.  Values are saved in the
 * doctypes config file.
 *
 * @access public
 * @param string $type The doctype to be generated
 * @return string
 */
if ( ! function_exists('doctype'))
{
    function doctype($type = 'xhtml1-strict')
    {
        global $_doctypes;

        if ( ! is_array($_doctypes))
        {
            if (defined('ENVIRONMENT') AND is_file(SHAREDPATH.'config/'.ENVIRONMENT.'/doctypes.php'))
            {
                include(SHAREDPATH.'config/'.ENVIRONMENT.'/doctypes.php');
            }
            elseif (is_file(SHAREDPATH.'config/doctypes.php'))
            {
                include(SHAREDPATH.'config/doctypes.php');
            }

            if ( ! is_array($_doctypes))
            {
                return FALSE;
            }
        }

        if (isset($_doctypes[$type]))
        {
            return $_doctypes[$type];
        }
        else
        {
            return FALSE;
        }
    }
}

