<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('find_record'))
{
    function find_record($class, $id, $options = array())
    {
        return is_object($id) ? $id : call_user_func(array($class, 'find'), $id, $options);
    }
}

if ( ! function_exists('record_id'))
{
    function record_id($class, $object)
    {
        switch(true)
        {
        case is_array($object):
            return $object[$class->primary_key];
        case is_object($object):
            return $object->{$class->primary_key};
        default:
            return $object;
        }
    }
}

if ( ! function_exists('create_blank_record'))
{
    function create_blank_record()
    {
        $record = new stdClass;
        foreach (func_get_args() as $column) {
            $record->{$column} = NULL;
        }
        return $record;
    }
}

if ( ! function_exists('nested_record'))                                                                                                                                                                                                                                                                                      {
    function nested_record($record, $prefix, $class = "stdClass")
    {
        $object = new $class;
        $r = get_object_vars($record);
        $keys = array_filter(array_keys($r), function($k)use($prefix){ return preg_match("/^{$prefix}_/", $k); });
        foreach ($keys as $k) {
            $property = preg_replace("/^{$prefix}_/", "", $k);
            $object->{$property} = $r[$k];
        }
        return $object;
    }
}

/* End of file model_helper.php */
/* Location: ./application/models/model_helper.php */
