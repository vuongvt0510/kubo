<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class APP_Mobile_display_filter {

    public function convert_to_sjis()
    {
        $CI =& get_instance();
        $output = $CI->output->get_output();

        // フィーチャーフォン確認ができるクラスでない場合はスルー
        if ( ! isset($CI->agent) || ! (get_class($CI->agent) === "APP_User_agent" || is_subclass_of($CI->agent, "APP_User_agent"))) {
            return $CI->output->_display($output);
        }

        // コンバータを利用しない場合はスルー
        if (isset($CI->mobile_convert_skip) && $CI->mobile_convert_skip) {
            return $CI->output->_display($output);
        }

        // フィーチャーフォンでない場合はスルー
        if ( ! $CI->agent->is_feature_phone()) {
            return $CI->output->_display($output);
        }

        if ($CI->agent->is_softbank() || $CI->agent->is_ezweb()) {
            header("Content-type: text/html; charset=Shift_JIS;");
        } else {
            header("Content-type: application/xhtml+xml; charset=Shift_JIS" );
        }

        $output = mb_convert_encoding($output, 'SJIS-win', 'UTF-8');

        return $CI->output->_display($output);
    }
}

