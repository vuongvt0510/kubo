<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "core/APP_Business_time_calculator.php";


if ( ! function_exists('array_value_or_default'))
{
    function array_value_or_default($key, $ary, $default = NULL)
    {
        return array_key_exists($key, $ary) ? $ary[$key] : $default;
    }
}

if ( ! function_exists('is_strict_array'))
{
    function is_strict_array($var)
    {
        return is_array($var) && $var == array_values($var);
    }
}

if ( ! function_exists('is_hash'))
{
    function is_hash($var, $empty = FALSE)
    {
        if ($empty && is_array($var) && empty($var)) return TRUE;
        return is_array($var) && $var != array_values($var);
    }
}

if ( ! function_exists('is_blank'))
{
    function is_blank($var)
    {
        if (is_null($var)) {
            return TRUE;
        }

        if (trim($var) === "") {
            return TRUE;
        }

        return FALSE;
    }
}

if ( ! function_exists('is_present'))
{
    function is_present($var)
    {
        return !is_blank($var);
    }
}

if ( ! function_exists('to_bool'))
{
    function to_bool($var)
    {
        if (in_array($var, array('1', 1, 't', 'T', 'true', 'TRUE', TRUE), TRUE)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

if (!function_exists('business_date'))
{
    /**
     * date関数の業務時間考慮版
     *
     * @param string $format
     * @param int $timestamp
     * @return string
     */
    function business_date($format, $timestamp = NULL)
    {
        if (is_null($timestamp)) {
            $timestamp = business_time();
        }

        return date($format, $timestamp);
    }

    /**
     * gmdate関数の業務時間考慮版
     *
     * @param string $format
     * @param int $timestamp
     * @return string
     */
    function gm_business_date($format, $timestamp = NULL)
    {
        if (is_null($timestamp)) {
            $timestamp = business_time();
        }

        return gmdate($format, $timestamp);
    }
}

if (!function_exists('business_time'))
{
    // テストダブルのために業務計算ロジックをグローバル変数へ待避
    $GLOBALS['__BUSINESS_TIME_CALCULATOR'] = new APP_Business_time_calculator();

    /**
     * time関数の業務時間考慮版
     *
     * @return int
     */
    function business_time()
    {
        global $__BUSINESS_TIME_CALCULATOR;
        return $__BUSINESS_TIME_CALCULATOR->now();
    }
}

if (!function_exists('business_strtotime'))
{
    /**
     * strtotime関数の業務時間考慮版
     *
     * @return int
     */
    function business_strtotime($time, $now = NULL)
    {
        if (is_null($now)) {
            $now = business_time();
        }

        return strtotime($time, $now);
    }
}


if ( ! function_exists('fixed_datetime'))
{
    class FixedDateTime {
        static $datetimes = array();

        static function get($namespace = 'now', $time = 'now', $timezone = NULL)
        {
            if (array_key_exists($namespace, self::$datetimes)) {
                return self::$datetimes[$namespace];
            }

            return (self::$datetimes[$namespace] = new DateTime($time, $timezone));
        }
    }

    function fixed_datetime($namespace = 'now', $time = 'now', $timezone = NULL)
    {
        return FixedDateTime::get($namespace, $time, $timezone);
    }
}


if ( ! function_exists('generate_unique_key'))
{
    function generate_unique_key($length = 8)
    {
        $key = "";

        $first_string = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $string = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        $len = strlen($string);

        $key .= substr($first_string, mt_rand(0, strlen($first_string) - 1), 1);
        for ($i = 1; $i < $length; $i++) {
            $key .= substr($string, mt_rand(0, $len - 1), 1);
        }

        return $key;
    }
}

if ( ! function_exists('generate_salt'))
{
    function generate_salt()
    {
        return generate_unique_key(32);
    }
}

if ( ! function_exists('encrypt_password'))
{
    function encrypt_password($password, $salt)
    {
        return base64_encode(hash_hmac('sha256', $password, $salt, TRUE));
    }
}

if ( ! function_exists('set_query_value'))
{
    function set_query_value($name, $default = '')
    {
        $CI =& get_instance();
        $value = $CI->input->get($name);

        if (FALSE !== $value) {
            return $value;
        } else {
            return $default;
        }
    }
}

if ( ! function_exists('controller_name'))
{
    function controller_name()
    {
        $CI =& get_instance();
        return $CI->router->class; 
    }
}

if ( ! function_exists('action_name'))
{
    function action_name()
    {
        $CI =& get_instance();
        return $CI->router->method; 
    }
}

if ( ! function_exists('h'))
{
    function h($string)
    {
        return htmlspecialchars($string, ENT_QUOTES);
    }
}

if ( ! function_exists('t'))
{
    function t($line)
    {
        $CI =& get_instance();
        return $CI->lang->tranlate($line);
    }
}

if ( ! function_exists('mb_trim'))
{
    define('MB_TRIM_DEFAULT_MASK', '\x0-\x20\x7f\xc2\xa0\xe3\x80');
    define('MB_TRIM_JAPANESE_MASK', '\x0-\x20\x7f\xc2\xa0\xe3\x80　');

    function mb_trim($str, $character_mask = MB_TRIM_DEFAULT_MASK)
    {
        return preg_replace(sprintf('/\A[%s]++|[%s]++\z/u', $character_mask, $character_mask), '', $str);
    }

    function jp_trim($str)
    {
        return mb_trim($str, MB_TRIM_JAPANESE_MASK);
    }
}

if ( ! function_exists('mb_truncate'))
{
    function mb_truncate($str, $n = 500, $end_char = '...')
    {
        mb_internal_encoding("UTF-8");
        if (mb_strlen($str) > $n) {
            return mb_substr($str, 0, $n).$end_char;
        } else {
            return $str;
        }
    }
}

if ( ! function_exists('simple_format'))
{
    function simple_format($string)
    {
        $str = str_replace("\r", "\n", str_replace("\r\n", "\n", $string));
        $ary = explode("\n\n", $str);
        return nl2br('<p>' . implode('</p><p>', $ary) . '</p>');
    }
}

if ( ! function_exists('postal_code_format'))
{
    function postal_code_format($postal_code)
    {
        return "〒" . substr($postal_code, 0, 3) . "-" . substr($postal_code, 3);
    }
}

if ( ! function_exists('datetime_format'))
{
    function datetime_format($datetime, $options = array())
    {
        $options = array_merge(array('date_separator' => '/', 'time_separator' => ':'), $options);

        $format = implode($options['date_separator'], array('Y', 'm', 'd')) . ' ' . implode($options['time_separator'], array('H', 'i'));
        
        return $datetime ? date($format, strtotime($datetime)) : '';
    }
}

if ( ! function_exists('date_format'))
{
    function date_format($date, $separator = "/")
    {
        return $date ? date("Y" . $separator . "m" . $separator . "d", strtotime($date)) : '';
    }
}

if ( ! function_exists('time_format'))
{
    function time_format($time)
    {
        return $time ? date("H:i", strtotime($time)) : '';
    }
}

if ( ! function_exists('log_mask'))
{
    function log_mask($array)
    {
        static $_log;
        $_log =& load_class('Log');

        return $_log->mask_parameter($array);
    }
}

/* End of file common_helper.php */
/* Location: ./application/core/helpers/common_helper.php */
