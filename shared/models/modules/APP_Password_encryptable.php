<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * パスワード暗号化モジュール
 *
 * パスワード登録を行うテーブルに対して制御を入れ込んでくれるモジュール
 *
 * @package APP\Model
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
trait APP_Password_encryptable
{
    /**
     * 作成
     *
     * @access public
     * @param array $attributes
     * @param array $options
     * @return mixed
     */
    public function create($attributes, $options = array())
    {
        $this->_set_encrypted_password($attributes);
        return parent::create($attributes, $options);
    }

    /**
     * 更新
     *
     * @access public
     * @access int $id
     * @access array $attributes
     * @access array $options
     * @return mixed
     */
    public function update()
    {
        list($args, $attributes, $options) = $this->_parse_update_args(func_get_args());

        $this->_set_encrypted_password($attributes);

        array_push($args, $attributes, $options);

        return call_user_func_array('parent::update', $args);
    }


    /**
     * パスワードを更新
     *
     * @access public
     * @return mixed
     * @throws APP_Model_exception
     *
     * @internal param int $id
     * @internal param string $password
     * @internal param array $options
     */
    public function update_password()
    {
        $args = func_get_args();
        $primary_keys = is_array($this->primary_key) ? $this->primary_key : array($this->primary_key);

        // パスワードが指定されていない場合は自動で8桁を指定する
        if (count($args) <= count($primary_keys)) {
            array_push($args, generate_unique_key(8));
        }

        list($args, $password, $options) = $this->_parse_update_args($args, array('validate_attributes' => FALSE));

        if (!is_string($password)) {
            throw new APP_Model_exception(sprintf('%s::update_password() Argument #%d ($password) can\'t set string.', get_class($this), count($primary_keys) + 1));
        }

        $this->transaction(function() use($args, $password){
            $target = $this->_set_condisions_by_primary_key($args)->for_update()->first(array('master' => TRUE));
            if (empty($target)) {
                return FALSE;
            }

            array_push($args, array('password' => $password));

            
            return call_user_func_array(array($this, 'update'), $args);
        });

        return $password;
    }

    /**
     * 暗号化済みパスワード設定
     *
     * @access public
     * @param array $attributes
     * @return array
     */
    protected function _set_encrypted_password(& $attributes)
    {
        if (array_key_exists('password', $attributes)) {
            $password = $attributes['password'];

            if (!empty($password)) {
                $attributes['salt'] = generate_salt();
                $attributes['encrypted_password'] = encrypt_password($password, $attributes['salt']);
            }

            unset($attributes['password']);
        }

        return $attributes;
    }
}


trait APP_Password_encryptable_record
{
    public function is_match_password($password)
    {
        return $this->encrypted_password === encrypt_password($password, $this->salt);
    }
}

