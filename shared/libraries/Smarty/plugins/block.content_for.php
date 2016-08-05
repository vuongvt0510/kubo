<?php

function smarty_block_content_for($params, $content, &$smarty, &$repeat)
{
    if ($repeat) return;
    if ( ! isset($content)) return;

    $smarty->_content_add($params['name'], $content);

    return '';
}

