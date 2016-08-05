<?php
/**
 * Smarty plugin
 */

/**
 * Smarty {url} function plugin
 *
 * Type function
 * Name url
 * @author Yoshikazu Ozawa
 * @mail: ozawa@interest-marketing.net
 */
function smarty_function_url($params, &$smarty)
{
    if (!function_exists('route_url')) {
        $CI =& get_instance();
        $CI->load->helper('url_helper');
    }

    $route = $params['route'];
    unset($params['route']);

    return route_url($route, $params);
}

