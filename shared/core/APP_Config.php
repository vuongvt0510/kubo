<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class APP_Config extends CI_Config {

    /**
     * @var array
     */
    var $_config_paths = array(SHAREDPATH, APPPATH);

    /**
     * APP_Config constructor.
     */
    public function __construct()
    {
        $this->config =& get_config();
        log_message('debug', "Config Class Initialized");

        // Set the base_url automatically if none was provided
        if ($this->config['base_url'] == '')
        {
            if (isset($_SERVER['HTTP_HOST']))
            {
                if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'){
                    $base_url = $_SERVER['HTTP_X_FORWARDED_PROTO'];
                } else {
                    $base_url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
                }
                $base_url .= '://'. $_SERVER['HTTP_HOST'];
                $base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
            }

            else
            {
                $base_url = 'http://localhost/';
            }

            $this->set_item('base_url', $base_url);
        }
    }

    /**
     * 設定ファイル読み込み
     *
     * @access public
     *
     * @param string $file
     * @param bool $use_sections
     * @param bool $fail_gracefully
     *
     * @return bool
     */
    public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
    {
        $file = ($file == '') ? 'config' : str_replace('.php', '', $file);
        $loaded = FALSE;

        foreach ($this->_config_paths as $path) {
            $check_locations = defined('ENVIRONMENT')
                ? array($file, ENVIRONMENT.'/'.$file)
                : array($file);

            foreach ($check_locations as $location) {
                $file_path = $path.'config/'.$location.'.php';

                if (in_array($file_path, $this->is_loaded, TRUE)) {
                    $loaded = TRUE;
                    continue;
                }

                if (!file_exists($file_path)) {
                    continue;
                }

                include($file_path);

                if ( ! isset($config) OR ! is_array($config)) {
                    if ($fail_gracefully === TRUE) {
                        return FALSE;
                    }
                    show_error('Your '.$file_path.' file does not appear to contain a valid configuration array.');
                }

                if ($use_sections === TRUE) {
                    if (isset($this->config[$file])) {
                        $this->config[$file] = array_merge($this->config[$file], $config);
                    } else {
                        $this->config[$file] = $config;
                    }
                } else {
                    $this->config = array_merge($this->config, $config);
                }

                $this->is_loaded[] = $file_path;
                unset($config);

                $loaded = TRUE;
                log_message('debug', 'Config file loaded: '.$file_path);
            }
        }

        if ($loaded === FALSE) {
            if ($fail_gracefully === TRUE) {
                return FALSE;
            }
            show_error('The configuration file '.$file.'.php'.' does not exist.');
        }

        return TRUE;
    }

    /**
     * Site URL
     * SSLに強制的に変換するオプションを追加
     *
     * @access public
     * @param string $uri URI
     * @param bool $force_ssl SSL変換にする
     * @return string
     */
    public function site_url($uri = '', $force_ssl = NULL)
    {
        if ($uri == '')
        {
            return $this->_site_root($force_ssl).$this->item('index_page');
        }

        if ($this->item('enable_query_strings') == FALSE)
        {
            $suffix = ($this->item('url_suffix') == FALSE) ? '' : $this->item('url_suffix');
            return $this->_site_root($force_ssl).$this->slash_item('index_page').$this->_uri_string($uri).$suffix;
        }
        else
        {
            return $this->_site_root($force_ssl).$this->item('index_page').'?'.$this->_uri_string($uri);
        }
    }

    /**
     * Base URL
     * SSLに強制的に変換するオプションを追加
     * 
     * @access public
     * @param string $uri URI
     * @param bool $force_ssl SSL変換する・しない
     * @return string
     */
    function base_url($uri = '', $force_ssl = NULL)
    {
        return $this->_site_root($force_ssl).ltrim($this->_uri_string($uri),'/');
    }

    /**
     * Site root URL
     *
     * @access protected
     * @param bool $force_ssl
     * @return string
     */
    protected function _site_root($force_ssl = NULL)
    {
        $b = $this->slash_item('base_url');
        if (isset($force_ssl)) {
            if ($force_ssl) {
                $b = preg_replace('/^https?\:\/\//', 'https://', $b);
            } else {
                $b = preg_replace('/^https\:\/\//', 'http://', $b);
            }
        }

        return $b;
    }

}
