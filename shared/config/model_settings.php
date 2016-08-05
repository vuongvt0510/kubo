<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['created_at_column_name'] = "/^([a-z]+_)?created_at$/";
$config['created_by_column_name'] = "/^([a-z]+_)?created_by$/";
$config['updated_at_column_name'] = "/^([a-z]+_)?updated_at$/";
$config['updated_by_column_name'] = "/^([a-z]+_)?updated_by$/";

// APP_Paranoid_model用の設定
// $use_deleted_flagがTRUEの場合のみdeleted_flag_column_nameは利用される
$config['deleted_at_column_name'] = "/^([a-z]+_)?deleted_at$/";
$config['deleted_by_column_name'] = "/^([a-z]+_)?deleted_by$/";
$config['deleted_flag_column_name'] = "/^([a-z]+_)?deleted_flag$/";

