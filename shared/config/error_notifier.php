<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['sendable'] = FALSE;

$config['project'] = "Error notifier";
$config['template_path'] = "";    // なければ標準のものを利用する
$config['masked_parameters'] = array('password');

// for email
$config['driver'] = 'email';
$config['from'] = array("example@example.com", "Error notifier");
$config['to'] = array("example@example.com");

// for amazon sns
// $config['driver'] = 'amazon';
// $config['key'] = '';
// $config['secret'] = '';
// $config['region'] = 'ap-northeast-1';
// $config['topic'] = '';

