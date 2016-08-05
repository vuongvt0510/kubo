<?php

function smarty_function_sd_website($params, &$smarty)
{
    if (empty($params['data'])) {
        return "";
    }

    return json_encode([
        '@context' => 'http://schema.org',
        '@type' => 'WebSite',
        'name' => $params['data']['site_name'],
        'alternateName' => $params['data']['site_description'],
        'url' => site_url()
    ]);
}

