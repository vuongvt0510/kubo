<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 入力クラス
 *
 * @author Yoshikazu Ozawa
 */
class APP_Input extends CI_Input {

    /**
     * AcceptLanguageを取得する
     * @var array
     */
    protected $accept_language = NULL;

    /**
     * SSL通信かどうか
     *
     * @access public
     * @return bool
     */
    function is_ssl()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https';
        } else {
            return isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
        }
    }

    /**
     * GETリクエストかどうか
     *
     * @access public
     * @return bool
     */
    function is_get()
    {
        return $this->server("REQUEST_METHOD") === "GET";
    }

    /**
     * POSTリクエストかどうか
     *
     * @access public
     * @return bool
     */
    function is_post()
    {
        return $this->server("REQUEST_METHOD") === "POST";
    }

    /**
     * リクエストパラメータを取得
     * get_post()メソッドとは違い $_REQUEST 変数から値を取得する
     *
     * @access public
     * @param string $index パラメータ名
     * @param bool $xss_clean XSS対策をするか
     * @return mixed
     */
    function param($index = NULL, $xss_clean = FALSE)
    {
        if ($index === NULL AND ! empty($_REQUEST))
        {
            $req = array();

            foreach (array_keys($_REQUEST) as $key)
            {
                $req[$key] = $this->_fetch_from_array($_REQUEST, $key, $xss_clean);
            }
            return $req;
        }

        return $this->_fetch_from_array($_REQUEST, $index, $xss_clean);
    }

    /**
     * GETパラメータを取得
     * 取得できない場合は第二引数の値を返す
     *
     * @access public
     * @param string $index GETパラメータ名
     * @param mixed $default GETパラメータが存在しない場合の値
     * @param bool $xss_clean XSS対策をするか
     * @return mixed
     */
    function get_or_default($index, $default = NULL, $xss_clean = FALSE)
    {
        $result = $this->get($index, $xss_clean);
        return $result === NULL ? $default : $result;
    }

    /**
     * POSTパラメータを取得
     * 取得できない場合は第二引数の値を返す
     *
     * @access public
     * @param string $index POSTパラメータ名
     * @param mixed $default POSTパラメータが存在しない場合の値
     * @param bool $xss_clean XSS対策をするか
     * @return mixed
     */
    function post_or_default($index, $default = NULL, $xss_clean = FALSE)
    {
        $result = $this->post($index, $xss_clean);
        return $result === NULL ? $default : $result;
    }

    /**
     * リクエストパラメータを取得
     * 取得できない場合は第二引数の値を返す
     *
     * @access public
     * @param string $index リクエストパラメータ名
     * @param mixed $default リクエストパラメータが存在しない場合の値
     * @param bool $xss_clean XSS対策をするか
     * @return mixed
     */
    function get_post_or_default($index, $default = NULL, $xss_clean = FALSE)
    {
        $result = $this->get_post($index, $xss_clean);
        return $result === NULL ? $default : $result;
    }

    /**
     * リクエストパラメータを取得
     * 取得できない場合は第二引数の値を返す
     *
     * @access public
     * @param string $index リクエストパラメータ名
     * @param mixed $default リクエストパラメータが存在しない場合の値
     * @param bool $xss_clean XSS対策をするか
     * @return mixed
     */
    function param_or_default($index, $default = NULL, $xss_clean = FALSE)
    {
        $result = $this->param($index, $xss_clean);
        return $result === NULL ? $default : $result;
    }

    /**
    * クライアントのIPを返す
    *
    * CIの優先順位を変更してHTTP-X-FORWARDED-FORを最優先にチェックする
    *
    * @access public
    * @return string
    */
    public function ip_address()
    {
        if ($this->ip_address !== FALSE)
        {
            return $this->ip_address;
        }

        if ($this->server('HTTP_X_FORWARDED_FOR')) {
            $this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ($this->server('REMOTE_ADDR') AND $this->server('HTTP_CLIENT_IP')) {
            $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ($this->server('REMOTE_ADDR')) {
            $this->ip_address = $_SERVER['REMOTE_ADDR'];
        } elseif ($this->server('HTTP_CLIENT_IP')) {
            $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }

        if ($this->ip_address === FALSE) {
            $this->ip_address = '0.0.0.0';
            return $this->ip_address;
        }

        if (strpos($this->ip_address, ',') !== FALSE) {
            $x = explode(',', $this->ip_address);
            $this->ip_address = trim(end($x));
        }

        if ( ! $this->valid_ip($this->ip_address)) {
            $this->ip_address = '0.0.0.0';
        }

        return $this->ip_address;
    }

    /**
     * 言語設定を返す
     *
     * @access public
     * @return string
     */
    public function accept_language()
    {
        if (!is_null($this->accept_language)) {
            return $this->accept_language;
        }

        try {
            $al = $this->server("HTTP_ACCEPT_LANGUAGE");

            if ($al === FALSE || trim($al) === '') {
                throw new Exception("request header ACCEPT_LANGUAGE is not found.");
            }

            $al = explode(",", $al);
            $al = array_map(function($e) {
                $e = explode(";", trim($e));

                if (count($e) == 2) {
                    list($lang, $priority) = $e;

                    $lang = @trim($lang);
                    $priority = @trim($priority);

                } else if (count($e) == 1) {
                    $lang = $e[0];
                    $lang = @trim($lang);
                    $priority = "q=1.0";
                } else {
                    throw new Exception("ACCEPT_LANGUAGE is invalid format.");
                }

                if (empty($lang)) {
                    throw new Exception("ACCEPT_LANGUAGE is invalid format. lang is not set.");
                }

                list($k, $v) = explode("=", $priority);

                $k = @trim($k);
                $v = @trim($v);

                if (empty($k) || $k != "q") {
                    throw new Exception("priority query is invalid.");
                }

                // priorityのフォーマットが間違っている場合はエラー
                if (empty($v) || !is_numeric($v)) {
                    throw new Exception("priority can not set numeric.");
                }

                return array($lang, (float)$v);

            }, $al);

            uasort($al, function($a, $b) {
                if ($a[1] == $b[1]) return 0;
                return ($a[1] > $b[1]) ? -1 : 1;
            });

            $al = array_map(function($a){ return $a[0]; }, $al);

            $this->accept_language = $al;

        } catch (Exception $e) {
            // 不整合なフォーマットの場合は初期値を設定
            $this->accept_language = array();
        }

        return $this->accept_language;
    }
}

