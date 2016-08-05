<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * レスポンスコード定義クラス
 *
 * @author Yoshikazu Ozawa
 */
class APP_Response {

    // 基本的にHTTP Status code を踏襲
    // 共通で必要なものがあれば随時追加

    // 情報提供系
    const TYPE_INFORMATIONAL = 1;

    // 通常系
    const TYPE_OK = 2;
    const OK       = 20000;
    const CREATED  = 20010;
    const ACCEPTED = 20020;

    // 転送系
    const TYPE_REDIRECTION = 3;

    // クライアントエラー系
    const TYPE_CLIENT_ERROR = 4;
    const BAD_REQUEST     = 40000;   // リクエストエラー
    const INVALID_PARAMS  = 40001;   // 入力項目エラー
    const UNAUTHORIZED    = 40010;   // 認証エラー
    const FORBIDDEN       = 40030;   // 権限エラー
    const INVALID_VERSION = 40031;   // バージョンエラー アプリ更新を求める
    const NOT_FOUND       = 40040;   // 404エラー
    const CONFLICT        = 40090;   // 重複エラー

    // 内部サーバーエラー系
    const TYPE_SERVER_ERROR = 5;
    const INTERNAL_SERVER_ERROR = 50010;    // 内部サーバーエラー
    const SERVICE_UNAVAILABLE   = 50030;    // サーバーが有効ではない

    // DBエラー系
    const TYPE_DB_ERROR = 6;
    const DB_UNKNOWN_ERROR     = 60000;    // DBエラー (未定義)
    const DB_CONNECT_ERROR     = 60010;    // 接続エラー
    const DB_DEADLOCK          = 60020;    // デッドロック
    const DB_LOCK_TIMEOUT      = 60030;    // タイムアウト

    // 外部APIエラー系
    const TYPE_API_ERROR = 7;
    const API_UNKNOWN_ERROR    = 70000;    // APIエラー (未定義)
    const API_UNAUTHORIZED     = 70010;    // 認証エラー
    const API_TOKEN_EXPIRED    = 70011;    // トークン有効期限切れ
    const API_PERMISSION_ERROR = 70020;    // 権限エラー
    const API_CONNECT_ERROR    = 70030;    // 接続エラー
    const API_LIMIT_OVER       = 70040;    // 利用回数オーバー
    const API_TIMEOUT          = 70050;    // タイムアウトエラー
    const API_CONFLICT         = 70070;    // API重複コールエラー

    // 不明なエラー
    const TYPE_UNKNOWN = 9;
    const UNKNOWN_ERROR = 99000;           // 不明なエラー

    static function is_success($code)
    {
        return in_array(self::status_type($code), array(self::TYPE_OK, self::TYPE_REDIRECTION));
    }

    static function is_error($code)
    {
        return ! self::is_success($code);
    }

    static function status_type($code)
    {
        return floor($code / 10000);
    }

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->lang->load('response_code');
    }

    /**
     * 成功時のJSON生成
     *
     * @access public
     * @param mixed $data
     * @param array $extra
     *
     * @return array
     */
    public function true_json($data = NULL, $extra = array())
    {
        $ex = array('submit' => TRUE);
        if (is_array($extra)) {
            $ex = array_merge($ex, $extra);
        }

        return $this->build_json(true, $data, $ex);
    }

    /**
     * 登録・更新失敗時のJSON生成
     *
     * @access public
     * @param array $invalid_fields
     * @param array $extra 
     * @return array
     */
    public function submit_false_json($invalid_fields, $extra = array())
    {
        $data = array(
            'submit' => FALSE,
            'invalid_fields' => $invalid_fields
        );

        if (is_array($extra)) {
            $data = array_merge($data, $extra);
        }

        return $this->true_json(NULL, $data);
    }

    /**
     * 失敗時のJSON生成
     *
     * @access public
     * @param integer $errcode
     * @param string $errmsg
     * @param array $extra
     *
     * @return array
     */
    public function false_json($errcode, $errmsg = NULL, $extra = array())
    {
        if (is_null($errmsg)) {
            $errmsg = $this->CI->lang->line("rescode_" . $errcode);
        }

        $error = array('submit' => FALSE, 'errcode' => $errcode, 'errmsg' => $errmsg);
        if ( ! empty($extra)) {
            $error = array_merge($error, $extra);
        }

        return $this->build_json(false, NULL, $error);
    }

    /**
     * JSONレスポンス用の配列を生成する
     *
     * @access public
     * @param bool $status
     *
     * @param array $result
     * @param array $extra
     *
     * @return array
     */
    public function build_json($status, $result = NULL, $extra = NULL)
    {
        $arr['success'] = $status;
        if (isset($result)) $arr['result'] = $result;
        if (isset($extra)) $arr = array_merge($arr, $extra);

        $this->sanitize($arr);

        return $arr;
    }

    /**
     * JSONレスポンス用のデータを生成する
     *
     * @access public
     * @param mixed $object
     *
     * @return array|mixed
     */
    public function & sanitize(& $object)
    {
        if (is_array($object)) {
            foreach ($object as $key => $value) {
                $object[$key] = $this->sanitize($value);
            }

            return $object;
        }

        if (is_object($object) && is_subclass_of($object, 'APP_Record')) {
            $object = $object->jsonSerialize();
            return $object;
        }

        return $object;
    }
}

