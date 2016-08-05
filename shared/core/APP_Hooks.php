<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Hook拡張クラス
 *
 * アプリケーション間で共通のhook設定をできるように拡張
 *
 * @author Yoshikazu Ozawa
 */
class APP_Hooks extends CI_Hooks {

    /**
     * イニシャライザ
     */
    function __construct()
    {
        $CFG =& load_class('Config', 'core');
        log_message('info', 'Hooks Class Initialized');

        // If hooks are not enabled in the config file
        // there is nothing else to do
        if ($CFG->item('enable_hooks') === FALSE)
        {
            return;
        }

        // 共通のhookファイルを読み込む
        // 共通のhookファイルはアプリケーションでhookを無効にしていても読み込む
        if (defined('ENVIRONMENT') AND is_file(SHAREDPATH . 'config/' . ENVIRONMENT . '/hooks.php')) {
            include(SHAREDPATH . 'config/' . ENVIRONMENT . '/hooks.php');
        } elseif (is_file(SHAREDPATH . 'config/hooks.php')) {
            include(SHAREDPATH . 'config/hooks.php');
        }

        // Grab the "hooks" definition file.
        if (file_exists(APPPATH.'config/hooks.php'))
        {
            include(APPPATH.'config/hooks.php');
        }

        if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/hooks.php'))
        {
            include(APPPATH.'config/'.ENVIRONMENT.'/hooks.php');
        }

        // If there are no hooks, we're done.
        if ( ! isset($hook) OR ! is_array($hook))
        {
            return;
        }

        $this->hooks =& $hook;
        $this->enabled = TRUE;
    }

    /**
     * @param $data
     *
     * @return bool|void
     */
    public function _run_hook($data)
    {
        if ( ! is_array($data)) {
            return FALSE;
        }

        if ($this->_in_progress == TRUE) {
            return;
        }

        if ( ! isset($data['filepath']) OR ! isset($data['filename'])) {
            return FALSE;
        }

        $filepath = APPPATH . $data['filepath'] . '/' . $data['filename'];

        if ( ! file_exists($filepath)) {
            $filepath = SHAREDPATH . $data['filepath'] . '/' . $data['filename'];
            if ( ! file_exists($filepath)) {
                return FALSE;
            }
        }

        $class = FALSE;
        $function = FALSE;
        $params = '';

        if (isset($data['class']) AND $data['class'] != '') {
            $class = $data['class'];
        }

        if (isset($data['function'])) {
            $function = $data['function'];
        }

        if (isset($data['params'])) {
            $params = $data['params'];
        }

        if ($class === FALSE AND $function === FALSE) {
            return FALSE;
        }

        $this->in_progress = TRUE;

        if ($class !== FALSE) {
            if ( ! class_exists($class)) {
                /** @noinspection PhpIncludeInspection */
                require($filepath);
            }

            $HOOK = new $class;
            $HOOK->$function($params);
        } else {
            if ( ! function_exists($function)) {
                /** @noinspection PhpIncludeInspection */
                require($filepath);
            }

            /** @var callable $function */
            $function($params);
        }

        $this->in_progress = FALSE;
        return TRUE;
    }
}

