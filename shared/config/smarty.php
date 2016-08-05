<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Smarty設定
|--------------------------------------------------------------------------
|
| Smartyの各種設定を記載する
|
*/
$smarty['left_delimiter'] = "<!--{";
$smarty['right_delimiter'] = "}-->";

$smarty['compile_dir'] = APPPATH . "tmp/templates_c";
$smarty['template_dir'] = array(APPPATH . "views", SHAREDPATH . "views");

$smarty['plugins_dir'] = array(SHAREDPATH . "libraries/Smarty/plugins");

