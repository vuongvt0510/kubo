<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class APP_Router extends CI_Router {

    /**
     * URLマッピング
     * @var array
     */
    protected $url_mappings = array();

    /**
     * リクエストフォーマットを返す
     *
     * @access public
     * @return string
     */
    public function fetch_format()
    {
        return $this->uri->extension;
    }

    /**
     * URL生成
     *
     * 利用したパラメータは自動的に削除されるので注意
     *
     * @access public
     *
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    public function generate_path($route, & $params = array())
    {
        $this->generate_reserve_mapping();

        if (isset($this->url_mappings[$route])) {

            // ルーティング制御されていもの

            $map = $this->url_mappings[$route];

            $arguments = $map['arguments'];

            foreach ($arguments as & $a) {
                if (!isset($params[$a])) {
                    throw new InvalidArgumentException(sprintf("%s `%s` is not set.", $route, $a));
                }

                $encoded = urlencode($params[$a]);

                unset($params[$a]);

                $a = $encoded;
            }

            return vsprintf($map['url'], $arguments);

        } else {

            // ルーティングが存在しない場合はCIの規定の方式に則る

            return $route;
        }
    }

    /**
     * URL生成用のマッピング生成
     *
     * @access public
     * @return void
     * @todo 全てのURL体系に万能になっていないので注意
     */
    public function generate_reserve_mapping()
    {
        if (!empty($this->url_mappings)) {
            return;
        }

        foreach ($this->routes as $url => $action) {

            switch ($url) {
            case 'default_controller':
                $this->url_mappings[$action] = array(
                    'url' => '/',
                    'arguments' => array()
                );
                break;

            case '404_override':
                // 404は無視
                break;

            default:
                // TODO: 良い展開方法を模索する

                $action = implode("/", array_filter(explode("/", $action), function($v) use(& $arguments){
                    if (preg_match("/^\\$[0-9]+$/", $v)) {
                        return false;
                    }
                    return true;
                }));

                $arguments = array();
                if (preg_match_all("/:([a-z0-9\_]+)/", $url, $matches)) {
                    $arguments = $matches[1];
                }

                $url = preg_replace("/\\(:[a-z0-9\_]+\\)/", "%s", $url);

                $this->url_mappings[$action] = array(
                    'arguments' => $arguments,
                    'url' => $url
                );

                break;
            }
        }
    }

    /**
     * サブディレクトリパス追加
     *
     * @access public
     * @param string $dir
     * @return void
     */
    public function add_directory($dir)
    {
        $subdir = $this->fetch_directory();

        if (empty($subdir)) {
            $this->set_directory($dir);
        } else {
            $this->directory .= str_replace(array('/', '.'), '', $dir).'/';
        }
    }

    /**
     * パース処理
     *
     * @access private
     * @return void
     */
    protected function _parse_routes()
    {
        // Turn the segment array into a URI string
        $uri = implode('/', $this->uri->segments);

        // Get HTTP verb
        $http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

        // Loop through the route array looking for wildcards
        foreach ($this->routes as $key => $val)
        {
            // Check if route format is using HTTP verbs
            if (is_array($val))
            {
                $val = array_change_key_case($val, CASE_LOWER);
                if (isset($val[$http_verb]))
                {
                    $val = $val[$http_verb];
                }
                else
                {
                    continue;
                }
            }

            // Convert wildcards to RegEx
            $key = str_replace(array(':any', ':num', ':id'), array('[^/]+', '[0-9]+', '[0-9]+'), $key);
            $key = preg_replace('/:[a-z0-9\_]+/', '.+', $key);

            // Does the RegEx match?
            if (preg_match('#^'.$key.'$#', $uri, $matches))
            {
                // Are we using callbacks to process back-references?
                if ( ! is_string($val) && is_callable($val))
                {
                    // Remove the original string from the matches array.
                    array_shift($matches);

                    // Execute the callback using the values in matches as its parameters.
                    $val = call_user_func_array($val, $matches);
                }
                // Are we using the default routing method for back-references?
                elseif (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE)
                {
                    $val = preg_replace('#^'.$key.'$#', $val, $uri);
                }

                $this->_set_request(explode('/', $val));
                return;
            }
        }

        // If we got this far it means we didn't encounter a
        // matching route so we'll set the site default route
        $this->_set_request(array_values($this->uri->segments));
    }

    /**
     * リクエスト確認
     *
     * 共通ディレクトリにあるコントローラも含めてチェックするように拡張
     *
     * @access public
     *
     * @param $segments
     *
     * @return mixed
     */
    public function _validate_request($segments)
    {
        $result = $this->__validate_request(APPPATH, $segments);
        if (FALSE !== $result) {
            return $result;
        }

        $result = $this->__validate_request(SHAREDPATH, $segments);
        if (FALSE !== $result) {
            return $result;
        }

        // If we've gotten this far it means that the URI does not correlate to a valid
        // controller class.  We will now see if there is an override
        if ( ! empty($this->routes['404_override']))
        {
            $x = explode('/', $this->routes['404_override']);

            $this->set_class($x[0]);
            $this->set_method(isset($x[1]) ? $x[1] : 'index');

            return $x;
        }

        show_404(!empty($segments[0]) ? $segments[0] : null);
    }

    /**
     * リクエストを検証
     *
     * @access protected
     *
     * @param $dir
     * @param $segments
     *
     * @return array|bool
     */
    protected function __validate_request($dir, $segments)
    {
        if (count($segments) == 0)
        {
            return $segments;
        }

        // Does the requested controller exist in the root folder?
        if (file_exists($dir.'controllers/'.ucfirst($segments[0]).'.php'))
        {
            return $segments;
        }

        // サブディレクトリ解析
        do {
            $path = $dir.'controllers/'. $this->fetch_directory() . $segments[0];

            if ( ! is_dir($path)) break;

            $this->add_directory($segments[0]);
            $segments = array_slice($segments, 1);

        } while (count($segments) > 0);

        if (count($segments) > 0)
        {
            // Does the requested controller exist in the sub-folder?
            if ( ! file_exists($dir.'controllers/'.$this->fetch_directory().ucfirst($segments[0]).'.php'))
            {
                return FALSE;
            }

            return $segments;
        }
        else
        {
            // Is the method being specified in the route?
            if (strpos($this->default_controller, '/') !== FALSE)
            {
                $x = explode('/', $this->default_controller);

                $this->set_class($x[0]);
                $this->set_method($x[1]);
            }
            else
            {
                $this->set_class($this->default_controller);
                $this->set_method('index');
            }

            // Does the default controller exist in the sub-folder?
            if ( ! file_exists($dir.'controllers/'.$this->fetch_directory().$this->default_controller.'.php'))
            {
                $this->directory = '';
                return array();
            }

            return $segments;
        }
    }

}
