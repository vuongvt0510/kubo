<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty render avatar link
 * @param array $params
 * @internal param $avatar_id of user
 * @internal param $primary_type of user
 *
 * @return string
 */
function smarty_function_avatar_link($params, &$smarty)
{
    $avatar_id = $params['avatar_id'];
    $primary_type = isset($params['primary_type']) ? $params['primary_type'] : 'student';
    if (!$avatar_id) {
        $avatar_id = $primary_type == 'student' ? 2 : 12;
    }

    return '/images/avatar/'.$avatar_id.'.png';
}

