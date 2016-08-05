<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$hook['post_controller_constructor'][] = array(
	'class'    => 'APP_Filter',
	'function' => 'before_filter',
	'filename' => 'APP_Filter.php',
	'filepath' => 'hooks'
);

$hook['post_controller'][] = array(
    'class'    => 'APP_Cookie_manager',
    'function' => 'flash',
    'filename' => 'APP_Cookie_manager.php',
    'filepath' => 'hooks'
);

$hook['display_override'][] = array(
    'class'    => 'APP_Mobile_display_filter',
    'function' => 'convert_to_sjis',
    'filename' => 'APP_Mobile_display_filter.php',
    'filepath' => 'hooks'
);

$hook['pre_system'][] = array(
    'class'    => 'APP_Mobile_param_filter',
    'function' => 'convert_to_utf8',
    'filename' => 'APP_Mobile_param_filter.php',
    'filepath' => 'hooks'
);

