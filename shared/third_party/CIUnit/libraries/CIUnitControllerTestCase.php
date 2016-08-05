<?php

/**
 * コントローラのテストケース追加
 */
class CIUnit_ControllerTestCase extends CIUnit_ModelTestCase
{
    protected $controller = NULL;

    protected function setUp()
    {
        if ($this->controller) {
            $this->CI =& set_controller($this->controller);
        }
        parent::setUp();
    }

    /**
     * POSTを送る
     */
    protected function post($method, $options = array())
    {
        // POSTデータを設定
        $_GET = array();
        $_POST = empty($options['params']) ? array() : $options['params'];
        $_REQUEST = empty($options['params']) ? array() : $options['params'];

        // セッションを設定
        if (!empty($options['session'])) {
            $this->CI->load->library("session");
            $this->CI->session->set_userdata($options['session']);
        }

        if (method_exists($this->CI, '_remap')) {
            $this->CI->_remap($method, empty($options['arguments']) ? array() : $options['arguments']);
        } else {
            call_user_func_array(array($this->CI, $method), empty($options['arguments']) ? array() : $options['arguments']);
        }
    }

    /**
     * GETを送る
     */
    protected function get($method, $options = array())
    {
        // GETデータを設定
        $_GET = empty($options['params']) ? array() : $options['params'];
        $_POST = array();
        $_REQUEST = empty($options['params']) ? array() : $options['params'];

        // セッションを設定
        if (!empty($options['session'])) {
            $this->CI->load->library("session");
            $this->CI->session->set_userdata($options['session']);
        }

        if (method_exists($this->CI, '_remap')) {
            $this->CI->_remap($method, empty($options['arguments']) ? array() : $options['arguments']);
        } else {
            call_user_func_array(array($this->CI, $method), empty($options['arguments']) ? array() : $options['arguments']);
        }
    }

    /**
     * Redirect先の判定
     *
     * @todo 後でまともなassertメソッドに修正
     */
    protected function assertRedirectTo($expected, $message = '')
    {
        if (is_string($expected)) {
            $expected = array(
                'uri' => $expected,
                'method' => 'location',
                'http_response_code' => 302
            );
        }

        $this->assertEquals($expected, CIUnit::get_redirect(), $message);
    }

    /**
     * セッション情報に該当のキーがあるかどうか
     *
     * @todo 後でまともなassertメソッドに修正
     */
    protected function assertSessionHasKey($key, $message = '')
    {
        $CI =& get_instance();
        $userdata = $CI->session->all_userdata();
        $this->assertArrayHasKey($key, $userdata, $message);
    }

    /**
     * セッション情報が存在するかどうか
     *
     * @todo 後でまともなassertメソッドに修正
     */
    protected function assertSessionEmpty($key, $message = '')
    {
        $CI =& get_instance();
        $userdata = $CI->session->all_userdata();
        $this->assertEmpty(array_key_exists($key, $userdata) ? NULL : $userdata[$key], $message);
    }

    /**
     * セッション情報が一致するかどうか
     *
     * @todo 後でまともなassertメソッドに修正
     */
    protected function assertSessionEquals($key, $expected, $message = '')
    {
        $CI =& get_instance();
        $userdata = $CI->session->all_userdata();
        $this->assertEquals($expected, $userdata[$key], $message);
    }
}

