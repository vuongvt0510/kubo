<?php

function smarty_function_sd_breadcrumb($params, &$smarty)
{
    if (empty($params['data'])) {
        return "";
    }

    $json = [
        '@context' => 'http://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => []
    ];

    $i = 1;
    foreach ($params['data'] AS $v) {
        if (empty($v['name'])) {
            continue;
        }

        $tmp = [
            '@type' => 'ListItem',
            'position' => $i++,
            'item' => [
                '@id' => $v['url'],
                'name' => $v['name']
            ]
        ];

        $json['itemListElement'][] = $tmp;
    }

    return json_encode($json);
}

