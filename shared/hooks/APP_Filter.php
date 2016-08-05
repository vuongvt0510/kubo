<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class APP_Filter {

    public function before_filter()
    {
        $CI =& get_instance();
        $router =& load_class('Router');
        $called_function = $router->fetch_method();

        if( ! isset($CI->before_filter)) return;

        foreach ($CI->before_filter as $filter) {
            if ( ! empty($CI->_skipped)) break;

            if ( ! isset($filter["name"])) {
                show_error("BeforeFilter: Name must be set");
                return;
            }

            if ( ! method_exists($CI,$filter["name"])) {
                show_error("BeforeFilter: Function \"".$CI->before_filter["name"]."\" does not exists");
            }

            if (isset($filter["except"]) && isset($filter["only"])) {
                show_error("BeforeFilter: Filter can only run either \"except\" or \"only\"");
            } else {
                if (isset($filter["except"])) {
                    if ( ! empty($filter["except"])) {
                        if ( ! in_array($called_function, $filter["except"])) {
                            call_user_func(array($CI,$filter["name"]));
                        }
                    } else {
                        call_user_func(array($CI,$filter["name"]));
                    }
                } else {
                    if (isset($filter["only"])) {
                        if( ! empty($filter["only"])) {
                            if( in_array($called_function, $filter["only"])) {
                                call_user_func(array($CI,$filter["name"]));
                            }
                        } else {
                            call_user_func(array($CI,$filter["name"]));
                        }
                    } else {
                        call_user_func(array($CI,$filter["name"]));
                    }
                }
            }
        }
    }
}

