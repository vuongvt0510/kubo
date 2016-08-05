<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config["database_schema"] = array(
    // このファイルは自動で生成されるファイルである。
    // データベースのスキーマを更新した場合は、こちらのファイルを生成し直すこと。
    // データベースのスキーマとこのファイルに差分がある状態で本番環境へ移行すると不具合の原因になるので注意すること。
<!--{foreach from=$schema item=database}-->

    '<!--{$database.name}-->' => array(
<!--{foreach from=$database.tables item=table}-->

        '<!--{$table.name}-->' => array(
<!--{foreach from=$table.columns item=column}-->
            '<!--{$column.name}-->' => array('type' => '<!--{$column.type}-->', 'strict_type' => "<!--{$column.strict_type}-->", 'null' => <!--{$column.null}-->),
<!--{/foreach}-->
        ),
<!--{/foreach}-->
    ),
<!--{/foreach}-->
);

