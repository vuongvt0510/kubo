<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * APP_Lang
 *
 * 読み込み時にSHAREDPATHをみるように修正
 *
 * @author Yoshikazu Ozawa
 */
class APP_Lang extends CI_Lang {

    /**
     * ロケール
     * @var string
     */
    private $locales = NULL;

    /**
     * 有効な文字列を返す
     *
     * @access public
     *
     * @param string $line
     * @param array $locales
     *
     * @return string
     */
    public function translate($line = "", $locales = array())
    {
        $directory = explode(".", $line);

        // TODO: 読み込み処理を自動化する必要があるかどうかは検討の余地あり
        $this->load($directory[0]);

        return $this->line($line);
    }

    /**
     * ロケールを設定する
     *
     * @access public
     * @param array $locales
     * @return string
     */
    public function set_locale($locales)
    {
        $this->locales = $locales;
    }

    /**
     * ロケールを取得する
     *
     * @access public
     * @param array $locales
     * @return string
     */
    public function lookup_locale($locales = array())
    {
        if (!is_null($this->locales)) {
            return $this->locales;
        }

        $CI =& get_instance();
        if (empty($locales)) {
            $locales = $CI->input->accept_language();
        }

        $support = $CI->config->item("support_languages");
        if (empty($support)) {
            $this->locales = array();
            return $this->locales;
        }

        $lookup = array();
        foreach ($locales as $request) {
            $result = Locale::lookup($support, $request, true);

            if (!empty($result)) {
                $lookup[] = $result;
            }
        }

        $this->locales = array_unique($lookup);

        return $this->locales;
    }

    /**
     * @param string $langfile
     * @param string $idioms
     * @param bool $return
     * @param bool $add_suffix
     * @param string $alt_path
     *
     * @return bool|void
     */
    public function load($langfile = '', $idioms = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
    {
        $langfile = str_replace('.php', '', $langfile);

        if ($add_suffix == TRUE)
        {
            $langfile = str_replace('_lang.', '', $langfile).'_lang';
        }

        $langfile .= '.php';

        if (in_array($langfile, $this->is_loaded, TRUE))
        {
            return;
        }

        $config =& get_config();

        if ($idioms == '')
        {
            $l = $this->lookup_locale();
            if (empty($l)) {
                if (isset($config['language']) && $config['language'] !== '') {
                    $idioms = array($this->_locale_to_idiom($config['language']));
                } else {
                    $idioms = array($this->_locale_to_idiom('en'));
                }
            } else {
                $idioms = array();
                foreach ($l as $c) {
                    $idioms[] = $this->_locale_to_idiom($c);
                }
            }
        }

        if (!is_array($idioms)) {
            $idioms = array($idioms);
        }

        $found = FALSE;

        foreach (array_reverse($idioms) as $idiom) {
            $langfile_yaml = preg_replace("/\.php$/", ".yaml", $langfile);

            if ($alt_path != '' && function_exists("yaml_emit_file") && file_exists($alt_path.'language/'.$idiom.'/'.$langfile_yaml))
            {
                $yaml = $this->_load_yaml($alt_path.'language/'.$idiom.'/'.$langfile_yaml);
                foreach ($yaml as $k => $v) {
                    $lang[$k] = $v;
                }
                log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile_yaml);
            }
            else if ($alt_path != '' && file_exists($alt_path.'language/'.$idiom.'/'.$langfile))
            {
                include($alt_path.'language/'.$idiom.'/'.$langfile);
                log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);
            }
            else
            {
                foreach (array_reverse(get_instance()->load->get_package_paths(TRUE)) as $package_path)
                {
                    if (function_exists("yaml_emit_file") && file_exists($package_path.'language/'.$idiom.'/'.$langfile_yaml))
                    {
                        $yaml = $this->_load_yaml($package_path.'language/'.$idiom.'/'.$langfile_yaml);
                        foreach ($yaml as $k => $v) {
                            $lang[$k] = $v;
                        }

                        log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile_yaml);

                        $found = TRUE;
                    }
                    else if (file_exists($package_path.'language/'.$idiom.'/'.$langfile))
                    {
                        include($package_path.'language/'.$idiom.'/'.$langfile);
                        log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);
                        $found = TRUE;
                    }
                }
            }
        }

        if ($alt_path == '' && $found !== TRUE)
        {
            show_error('Unable to load the requested language file: language/('.implode("|", $idioms).')/'.$langfile);
        }

        if ( ! isset($lang))
        {
            log_message('error', 'Language file contains no data: language/('.implode("|", $idioms).')/'.$langfile);
            return;
        }

        if ($return == TRUE)
        {
            return $lang;
        }

        $this->is_loaded[] = $langfile;
        $this->language = array_merge($this->language, $lang);
        unset($lang);

        return TRUE;

    }

    /**
     * YAMLファイルを読み込む
     *
     * @access private
     * @param string $file_path
     * @return mixed
     */
    private function _load_yaml($file_path)
    {
        $yaml = yaml_parse_file($file_path, 0);
        return $this->_flatten_yaml($yaml);
    }

    /**
     * YAML形式で展開されたデータの階層構造を1階層にする
     *
     * @access private
     * @param array $data
     * @param string $prefix
     * @return array
     */
    private function _flatten_yaml($data, $prefix = "")
    {
        $flatten = array();

        foreach ($data as $key => $value) {
            $idx = empty($prefix) ? $key : "{$prefix}.{$key}";

            if (is_strict_array($value)) {
                // 言語ファイルで配列形式はないはずなので無視する
                return FALSE;
            } else if (is_hash($value)) {
                $tmp = $this->_flatten_yaml($value, $idx);
                $flatten = array_merge($flatten, $tmp);
            } else {
                $flatten[$idx] = $value;
            }
        }

        return $flatten;
    }

    /**
     * ロケールからCIのディレクトリ形式の名称に変換
     *
     * @access private
     * @param string $locale
     * @return string
     */
    private function _locale_to_idiom($locale)
    {
        $c = Locale::getDisplayName($locale, 'en');
        return strtolower(preg_replace("/\s+/", "_", trim(str_replace(array(" ", "(", ")", ","), " ", $c))));
    }
}

