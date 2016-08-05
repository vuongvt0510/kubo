<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty dummy modifier plugin
 *
 * ex) $dummy|dummy:'Dummy sentence to show'
 *
 * Type:     modifier<br>
 * Name:     dummy<br>
 * Purpose:  set dummy string into smarty tag<br>
 * Input:<br>
 *          - string: input date string
 *
 * @link
 * @param array $params
 * @return string
 */
function smarty_modifiercompiler_dummy ($params)
{
    $CI =& get_instance();
    $enabled = $CI->config->item('dummy_enabled');

    if ($enabled === FALSE) {
        return "''";
    }

    $output = $params[0];
    if (!isset($params[1])) {
        $params[1] = "''";
    }

    return '(($tmp = @' . $output . ')===null||$tmp===\'\' ? ' . $params[1] . ' : $tmp)';
}
