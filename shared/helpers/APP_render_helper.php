<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists("content_yield"))
{
    function content_yield($name)
    {
        $CI =& get_instance();
        $engine =& $CI->_template_engine();
        return $engine->_content_yield($name);
    }
}

if ( ! function_exists("content_start"))
{
    function content_start($name)
    {
        $CI =& get_instance();
        $engine =& $CI->_template_engine();
        return $engine->_content_start($name);
    }
}

if ( ! function_exists("content_end"))
{
    function content_end()
    {
        $CI =& get_instance();
        $engine =& $CI->_template_engine();
        return $engine->_content_end();
    }
}

if ( ! function_exists('render_partial'))
{
    function render_partial($template_path, $data = array())
    {
        $CI =& get_instance();
        $engine =& $CI->_template_engine();
        return $engine->view($template_path, $data, TRUE);
    }
}

