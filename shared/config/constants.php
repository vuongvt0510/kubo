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
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/*
|--------------------------------------------------------------------------
| For Elearning
|--------------------------------------------------------------------------
*/

if (!defined('DEFAULT_MAX_USER_POWER')) {
    define('DEFAULT_MAX_USER_POWER', 40);
}

if (!defined('DEFAULT_MAX_USER_IN_GROUP')) {
    define('DEFAULT_MAX_USER_IN_GROUP', 99);
}

if (!defined('DEFAULT_MONTHLY_PAYMENT_AMOUNT')) {
    define('DEFAULT_MONTHLY_PAYMENT_AMOUNT', 300);
}

if (!defined('DEFAULT_MONTHLY_PAYMENT_EXPIRED_DAY')) {
    define('DEFAULT_MONTHLY_PAYMENT_EXPIRED_DAY', 30);
}

if (!defined('DEFAULT_NICKNAME')) {
    define('DEFAULT_NICKNAME', 'ニックネームなし');
}

if (!defined('DEFAULT_MAX_USER_IN_TEAM')) {
    define('DEFAULT_MAX_USER_IN_TEAM', 50);
}

if (!defined('DEFAULT_MAX_TEAM')) {
    define('DEFAULT_MAX_TEAM', 5);
}

/*
|--------------------------------------------------------------------------
| Database name
|--------------------------------------------------------------------------
*/
if (!defined('DB_MAIN')) {
    define('DB_MAIN', 'schooltv_main');
}

if (!defined('DB_IMAGE')) {
    define('DB_IMAGE', 'schooltv_image');
}

if (!defined('DB_CONTENT')) {
    define('DB_CONTENT', 'schooltv_content');
}

if (!defined('DB_MAIL')) {
    define('DB_MAIL', 'schooltv_mail');
}