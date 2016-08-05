<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * OAuth認証用モジュール
 *
 * @property object load
 * @property CI_Session session
 * @property APP_Input input
 * @property APP_Operator current_user
 *
 * @package APP\Controller
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interset-marketing.net>
 */
trait APP_OAuth_authenticatable
{

    /**
     * @return string
     */
    protected function _generate_authorization_callback_url()
    {
        $protocol = $this->input->is_ssl() ? 'https' : 'http';

        $url = sprintf('%s://%s%s',
            $protocol,
            $this->input->server('SERVER_NAME'),
            $this->input->server('REQUEST_URI')
        );

        $parts = parse_url($url);

        $port = $this->input->server('SERVER_PORT');
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $path = empty($parts['path']) ? NULL : $parts['path'];
        $query = empty($parts['query']) ? NULL : $parts['query'];

        if (empty($port)) {
            $port = $scheme == 'https' ? '443' : '80';
        }

        /*
        if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }
        */

        if (empty($path)) {
            $path = "/authorization_callback";
        } else {
            $segments = explode("/", $path);

            array_pop($segments);
            array_push($segments, "authorization_callback");

            $path = implode("/", $segments);
        }

        return "$scheme://$host$path";
    }

    /**
     * @param string $prefix
     * @param array $data
     */
    protected function _store_oauth_session($prefix, $data)
    {
        $key = $this->_generate_oauth_session_key($prefix);

        $this->load->library("session");
        $this->session->set_userdata($key, $data);
    }

    /**
     * @param string $prefix
     * @return mixed
     */
    protected function _spend_oauth_session($prefix)
    {
        $key = $this->_generate_oauth_session_key($prefix);

        $this->load->library("session");

        $data = $this->session->userdata($key);
        $this->session->unset_userdata($key);

        return $data;
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function _generate_oauth_session_key($prefix)
    {
        return empty($prefix) ? "_oauth" :  "_{$prefix}_oauth";
    }

}

