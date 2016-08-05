<?php
/**
 * Smarty plugin
 */

/**
 * Smarty {common_time} function plugin
 *
 * Type function
 * Name common_time
 */
function smarty_function_common_time($params, &$smarty)
{
	$datetime = isset($params['datetime']) ? $params['datetime'] : null;

	if (!$datetime) {
		return null;
	}

	$common_time = '';

	$date = strtotime($datetime);

    $now = business_time();
    	
    $time = $now - $date; 

    if ( $time < 60 ) {

        $common_time =  "1 分前";

    } if ( 60 <= $time && $time < 3600 ) {
        
        $common_time = (int)($time / 60 )." 分前";
        
    } elseif ( 3600 <= $time && $time < 86400 ) {

        $common_time = (int)($time / 3600)." 時間前";
    
    } elseif ( 86400 < $time ) {

        $common_time = date('Y-m-d', $date); 

    } else {

    }

    return $common_time;
}

