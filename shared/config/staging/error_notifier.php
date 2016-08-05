<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['sendable'] = TRUE;

$config['project'] = "Error notifier";
$config['template_path'] = "";    // なければ標準のものを利用する
$config['masked_parameters'] = array('password', 'card_number', 'expired_year', 'expired_month', 'cvv_code');

// for email
$config['driver'] = 'email';
$config['from'] = array("noreply@school-tv.jp", "Error notifier");
$config['to'] = array("alert+schooltv@interest-marketing.net");

// for amazon sns
// $config['driver'] = 'amazon';
// $config['key'] = '';
// $config['secret'] = '';
// $config['region'] = 'ap-northeast-1';
// $config['topic'] = '';

