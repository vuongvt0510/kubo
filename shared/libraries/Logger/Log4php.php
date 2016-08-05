<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ログドライバ - Log4php
 *
 * @author Yoshikazu Ozawa
 */
class APP_Log_driver_log4php {

    protected $_levels = array(
        'FATAL' => '0',
        'ERROR' => '1',
        'WARN' => '1',
        'DEBUG' => '2',
        'INFO' => '3',
        'TRACE' => '4',
        'ALL' => '4'
    );

    public function __construct($params = array())
    {
        require_once SHAREDPATH . "third_party/Apache_log4php/log4php/Logger.php";

        // 設定ファイル読み込み
        $files = array(
            APPPATH . "config/" . ENVIRONMENT . "/log.php",
            APPPATH . "config/log.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/log.php",
            SHAREDPATH . "config/log.php"
        );

        foreach ($files as $f) {
            if (file_exists($f)) {
                include $f;
                break;
            }
        }

        // デフォルト設定がない場合は、CIを踏襲するような設定を追加
        if (empty($log) || empty($log['threshold'])) {
            $log['threshold'] = 4;
        }

        if (empty($log) || empty($log['loggers']) || empty($log['loggers']['codeigniter'])) {
            $log['loggers']['codeigniter'] = array(
                'appenders' => array('codeigniter')
            );
        }

        if (empty($log['appenders']['codeigniter'])) {
            $log['appenders']['codigniter'] = array(
                'class' => 'LoggerAppenderDailyFile',
                'layout' => array(
                    'class' => 'LoggerLayoutPattern',
                    'params' => array(
                        'conversionPattern' => '%date{Y-m-d H:i:s,u} [%pid] %-5level %msg%n'
                    )
                ),
                'params' => array(
                    'file' => APPPATH . 'logs/log-%s.php',
                    'append' => TRUE,
                    'datePattern' => 'Y-m-d'
                )
            );
        }

        // CIの設定である log_threshold に合わせて出力レベルのフィルタを設定

        switch ((int)$log['threshold']) {
        case 0:
            $log['appenders']['codeigniter']['filters'][] = array(
                'class' => 'LoggerFilterLevelMatch',
                'params' => array(
                    'levelToMatch' => 'fatal',
                    'acceptOnMatch' => TRUE
                )
            );
            break;
        case 1:
            $log['appenders']['codeigniter']['filters'][] = array(
                'class' => 'LoggerFilterLevelRange',
                'params' => array(
                    'levelMin' => 'warn',
                    'acceptOnMatch' => TRUE
                )
            );
            break;
        case 2:
            $log['appenders']['codeigniter']['filters'][] = array(
                'class' => 'LoggerFilterLevelRange',
                'params' => array(
                    'levelMin' => 'info',
                    'acceptOnMatch' => TRUE
                )
            );
            break;
        case 3:
            $log['appenders']['codeigniter']['filters'][] = array(
                'class' => 'LoggerFilterLevelMatch',
                'params' => array(
                    'levelToMatch' => 'trace',
                    'acceptOnMatch' => FALSE
                )
            );
           break;
        case 4:
            // レベルによるフィルタリングはしない
            break;
        }

        unset($log['threshold']);

        Logger::configure($log);

        $this->logger = Logger::getLogger('codeigniter');
    }

    public function write_log($level = 'error', $message, $php_error = FALSE)
    {
        switch (strtolower($level)) {
        case 'fatal':
        case 'error':
        case 'warn':
        case 'info':
        case 'trace':
            call_user_func(array($this->logger, strtolower($level)), $message);
            break;

        default:
            $this->logger->debug($message);
            break;
        }
    }
}

