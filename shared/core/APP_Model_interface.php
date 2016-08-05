<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * モデル系のインターフェイス
 * 
 * @author Yoshikazu Ozawa
 */
interface APP_Model_interface {

    /**
     * @return mixed
     */
    public function find(/* polymorphic */);

    /**
     * @param array $options
     * @return mixed
     */
    public function all($options = array());

    /**
     * @param $attributes
     * @param array $options
     * @return mixed
     */
    public function create($attributes, $options = array());

    /**
     * @return mixed
     */
    public function update(/* polymorphic */);

    /**
     * @param $attributes
     * @param array $options
     * @return mixed
     */
    public function update_all($attributes, $options = array());

    /**
     * @return mixed
     */
    public function destroy(/* polymorphic */);

    /**
     * @param array $options
     * @return mixed
     */
    public function destroy_all($options = array());
}

