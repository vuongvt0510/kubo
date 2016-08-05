<?php

function smarty_function_content_yield($params, &$smarty)
{
    return $smarty->_content_yield($params['name']);
}

