<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty render image link
 * @param array $params
 * @internal param $key of image in db
 * @internal param $type of image in db (Ex: original|large|medium|small|tiny)
 *
 * @return string
 */
function smarty_function_image_link($params, &$smarty)
{
    if (!function_exists('site_url')) {
        $CI =& get_instance();
        $CI->load->helper('url_helper');
    }

    $key = isset($params['key']) ? $params['key'] : '';
    $type = isset($params['type']) ? $params['type'] : '';

    if (preg_match('/^http/', $key)) {
        return $key;
    }

    if (preg_match('/^\//', $key)) {
        return $key;
    }

    return site_url('image/show/' . $key . '/' . $type);
}

