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
function smarty_function_page_link($params, &$smarty)
{
    if (!function_exists('site_url')) {
        $CI =& get_instance();
        $CI->load->helper('url_helper');
    }

    $info = parse_url($_SERVER['REQUEST_URI']);
    parse_str(!empty($info['query']) ? $info['query'] : '', $q);

    switch (TRUE) {
        case !empty($params['name']) :
            foreach ($q AS $k => $v) {
                if ($k == $params['name']) {
                    unset($q[$k]);
                    break;
                }
            }

            $q[$params['name']] = $params['page'];
            return $info['path'] . '?' . http_build_query($q);

        case !empty($params['exclude']):
            foreach ($q AS $k => $v) {
                if (in_array($k, $params['exclude'])) {
                    unset($q[$k]);
                    break;
                }
            }

            return $info['path'] . '?' . http_build_query($q);
    }

}
