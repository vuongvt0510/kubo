<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "third_party/AWS/aws.phar";
require_once SHAREDPATH . "libraries/Aws/APP_Aws_exception.php";

/**
 * AWS基底クラス
 *
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Norio Ohata <ohata@interest-marketing.net>
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
abstract class APP_Aws
{
    /**
     * AWS のキー情報
     * @var string
     */
    protected $key = NULL;

    /**
     * AWS のプライベートキー情報
     * @var string
     */
    protected $secret = NULL;

    /**
     * AWS のリージョン情報
     * @var string
     */
    protected $region = 'ap-northeast-1';

    /**
     * AWS の利用オブジェクト
     * @var object
     */
    protected $aws = NULL;

    /**
     * コンストラクタ
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        $files = array(
            SHAREDPATH . "config/aws.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/aws.php",
            APPPATH . "config/aws.php",
            APPPATH . "config/" . ENVIRONMENT . "/aws.php"
        );

        foreach ($files as $f) {
            if (is_file($f)) {
                include $f;
            }
        }

        if (!empty($aws)) {
            $params = array_merge($aws, $params);
        }

        if (!empty($aws[$this->instance_name()])) {
            $params = array_merge($params, $aws[$this->instance_name()]);
        }

        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * デストラクタ
     */
    public function __destruct()
    {
        // AWSオブジェクトをクリア
        unset($this->aws);
    }
 
    /**
     * 認証パラメータの取得
     * 必要であれば、下位でオーバーライドして呼び出してもらう
     *
     * @param array $config マージ対象の認証情報
     */
    public function auth_config()
    {
        // 設定が有る場合のみ返却
        $ret = array();

        if ($this->key) {
            $ret['key'] = trim($this->key);
        }

        if ($this->secret) {
            $ret['secret'] = trim($this->secret);
        }

        if ($this->region) {
            $ret['region'] = trim($this->region);
        }
        
        return $ret;
    }

    /**
     * クラスインスタスの取得処理
     *
     * @access protected
     * @return object
     */
    protected function & instance($options = array())
    {
        if (!empty($this->aws)) {
            return $this->aws;
        }
        
        $this->aws = NULL;
        $inst = Aws\Common\Aws::factory($this->auth_config());
        if (empty($inst)){
            throw new APP_Aws_exception('Aws factory method is failed.', APP_Aws_exception::UNKNOWN_ERROR);
        }

        $this->aws = $inst->get($this->instance_name(), $options);
        if (empty($this->aws)) {
            throw new APP_Aws_exception("Aws instance `{$this->instance_name()}` is not found.", APP_Aws_exception::UNKNOWN_ERROR);
        }

        return $this->aws;
    }

    /**
     * 指定リージョン情報の取得
     *
     * @access public
     * @return string
     */
    public function region()
    {
        return $this->region;
    }
 
    /**
     * インスタンス名の取得
     *
     * @access protected
     * @return string
     */
    abstract protected function instance_name();
}

