<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| 全アプリケーション・全モードで共通の定数の定義
|--------------------------------------------------------------------------
| 全アプリケーション、全モード(development・testing・staging・production)で
| 共通で読み込まれるので注意が必要
|
| 定数ファイルの読み込み順番
|   APPPATH/config/ENVIRONMENT/constants.php
|   APPPATH/config/constants.php
|   shared/config/ENVIRONMENT/constants.php
|   shared/config/constants.php
*/

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
if (!defined('DB_MAIN')) {
    define('DB_MAIN', 'schooltv_main');
}

if (!defined('DB_IMAGE')) {
    define('DB_IMAGE', 'schooltv_image');
}
