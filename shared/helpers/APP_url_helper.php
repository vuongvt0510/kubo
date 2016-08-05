<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Site URL
 *
 * @access public
 * @param string $uri
 * @param bool $force_ssl
 * @return string
 */
if ( ! function_exists('site_url'))
{
    function site_url($uri = '', $force_ssl = NULL)
    {
        $CI =& get_instance();
        return $CI->config->site_url($uri, $force_ssl);
    }
}

/**
 * Base URL
 *
 * @access public
 * @param string $uri
 * @param bool $force_ssl
 * @return string
 */
if ( ! function_exists('base_url'))
{
    function base_url($uri = '', $force_ssl = NULL)
    {
        $CI =& get_instance();
        return $CI->config->base_url($uri, $force_ssl);
    }
}

/**
 * Current URL
 *
 * @access	public
 * @param bool $force_ssl
 * @return	string
 */
if ( ! function_exists('current_url'))
{
    function current_url($force_ssl = NULL)
    {
        $CI =& get_instance();
        return $CI->config->site_url($CI->uri->uri_string(), $force_ssl);
    }
}

/**
 * Redirect
 *
 * @access public
 * @param string redirect
 */
if ( ! function_exists('redirect'))
{
    function redirect($uri = '', $method = 'location', $http_response_code = 302)
    {
        // セッション情報を反映させる
        if (class_exists('APP_Session')) {
            APP_Session::flash_cookie_all();
        }

        if ( ! preg_match('#^https?://#i', $uri))
        {
            $uri = site_url($uri);
        }

        switch($method)
        {
            case 'refresh'  : header("Refresh:0;url=".$uri);
            break;
            default         : header("Location: ".$uri, TRUE, $http_response_code);
            break;
        }
        exit;
    }
}

/**
 * Route url
 *
 * @access public
 * @param string $action
 * @param mixed $args
 * @param array $options
 */
if (! function_exists('route_url'))
{
    function route_url($route, $options = array())
    {
        $router =& load_class('Router', 'core');

        $force_ssl = NULL;
        if (isset($options['ssl'])) {
            $force_ssl = $options['ssl'];
            unset($options['ssl']);
        }

        $path = $router->generate_path($route, $options);

        $url = site_url($path, $force_ssl);
        if (empty($options)) {
            return $url;
        }

        return $url . "?" . http_build_query($options);
    }
}

