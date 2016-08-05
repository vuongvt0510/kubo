<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * API認証用モジュール
 *
 * API認証制御を入れ込んでくれるモジュール
 *
 * @property APP_Loader load
 * @property APP_Input input
 * @property APP_Operator current_user
 *
 * @method void _before_filter(String $method_name, Array $options = [])
 * @method void _false_json(Int $code, String $errmsg = null)
 *
 * @package APP\Controller
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interset-marketing.net>
 */
trait APP_Api_authenticatable
{
    /**
     * 認証処理対象モデル名
     * @var string
     */
    protected $authentication_target_model = "user_model";

    /**
     * 認証を有効にする
     *
     * @access protected
     * @param string $target
     */
    protected function _enable_authentication($target = "user_model")
    {
        $this->authentication_target_model = $target;

        $this->_before_filter('_find_current_user');
        $this->_before_filter('_require_login');
    }

    /**
     * ログインユーザー検索
     *
     * @access public
     */
    public function _find_current_user()
    {
        $this->load->model($this->authentication_target_model);

        $token = $this->input->server("HTTP_X_API_TOKEN");
        if (empty($token)) {
            return;
        }

        $target = $this->{$this->authentication_target_model}->available()->find_by(array('token' => $token));
        if (empty($target)) {
            return;
        }

        $this->current_user = $target;

        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }

    /**
     * ログイン認証
     *
     * @access public
     */
    public function _require_login()
    {
        if (empty($this->current_user) || !$this->current_user->is_login()) {
            $this->_false_json(APP_Response::UNAUTHORIZED);
        }
    }
}


