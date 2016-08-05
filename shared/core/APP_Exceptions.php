<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class APP_Exceptions extends CI_Exceptions {

    /**
     * 404エラー表示
     *
     * @param string $page
     * @param bool $log_error
     *
     * @return string|void
     */
    public function show_404($page = '', $log_error = TRUE)
    {
        if ($log_error) {
            log_message('info', '404 Page Not Found --> '.$page);
        }

        $heading = "お探しのページは見つかりません";
        $message = "ご指定のページは削除されたか、移動した可能性がございます。";

        if ((php_sapi_name() == 'cli') or defined('STDIN')) {
            echo $message . "\n";
            return;
        }

        set_status_header(404);
        echo $this->template_engine()->view("errors/error_404", array(
            "message" => $message,
            "heading" => $heading
        ), TRUE);
        exit;
    }

    /**
     * エラー表示
     *
     * @param string $heading
     * @param string $message
     * @param string $template
     * @param int $status_code
     *
     * @return string|void
     */
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        if ((php_sapi_name() == 'cli') or defined('STDIN')) {
            echo $message . "\n";
            return;
        }

        log_message("info", "render {$status_code} error --> {$heading} {$message}");

        set_status_header($status_code);

        if ($template !== 'error_404' && defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            $heading = "システムが大変混雑しております";
            $message = "しばらく経ってから再度お試しください。";
        }

        return $this->template_engine()->view("errors/" . $template, array(
            "message" => $message,
            "heading" => $heading
        ), TRUE);
    }

    /**
     * PHPエラー表示
     *
     * @param $severity
     * @param $message
     * @param $filepath
     * @param $line
     *
     * @return string|void
     */
    public function show_php_error($severity, $message, $filepath, $line)
    {
        if ((php_sapi_name() == 'cli') or defined('STDIN')) {
            return;
        }

        $severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

        echo $this->template_engine()->view("errors/error_php", array(
            'severity' => $severity,
            'message' => $message,
            'filepath' => $filepath,
            'line' => $line
        ), TRUE);
    }

    /**
     * @return object
     */
    private function & template_engine()
    {
        $config =& get_config();

        switch (@$config['template_engine']) {
        case 'smarty':
            $engine =& load_class('Smarty');
            break;
        default:
            $engine =& load_class('Loader', 'core');
            break;
        }

        return $engine;
    }
}

