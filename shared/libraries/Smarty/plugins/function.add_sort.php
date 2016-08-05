<?php
/**
 * Smarty plugin
 */

/**
 * Smarty {add_sort} function plugin
 *
 * Type function
 * Name add_sort
 *
 * Return template sort in table
 */
function smarty_function_add_sort($params, &$smarty)
{
    // Add default sort parameter
    $sort_by = isset($params['sort_by']) ? strtolower($params['sort_by']) : 'id';

    if (!$params['text']) {
        return null;
    }

    // Get current uri 
    $document_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $uri = parse_url($document_url, PHP_URL_QUERY);
    parse_str($uri, $get_method);

    // Get sort uri
    $uri_sort_by = !empty($get_method['sort_by']) ? $get_method['sort_by'] : 'id';
    $uri_sort_position = !empty($get_method['sort_position']) ? $get_method['sort_position'] : 'desc';

    // Change sort in get_method
    $get_method['sort_by'] = $sort_by;
    $get_method['sort_position'] = ($sort_by == $uri_sort_by && $uri_sort_position == 'desc') ? 'asc' : 'desc';

    // Get url return
    $url = strtok($document_url, '?'). '?'. http_build_query($get_method);

    // template <a>
    $result = '<a href="'.$url .'"> '. $params['text']. '</a>';

    // template <i>
    $icon = '';
    if ($sort_by == $uri_sort_by) {
        $icon = $uri_sort_position == 'asc' ? 'icon-arrow-up' : 'icon-arrow-down';
    }
    $result .= '&nbsp<i class="text-success '.$icon. '"></i>';

    return $result;
}
