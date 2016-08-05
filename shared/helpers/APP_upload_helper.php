<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('upload_form_error'))
{
    function upload_form_error($field)
    {
        $CI =& get_instance();
        if ( ! isset($CI->upload)) {
            return '';
        }
        return $CI->upload->error($field);
    }
}

/* End of file upload_helper.php */
/* Location: ./application/helpers/upload_helper.php */
