<?php

function smarty_modifier_translate($str)
{
    $CI =& get_instance();
    return $CI->lang->translate($str);
}

