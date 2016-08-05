<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| メール基本設定
|--------------------------------------------------------------------------
*/
$config['protocol'] = 'smtp';
$config['smtp_port'] = '465';
$config['charset']  = 'ISO-2022-JP';
$config['wordwrap'] = FALSE;

/**
 * 以下はAmazonSES で作成する
 * user, passはcredentials.csvとしてダウンロードできる情報
 */
// Server Name
$config['smtp_host'] = 'ssl://email-smtp.us-west-2.amazonaws.com';
// Smtp Username
$config['smtp_user'] = 'AKIAJPS7KSVIMF7RVLVA';
// Smtp Password
$config['smtp_pass'] = 'AvHkkY/2oX8SvkLnquY0AAVX1MyNb4RJUf20MRKGAFwA';