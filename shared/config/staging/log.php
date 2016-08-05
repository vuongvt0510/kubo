<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| ログ設定
|--------------------------------------------------------------------------
|
| APP_Logを利用している場合は、こちらのフォーマット設定が最優先される。
| APP_Logはlog4phpを利用しているので、詳しい設定は、log4phpを参照すること。
|
| http://logging.apache.org/log4php/index.html
|
*/
$log = array(
    // CodeiniterのLogで出力されるレベル
    //
    //  0 = FATAL
    //  1 = FATAL・ERROR・WARN
    //  2 = FATAL・ERROR・WARN・INFO
    //  3 = FATAL・ERROR・WARN・DEBUG
    //  4 = すべて
    'threshold' => 2,

    // 全てのログの共通設定。ログ関連で共通で出力したいものはこちらを利用する
    // 'rootLogger' => array(
    //     'appenders' => array('default')
    // )
    //

    'loggers' => array(
        // CodeiniterのLogで使われる設定 (削除しないこと)
        'codeigniter' => array(
            'appenders' => array('codeigniter'
            //, 'syslog'
            )
        )
    ),

    'appenders' => array()
);


$log['appenders']['codeigniter'] = array(
    'class' => 'LoggerAppenderFile',
    'layout' => array(
        'class' => 'LoggerLayoutPattern',
        'params' => array(
            'conversionPattern' => '%date{Y-m-d H:i:s,u} %s{SERVER_ADDR} [%pid] %-5level: %msg%n'
        )
    ),
    'params' => array(
        'file' => APPPATH . 'logs/application.log',
        'append' => TRUE,
    ),
);

$log['appenders']['syslog'] = array(
    'class' => 'LoggerAppenderSyslog',
    'params' => array(
        'ident' => 'CI',
        'facility' => 'LOCAL0'
    ),
    'layout' => array(
        'class' => 'LoggerLayoutSimple',
    ),
    'filters' => array(
        array(
            'class' => 'LoggerFilterLevelRange',
            'params' => array(
                'levelMin' => 'error',
                'levelMax' => 'fatal',
                'acceptOnMatch' => TRUE
            )
        )
    )
);
