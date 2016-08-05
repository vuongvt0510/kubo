<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Remote Push Notification 設定
|--------------------------------------------------------------------------
| 
| Remote Push Notification のアプリケーションごとの証明書などを設定する
|
*/
$config["appname1"] = array(
    "mode" => "staging",
    "ios" => array(
        "pem_file" => SHAREDPATH . "config/pems/appname1.pem",
        'authority_file' => SHAREDPATH . "third_party/RemotePushNotification/config/pems/entrust_root_certification_authority.cer"
    ),
    "android" => array(
        "title" => "Test",
        "key" => "google api key"
    ) 
);

$config["appname2"] = array(
    "mode" => "staging",
    "ios" => array(
        "pem_file" => SHAREDPATH . "config/pems/appname2.pem",
        'authority_file' => SHAREDPATH . "third_party/RemotePushNotification/config/pems/entrust_root_certification_authority.cer"
    ),
    "android" => array(
        "title" => "Test",
        "key" => "google api key"
    ) 
);

