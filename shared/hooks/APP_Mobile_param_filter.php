<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once BASEPATH . "libraries/User_agent.php";
require_once SHAREDPATH . "libraries/APP_User_agent.php";

class APP_Mobile_param_filter {

    public function convert_to_utf8()
    {
        // pre_systemで変更する場合、CIインスタンスができていないので強制的にクラスをnewしている。。。
        $agent = new APP_User_agent();

        if ($agent->is_feature_phone()) {
            mb_convert_variables('UTF-8', 'SJIS-win', $_GET, $_POST, $_REQUEST);
        }
    }
}

