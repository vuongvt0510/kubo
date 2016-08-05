<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (!class_exists("APP_Response")) {
    require SHAREDPATH . "libraries/APP_Response.php";
}

if ( ! class_exists('APP_Form_validation')) {
    require_once SHAREDPATH . "libraries/APP_Form_validation.php";
}

if ( ! class_exists('APP_Anonymous_operator')) {
    require_once SHAREDPATH . "core/APP_Operator.php";
}

/**
 * API基底クラス
 *
 * @property array $params
 * @property object load
 * @property Master_grade_model master_grade_model
 * @property Textbook_model textbook_model
 * @property Group_invite_model group_invite_model
 * @property User_model user_model
 * @property Group_model group_model
 * @property Master_school_model master_school_model
 * @property Video_model video_model
 *
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class APP_Api extends APP_Response
{
    /**
     * バリデータクラス名
     * @var string
     */
    public $validator_name = "APP_Api_validation";

    /**
     * CIインスタンス
     * @var APP_Controller
     */
    protected $CI = NULL;

    /**
     * API操作者
     * @var APP_Operator
     */
    protected $operator = NULL;

    /**
     * コンストラクタ
     *
     * @access public
     * @param array $params パラメータ
     */
    public function __construct($params = array())
    {
        $this->CI =& get_instance();

        $this->set_operator(isset($params['operator']) ? $params['operator'] : new APP_Anonymous_operator());
    }

    /**
     * @ignore
     */
    public function __get($name)
    {
        return $this->CI->{$name};
    }

    /**
     * オペレータ取得
     *
     * @access public
     * @return APP_Operator|object
     */
    public function operator()
    {
        return $this->operator;
    }

    /**
     * オペレータ設定
     *
     * @access public
     * @param APP_Operator $operator
     * @throws APP_Api_exception
     */
    public function set_operator($operator)
    {
        if (! $operator instanceof APP_Operator) {
            throw new APP_Api_exception("operator is not Operator instance.");
        }

        $this->operator = $operator;
    }

    /**
     * パラメータを取得する
     *
     * @access public
     * @param string $name
     * @return mixed
     */
    public function params($name = NULL)
    {
        return empty($name) ? $this->params : $this->params[$name];
    }

    /**
     * 検証クラスを生成する
     *
     * @access public
     * @param array $params リクエストパラメータ
     * @param array $rules 検証ルール
     * @return APP_param_validation
     */
    protected function validator(& $params, $rules = array())
    {
        $v = new $this->validator_name($this, $params, $rules);
        return $v;
    }

    /**
     * 受け取ったデータを適切なレスポンスに整形する
     *
     * @access protected
     * @param object $record オブジェクト
     * @param array $options オプション
     * @return array 整形したレスポンスデータ
     */
    protected function build_response($record, $options = array())
    {
        return get_object_vars($record);
    }

    /**
     * 受け取ったデータを適切なレスポンスに整形する
     *
     * @access protected
     * @final
     * @param array|object $record オブジェクト
     * @param array $options オプション
     * @return array 整形したレスポンスデータ
     */
    final protected function build_responses($record, $options = array())
    {
        $is_array = is_array($record);
        if ( ! $is_array) $record = array($record);

        $result = array();
        foreach ($record as $rec) {
            $result[] = $this->build_response($rec, $options);
        }

        return $is_array ? $result : $result[0];
    }
}


/**
 * API用パラメータ検証クラス
 *
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Api_validation extends APP_Form_validation {

    /**
     * APIクラス
     * @var APP_Api
     */
    protected $base = NULL;

    /**
     * ログイン検証判定フラグ
     * @var bool
     * @ignore
     */
    private $_login_required = FALSE;

    /**
     * コンストラクタ
     *
     * @access public
     * @param APP_Api $base
     * @param array $params パラメータ
     * @param array $rules 検証ルール
     */
    public function __construct(& $base, & $params, $rules = array())
    {
        parent::__construct($rules);
        $this->base =& $base;
        $this->params =& $params;
    }

    /**
     * エラー結果を返す
     *
     * @access public
     * @param array $extra 追加情報
     * @param array $options オプション
     * @param bool $render
     * @return array
     */
    public function error_json($extra = array(), $options = array(), $render = TRUE)
    {
        if ($this->_login_required && $this->base->operator()->is_anonymous()) {
            return $this->base->false_json(APP_Api::UNAUTHORIZED);
        }

        return $this->base->submit_false_json($this->_error_array);
    }

    /**
     * 検証時にログインチェックをする
     *
     * @access public
     * @return void
     */
    public function require_login()
    {
        $this->_login_required = TRUE;
    }

    /**
     * 実行
     *
     * @access public
     * @param string $group
     * @return array
     */
    public function run($group = '')
    {
        // ログインチェック
        if ($this->_login_required && $this->base->operator()->is_anonymous()) {
            return FALSE;
        }

        return parent::run($group);
    }
}


/**
 * API 例外クラス
 *
 * @version $id$
 * @copyright 2013- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Api_exception extends APP_Exception
{
}


