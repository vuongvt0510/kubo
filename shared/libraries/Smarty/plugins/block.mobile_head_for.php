<?php

function smarty_block_mobile_head_for($params, $content, &$smarty, &$repeat)
{
    if ($repeat) return;
    if (!isset($content)) return;

    if (empty($params['type'])) {
        if (!$smarty->agent->is_feature_phone()) {
            $params['type'] = 'utf8';
        } else if ($smarty->agent->is_ezweb()) {
            $params['type'] = 'ezweb';
        } else if ($smarty->agent->is_softbank()) {
            $params['type'] = 'softbank';
        } else {
            $params['type'] = 'docomo';
        }
    }

    switch ($params['type'])
    {
    case 'docomo':
        $head =<<<EOT
<head>
<meta http-equiv="content-type" content="text/html; charset=Shift_JIS" />
EOT;
        break;

    case 'softbank':
        $head =<<<EOT
<head>
<meta http-equiv="content-type" content="text/html; charset=Shift_JIS" />
EOT;
        break;

    case 'ezweb':
        $head =<<<EOT
<head>
<meta http-equiv="content-type" content="text/html; charset=Shift_JIS" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
EOT;
        break;

    default:
        $head =<<<EOT
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
EOT;
        break;
    }

    return $head . $content;
}

