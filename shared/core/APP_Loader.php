<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class APP_Loader
 */
class APP_Loader extends CI_Loader {

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct();

        $this->_ci_library_paths = array(APPPATH, SHAREDPATH, BASEPATH);
        $this->_ci_helper_paths = array(APPPATH, SHAREDPATH, BASEPATH);
        $this->_ci_model_paths = array(APPPATH, SHAREDPATH);
        $this->_ci_view_paths = array(APPPATH.'views/'	=> TRUE, SHAREDPATH.'views/' => TRUE);
    }

    /**
     * ヘルパーのロード
     *
     * @access public
     * @param array $helpers ヘルパー名
     * @return void
     */
    public function helper($helpers = array())
    {
        foreach ($this->_ci_prep_filename($helpers, '_helper') as $helper)
        {
            if (isset($this->_ci_helpers[$helper])) {
                continue;
            }

            $ext_helper = APPPATH.'helpers/'.config_item('subclass_prefix').$helper.'.php';
            if (!file_exists($ext_helper)) {
                $ext_helper = SHAREDPATH.'helpers/'.config_item('subclass_prefix').$helper.'.php';
            }

            if (file_exists($ext_helper)) {
                $shared_exists = FALSE;
                $base_exists = FALSE;

                $shared_helper = SHAREDPATH.'helpers/APP_'.$helper.'.php';
                if (file_exists($shared_helper)) {
                    $shared_exists = TRUE;
                }

                $base_helper = BASEPATH.'helpers/'.$helper.'.php';
                if (file_exists($base_helper)) {
                    $base_exists = TRUE;
                }

                if (!($shared_exists || $base_exists)) {
                    show_error('Unable to load the requested file: helpers/'.$helper.'.php');
                    return;
                }

                include_once($ext_helper);
                if ($shared_exists) include_once($shared_helper);
                if ($base_exists) include_once($base_helper);

                $this->_ci_helpers[$helper] = TRUE;
                log_message('debug', 'Helper loaded: '.$helper);
                continue;
            }

            $shared_helper = SHAREDPATH.'helpers/APP_'.$helper.'.php';
            if (file_exists($shared_helper)) {
                $base_exists = FALSE;

                $base_helper = BASEPATH.'helpers/'.$helper.'.php';
                if (file_exists($base_helper)) {
                    $base_exists = TRUE;
                }

                include_once($shared_helper);
                if ($base_exists) include_once($base_helper);

                $this->_ci_helpers[$helper] = TRUE;
                log_message('debug', 'Helper loaded: '.$helper);
                continue;
            }

            // Try to load the helper
            foreach ($this->_ci_helper_paths as $path)
            {
                if (file_exists($path.'helpers/'.$helper.'.php'))
                {
                    include_once($path.'helpers/'.$helper.'.php');

                    $this->_ci_helpers[$helper] = TRUE;
                    log_message('debug', 'Helper loaded: '.$helper);
                    break;
                }
            }

            // unable to load the helper
            if ( ! isset($this->_ci_helpers[$helper]))
            {
                show_error('Unable to load the requested file: helpers/'.$helper.'.php');
            }
        }
    }

    /**
     * @param $view
     * @param array $vars
     * @param bool $return
     *
     * @return object|string
     */
    public function view($view, $vars = array(), $return = FALSE)
    {
        $auto = TRUE;

        if (is_array($return)) {
            $options = $return;

            $return = isset($options['return']) ? $options['return'] : FALSE;
            $auto = isset($options['auto_select']) ? $options['auto_select'] : TRUE;
        }

        $CI =& get_instance();

        $dir = pathinfo($view, PATHINFO_DIRNAME);
        $filename = pathinfo($view, PATHINFO_FILENAME);
        $ext = pathinfo($view, PATHINFO_EXTENSION);

        $base_path = $dir . "/" . $filename;

        if ($ext == '') $ext = 'php';

        $files = array(
            $base_path . "." . $ext
        );

        if ($auto) {
            if (isset($CI->agent) && (get_class($CI->agent) === "APP_User_agent" || is_subclass_of($CI->agent, "APP_User_agent"))) {
                if ($CI->agent->is_smart_phone()) {
                    if ($CI->agent->is_iphone()) {
                        array_unshift($files, $base_path . ".iphone" . "." . $ext);
                    }

                    if ($CI->agent->is_android()) {
                        array_unshift($files, $base_path . ".android" . "." . $ext);
                    }
                    array_unshift($files, $base_path . ".sp" . "." . $ext);
                }

                if ($CI->agent->is_feature_phone()) {
                    array_unshift($files, $base_path . ".mobile" . "." . $ext);
                }
            }
        }

        foreach ($this->_ci_view_paths as $view_file => $cascade)
        {
            foreach ($files as $file) {
                if (file_exists($view_file.$file))
                {
                    return $this->_ci_load(array('_ci_view' => $file, '_ci_vars' => $this->_ci_object_to_array($vars), '_ci_return' => $return));
                }
            }
        }

        return $this->_ci_load(array('_ci_view' => $view, '_ci_vars' => $this->_ci_object_to_array($vars), '_ci_return' => $return));
    }

    /**
     * @return bool
     */
    public function ci_autoloader()
    {
        if (file_exists(SHAREDPATH.'config/autoload.php')) {
            include_once(SHAREDPATH.'config/autoload.php');
        }

        if (defined('ENVIRONMENT') AND file_exists(SHAREDPATH.'config/'.ENVIRONMENT.'/autoload.php')) {
            include_once(SHAREDPATH.'config/'.ENVIRONMENT.'/autoload.php');
        }

        if (file_exists(APPPATH.'config/autoload.php')) {
            include_once(APPPATH.'config/autoload.php');
        }

        if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/autoload.php')) {
            include_once(APPPATH.'config/'.ENVIRONMENT.'/autoload.php');
        }

        if ( ! isset($autoload)) {
            return FALSE;
        }

        // Autoload packages
        if (isset($autoload['packages'])) {
            foreach ($autoload['packages'] as $package_path) {
                $this->add_package_path($package_path);
            }
        }

        if (count($autoload['config']) > 0) {
            $CI =& get_instance();
            foreach ($autoload['config'] as $key => $val) {
                $CI->config->load($val);
            }
        }

        foreach (array('helper', 'language') as $type) {
            if (isset($autoload[$type]) AND count($autoload[$type]) > 0) {
                $this->$type($autoload[$type]);
            }
        }

        // A little tweak to remain backward compatible
        // The $autoload['core'] item was deprecated
        if ( ! isset($autoload['libraries']) AND isset($autoload['core'])) {
            $autoload['libraries'] = $autoload['core'];
        }

        // Load libraries
        if (isset($autoload['libraries']) AND count($autoload['libraries']) > 0) {
            // Load the database driver.
            if (in_array('database', $autoload['libraries'])) {
                $this->database();
                $autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
            }

            // Load all other libraries
            foreach ($autoload['libraries'] as $item) {
                $this->library($item);
            }
        }

        // Autoload models
        if (isset($autoload['model'])) {
            $this->model($autoload['model']);
        }
    }

    /**
     * @param $class
     * @param null $params
     * @param null $object_name
     *
     * @return null|void
     */
    protected function _ci_load_class($class, $params = NULL, $object_name = NULL)
    {
        $class = str_replace('.php', '', trim($class, '/'));

        $subdir = '';
        if (($last_slash = strrpos($class, '/')) !== FALSE) {
            $subdir = substr($class, 0, $last_slash + 1);
            $class = substr($class, $last_slash + 1);
        }

        // APP系のライブラリもまとめて読み込む
        foreach (array(ucfirst($class), strtolower($class)) as $class)
        {
            $subclass = APPPATH.'libraries/'.$subdir.config_item('subclass_prefix').$class.'.php';

            if (file_exists($subclass)) {
                $this->_ci_load_class_chain(ucfirst($class), array(
                    array('subdir' => BASEPATH . 'libraries/', 'prefix' => '', 'skip' => FALSE),
                    array('subdir' => SHAREDPATH . 'libraries/', 'prefix' => 'APP_', 'skip' => TRUE)
                ));

                if (in_array($subclass, $this->_ci_loaded_files)) {
                    if ( ! is_null($object_name)) {
                        $CI =& get_instance();
                        if ( ! isset($CI->$object_name)) {
                            return $this->_ci_init_class($class, config_item('subclass_prefix'), $params, $object_name);
                        }
                    }

                    $is_duplicate = TRUE;
                    log_message('debug', $class." class already loaded. Second attempt ignored.");
                    return;
                }

                include_once($subclass);
                $this->_ci_loaded_files[] = $subclass;

                return $this->_ci_init_class($class, config_item('subclass_prefix'), $params, $object_name);
            }

            // APP系のライブラリを発見したら、そちらを読み込む
            $sharedclass = SHAREDPATH.'libraries/'.'APP_'.$class.'.php';
            if (file_exists($sharedclass)) {
                $this->_ci_load_class_chain(ucfirst($class), array(
                    array('subdir' => BASEPATH . 'libraries/', 'prefix' => '', 'skip' => TRUE)
                ));

                if (in_array($sharedclass, $this->_ci_loaded_files)) {
                    if ( ! is_null($object_name)) {
                        $CI =& get_instance();
                        if ( ! isset($CI->$object_name)) {
                            return $this->_ci_init_class($class, 'APP_', $params, $object_name);
                        }
                    }

                    $is_duplicate = TRUE;
                    log_message('debug', $class." class already loaded. Second attempt ignored.");
                    return;
                }

                include_once($sharedclass);
                $this->_ci_loaded_files[] = $sharedclass;

                return $this->_ci_init_class($class, 'APP_', $params, $object_name);
            }

            $is_duplicate = FALSE;
            foreach ($this->_ci_library_paths as $path)
            {
                $filepath = $path.'libraries/'.$subdir.$class.'.php';

                if ( ! file_exists($filepath))
                {
                    continue;
                }

                if (in_array($filepath, $this->_ci_loaded_files))
                {
                    if ( ! is_null($object_name))
                    {
                        $CI =& get_instance();
                        if ( ! isset($CI->$object_name))
                        {
                            return $this->_ci_init_class($class, '', $params, $object_name);
                        }
                    }

                    $is_duplicate = TRUE;
                    log_message('debug', $class." class already loaded. Second attempt ignored.");
                    return;
                }

                include_once($filepath);
                $this->_ci_loaded_files[] = $filepath;
                return $this->_ci_init_class($class, '', $params, $object_name);
            }

        }

        if ($subdir == '')
        {
            $path = strtolower($class).'/'.$class;
            return $this->_ci_load_class($path, $params);
        }

        if ($is_duplicate == FALSE)
        {
            log_message('error', "Unable to load the requested class: ".$class);
            show_error("Unable to load the requested class: ".$class);
        }
    }

    /**
     * @param string $class
     * @param string $chain
     */
    protected function _ci_load_class_chain($class, $chain)
    {
        foreach ($chain as $c) {
            $path = $c['subdir'] . $c['prefix'] . $class . '.php';

            if (file_exists($path)) {
                include_once($path);
            } elseif (!$c['skip']) {
                log_message('error', "Unable to load the requested class: ".$class);
                show_error("Unable to load the requested class: ".$class);
            }
        }
    }

    /**
     * Internal CI Library Loader
     *
     * @used-by	CI_Loader::library()
     * @uses	CI_Loader::_ci_init_library()
     *
     * @param	string	$class		Class name to load
     * @param	mixed	$params		Optional parameters to pass to the class constructor
     * @param	string	$object_name	Optional object name to assign to
     * @return	void
     */
    protected function _ci_load_library($class, $params = NULL, $object_name = NULL)
    {
        // Get the class name, and while we're at it trim any slashes.
        // The directory path can be included as part of the class name,
        // but we don't want a leading slash
        $class = str_replace('.php', '', trim($class, '/'));

        // Was the path included with the class name?
        // We look for a slash to determine this
        if (($last_slash = strrpos($class, '/')) !== FALSE)
        {
            // Extract the path
            $subdir = substr($class, 0, ++$last_slash);

            // Get the filename from the path
            $class = substr($class, $last_slash);
        }
        else
        {
            $subdir = '';
        }

        $class = ucfirst($class);

        $filepath = SHAREDPATH.'libraries/'.'APP_'.$class.'.php';
        if (file_exists($filepath))
        {
            include_once($filepath);
            return $this->_ci_init_library($class, 'APP_', $params, $object_name !== null ? $object_name : strtolower($class));
        }

        // Is this a stock library? There are a few special conditions if so ...
        if (file_exists(BASEPATH.'libraries/'.$subdir.$class.'.php'))
        {
            return $this->_ci_load_stock_library($class, $subdir, $params, $object_name);
        }

        // Let's search for the requested library file and load it.
        foreach ($this->_ci_library_paths as $path)
        {
            // BASEPATH has already been checked for
            if ($path === BASEPATH)
            {
                continue;
            }

            $filepath = $path.'libraries/'.$subdir.$class.'.php';

            // Safety: Was the class already loaded by a previous call?
            if (class_exists($class, FALSE))
            {
                // Before we deem this to be a duplicate request, let's see
                // if a custom object name is being supplied. If so, we'll
                // return a new instance of the object
                if ($object_name !== NULL)
                {
                    $CI =& get_instance();
                    if ( ! isset($CI->$object_name))
                    {
                        return $this->_ci_init_library($class, '', $params, $object_name);
                    }
                }

                log_message('debug', $class.' class already loaded. Second attempt ignored.');
                return;
            }
            // Does the file exist? No? Bummer...
            elseif ( ! file_exists($filepath))
            {
                continue;
            }

            include_once($filepath);
            return $this->_ci_init_library($class, '', $params, $object_name);
        }

        // One last attempt. Maybe the library is in a subdirectory, but it wasn't specified?
        if ($subdir === '')
        {
            return $this->_ci_load_library($class.'/'.$class, $params, $object_name);
        }


        // If we got this far we were unable to find the requested class.
        log_message('error', 'Unable to load the requested class: '.$class);
        show_error('Unable to load the requested class: '.$class);
    }

    //
    // 以下 view 用の特殊メソッド
    //

    /**
     * コンテンツバッファ
     * @var array
     */
    protected $content_buffers = array();

    /**
     * バッファリングしておいたコンテンツを取得する
     *
     * @access public
     * @param string $name
     * @return string
     */
    public function _content_yield($name)
    {
        if ( ! array_key_exists($name, $this->content_buffers)) {
            return "";
        }
        return implode("\n", $this->content_buffers[$name]);
    }

    /**
     * バッファリング開始
     * 指定した名前をキーにコンテンツのバッファリングを開始する
     *
     * @access public
     * @params string $name
     * @return void
     */
    public function _content_start($name)
    {
        $this->content_names[] = $name;
        ob_start();
    }

    /**
     * バッファリング終了
     * MY_Controller::content_start() で開始したバッファリングを終了する
     *
     * @access public
     * @return void
     * @todo バッファリングのネストに対応させる
     */
    public function _content_end()
    {
        $name = array_pop($this->content_names);
        $buffer = ob_get_contents();

        $this->_content_add($name, $buffer);

        @ob_end_clean();
    }

    /**
     * @var array
     */
    private $content_names = array();

    /**
     * バッファリングするコンテンツを保存する
     * 指定した名前をキーにバッファリングしたコンテンツを保存する
     *
     * @access public
     *
     * @param string $name
     * @param string $content
     */
    public function _content_add($name, $content)
    {
        $this->content_buffers[$name][] = $content;
    }

}
