<?php

function smarty_function_sd_video($params, &$smarty)
{
    if (empty($params['data'])) {
        return "";
    }

    return json_encode([
        '@context' => 'http://schema.org',
        '@type' => 'VideoObject',
        'name' => $params['data']['title'],
        'description' => $params['data']['description'],
        'thumbnailUrl' => $params['data']['thumbnail_url'],
        'uploadDate' => date(DATE_ISO8601, strtotime($params['data']['uploaded_at']))
    ]);
}
