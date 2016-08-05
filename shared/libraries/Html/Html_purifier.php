<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "third_party/Htmlpurifier-4.7.0/library/HTMLPurifier.auto.php";

/**
 * Html purifier is a library which is used to clean bad html and defeat xss.
 *
 * @author Duy Ton That <duytt@nal.vn>
 */
class Html_purifier
{

    /**
     * @var HTMLPurifier HTML Purifier instance
     */
    private $instance;

    /**
     * Html_purifier constructor.
     * @param array $params configs for htmlpurifier
     */
    public function __construct($params = array()) {
        $config = $this->build_config($params);
        $this->instance = new HTMLPurifier($config);
    }

    /**
     * Build and return HTMLPurifier_Config from array
     * @param array $configs
     * @return HTMLPurifier_Config
     */
    private function build_config($configs = array())
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
        $config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your doctype

        if (is_array($configs) && count($configs) > 1) {
            foreach ($configs as $key => $val) {
                $configs->set($key, $val);
            }
        }
        return $config;
    }

    /**
     * Clean html
     * @param string $html
     * @param array $configs
     * @return string
     */
    public function clean_html($html = '', $configs = NULL) {
        return !$configs ? $this->instance->purify($html) : $this->instance->purify($html, $this->build_config($configs));
    }
}

