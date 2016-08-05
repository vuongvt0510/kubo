<?php

function smarty_function_mobile_html_tag($params, &$smarty)
{
    if (empty($params['type'])) {
        // フィーチャーフォンでない場合はスルー
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
        return <<<EOT
<?xml version="1.0" encoding="Shift_JIS"?>
<!DOCTYPE html PUBLIC "-//i-mode group (ja)//DTD XHTML i-XHTML(Locale/Ver.=ja/1.1) 1.0//EN" "i-xhtml_4ja_10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
EOT;

    case 'softbank':
        return <<<EOT
<?xml version="1.0" encoding="Shift_JIS"?>
<!DOCTYPE html PUBLIC "-//J-PHONE//DTD XHTML Basic 1.0 Plus//EN" "xhtml-basic10-plus.dtd">
<html>
EOT;

    case 'ezweb':
        return <<<EOT
<html>
EOT;

    default:
        return <<<EOT
<html>
EOT;
    }
}

