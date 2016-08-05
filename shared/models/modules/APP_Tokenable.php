<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * APIトークン生成モジュール
 *
 * APIトークンの登録を行うテーブルに対して制御を入れ込んでくれるモジュール
 *
 * @package APP\Model
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
trait APP_Tokenable
{
    protected $token_size = 128;

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
        $this->_set_token($attributes);
        return parent::create($attributes, $options);
    }

    /**
     * APIトークンを指定する
     *
     * @access public
     * @param array $attributes
     * @return void
     */
    protected function _set_token(& $attributes)
    {
        $attributes['token'] = $this->_generate_unique_key('token', $this->token_size);
    }
}

