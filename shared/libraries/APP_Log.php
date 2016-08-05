<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'Logger/CodeIgniter.php';
require_once 'Logger/Log4php.php';

/**
 * ログクラス
 *
 * CodeIgniterとLog4phpのドライバを利用できるように拡張したクラス
 * CI_Logと同じI/Fを持っているドライバを指定することもできる
 *
 * @author Yoshikazu Ozawa
 */
class APP_Log {

    /**
     *
     */
    const MASKED_VALUE = "[MASKED]";

    /**
     * @var object
     */
    protected $_driver = 'codeigniter';

    /**
     * @var array
     */
    protected $masking_parameters = array();

    /**
     * APP_Log constructor.
     * @param array $params
     */
    public function __construct($params = array())
    {
        $config =& get_config();

        if (empty($config['log_driver'])) {
            $config['log_driver'] = 'codeigniter';
        }

        if (isset($config['log_masking_parameters'])) {
            $this->masking_parameters = $config['log_masking_parameters'];
        }

        switch ($driver = $config['log_driver']) {
        case 'log4php':
            require_once SHAREDPATH . "libraries/Logger/Log4php.php";
            $this->_driver = new APP_Log_driver_log4php($params);
            break;
        case 'codeigniter':
            require_once SHAREDPATH . "libraries/Logger/CodeIgniter.php";
            $this->_driver = new APP_Log_driver_codeigniter($params);
            break;
        default:
            $this->_driver = new $driver($params);
            break;
        }
    }

    /**
     * @param string $level
     * @param $message
     * @param bool|FALSE $php_error
     */
    public function write_log($level = 'error', $message, $php_error = FALSE)
    {
        switch (strtolower($level)) {
            case 'fatal':
            case 'error':
            case 'warn':
                $input =& load_class('Input', 'core');

                if ($input->is_cli_request()) {
                    global $argv;
                    $message .= " arguments is " . json_encode(array_slice($argv, 1));

                } else {
                    $message .= " requested from";
                    if (!empty($_SERVER['REQUEST_URI'])) {
                        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                        $message .=  ' ' . $path;
                    }
                    if (!empty($_GET)) $message .= ' GET:' . json_encode($this->mask_parameter($_GET));
                    if (!empty($_POST)) $message .= ' POST:' . json_encode($this->mask_parameter($_POST));
                }
        }

        return $this->_driver->write_log($level, $message, $php_error);
    }

    /**
     * @param $parameter
     * @return array
     */
    public function mask_parameter($parameter)
    {
        $result = array();

        if (empty($parameter)) {
            return $result;
        }

        foreach ($parameter as $idx => $value) {
            if (is_object($value)) {
                $value = get_object_vars($value);
            }

            if (is_array($value)) {
                $value = $this->mask_parameter($value);
            } else {
                // マスク値のチェック
                if (preg_match("/" . implode("|", $this->masking_parameters) . "/", $idx)) {
                    $value = self::MASKED_VALUE;
                }
            }

            $result[$idx] = $value;
        }

        return $result;
    }
}

