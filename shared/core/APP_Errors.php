<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class APP_Errors implements ArrayAccess, Iterator {

    protected $errors = array();

    protected $CI = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->lang->load('form_validation');
    }

    public function count()
    {
        return count($this->full_messages());
    }

    public function add($label_or_array, $message)
    {
        if (is_array($label_or_array)) {
            $label = $label_or_array[0];
            $name = $this->translate($label_or_array[1]);
        } else {
            $label = $label_or_array;
            $name = ucfirst($label_or_array);
        }

        $args = array();
        if (func_num_args() > 2) {
            if ( ! is_array($args = func_get_arg(2))) {
                $args = array_slice(func_get_args(), 2);
            }
        }

        array_unshift($args, $name);

        $this->errors[$label][] = $this->translate($message, $args);
        return TRUE;
    }

    public function add_to_base($message)
    {
        $args = array();
        if (func_num_args() > 1) {
            if ( ! is_array($args = func_get_arg(1))) {
                $args = array_slice(func_get_args(), 1);
            }
        }
        $this->errors['base'][] = $this->translate($message, $args);
        return TRUE;
    }

    public function clear()
    {
        $this->errors = array();
    }

    public function full_messages()
    {
        $full_messages = array();

        foreach ($this->errors as $label => $messages) {
            foreach ($messages as $message) {
                $full_messages[] = $messages;
            }
        }

        return $full_messages;
    }

    protected function translate($string, $args = array())
    {
        if (substr($string, 0, 5) == 'lang:') {
            $line = substr($string, 5);

            if (FALSE === ($string = $this->CI->lang->line($line))) {
                return $line;
            }
        }

        $string = vsprintf($string, $args);

        return $string;
    }

    // ArrayAccess
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->errors[] = $value;
        } else {
            $this->errors[$offset] = $value;
        }
    }
    public function offsetExists($offset)
    {
        return isset($this->errors[$offset]);
    }
    public function offsetUnset($offset)
    {
        unset($this->errors[$offset]);
    }
    public function offsetGet($offset)
    {
        return isset($this->errors[$offset]) ? $this->errors[$offset] : NULL;
    }

    // Iterator
    private $__position = 0;

    public function rewind()
    {
        $this->__position = 0;
    }
    public function current()
    {
        return $this->errors[$this->key()];
    }
    public function key()
    {
        $keys = array_keys($this->errors);
        return $keys[$this->__position];
    }
    public function next()
    {
        ++$this->__position;
    }
    public function valid()
    {
        $keys = array_keys($this->errors);
        return isset($keys[$this->__position]);
    }
}

