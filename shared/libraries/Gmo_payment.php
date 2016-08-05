<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

set_include_path(get_include_path() . PATH_SEPARATOR . SHAREDPATH.'third_party/GMO_Payment');

require_once('com/gmo_pg/client/input/EntryTranInput.php');
require_once('com/gmo_pg/client/input/ExecTranInput.php');
require_once('com/gmo_pg/client/input/EntryExecTranInput.php');
require_once('com/gmo_pg/client/tran/EntryExecTran.php');

/**
 * Class Gmo_payment
 */
class Gmo_payment
{
    /**
     * @var string|null $shop_id
     */
    private $shop_id = null;

    /**
     * @var string|null $shop_secret
     */
    private $shop_secret = null;

    /**
     * @var string|null $site_id
     */
    private $site_id = null;

    /**
     * @var string|null $site_secret
     */
    private $site_secret = null;

    /**
     * @var array
     */
    private $error = null;

    /**
     * @var EntryTranInput|null
     */
    private $entry_input = null;

    /**
     * @var array AUTH:仮売上, CHECK:有効性チェック, CAPTURE:即時売上, FORCERETURN:強制返品
     */
    private $job_cd = [
        'AUTH', 'CHECK', 'CAPTURE', 'FORCERETURN'
    ];

    /**
     * @var int $td_flag 3D利用 0:disable 1:enable
     */
    private $td_flag = 0;

    /**
     * @var string $td_tenant_name 3D認証画面店舗名
     */
    private $td_tenant_name = null;

    /**
     * GMO_Payment constructor.
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        $files = [
            SHAREDPATH . "config/gmo_payment.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/gmo_payment.php",
            APPPATH . "config/gmo_payment.php",
            APPPATH . "config/" . ENVIRONMENT . "/gmo_payment.php"
        ];

        foreach ($files as $f) {
            if (is_file($f)) {
                /** @var string $f */
                include $f;
            }
        }

        if (!empty($gmo_payment)) {
            $params = array_merge($gmo_payment, $params);
        }

        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        mb_language('ja');
        mb_internal_encoding('UTF-8');

        $this->entry_input = new EntryTranInput();
        $this->entry_input->setShopId($this->shop_id);
        $this->entry_input->setShopPass($this->shop_secret);
    }

    /**
     * [Member] Create member record
     *
     * @param array $params
     *
     * @internal param string $id
     * @internal param string $name
     * @return array|bool
     */
    public function create_member($params = [])
    {
        require_once('com/gmo_pg/client/input/SaveMemberInput.php');
        require_once('com/gmo_pg/client/tran/SaveMember.php');

        /* @var $input SaveMemberInput */
        $input = new SaveMemberInput();

        $input->setSiteId($this->site_id);
        $input->setSitePass($this->site_secret);

        $input->setMemberId($params['id']);

        if (!empty($params['name'])) {
            $input->setMemberName(mb_convert_encoding($params['name'], 'SJIS', 'UTF-8'));
        }

        /* @var $exec SaveMember */
        $exe = new SaveMember();
        $output = $exe->exec($input);

        // Error occurs
        // 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        }

        // 出力パラメータにエラーコードが含まれていないか、チェック
        if ($output->isErrorOccurred()) {
            $this->error = $this->handle_error($output);
            return FALSE;
        }

        return [
            'id' => $output->getMemberId()
        ];
    }

    /**
     * [Member] Search member
     *
     * @param array $params
     * @internal param string $id of user
     *
     * @return array|bool
     */
    public function search_member($params = [])
    {
        require_once('com/gmo_pg/client/input/SearchMemberInput.php');
        require_once('com/gmo_pg/client/tran/SearchMember.php');

        /* @var $input SaveMemberInput */
        $input = new SearchMemberInput();

        $input->setSiteId($this->site_id);
        $input->setSitePass($this->site_secret);

        $input->setMemberId($params['id']);

        /* @var $exec SaveMember */
        $exe = new SearchMember();
        $output = $exe->exec($input);

        // Error occurs
        // 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        }

        // 出力パラメータにエラーコードが含まれていないか、チェック
        if ($output->isErrorOccurred()) {
            $this->error = $this->handle_error($output);
            return FALSE;
        }

        return [
            'id' => $output->getMemberId(),
            'name' => $output->getMemberName()
        ];
    }

    /**
     * [Member] Get member
     *
     * @param array $params
     *
     * @internal param string $id
     * @return bool
     */
    public function get_member($params = [])
    {
        require_once('com/gmo_pg/client/input/SearchMemberInput.php');
        require_once('com/gmo_pg/client/tran/SearchMember.php');

        /* @var $input SearchMemberInput */
        $input = new SearchMemberInput();

        $input->setSiteId($this->site_id);
        $input->setSitePass($this->site_secret);

        $input->setMemberId($params['id']);

        /* @var $exec SearchMember */
        $exe = new SearchMember();

        /* @var $output SearchMemberOutput */
        $output = $exe->exec($input);

        // Error occurs
        // 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        }

        // 出力パラメータにエラーコードが含まれていないか、チェック
        if ($output->isErrorOccurred()) {
            $this->error = $this->handle_error($output);
            return FALSE;
        }

        return [
            'id' => $output->getMemberId(),
            'name' => mb_convert_encoding($output->getMemberName(), 'SJIS', 'UTF-8')
        ];
    }

    /**
     * [Member] Delete member
     *
     * @param array $params
     *
     * @internal param string $id
     * @return bool
     */
    public function delete_member($params = [])
    {
        require_once('com/gmo_pg/client/input/DeleteMemberInput.php');
        require_once('com/gmo_pg/client/tran/DeleteMember.php');

        /* @var $input DeleteMemberInput */
        $input = new DeleteMemberInput();

        $input->setSiteId($this->site_id);
        $input->setSitePass($this->site_secret);

        $input->setMemberId($params['id']);

        /* @var $exe DeleteMember */
        $exe = new DeleteMember();

        /* @var $output DeleteMemberOutput */
        $output = $exe->exec($input);

        // Error occurs
        // 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        }

        // 出力パラメータにエラーコードが含まれていないか、チェック
        if ($output->isErrorOccurred()) {
            $this->error = $this->handle_error($output);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * [Credit card] Add
     *
     * @param array $params
     *
     * @internal param string $member_id
     *
     * @internal param string $card_no
     * @internal param string $card_pass
     * @internal param string $expire
     * @internal param string $holder_name
     * @internal param string $default_flag (0|1)
     *
     * @return array|bool
     */
    public function add_credit_card($params = [])
    {
        require_once('com/gmo_pg/client/input/SaveCardInput.php');
        require_once('com/gmo_pg/client/tran/SaveCard.php');

        /* @var $input SaveCardInput */
        $input = new SaveCardInput();

        $input->setSiteId($this->site_id);
        $input->setSitePass($this->site_secret);

        $input->setMemberId($params['member_id']);

        $input->setSeqMode(0);

        $input->setCardNo($params['card_no']);
        $input->setExpire($params['expire']);
        $input->setDefaultFlag(empty($params['default_flag']) ? 0 : 1);

        if (!empty($params['holder_name'])) {
            $input->setHolderName($params['holder_name']);
        }

        if (!empty($params['card_pass'])) {
            $input->setCardPass($params['card_pass']);
        }

        if (!empty($params['card_name'])) {
            $input->setCardName($params['card_name']);
        }

        /* @var $exe SaveCard */
        $exe = new SaveCard();

        /* @var $output SaveCardOutput */
        $output = $exe->exec($input);

        // Error occurs
        // 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        }

        // 出力パラメータにエラーコードが含まれていないか、チェック
        if ($output->isErrorOccurred()) {
            $this->error = $this->handle_error($output);
            return FALSE;
        }

        return [
            'card_no' => $output->getCardNo(),
            'seq' => $output->getCardSeq(),
            'forward' => $output->getForward()
        ];
    }

    /**
     * [Credit card] Search credit card
     *
     * @param array $params
     * @internal param string $member_id
     * @internal param string $card_seq
     *
     * @return bool
     */
    public function search_credit_card($params = [])
    {
        require_once( 'com/gmo_pg/client/input/SearchCardInput.php');
        require_once( 'com/gmo_pg/client/tran/SearchCard.php');

        //入力パラメータクラスをインスタンス化します
        $input = new SearchCardInput();/* @var $input SearchCardInput */

        //このサンプルでは、サイトID・パスワードはコンフィグファイルで
        //定数defineしています。
        $input->setSiteId($this->site_id);
        $input->setSitePass($this->site_secret);

        //会員IDは必須です
        $input->setMemberId($params['member_id']);

        //カード登録連番が指定された場合、パラメータに設定します。
        $cardSeq = $params['card_seq'];
        if( 0 < strlen( $cardSeq ) ){
            //登録カード連番
            $input->setCardSeq( $cardSeq );
            $input->setSeqMode(0);
        }

        //API通信クラスをインスタンス化します
        $exe = new SearchCard();/* @var $exec SearchCard */

        //パラメータオブジェクトを引数に、実行メソッドを呼びます。
        //正常に終了した場合、結果オブジェクトが返るはずです。
        $output = $exe->exec( $input );/* @var $output SearchCardOutput */

        //実行後、その結果を確認します。

        // Error occurs
        // 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        }

        // 出力パラメータにエラーコードが含まれていないか、チェック
        if ($output->isErrorOccurred()) {
            $this->error = $this->handle_error($output);
            return FALSE;
        }

        $return = get_object_vars($output);

        if (isset($return['cardList']) && is_array($return['cardList'])) {
            foreach($return['cardList'] AS $card) {
                if ($card['CardSeq'] == $params['card_seq']) {
                    return [
                        'card_seq' => $card['CardSeq'],
                        'card_type' => $card['CardName'],
                        'card_number' => $card['CardNo'],
                        'expire' => $card['Expire'],
                        'holder_name' => $card['HolderName'],
                        'delete_flag' => $card['DeleteFlag']
                    ];
                }
            }
        }

        return FALSE;
    }

    /**
     * [Credit card] Delete
     *
     * @param array $params
     *
     * @internal param string $member_id
     * @return bool
     */
    public function delete_credit_card($params = [])
    {
        require_once('com/gmo_pg/client/input/DeleteCardInput.php');
        require_once('com/gmo_pg/client/tran/DeleteCard.php');

        /* @var $input DeleteCardInput */
        $input = new DeleteCardInput();

        $input->setSiteId($this->site_id);
        $input->setSitePass($this->site_secret);

        $input->setMemberId($params['member_id']);

        $input->setSeqMode(0);
        $input->setCardSeq(0);

        /* @var $exe DeleteCard */
        $exe = new DeleteCard();

        /* @var $output DeleteCardOutput */
        $output = $exe->exec($input);

        // Error occurs
        // 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        }

        // 出力パラメータにエラーコードが含まれていないか、チェック
        if ($output->isErrorOccurred()) {
            $this->error = $this->handle_error($output);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate Immediate Credit card payment method
     *
     * @param array $params
     * @return bool
     */
    public function validate_credit_card($params = [])
    {
        if (in_array($params['job_cd'], $this->job_cd)) {
            return FALSE;
        }

        if (empty($params['order_id'])) {
            return FALSE;
        }

        if (!is_numeric($params['amount'])) {
            return FALSE;
        }

        if (!is_numeric($params['tax'])) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * 即時決済を実行
     * Immediate Credit card payment execution
     *
     * @param array $params
     *
     * @internal param string $job_cd
     * @internal param string $order_id
     * @internal param int $amount
     * @internal param int $tax
     *
     * @internal param string $item_code (Option)
     *
     * @internal param string $card_no
     * @internal param string $expire
     * @internal param string $security_code
     *
     * @internal param string $client_field1
     * @internal param string $client_field2
     * @internal param string $client_field3
     * @internal param string $display_info
     *
     * @return bool
     */
    public function exec_credit_card($params = [])
    {
        // 取引登録時に必要なパラメータ
        $this->entry_input->setJobCd($params['job_cd']);
        $this->entry_input->setOrderId($params['order_id']);
        $this->entry_input->setAmount($params['amount']);
        $this->entry_input->setTax(isset($params['tax']) ? $params['tax'] : 0);

        // 商品コードが設定されている場合
        if (isset($params['item_code'])) {
            $this->entry_input->setItemCode($params['item_code']);
        }

        // 3D Secureの利用
        $this->entry_input->setTdFlag($this->td_flag ? 1 : 0);

        // 3D Secure表示店舗名
        if ($this->td_flag) {
            $this->entry_input->setTdTenantName($this->td_tenant_name);
        }

        $exec_input = $this->create_exec_input($params);

        // 即売上の実行
        /* @var $input EntryExecTranInput */
        $input = new EntryExecTranInput();
        $input->setEntryTranInput($this->entry_input);
        $input->setExecTranInput($exec_input);

        /* @var $exec EntryExecTran API通信クラスをインスタンス化*/
        $exe = new EntryExecTran();

        //パラメータオブジェクトを引数に、実行メソッドを呼びだし
        //正常に終了した場合、結果オブジェクトが返るはず
        /* @var $output EntryExecTranOutput */
        $output = $exe->exec($input);

        // Error occurs
        // 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        }

        // 出力パラメータにエラーコードが含まれていないか、チェック
        if ($output->isErrorOccurred()) {
            $this->error = $this->handle_error($output->execTranOutput);
            return FALSE;
        }

        $res = [
            'order_id' => $output->getOrderId(),
            'forward' => $output->getForward(),
            'method' => $output->getMethod(),
            'pay_times' => $output->getPayTimes(),
            'approve_no' => $output->getApprovalNo(),
            'trans_id' => $output->getTranId(),
            'trans_date' => $output->getTranDate(),
            'check_string' => $output->getCheckString()
        ];

        // 3D Secureが実行されている場合
        if($output->isTdSecure()) {

            $res['acs'] = [
                'url' => $output->getAcsUrl(),
                'md' => $output->getAccessId(),
                'pa_req' => $output->getPaReq(),
            ];

        }

        return $res;
    }

    /**
     * Create redirect page for 3d verify credit card
     *
     * @param array $params
     * @internal param string $url
     * @internal param string $md
     * @internal param string $pa_req
     * @internal param string $term_url
     *
     * @return string
     */
    public function create_redirect_page($params = [])
    {
        require_once('com/gmo_pg/client/input/AcsParam.php');
        require_once('com/gmo_pg/client/common/RedirectUtil.php');
        $redirectInput = new AcsParam();
        $redirectInput->setAcsUrl($params['url']);
        $redirectInput->setMd($params['md']);
        $redirectInput->setPaReq($params['pa_req']);
        $redirectInput->setTermUrl($params['term_url']);

        //リダイレクトページ表示クラスをインスタンス化して実行します。
        $redirectShow = new RedirectUtil();
        return $redirectShow->createRedirectPage(SHAREDPATH.'third_party/GMO_Payment/RedirectPage.html' , $redirectInput);
    }

    /**
     * Verify Tran
     *
     * @param array $params
     * @internal param string $md
     * @internal param string $pa_res
     *
     * @return array|bool
     */
    public function verify_tran($params = [])
    {
        require_once( 'com/gmo_pg/client/input/TdVerifyInput.php');
        require_once( 'com/gmo_pg/client/tran/TdVerify.php');
        //入力パラメータクラスをインスタンス化します
        $input = new TdVerifyInput();

        $input->setMd($params['md']);
        $input->setPaRes($params['pa_res']);
        $exe = new TdVerify();/* @var $exec TdVerify */

        $output = $exe->exec( $input );/* @var $output TdVerifyOutput */

        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        } else {
            // 出力パラメータにエラーコードが含まれていないか、チェック
            if ($output->isErrorOccurred()) {
                $this->error = $this->handle_error($output);
                return FALSE;
            }
        }

        return [
            'order_id' => $output->getOrderId(),
            'forward' => $output->getForward(),
            'method' => $output->getMethod(),
            'pay_times' => $output->getPayTimes(),
            'approve_no' => $output->getApprovalNo(),
            'trans_id' => $output->getTranId(),
            'trans_date' => $output->getTranDate(),
            'check_string' => $output->getCheckString()
        ];

    }

    /**
     * @param array $params
     *
     * @return ExecTranInput
     */
    private function create_exec_input($params = [])
    {
        // 決済実行のパラメータ
        $exec_input = new ExecTranInput();

        // カード番号入力型・会員ID決済型に共通する値
        $exec_input->setOrderId($params['order_id']);
        $exec_input->setMethod($params['method']);

        if (!empty($params['client_field1'])) {
            $exec_input->setClientField1(mb_convert_encoding($params['client_field1'], 'SJIS', 'UTF-8'));
        }

        if (!empty($params['client_field2'])) {
            $exec_input->setClientField2(mb_convert_encoding($params['client_field2'], 'SJIS', 'UTF-8'));
        }

        if (!empty($params['client_field3'])) {
            $exec_input->setClientField3(mb_convert_encoding($params['client_field3'], 'SJIS', 'UTF-8'));
        }

        if (!empty($params['display_info'])) {
            $exec_input->setDisplayInfo(mb_convert_encoding($params['display_info'], 'SJIS', 'UTF-8'));
        }

        // For 3D Input
        if ($this->td_flag) {
            $exec_input->setHttpUserAgent($_SERVER['HTTP_USER_AGENT']);
            $exec_input->setHttpAccept($_SERVER['HTTP_ACCEPT']);
        }

        if (!empty($params['member_id'])) {
            $exec_input->setSiteId($this->site_id);
            $exec_input->setSitePass($this->site_secret);

            $exec_input->setMemberId($params['member_id']);
            $exec_input->setCardSeq(isset($params['card_seq']) ? $params['card_seq'] : 0);
        } else {
            //カード番号・有効期限は必須で
            $exec_input->setCardNo($params['card_no']);
            $exec_input->setExpire($params['expire']);

            $exec_input->setSecurityCode($params['security_code']);
        }

        return $exec_input;
    }

    /**
     * Create entry tran
     *
     * @param array $params
     *
     * @return array|bool
     */
    public function create_entry_tran($params = [])
    {
        $this->entry_input->setJobCd($params['job_cd']);

        $this->entry_input->setOrderId($params['order_id']);
        $this->entry_input->setAmount($params['amount']);
        $this->entry_input->setTax( !empty($params['tax']) ? $params['tax'] : 0);

        if (!empty($params['td_flag'])) {
            $this->entry_input->setTdFlag($params['td_flag']);
            $this->entry_input->setTdTenantName($params['td_tenant_name']);
        }

        //API通信クラスをインスタンス化します
        $exe = new EntryTran();/* @var $exec EntryTran */

        //パラメータオブジェクトを引数に、実行メソッドを呼び、結果を受け取ります。
        $output = $exe->exec( $this->entry_input );
        //実行後、その結果を確認します。
        if ($exe->isExceptionOccured()) {
            $this->error = $this->handle_exception($exe);
            return FALSE;
        } else {

            if ($output->isErrorOccurred()) {
                $this->error = $this->handle_error($output);
                return FALSE;
            }
        }

        return [
            'access_id' => $output->accessId,
            'access_pass' => $output->accessPass
        ];
    }

    /**
     * Error
     */
    public function get_error()
    {
        if (empty($this->error)) {
            return null;
        }

        $has_internal_error = FALSE;

        $error_res = [];

        $internal_error = [];

        $match_user_error_codes = ['E210', 'E0125', 'E0117', 'E0118', 'E0113', 'N0C030G', 'N0N0200', 'P010812', 'P010811', 'P010200', 'P010107', 'P010106', '42G9', '42G56', '42G65', '42G60', '42G61', '42G1', '42G2', '42G3', '42G83', 'E01240002'];

        foreach ($this->error AS $key => $value) {
            //
            if (isset($value['message'])) {
                $check_user_error = FALSE;
                foreach ($match_user_error_codes AS $errcode) {
                    if (strpos($value['info'], $errcode) === 0) {
                        $error_res[] = $value['info'].': '.$value['message'];
                        $check_user_error = TRUE;
                        break;
                    }
                }

                if (!$check_user_error) {
                    $internal_error[] = $value['info'];
                }
            }

            if ($key === 'message') {
                $has_internal_error = TRUE;
                $error_res[] = 'GMO_EXCEPTION: ' . $value;
            }
        }

        if (!empty($internal_error)) {
            $has_internal_error = TRUE;
            $error_res[] = '[' . implode(',', $internal_error) .'] 決済システムへの接続時に、問題が発生しました';
        }

        return [
            'has_internal_error' => $has_internal_error,
            'message' => implode("\n", $error_res)
        ];
    }

    /**
     * @param object $exe
     *
     * @return array
     */
    private function handle_exception($exe)
    {
        /** @var Exception $exception */
        $exception = $exe->getException();

        $m = [];
        $messages = $exception->getMessages();
        if (is_array($messages)) {
            foreach( $messages as  $message ){
                $m[] = $message;
            }
        }

        if (!empty($m)) {
            log_message('debug', '[GMO_PAYMENT] Handle Exception ' . implode("\n", $m));
        }

        return [
            'message' => $exception->getMessage(),
            'messages' => $m
        ];
    }

    /**
     * @param object $output
     * @return array
     */
    private function handle_error($output)
    {
        $arr = $output->getErrList();

        $obj = new GMO_Payment_error_handler();

        $e = [];
        /** @var object $v */
        foreach ($arr AS $v) {
            $e[] = [
                'no' => $v->getErrCode(),
                'info' => $v->getErrInfo(),
                'message' => $obj->get_message($v->getErrInfo())
            ];

            log_message('debug', '[GMO_PAYMENT] Error ' . $v->getErrInfo() . ': ' . $obj->get_message($v->getErrInfo()));
        }

        return $e;
    }

}

/**
 * Class GMO_Payment_error_handler
 */
class GMO_Payment_error_handler
{
    private $map = [
        'E00000000' => '特になし',
        'E01010001' => 'ショップIDが指定されていません。',
        'E01010008' => 'ショップIDに半角英数字以外の文字が含まれているか、13文字を超えています。',
        'E01010010' => 'ショップIDが一致しません。',
        'E01020001' => 'ショップパスワードが指定されていません。',
        'E01020008' => 'ショップパスワードに半角英数字以外の文字が含まれているか、10 文字を超えています。',
        'E01030002' => '指定されたIDとパスワードのショップが存在しません。',
        'E01040001' => 'オーダーIDが指定されていません。',
        'E01040003' => 'オーダーIDが最大文字数を超えています。',
        'E01040010' => '既にオーダーIDが存在しています。',
        'E01040013' => 'オーダーIDに半角英数字と”-”以外の文字が含まれています。',
        'E01050001' => '処理区分が指定されていません。',
        'E01050002' => '指定された処理区分は定義されていません。',
        'E01050004' => '指定した処理区分の処理は実行出来ません。',
        'E01060001' => '利用金額が指定されていません。',
        'E01060005' => '利用金額が最大桁数を超えています。',
        'E01060006' => '利用金額に数字以外の文字が含まれています。',
        'E01060010' => '取引の利用金額と指定した利用金額が一致していません。',
        'E01070005' => '税送料が最大桁数を超えています。',
        'E01070006' => '税送料に数字以外の文字が含まれています。',
        'E01080007' => '3Dセキュア使用フラグに0,1以外の値が指定されています。',
        'E01080010' => '管理画面の設定と一致しません。',
        'E01080101' => '3D必須店舗にも関わらず3Dセキュア使用フラグがOFFになっています。',
        'E01090001' => '取引IDが指定されていません。',
        'E01090008' => '取引IDの書式が正しくありません。',
        'E01100001' => '取引パスワードが指定されていません。',
        'E01100008' => '取引パスワードの書式が正しくありません。',
        'E01160001' => 'ボーナス分割回数が指定されていません。',
        'E01110002' => '指定されたIDとパスワードの取引が存在しません。',
        'E01160007' => 'ボーナス分割回数に数字以外の文字が含まれています。',
        'E01110010' => '指定された取引は決済が完了していません。',
        'E01130012' => 'カード会社略称が最大バイト数を超えています。',
        'E01160010' => 'ボーナス分割回数に“2”以外を指定しています。',
        'E01170001' => 'カード番号が指定されていません。',
        'E01170003' => 'カード番号が最大文字数を超えています。',
        'E01170006' => 'カード番号に数字以外の文字が含まれています。',
        'E01170011' => 'カード番号が10桁~16桁の範囲ではありません。',
        'E01180001' => '有効期限が指定されていません。',
        'E01180003' => '有効期限が4桁ではありません。',
        'E01180006' => '有効期限に数字以外の文字が含まれています。',
        'E01190001' => 'サイトIDが指定されていません。',
        'E01190008' => 'サイトIDの書式が正しくありません。',
        'E01200001' => 'サイトパスワードが指定されていません。',
        'E01200008' => 'サイトパスワードの書式が正しくありません。',
        'E01210002' => '指定されたIDとパスワードのサイトが存在しません。',
        'E01220001' => '会員IDが指定されていません。',
        'E01220005' => '会員IDが最大桁数を超えています。',
        'E01220008' => '会員IDの書式が正しくありません。',
        'E01230006' => 'カード登録連番に数字以外の文字が含まれています。',
        'E01230009' => 'カード登録連番が最大登録可能数を超えています。',
        'E01240002' => '指定されたカードが存在しません。',
        'E01240012' => '指定された会員IDがファイル内で重複しています(※洗替時)',
        'E01250008' => 'カードパスワードの書式が正しくありません。',
        'E01250010' => 'カードパスワードが一致しません。',
        'E01260001' => '支払方法が指定されていません。',
        'E01260002' => '指定された支払方法が存在しません。',
        'E01260010' => '指定されたカード番号または支払方法が正しくありません。',
        'E01270001' => '支払回数が指定されていません。',
        'E01270005' => '支払回数が最大桁数を超えています。',
        'E01270006' => '支払回数の数字以外の文字が含まれています。',
        'E01270010' => '指定された支払回数はご利用できません。',
        'E01290001' => 'HTTP_ACCEPTが指定されていません。',
        'E01300001' => 'HTTP_USER_AGENTが指定されていません。',
        'E01310002' => '使用端末が指定されていません。',
        'E01310007' => '使用端末に”0”,”1”以外の値が指定されています。',
        'E01320012' => '加盟店自由項目1の値が最大バイト数を超えています。',
        'E01330012' => '加盟店自由項目2の値が最大バイト数を超えています。',
        'E01340012' => '加盟店自由項目3の値が最大バイト数を超えています。',
        'E01350001' => 'MDが指定されていません。',
        'E01350008' => 'MDの書式が正しくありません。',
        'E01360001' => 'PaResが指定されていません。',
        'E01370008' => '3Dセキュア表示店舗名の書式が正しくありません。',
        'E01370012' => '3Dセキュア表示店舗名の値が最大バイト数を超えています。',
        'E01390002' => '指定されたサイトIDと会員IDの会員が存在しません。',
        'E01390010' => '指定されたサイトIDと会員IDの会員が既に存在しています。',
        'E01400007' => '加盟店自由項目返却フラグに”0”,”1”以外の値が指定されています。',
        'E01410010' => '該当取引は操作禁止状態です。',
        'E01420010' => '仮売上有効期間を超えています。',
        'E01430012' => '会員名の値が最大バイト数を超えています。',
        'E01440008' => '洗替・継続課金フラグの書式が正しくありません。',
        'E01450008' => '商品コードの書式が正しくありません。',
        'E01460008' => 'セキュリティコードの書式が正しくありません。',
        'E01470008' => 'カード登録連番モードの書式が正しくありません。',
        'E01480008' => '名義人の書式が正しくありません。',
        'E01490005' => '利用金額・税送料の合計値が最大桁数を超えています。',
        'E01500001' => 'ショップ情報文字列が設定されていません。',
        'E01500005' => 'ショップ情報文字列の文字数が間違っています。',
        'E01500012' => 'ショップ情報文字列が他の項目と矛盾しています。',
        'E01510001' => '購買情報文字列が設定されていません。',
        'E01510005' => '購買情報文字列の文字数が間違っています。',
        'E01510010' => '利用日の書式が正しくありません。',
        'E01510011' => '利用日の値が指定可能範囲外です。',
        'E01510012' => '購買情報文字列が他の項目と矛盾しています。',
        'E01520002' => 'ユーザー利用端末情報に無効な値が設定されています。',
        'E01530001' => '決済結果戻り先URLが設定されていません。',
        'E01530005' => '決済結果戻り先URLが最大文字数を越えています。',
        'E01540005' => '決済キャンセル時URLが最大文字数を超えています。',
        'E01550001' => '日時情報文字列が設定されていません。',
        'E01550005' => '日時情報文字列の文字数が間違っています。',
        'E01550006' => '日時情報文字列に無効な文字が含まれます。',
        'E01590005' => '商品コードが最大桁数を超えています。',
        'E01590006' => '商品コードに無効な文字が含まれます。',
        'E01600001' => '会員情報チェック文字列が設定されていません。',
        'E01600005' => '会員情報チェック文字列が最大文字数を超えています。',
        'E01600012' => '会員情報チェック文字列が他の項目と矛盾しています。',
        'E01610005' => 'リトライ回数が0~99の範囲外です。',
        'E01610006' => 'リトライ回数に数字以外が設定されています。',
        'E01620005' => 'セッションタイムアウト値が0~9999の範囲外です。',
        'E01620006' => 'セッションタイムアウト値に数字以外が設定されています。',
        'E01630010' => '取引後カード登録時、取引の会員IDとパラメータの会員IDが一致しません。',
        'E01640010' => '取引後カード登録時、取引のサイトIDとパラメータのサイトIDが一致しません。',
        'E01650012' => '指定されたショップは、指定されたサイトに属していません。',
        'E01660013' => '言語パラメータにサポートされない値が設定されています。',
        'E01670013' => '出力エンコーディングにサポートされない値が設定されています。',
        'E01700001' => '項目数が誤っています。',
        'E01710001' => '取引区分(継続課金)が設定されていません。',
        'E01710002' => '指定された取引区分が存在しません。',
        'E01730001' => 'ボーナス金額が指定されていません。',
        'E01730005' => 'ボーナス金額が最大桁数を超えています。',
        'E01730006' => '商品コードが”0000990”ではありません。',
        'E01730007' => 'ボーナス金額に数字以外の文字が含まれています。',
        'E01740001' => '端末処理通番が指定されていません。',
        'E01740005' => '端末処理通番が最大桁数を超えています。',
        'E01740007' => '端末処理通番に数字以外の文字が含まれています。',
        'E01750001' => '利用日が指定されていません。',
        'E01750008' => '利用日の書式が正しくありません。',
        'E01800001' => '暗証番号が未入力です。',
        'E01800008' => '暗証番号の書式が正しくありません。',
        'E01800010' => '暗証番号は利用できません。',
        'E11010001' => 'この取引は既に決済が終了しています。',
        'E11010002' => '取引エラー/決済を中止して、取引が出来ない事を通知して下さい。',
        'E11010003' => 'この取引は指定処理区分処理を行う事が出来ません。',
        'E11010010' => '180日超えの取引のため、処理を行う事が出来ません。',
        'E11010011' => '180日超えの取引のため、処理を行う事が出来ません。',
        'E11010012' => '180日超えの取引のため、処理を行う事が出来ません。',
        'E11010013' => '180日超えの取引のため、処理を行う事が出来ません。',
        'E11010014' => '180日超えの取引のため、処理を行う事が出来ません。',
        'E11010099' => 'このカードはご利用になれません。',
        'E11010999' => '特になし',
        'E11310001' => 'この取引はリンク決済を実行できません。',
        'E11310002' => 'この取引はリンク決済を実行できません。',
        'E11310003' => 'この取引はリンク決済を実行できません。',
        'E11310004' => 'この取引はリンク決済を実行できません。',
        'E11310005' => '既にカードを登録している会員は、取引後カード登録を実行できません。',
        'E21010001' => '3Dセキュア認証に失敗しました。もう一度、購入画面からやり直して下さい。',
        'E21010007' => '3Dセキュア認証に失敗しました。もう一度、購入画面からやり直して下さい。',
        'E21010999' => '3Dセキュア認証に失敗しました。もう一度、購入画面からやり直して下さい。',
        'E21020001' => '3Dセキュア認証に失敗しました。もう一度、購入画面からやり直して下さい。',
        'E21020002' => '3Dセキュア認証がキャンセルされました。もう一度、購入画面からやり直して下さい。',
        'E21020007' => '3Dセキュア認証に失敗しました。もう一度、購入画面からやり直して下さい。',
        'E21020999' => '3Dセキュア認証に失敗しました。もう一度、購入画面からやり直して下さい。',
        'E21010201' => 'このカードでは取引をする事が出来ません。3Dセキュア認証に対応したカードをお使い下さい。',
        'E21010202' => 'このカードでは取引をする事が出来ません。3Dセキュア認証に対応したカードをお使い下さい。',
        'E31500014' => '-',
        'E41170002' => '入力されたカード会社に対応していません。別のカード番号を入力して下さい。',
        'E41170099' => 'カード番号に誤りがあります。再度確認して入力して下さい。',
        'E61010001' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'E61010002' => 'ご利用出来ないカードをご利用になったもしくはカード番号が誤っております。',
        'E61010003' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'E61020001' => '指定の決済方法は利用停止になっています。',
        'E61030001' => 'ご契約内容エラー/現在のご契約では、ご利用になれません。',
        'E82010001' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'E90010001' => '現在処理を行っているのでもうしばらくお待ち下さい。',
        'E91099996' => 'システムの内部エラーです。発生時刻や呼び出しパラメータをご確認のうえ、お問い合わせください。',
        'E91099997' => 'リクエストされたAPIは存在しません。URLをお確かめください。',
        'E91019999' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'E91020001' => '通信タイムアウトが発生しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'E91029998' => '決済処理に失敗しました。該当のお取引について、店舗までお問合せください。',
        'E91029999' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'E91050001' => '決済処理に失敗しました。',
        'E91060001' => 'システムの内部エラーです。発生時刻や呼び出しパラメータをご確認のうえ、お問い合わせください。',
        'E91099999' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'E92000001' => '只今、大変込み合っていますので、しばらく時間をあけて再度決済を行ってください。',
        'M01001005' => 'バージョンの文字数が最大文字数を超えています。',
        'M01002001' => 'ショップIDが指定されていません。',
        'M01002002' => '指定されたIDとパスワードのショップが存在しません。',
        'M01002008' => 'ショップIDの書式が正しくありません。',
        'M01003001' => 'ショップパスワードが指定されていません。',
        'M01003008' => 'ショップパスワードの書式が正しくありません。',
        'M01004001' => 'オーダーIDが指定されていません。',
        'M01004002' => '指定されたオーダーIDの取引は登録されていません。',
        'M01004010' => '既にオーダーIDが存在しています。',
        'M01004012' => 'オーダーIDが最大文字数を超えています。',
        'M01004013' => 'オーダーIDに不正な文字が含まれています。',
        'M01004014' => '指定されたオーダーIDの取引は既に決済を依頼してます。',
        'M01005001' => '利用金額が指定されていません。',
        'M01005005' => '利用金額が最大桁数を超えています。',
        'M01005006' => '利用金額に数字以外の文字が含まれています。',
        'M01005011' => '利用金額が有効な範囲を超えています。',
        'M01006005' => '税送料が最大桁数を超えています。',
        'M01006006' => '税送料に数字以外の文字が含まれています。',
        'M01007001' => '取引IDが指定されていません。',
        'M01007008' => '取引IDの書式が正しくありません。',
        'M01008001' => '取引Iパスワードが指定されていません。',
        'M01008008' => '取引パスワードの書式が正しくありません。',
        'M01009001' => '支払先コンビニコードが指定されていません。',
        'M01009002' => '指定された支払先コンビニコードが正しくありません。',
        'M01009005' => '支払先コンビニコードが最大文字数を超えています。',
        'M01010001' => '氏名が指定されていません。',
        'M01010012' => '氏名が最大バイト数を超えています。',
        'M01010013' => '氏名に不正な文字が含まれています。',
        'M01011001' => 'フリガナが指定されていません。',
        'M01011012' => 'フリガナが最大バイト数を超えています。',
        'M01011013' => 'フリガナに不正な文字が含まれています。',
        'M01012001' => '電話番号が指定されていません。',
        'M01012005' => '電話番号が最大文字数を超えています。',
        'M01012008' => '電話番号の書式が正しくありません。',
        'M01013005' => '支払期限日数が最大文字数を超えています。',
        'M01013006' => '支払期限日数に数字以外の文字が指定されています。',
        'M01013011' => '支払期限日数が有効な範囲ではありません。',
        'M01014001' => '結果通知先メールアドレスが指定されていません。',
        'M01014005' => '結果通知先メールアドレスが最大文字数を超えています。',
        'M01014008' => '結果通知先メールアドレスの書式が正しくありません。',
        'M01015005' => '加盟店メールアドレスが最大文字数を超えています。',
        'M01015008' => '加盟店メールアドレスの書式が正しくありません。',
        'M01016012' => '予約番号が最大バイト数を超えています。',
        'M01016013' => '予約番号に不正な文字が含まれています。',
        'M01017012' => '会員番号が最大バイト数を超えています。',
        'M01017013' => '会員番号に不正な文字が含まれています。',
        'M01018012' => 'POSレジ表示欄1が最大バイト数を超えています。',
        'M01018013' => 'POSレジ表示欄1に不正な文字が含まれています。',
        'M01019012' => 'POSレジ表示欄2が最大バイト数を超えています。',
        'M01019013' => 'POSレジ表示欄2に不正な文字が含まれています。',
        'M01020012' => 'POSレジ表示欄3が最大バイト数を超えています。',
        'M01020013' => 'POSレジ表示欄3に不正な文字が含まれています。',
        'M01021012' => 'POSレジ表示欄4が最大バイト数を超えています。',
        'M01021013' => 'POSレジ表示欄4に不正な文字が含まれています。',
        'M01022012' => 'POSレジ表示欄5が最大バイト数を超えています。',
        'M01022013' => 'POSレジ表示欄5に不正な文字が含まれています。',
        'M01023012' => 'POSレジ表示欄6が最大バイト数を超えています。',
        'M01023013' => 'POSレジ表示欄6に不正な文字が含まれています。',
        'M01024012' => 'POSレジ表示欄7が最大バイト数を超えています。',
        'M01024013' => 'POSレジ表示欄7に不正な文字が含まれています。',
        'M01025012' => 'POSレジ表示欄8が最大バイト数を超えています。',
        'M01025013' => 'POSレジ表示欄8に不正な文字が含まれています。',
        'M01026012' => 'レシート表示欄1が最大バイト数を超えています。',
        'M01026013' => 'レシート表示欄1に不正な文字が含まれています。',
        'M01027012' => 'レシート表示欄2が最大バイト数を超えています。',
        'M01027013' => 'レシート表示欄2に不正な文字が含まれています。',
        'M01028012' => 'レシート表示欄3が最大バイト数を超えています。',
        'M01028013' => 'レシート表示欄3に不正な文字が含まれています。',
        'M01029012' => 'レシート表示欄4が最大バイト数を超えています。',
        'M01029013' => 'レシート表示欄4に不正な文字が含まれています。',
        'M01030012' => 'レシート表示欄5が最大バイト数を超えています。',
        'M01030013' => 'レシート表示欄5に不正な文字が含まれています。',
        'M01031012' => 'レシート表示欄6が最大バイト数を超えています。',
        'M01031013' => 'レシート表示欄6に不正な文字が含まれています。',
        'M01032012' => 'レシート表示欄7が最大バイト数を超えています。',
        'M01032013' => 'レシート表示欄7に不正な文字が含まれています。',
        'M01033012' => 'レシート表示欄8が最大バイト数を超えています。',
        'M01033013' => 'レシート表示欄8に不正な文字が含まれています。',
        'M01034012' => 'レシート表示欄9が最大バイト数を超えています。',
        'M01034013' => 'レシート表示欄9に不正な文字が含まれています。',
        'M01035012' => 'レシート表示欄10が最大バイト数を超えています。',
        'M01035013' => 'レシート表示欄10に不正な文字が含まれています。',
        'M01036001' => 'お問合せ先が指定されていません。',
        'M01036012' => 'お問合せ先が最大バイト数を超えています。',
        'M01036013' => 'お問合せ先に不正な文字が含まれています。',
        'M01037001' => 'お問合せ先電話番号が指定されていません。',
        'M01037005' => 'お問合せ先電話番号が最大文字数を超えています。',
        'M01037008' => 'お問合せ先電話番号に数字、-以外の文字が指定されています。',
        'M01038001' => 'お問合せ先受付時間が指定されていません。',
        'M01038005' => 'お問合せ先受付時間が最大文字数を超えています。',
        'M01038008' => 'お問合せ先受付時間に数字、”:、””-“以外の文字が指定されています。',
        'M01039012' => '加盟店自由項目1が最大バイト数を超えています。',
        'M01039013' => '加盟店自由項目1に不正な文字が含まれています。',
        'M01040012' => '加盟店自由項目2が最大バイト数を超えています。',
        'M01040013' => '加盟店自由項目2に不正な文字が含まれています。',
        'M01041012' => '加盟店自由項目3が最大バイト数を超えています。',
        'M01041013' => '加盟店自由項目3に不正な文字が含まれています。',
        'M01042005' => '結果返却方法フラグが最大文字数を超えています。',
        'M01042011' => '結果返却方法フラグに”0”,”1”以外の値が指定されています。',
        'M01043001' => '商品・サービス名が指定されていません。',
        'M01043012' => '商品・サービス名が最大バイト数を超えています。',
        'M01043013' => '商品・サービス名に不正な文字が含まれています。',
        'M01044012' => '決済開始メール付加情報が最大バイト数を超えています。',
        'M01044013' => '決済開始メール付加情報に不正な文字が含まれています。',
        'M01045012' => '決済完了メール付加情報が最大バイト数を超えています。',
        'M01045013' => '決済完了メール付加情報に不正な文字が含まれています。',
        'M01046012' => '決済内容確認画面付加情報が最大バイト数を超えています。',
        'M01046013' => '決済内容確認画面付加情報に不正な文字が含まれています。',
        'M01047012' => '決済完了画面付加情報が最大バイト数を超えています。',
        'M01047013' => '決済完了画面付加情報に不正な文字が含まれています。',
        'M01048005' => '支払期限秒数が最大文字数を超えています。',
        'M01048006' => '支払期限秒数に数字以外の文字が指定されています。',
        'M01048011' => '支払期限秒数が有効な範囲ではありません。',
        'M01049012' => '決済開始メール付加情報が最大バイト数を超えています。',
        'M01049013' => '決済開始メール付加情報に不正な文字が含まれています。',
        'M01050012' => '決済完了メール付加情報が最大バイト数を超えています。',
        'M01050013' => '決済完了メール付加情報に不正な文字が含まれています。',
        'M01051001' => '決済方法が指定されていません。',
        'M01051005' => '決済方法が最大文字数を超えています。',
        'M01051011' => '決済方法が有効な範囲ではありません。',
        'M01052011' => '支払期限日を超えています。',
        'M01053002' => '指定されたコンビニはご利用できません。',
        'M01054001' => '処理区分が指定されていません。',
        'M01054004' => '取引の現状態に対して、処理可能な操作ではありません。',
        'M01054010' => '指定された処理区分は定義されていません。',
        'M01055010' => '取引の利用金額・税送料の合計値が、指定された利用金額・税送料の合計値と一致しません。',
        'M01055011' => '指定された利用金額・税送料の合計値が取引の利用金額・税送料の合計値を超えています。',
        'M01056001' => 'リダイレクトURLが指定されていません。',
        'M01056012' => 'リダイレクトURLが最大文字数を超えています。',
        'M01057010' => '取消可能な期間を超えています。',
        'M01058002' => '指定された取引が存在しません。',
        'M01058010' => '取引のショップIDが、指定されたショップIDと一致しません。',
        'M01059001' => '戻り先URLが設定されていません。',
        'M01059005' => '戻り先URLが最大文字数を超えています。',
        'M01059012' => '戻り先URLが最大文字数を超えています。',
        'M01060010' => '仮売上有効期間を超えています。',
        'M01061001' => '金融機関コードが設定されていません。',
        'M01061002' => '存在しない金融機関コードが設定されました。',
        'M01061005' => '金融機関コードが最大桁数を超えています。',
        'M01062001' => '支店コードが設定されていません。',
        'M01062002' => '存在しない支店コードが設定されました。',
        'M01062005' => '支店コードが最大桁数を超えています。',
        'M01063001' => '会員登録区分が設定されていません。',
        'M01063002' => '存在しない会員登録区分が設定されました。',
        'M01064001' => '口座名義人(姓:漢字)が設定されていません。',
        'M01064003' => '口座名義人(姓:漢字)が最大文字数を超えています。',
        'M01064013' => '口座名義人(姓:漢字)に利用できない文字が含まれています。',
        'M01065001' => '口座名義人(姓:読み)が設定されていません。',
        'M01065003' => '口座名義人(姓:読み)が最大文字数を超えています。',
        'M01065013' => '口座名義人(姓:読み)に利用できない文字が含まれています。',
        'M01066001' => '口座名義人(名:漢字)が設定されていません。',
        'M01066003' => '口座名義人(名:漢字)が最大文字数を超えています。',
        'M01066013' => '口座名義人(名:漢字)に利用できない文字が含まれています。',
        'M01067001' => '口座名義人(名:読み)が設定されていません。',
        'M01067003' => '口座名義人(名:読み)が最大文字数を超えています。',
        'M01067013' => '口座名義人(名:読み)に利用できない文字が含まれています。',
        'M01068001' => '口座名義人(法人名漢字)が設定されていません。',
        'M01068003' => '口座名義人(法人名漢字)が最大文字数を超えています。',
        'M01068013' => '口座名義人(法人名漢字)に利用できない文字が含まれています。',
        'M01069001' => '口座名義人(法人名読み)が設定されていません。',
        'M01069003' => '口座名義人(法人名読み)が最大文字数を超えています。',
        'M01069013' => '口座名義人(法人名読み)に利用できない文字が含まれています。',
        'M01070001' => '口座番号が設定されていません。',
        'M01070002' => '存在しない預金種目が設定されました。',
        'M01071001' => '口座番号が設定されていません。',
        'M01071005' => '口座番号が最大桁数を超えています。',
        'M01071006' => '口座番号に数字以外の文字が含まれています。',
        'M01073001' => 'トランザクションIDが設定されていません。',
        'M01073002' => '存在しないトランザクションIDが指定されました。',
        'M01073004' => '指定した申込処理は実行出来ません。',
        'M01074090' => 'トークンが不正です。',
        'M01075001' => '口座名義が設定されていません。',
        'M01075005' => '口座名義が最大文字数を超えています。',
        'M01075013' => '口座名義に利用できない文字が含まれています。',
        'M01076001' => 'ユーザ利用端末が設定されていません。',
        'M01076010' => '指定されたユーザ利用端末は定義されていません。',
        'M01077005' => '口座名義漢字が最大文字数を超えています。',
        'M01077013' => '口座名義漢字に利用できない文字が含まれています。',
        'M01078005' => '通貨コードの桁数が間違っています。',
        'M01078010' => '利用可能な通貨コードではありません。',
        'M01079010' => '利用可能なロケールではありません。',
        'M01080001' => '摘要が設定されていません。',
        'M01080005' => '摘要が最大文字数を超えています。',
        'M01080013' => '摘要に利用できない文字が含まれています。',
        'M01081011' => '決済結果URL有効期限秒が有効な範囲ではありません。',
        'M01081013' => '決済結果URL有効期限秒に利用できない文字が含まれています。',
        'M01082001' => 'サービス名が設定されていません。',
        'M01082005' => 'サービス名が最大文字数を超えています。',
        'M01082013' => 'サービス名に利用できない文字が含まれています。',
        'M01083001' => 'サービス電話番号が設定されていません。',
        'M01084002' => '存在しないOpenIDが指定されました。',
        'M01085001' => 'キャンセル金額が指定されていません。',
        'M01085005' => 'キャンセル金額が最大桁数を超えています。',
        'M01085006' => 'キャンセル金額に数字以外の文字が含まれています。',
        'M01085010' => 'オーソリ時の金額とキャンセル金額が一致しません。',
        'M01085011' => 'キャンセル金額がオーソリ時の金額を超えています。',
        'M01086005' => 'キャンセル税送料が最大桁数を超えています。',
        'M01086006' => 'キャンセル税送料に数字以外の文字が含まれています。',
        'M01087012' => 'ドコモ表示項目1が最大桁数を超えています。',
        'M01087013' => 'ドコモ表示項目1に利用できない文字が含まれています。',
        'M01088012' => 'ドコモ表示項目2が最大桁数を超えています。',
        'M01088013' => 'ドコモ表示項目2に利用できない文字が含まれています。',
        'M01089010' => '処理要求実施最終期限を超えています。',
        'M01091001' => '確定日が設定されていません。',
        'M01091010' => '利用可能な確定日ではありません。',
        'M01092001' => '初月利用料無料区分が設定されていません。',
        'M01092010' => '利用可能な初月利用料無料区分ではありません。',
        'M01093001' => '終了月利用料無料区分が設定されていません。',
        'M01093004' => '該当取引は確定中のため終了月無料は設定できません。',
        'M01093010' => '利用可能な終了月利用料無料区分ではありません。',
        'M01094001' => '継続課金月が設定されていません。',
        'M01094008' => '継続課金月の書式が正しくありません。',
        'M01095010' => '当月分の課金データが生成されていないため処理できません。しばらくたってから再度実行してください。',
        'M01096010' => '前回実行した処理から規定時間が経過していません。しばらくたってから再度実行してください。',
        'M01097010' => 'ショップからの継続課金変更・終了処理は月末20:00~24:00の間は受付できません。',
        'M01100012' => '振込内容が最大桁数を超えています。',
        'M01100013' => '振込内容に利用できない文字が含まれています。',
        'M01101001' => '初回課金利用金額が設定されていません。',
        'M01107010' => '指定可能な初回課金日ではありません。',
        'M01120001' => 'NET CASH決済方法が指定されていません。',
        'M01120010' => 'NET CASH決済方法が不正です。',
        'M01120012' => 'NET CASH決済方法が最大バイト数を超えています。',
        'M01120013' => 'NET CASH決済方法に不正な文字が含まれています。',
        'M01500001' => 'ショップ情報文字列が設定されていません。',
        'M01500005' => 'ショップ情報文字列の文字数が間違っています。',
        'M01500012' => 'ショップ情報文字列が他の項目と矛盾しています。',
        'M01510001' => '購買情報文字列が設定されていません。',
        'M01510005' => '購買情報文字列の文字数が間違っています。',
        'M01510012' => '購買情報文字列が他の項目と矛盾しています。',
        'M01520002' => 'ユーザー利用端末情報に無効な値が設定されています。',
        'M01530001' => '決済結果戻り先URLが設定されていません。',
        'M01530005' => '決済結果戻り先URLが最大文字数を越えています。',
        'M01540005' => '決済キャンセル時URLが最大文字数を超えています。',
        'M01550001' => '日時情報文字列が設定されていません。',
        'M01550005' => '日時情報文字列の文字数が間違っています。',
        'M01550006' => '日時情報文字列に無効な文字が含まれます。',
        'M01590005' => '商品コードが最大桁数を超えています。',
        'M01590006' => '商品コードに無効な文字が含まれます。',
        'M01600001' => '会員情報チェック文字列が設定されていません。',
        'M01600005' => '会員情報チェック文字列が最大文字数を超えています。',
        'M01600012' => '会員情報チェック文字列が他の項目と矛盾しています。',
        'M01610005' => 'リトライ回数が0~99の範囲外です。',
        'M01610006' => 'リトライ回数に数字以外が設定されています。',
        'M01620005' => 'セッションタイムアウト値が0~9999の範囲外です。',
        'M01620006' => 'セッションタイムアウト値に数字以外が設定されています。',
        'M01630010' => '取引後カード登録時、取引の会員IDとパラメータの会員IDが一致しません。',
        'M01640010' => '取引後カード登録時、取引のサイトIDとパラメータのサイトIDが一致しません。',
        'M01650012' => '指定されたショップは、指定されたサイトに属していません。',
        'M01660013' => '言語パラメータにサポートされない値が設定されています。',
        'M01670013' => '出力エンコーディングにサポートされない値が設定されています。',
        'M01680001' => '決済利用フラグが設定されていません。',
        'M01680008' => '決済利用フラグに”0”,”1”以外の値が指定されています。',
        'M01700001' => 'メールリンクのご利用契約が無いか、利用停止中です。',
        'M01701002' => '呼び出したメールリンクデータは存在しません。',
        'M01702003' => '呼び出したメールリンクデータは有効期限切れです。',
        'M01703001' => 'ユニーク文字列が指定されていません。',
        'M01703005' => 'ユニーク文字列の長さが32バイト以外です。',
        'M01704005' => 'テンプレート番号が1桁を超えています。',
        'M01704006' => 'テンプレート番号に数字以外が設定されています。',
        'M11010099' => 'この取引は決済が終了していません。',
        'M11010999' => '特になし',
        'M91099999' => '決済処理に失敗しました。',
        '42C010000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C030000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C120000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C130000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C140000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C150000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C500000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C510000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C530000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C540000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C550000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C560000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C570000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C580000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C600000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C700000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C710000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C720000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C730000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C740000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C750000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C760000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C770000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42C780000' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        '42G020000' => 'カード残高が不足しているために、決済を完了する事が出来ませんでした。',
        '42G030000' => 'カード限度額を超えているために、決決済を完了する事が出来ませんでした。',
        '42G040000' => 'カード残高が不足しているために、決済を完了する事が出来ませんでした。',
        '42G050000' => 'カード限度額を超えているために、決済を完了する事が出来ませんでした。',
        '42G120000' => 'このカードでは取引をする事が出来ません。',
        '42G220000' => 'このカードでは取引をする事が出来ません。',
        '42G300000' => 'このカードでは取引をする事が出来ません。',
        '42G420000' => '暗証番号が誤っていた為に、決済を完了する事が出来ませんでした。',
        '42G440000' => 'セキュリティーコードが誤っていた為に、決済を完了する事が出来ませんでした。',
        '42G450000' => 'セキュリティーコードが入力されていない為に、決済を完了する事が出来ませんでした。',
        '42G540000' => 'このカードでは取引をする事が出来ません。',
        '42G550000' => 'カード限度額を超えているために、決済を完了する事が出来ませんでした。',
        '42G560000' => 'このカードでは取引をする事が出来ません。',
        '42G600000' => 'このカードでは取引をする事が出来ません。',
        '42G610000' => 'このカードでは取引をする事が出来ません。',
        '42G650000' => 'カード番号に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G670000' => '商品コードに誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G680000' => '金額に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G690000' => '税送料に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G700000' => 'ボーナス回数に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G710000' => 'ボーナス月に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G720000' => 'ボーナス額に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G730000' => '支払開始月に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G740000' => '分割回数に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G750000' => '分割金額に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G760000' => '初回金額に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G770000' => '業務区分に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G780000' => '支払区分に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G790000' => '照会区分に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G800000' => '取消区分に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G810000' => '取消取扱区分に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G830000' => '有効期限に誤りがあるために、決済を完了する事が出来ませんでした。',
        '42G920000' => 'このカードでは取引をする事が出来ません。',
        '42G950000' => 'このカードでは取引をする事が出来ません。',
        '42G960000' => 'このカードでは取引をする事が出来ません。',
        '42G970000' => 'このカードでは取引をする事が出来ません。',
        '42G980000' => 'このカードでは取引をする事が出来ません。',
        '42G990000' => 'このカードでは取引をする事が出来ません。',
        'B01000002' => '【決済結果問合せ】楽天Edyセンタから発信する決済開始メールが不達となりました。不達の原因は、携帯端末側のメールアドレス変更、ドメイン拒否等が考えられます。',
        'B01000003' => '【決済結果問合せ】楽天Edyセンタに該当の注文番号が存在しません。',
        'B01000100' => '決済申込みで指定した注文番号は、既に楽天Edyセンタに登録されています。',
        'B01001011' => '指定したモールIDに誤りがあります(タグ自体がない)',
        'B01001012' => '指定したモールIDに誤りがあります(値なし)',
        'B01001013' => '指定したモールIDに誤りがあります(サイズエラー)',
        'B01001014' => '指定したモールIDに誤りがあります(属性エラー)',
        'B01001021' => '指定した注文番号に誤りがあります(タグ自体がない)',
        'B01001022' => '指定した注文番号に誤りがあります(値なし)',
        'B01001023' => '指定した注文番号に誤りがあります(サイズエラー)',
        'B01001024' => '指定した注文番号に誤りがあります(属性エラー)',
        'B01001031' => '指定した購入金額の範囲が誤っています(タグ自体がない)',
        'B01001032' => '指定した購入金額の範囲が誤っています(値がない)',
        'B01001033' => '指定した購入金額の範囲が誤っています(サイズエラー)',
        'B01001034' => '指定した購入金額の範囲が誤っています(属性エラー)',
        'B01001035' => '指定した購入金額の範囲が誤っています(値エラー)',
        'B01001041' => '指定したユーザメールアドレスの範囲が誤っています(タグ自体がない)',
        'B01001042' => '指定したユーザメールアドレスの範囲が誤っています(値がない)',
        'B01001043' => '指定したユーザメールアドレスの範囲が誤っています(サイズエラー)',
        'B01001044' => 'To日付時刻指定(属性エラー)',
        'B01001045' => '指定したユーザメールアドレスの範囲が誤っています(値エラー)',
        'B01001055' => '指定した<検索条件>が指定範囲を超えています',
        'B01001064' => '指定した予備に誤りがあります(属性エラー)',
        'B01001083' => '請求書メール付加の指定に誤りがあります(サイズエラー)',
        'B01001111' => '決済終了通知の指定に誤りがあります(タグ自体がない)',
        'B01001112' => '決済終了通知の指定に誤りがあります(値がない)',
        'B01001113' => '決済終了通知の指定に誤りがあります(サイズエラー)',
        'B01001114' => '決済終了通知の指定に誤りがあります(属性エラー)',
        'B01001121' => '指定した有効期限に誤りがあります(タグ自体がない)',
        'B01001122' => '指定した有効期限に誤りがあります(値がない)',
        'B01001123' => '指定した有効期限に誤りがあります(サイズエラー)',
        'B01001124' => '指定した有効期限に誤りがあります(属性エラー)',
        'B01001125' => '指定した有効期限に誤りがあります(値エラー)',
        'B01002001' => '楽天Edyセンタのサービスが停止しています',
        'B01002010' => '指定された加盟店IDは利用できない状態です(未登録)',
        'B01002011' => '指定された加盟店IDは利用できない状態です(閉塞状態)',
        'B01002012' => '指定された加盟店IDは利用できない状態です(適用期間外)',
        'B01003001' => 'システムエラー1',
        'B01003002' => 'システムエラー2',
        'B01003007' => 'システムエラー3',
        'B01003008' => 'システムエラー4',
        'B01003009' => 'システムエラー5',
        'B01004001' => 'クライアント証明書の情報と異なる加盟店IDが指定されました',
        'B01007001' => '決済完了URLの指定に誤りがあります(タグ自体がない)',
        'B01007002' => '決済完了URLの指定に誤りがあります(値がない)',
        'B01007003' => '決済完了URLの指定に誤りがあります(サイズエラー)',
        'B01007004' => '決済完了URLの指定に誤りがあります(属性エラー)',
        'B01007005' => '決済完了URLの指定に誤りがあります(値エラー)',
        'B01007011' => '指定したユーザメールアドレスに誤りがあります(属性エラー)',
        'B01007021' => '指定したモールメールアドレスに誤りがあります(属性エラー)',
        'B01009000' => '【決済申込み】加盟店IDの指定に誤りがあります',
        'B01009001' => '【決済申込み】パスワードの指定に誤りがあります',
        'B01009002' => '【決済申込み】注文番号の指定に誤りがあります',
        'B01009003' => '【決済申込み】金額の指定に誤りがあります',
        'B01009004' => '【決済申込み】ユーザメールアドレスの指定に誤りがあります',
        'B01009005' => '【決済申込み】加盟店様メールアドレスの指定に誤りがあります',
        'B01009006' => '【決済申込み】予備の指定に誤りがあります',
        'B01009007' => '【決済申込み】顧客名の指定に誤りがあります',
        'B01009008' => '【決済申込み】請求書メール付加情報の指定に誤りがあります',
        'B01009009' => '【決済申込み】決済完了メール付加情報の指定に誤りがあります',
        'B01009010' => '【決済申込み】店舗名の指定に誤りがあります',
        'B01009011' => '【決済申込み】決済終了通知URLの指定に誤りがあります',
        'B01009012' => '【決済申込み】有効期限の指定に誤りがあります',
        'B01009013' => '【決済申込み】XML の書式に誤りがあります',
        'B01009014' => '【決済申込み】HTML エラー楽天Edyセンタから受信した内容が想定外の内容です',
        'B01009050' => '【決済結果問合せ】加盟店IDの指定に誤りがあります',
        'B01009051' => '【決済結果問合せ】パスワードの指定に誤りがあります',
        'B01009052' => '【決済結果問合せ】注文番号の指定に誤りがあります',
        'B01009053' => '【決済結果問合せ】From日付時刻の指定に誤りがあります',
        'B01009054' => '【決済結果問合せ】To日付時刻の指定に誤りがあります',
        'B01009055' => '【決済結果問合せ】検索パターンの指定に誤りがあります',
        'B01009056' => '【決済結果問合せ】XML エラー',
        'B01009057' => '【決済結果問合せ】HTML エラー',
        'B01009100' => 'センタから受信したHTTP レスポンスコードが異常でした (100)HTTP-Status-Continue',
        'B01009101' => 'センタから受信したHTTP レスポンスコードが異常でした (101)HTTP-Status-SwitchingProtocol',
        'B01009201' => 'センタから受信したHTTP レスポンスコードが異常でした (201)HTTP-Status-Created',
        'B01009202' => 'センタから受信したHTTP レスポンスコードが異常でした (202)HTTP-Status-Accepted',
        'B01009203' => 'センタから受信したHTTP レスポンスコードが異常でした (203)HTTP-Status-NonAuthoritative Infomation',
        'B01009204' => 'センタから受信したHTTP レスポンスコードが異常でした (204)HTTP-Status-NoContent',
        'B01009205' => 'センタから受信したHTTP レスポンスコードが異常でした (205)HTTP-Status-ResetContent',
        'B01009206' => 'センタから受信したHTTP レスポンスコードが異常でした (206)HTTP-Status-PartialContent',
        'B01009300' => '(300)HTTP-Status-MultipleChoices',
        'B01009301' => '(301)HTTP-Status-MovePermanently',
        'B01009302' => '(302)HTTP-Status-MovedTemporarily',
        'B01007600' => 'サーバ閉塞中です',
        'B01009303' => '(303)HTTP-Status-SeeOther',
        'B01009304' => '(304)HTTP-Status-NotModified',
        'B01009305' => '(305)HTTP-Status-UseProxy',
        'B01009400' => '(400)HTTP-Status-BadRequest',
        'B01009401' => '(401)HTTP-Status-Unauthorized',
        'B01009402' => '(402)HTTP-Status-PaymentRequired',
        'B01009403' => '(403)HTTP-Status-Forbidden',
        'B01009404' => '(404)HTTP-Status-NotFound',
        'B01009405' => '(405)HTTP-Status-MethodNotAllowed',
        'B01009406' => '(406)HTTP-Status-NotAcceptable',
        'B01009407' => '(407)HTTP-Status-ProxyAuthenticationRequired',
        'B01009408' => '(408)HTTP-Status-RequestTimeout',
        'B01009409' => '(409)HTTP-Status-Conflict',
        'B01009410' => '(410)HTTP-Status-Gone',
        'B01009411' => '(411)HTTP-Status-LengthRequired',
        'B01009412' => '(412)HTTP-Status-PreconditionFailed',
        'B01009413' => '(413)HTTP-Status-RequestEntityTooLarge',
        'B01009414' => '(414)HTTP-Status-RequestURITooLong',
        'B01009415' => '(415)HTTP-Status-UnsupportedMediaType',
        'B01009500' => '(500)HTTP-Status-InternalServerError',
        'B01009501' => '(501)HTTP-Status-NotInplemented',
        'B01009502' => '(502)HTTP-Status-BadGateway',
        'B01009503' => '(503)HTTP-Status-ServiceUnavailable',
        'B01009504' => '(504)HTTP-Status-GatewayTimeout',
        'B01009505' => '(505)HTTP-Status-HTTPVersionNotSupported',
        'B01009600' => 'センタとの通信開始に失敗しました',
        'B01009601' => 'センタとの通信開始(名前解決)に失敗しました',
        'B01009602' => 'センタとの通信開始(IP Address解決)に失敗しました',
        'B01009603' => 'センタとの通信開始(connect)に失敗しました',
        'B01009604' => 'センタとの通信中にエラーが発生しました',
        'B01009605' => 'センタとの通信中(受信時)にエラーが発生しました',
        'B01009606' => 'センタとの通信中(送信時)にエラーが発生しました',
        'B01009607' => 'センタからの受信内容(HTTP Header部)が異常でした',
        'B01009610' => 'Proxyサーバとの通信開始に失敗しました',
        'B01009611' => 'Proxyサーバとの通信開始(名前解決)に失敗しました',
        'B01009612' => 'Proxyサーバとの通信開始(IP Address解決)に失敗しました',
        'B01009613' => 'Proxyサーバとの通信開始(connect) に失敗しました',
        'B01009614' => 'Proxyサーバとの通信中にエラーが発生しました',
        'B01009615' => 'Proxyサーバとの通信中(受信時)にエラーが発生しました',
        'B01009616' => 'Proxyサーバとの通信中(送信時)にエラーが発生しました',
        'B01009617' => 'Proxyサーバからの受信内容が異常でした',
        'B01009620' => 'SSL通信の初期化中にエラーが発生しました',
        'B01009621' => 'SSL通信の初期化中にエラーが発生しました',
        'B01009622' => 'SSL通信の初期化中にエラーが発生しました',
        'B01009623' => 'SSL通信の初期化中にエラーが発生しました',
        'B01009624' => 'SSL通信の初期化中にエラーが発生しました',
        'B01009625' => 'SSL通信の初期化中にエラーが発生しました',
        'B01009626' => 'SSL通信のハンドシェイク時にエラーが発生しました',
        'B01009627' => 'SSL通信のハンドシェイク時にエラーが発生しました',
        'B01009628' => 'SSL通信のハンドシェイク時にエラーが発生しました',
        'B01009629' => 'SSL通信の受信時にエラーが発生しました',
        'B01009630' => 'SSL通信の送信時にエラーが発生しました',
        'B01009700' => '定義ファイル読込み時にエラーが発生しました(socket定義ファイル)',
        'B01009701' => '定義ファイル読込み時にエラーが発生しました(通信定義ファイル)',
        'B01009702' => '定義ファイル読込み時にエラーが発生しました(ログ定義ファイル)',
        'B01009900' => '楽天Edy決済プログラムの内部エラーが発生しました',
        'B01009901' => 'URLの解釈中にエラーが発生しました',
        'B01009902' => '文字コードの変換中にエラーが発生しました',
        'B01009903' => 'URLのプロトコルエラー',
        'B01009904' => 'SIGTERMを受信しました',
        'B01009999' => 'XML文字列の解釈に失敗しました',
        'S01000002' => 'モバイルSuicaアプリのネット決済一覧から決済を行ってください。',
        'S01001001' => '決済依頼処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'S01001002' => '決済依頼処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'S01001006' => 'モバイルSuica決済は利用できません。',
        'S01001007' => 'モバイルSuicaの登録が終わってから、再度購入画面からやり直してください。',
        'S01001008' => 'モバイルSuica決済の決済依頼件数がオーバーしています。モバイルSuicaアプリのネット決済一覧確認してから、再度購入画面からやり直してください。',
        'S01001010' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'S01001012' => '登録データなし',
        'S01001015' => 'モバイルSuicaの登録状況を確認した後、再度購入画面からやり直してください。',
        'S01001016' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'S01001017' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'S01009901' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'S01009902' => '決済処理に失敗しました。申し訳ございませんが、しばらく時間をあけて購入画面からやり直してください。',
        'W0100W001' => 'データ種別不正',
        'W0100W002' => 'UserId/Passwordが存在しない',
        'W0100W003' => '収納処理企業コード/支払いコードが一致しない',
        'W0100W004' => '2DBC処理事業者番号/契約案件番号が一致しない',
        'W0100W005' => '入金処理企業コード/支払いコードが一致しない',
        'W0100W090' => 'キーデータ取得時エラー',
        'W0100W600' => '収納処理 項目チェック時エラー (不正な値)',
        'W0100W601' => '収納処理 項目チェック時エラー (支払いコード未設定)',
        'W0100W602' => '収納処理 項目チェック時エラー (支払いコード桁不足)',
        'W0100W603' => '収納処理 項目チェック時エラー (受付番号未設定)',
        'W0100W604' => '収納処理 項目チェック時エラー (受付番号桁不足)',
        'W0100W605' => '収納処理 項目チェック時エラー (企業コード未設定)',
        'W0100W606' => '収納処理 項目チェック時エラー (企業コード桁不足)',
        'W0100W607' => '収納処理 項目チェック時エラー (電話番号未設定)',
        'W0100W608' => '収納処理 項目チェック時エラー (漢字氏名未設定)',
        'W0100W609' => '収納処理 項目チェック時エラー (支払期限未設定)',
        'W0100W610' => '収納処理 項目チェック時エラー (支払期限 数字以外の値)',
        'W0100W611' => '収納処理 項目チェック時エラー (支払期限桁不正)',
        'W0100W612' => '収納処理 項目チェック時エラー (支払期限 日時の値不正)',
        'W0100W613' => '収納処理 項目チェック時エラー (支払期限 過去日付不正)',
        'W0100W614' => '収納処理 項目チェック時エラー (支払金額未設定)',
        'W0100W615' => '収納処理 項目チェック時エラー (支払金額値不正)',
        'W0100W616' => '収納処理 項目チェック時エラー (支払金額 ≦0)',
        'W0100W617' => '収納処理 項目チェック時エラー (支払金額 > 999999)',
        'W0100W640' => '収納情報重複エラー',
        'W0100W641' => '収納情報論理削除済みエラー',
        'W0100W670' => '収納DB OPEN時エラー',
        'W0100W671' => '収納DB READ時エラー',
        'W0100W672' => '収納DB INSERT時エラー',
        'W0100W680' => 'ケータイ決済番号用シーケンスNoを取得できない',
        'W0100W700' => '入金処理 項目チェックエラー(支払コード未設定)',
        'W0100W701' => '入金処理 項目チェックエラー(企業コード未設定)',
        'W0100W730' => '入金処理 未入金エラー',
        'W0100W731' => '入金処理 未送信データなしエラー',
        'W0100W740' => '入金処理 入金情報なしエラー',
        'W0100W741' => '入金処理 入金情報論理削除済みエラー',
        'W0100W770' => '入金処理 READ要求 入金DB OPEN時エラー',
        'W0100W771' => '入金処理 READ要求 入金DB READ時エラー',
        'W0100W772' => '入金処理 READ要求 入金DB UPDATE時エラー',
        'W0100W773' => '入金処理 SEARCH要求 入金DB OPEN時エラー',
        'W0100W774' => '入金処理 SEARCH要求 入金DB READ時エラー',
        'W0100W775' => '入金処理 SEARCH要求 入金DB UPDATE時エラー',
        'D01000001' => 'システムエラー(通信)/取引失敗を表示し、お問い合わせ下さい。',
        'D01000002' => 'システムエラー(通信)/取引失敗を表示し、お問い合わせ下さい。',
        'D01000099' => 'システムエラー(通信)/取引失敗を表示し、お問い合わせ下さい。',
        'F01001001' => 'ショップIDが指定されていません。',
        'F01001008' => 'ショップIDに半角英数字以外の文字が含まれているか、13 文字を超えています。',
        'F01002001' => 'ショップパスワードが指定されていません。',
        'F01002008' => 'ショップパスワードに半角英数字以外の文字が含まれているか、10文字を超えています。',
        'F01003002' => '指定されたIDのショップが存在しません。',
        'F01004001' => '照会IDが指定されていません。',
        'F01004002' => '指定されたIDの照会が存在しません。',
        'F01004005' => '照会IDが最大桁数を超えています。',
        'F01010001' => '住所(都道府県)が指定されていません。',
        'F01010012' => '住所(都道府県)が最大バイト数を超えています。',
        'F01011001' => '住所(市区町村)が指定されていません。',
        'F01011012' => '住所(市区町村)が最大バイト数を超えています。',
        'F01012001' => '住所(地名)が指定されていません。',
        'F01012012' => '住所(地名)が最大バイト数を超えています。',
        'F01013001' => '住所(番地・丁目)が指定されていません。',
        'F01013012' => '住所(番地・丁目)が最大バイト数を超えています。',
        'F01014012' => '住所(号室)が最大バイト数を超えています。',
        'F01015005' => '電話番号が最大文字数を超えています。',
        'F01015008' => '電話番号に数字、-以外の文字が指定されています。',
        'F01020008' => 'レコード区分にHD以外の値が指定されています。',
        'F01021008' => 'レコード区分にDT以外の値が指定されています。',
        'F01022008' => 'レコード区分にFT以外の値が指定されています。',
        'F01023008' => '項目数が誤っています。',
        'F01024008' => '項目数が誤っています。',
        'F01025008' => '項目数が誤っています。',
        'F01026008' => '項目数が誤っています。',
        'F01030001' => 'データレコード件数が指定されていません。',
        'F01030006' => 'データレコード件数に数字以外の文字が含まれています。',
        'F01030011' => 'データレコード件数が1~20 000の範囲ではありません。',
        'F01040010' => 'ヘッダレコードのレコード件数とデータレコードの件数が一致しません。',
        'F01050001' => '同一ショップ内で照会I/Fの照会実行中に照会データ登録が実行されました。',
        'F01060001' => '照会機能が利用停止になっています。',
        'F01070001' => '照会データが指定されていません。',
        'F01090999' => '照会実行中にエラーが発生しました。',
        'P01010001' => '内部エラーが発生しました。',
        'P01010002' => 'APIの認証に失敗しました。',
        'P01010003' => '通信パラメータが不正です。',
        'P01010004' => '通信パラメータが不正です。',
        'P01010005' => '指定オプションが不正です。',
        'P01010006' => '指定バージョンが不正です。',
        'P01010007' => 'API呼出し権限がありません。',
        'P01010008' => 'セキュリティーヘッダーが不正です。',
        'P01010009' => '預金口座状態が不正です。',
        'P01010010' => '通信パラメータが不正です。',
        'P01010011' => '指定取引は無効です。',
        'P01010101' => 'APIが一時的に使用不可になっています。',
        'P01010102' => '指定オプションが一時的に使用不可になっています。',
        'P01010103' => '指定オプションが一時的に使用不可になっています。',
        'P01010202' => '取引数が月間の最大数を超えています。',
        'P01010400' => '注文合計が不正です。',
        'P01010401' => '注文合計が不正です。',
        'P01010402' => '加盟店の設定が認証オプションを使用できない契約になっています。',
        'P01010404' => '戻りURLが不正です。',
        'P01010405' => 'キャンセル時のURLが不正です。',
        'P01010406' => '顧客IDが無効です。',
        'P01010407' => '顧客のメールアドレスが無効です。',
        'P01010408' => 'トークンが不正です。',
        'P01010409' => 'トークンが不正です。',
        'P01010410' => 'トークンが無効です。',
        'P01010411' => 'トークンの有効期限が切れました。',
        'P01010412' => '請求番号が重複しています。',
        'P01010413' => '商品の合計金額が不正です。',
        'P01010414' => '取引の金額上限を超えています。',
        'P01010415' => '指定取引は処理済みです。',
        'P01010416' => '再処理の最大試行回数を超えています。',
        'P01010417' => '支払方法が無効です。',
        'P01010418' => '通貨コードが不正です。',
        'P01010419' => '顧客IDが不正です。',
        'P01010420' => '支払オプションが不正です。',
        'P01010421' => 'トークンが無効です。',
        'P01010422' => '顧客の資金源が不正です。',
        'P01010424' => '配送先住所が無効です。',
        'P01010425' => '加盟店の設定がAPIを使用できない契約になっています。',
        'P01010426' => '商品の合計金額が無効です。',
        'P01010427' => '送料の合計が無効です。',
        'P01010428' => '手数料の合計が無効です。',
        'P01010429' => '税金の合計が無効です。',
        'P01010430' => '商品金額が不正です。',
        'P01010431' => '商品金額が無効です。',
        'P01010432' => '請求番号の桁数オーバーです。',
        'P01010433' => '商品説明の一部が省略されました。',
        'P01010434' => '自由項目の一部が省略されました。',
        'P01010435' => '承認が未処理です。',
        'P01010436' => 'ページスタイル名の桁数オーバーです。',
        'P01010437' => 'ヘッダーイメージURLの桁数オーバーです。',
        'P01010438' => 'ヘッダーイメージURLの桁数オーバーです。',
        'P01010439' => 'ヘッダーイメージURLの桁数オーバーです。',
        'P01010440' => 'ヘッダーイメージURLの桁数オーバーです。',
        'P01010441' => '通知先URLの桁数オーバーです。',
        'P01010442' => '識別コードの桁数オーバーです。',
        'P01010443' => '支払オプションが不正です。',
        'P01010444' => '通貨コードが不正です。',
        'P01010445' => '指定取引の処理を続行できません。',
        'P01010446' => '支払オプションが不正です。',
        'P01010457' => 'eBayのAPIの初期化に失敗しました。',
        'P01010458' => 'eBayのAPIでエラーが発生しました。',
        'P01010459' => 'eBayのAPIでエラーが発生しました。',
        'P01010460' => 'eBayの通信でエラーが発生しました。',
        'P01010461' => '商品数が不正です。',
        'P01010462' => '注文が存在しません。',
        'P01010463' => 'eBayの接続情報が不正です。',
        'P01010464' => '商品番号と取引IDが不整合です。',
        'P01010465' => 'eBayの接続情報が無効です。',
        'P01010467' => '商品番号が重複しています。',
        'P01010468' => '注文IDが重複しています。',
        'P01010469' => '指定オプションが一時的に使用不可になっています。',
        'P01010470' => '指定オプションが無効です。',
        'P01010471' => '戻りURLが不正です。',
        'P01010472' => 'キャンセル時のURLが不正です。',
        'P01010473' => '指定パラメータはサポート対象外です。',
        'P01010474' => '指定取引の処理を続行できません。',
        'P01010475' => '支払オプションが不正です。',
        'P01010476' => '無効なデータです。',
        'P01010477' => '無効なデータです。',
        'P01010478' => '無効なデータです。',
        'P01010479' => '無効なデータです。',
        'P01010480' => '無効なデータです。',
        'P01010481' => '支払オプションが不正です。',
        'P01010482' => '支払オプションが不正です。',
        'P01010537' => 'リスク管理設定により、該当取引が拒否されました。',
        'P01010538' => 'リスク管理設定により、該当取引が拒否されました。',
        'P01010539' => 'リスク管理設定により、支払いが拒否されました。',
        'P01010600' => '承認が取消されました。',
        'P01010601' => '承認期間の有効期限が切れました。',
        'P01010602' => '承認は既に完了しています。',
        'P01010603' => '顧客のアカウントに制限が掛けられています。',
        'P01010604' => '承認処理を続行できません。',
        'P01010605' => 'サポート対象外の通貨コードです。',
        'P01010606' => '取引が拒否されました。',
        'P01010607' => '承認と回収機能が使用できません。',
        'P01010608' => '顧客の資金源が不正です。',
        'P01010609' => '取引IDが無効です。',
        'P01010610' => '指定された金額の上限を超えています。',
        'P01010611' => '加盟店の設定が承認と回収機能を使用できない契約になっています。',
        'P01010612' => '決済可能な最大数に達しました。',
        'P01010613' => '通貨コードが不正です。',
        'P01010614' => '取消の承認番号が不正です。',
        'P01010615' => '再承認の指定方法が不正です。',
        'P01010616' => '承認に許される再承認の最大数に達しました。',
        'P01010617' => '保証期間中に再承認が呼出されました。',
        'P01010618' => '取引が取消、又は期限切れの状態です。',
        'P01010619' => '請求番号の桁数オーバーです。',
        'P01010620' => '注文の状態が取消、期限切れ、又は完了状態です。',
        'P01010621' => '注文の有効期限が切れました。',
        'P01010622' => '注文が取消されました。',
        'P01010623' => '注文に許される承認の最大数に達しました。',
        'P01010624' => '請求番号が重複しています。',
        'P01010625' => '取引の金額上限を超えています。',
        'P01010626' => '取引がリスクモデルによって拒否されました。',
        'P01010627' => 'サポート対象外のパラメータです。',
        'P01010628' => '指定取引の処理を続行できません。',
        'P01010629' => '再承認の指定方法が不正です。',
        'P01010630' => '商品金額が無効です。',
        'P01010725' => '配送先住所が不正です。',
        'P01010726' => '配送先住所が不正です。',
        'P01010727' => '配送先住所が不正です。',
        'P01010728' => '配送先住所が不正です。',
        'P01010729' => '配送先住所が不正です。',
        'P01010730' => '配送先住所が不正です。',
        'P01010731' => '配送先住所が不正です。',
        'P01010736' => '配送先住所の照会に失敗しました。',
        'P01010800' => '無効なデータです。',
        'P01011001' => '桁数オーバーです。',
        'P01011094' => '指定承認の取消、再承認、回収はできません。',
        'P01011547' => '指定オプションが一時的に使用不可になっています。',
        'P01011601' => '請求先住所が不正です。',
        'P01011602' => '請求先住所が不正です。',
        'P01011610' => '支払が保留されています。',
        'P01011611' => '取引が中止されました。',
        'P01011612' => '取引の処理を続行できません。',
        'P01011801' => '無効なデータです。',
        'P01011802' => '無効なデータです。',
        'P01011803' => '無効なデータです。',
        'P01011804' => '無効なデータです。',
        'P01011805' => '無効なデータです。',
        'P01011806' => '無効なデータです。',
        'P01011807' => '無効なデータです。',
        'P01011810' => '無効なデータです。',
        'P01011811' => '無効なデータです。',
        'P01011812' => '無効なデータです。',
        'P01011813' => '無効なデータです。',
        'P01011814' => '無効なデータです。',
        'P01011815' => '無効なデータです。',
        'P01011820' => '無効なデータです。',
        'P01011821' => '無効なオプションです。',
        'P01011822' => 'オプションの指定に誤りがあります。',
        'P01011823' => 'オプションの指定に誤りがあります。',
        'P01011824' => 'オプションの指定に誤りがあります。',
        'P01011825' => 'オプションの指定に誤りがあります。',
        'P01011826' => '送料の合計が無効です。',
        'P01011827' => 'オプションの指定に誤りがあります。',
        'P01011828' => 'オプションの指定に誤りがあります。',
        'P01011829' => 'オプションの指定に誤りがあります。',
        'P01011830' => 'オプションの指定に誤りがあります。',
        'P01011831' => 'URLの桁数オーバーです。',
        'P01011832' => '注文合計が不正です。',
        'P01012109' => '無効なオプションです。',
        'P01012124' => '無効なオプションです。',
        'P01012200' => '顧客IDが不正です。',
        'P01012201' => 'オプションの指定に誤りがあります。',
        'P01012202' => 'オプションの指定に誤りがあります。',
        'P01012203' => '保留状態の為、支払が失敗しました。',
        'P01012204' => 'エラーが発生した為、取引は戻されました。',
        'P01012205' => 'オプションの指定に誤りがあります。',
        'P01012206' => 'オプションの指定に誤りがあります。',
        'P01012207' => 'オプションの指定に誤りがあります。',
        'P01012208' => '商品金額が一致しません。',
        'P01020000' => '支払状況が不正です。 (None)',
        'P01020001' => '支払状況が不正です。 (Canceled-Reversal)',
        'P01020003' => '支払状況が不正です。 (Denied)',
        'P01020004' => '支払状況が不正です。 (Expired)',
        'P01020005' => '支払状況が不正です。 (Failed)',
        'P01020006' => '支払状況が不正です。 (In-Progress)',
        'P01020007' => '支払状況が不正です。 (Partially-Refunded)',
        'P01020008' => '支払状況が不正です。 (Pending)',
        'P01020009' => '支払状況が不正です。 (Refunded)',
        'P01020010' => '支払状況が不正です。 (Reversed)',
        'P01020011' => '支払状況が不正です。 (Processed)',
        'P01020012' => '支払状況が不正です。 (Voided)',
        'P01029999' => '支払状況が不正です。',
        'P01081000' => '無効なパラメータです。',
        'P01081001' => '無効なパラメータです。',
        'P01081002' => '指定メソッドはサポートされていません。',
        'P01081003' => 'メソッドが指定されていません。',
        'P01081004' => 'リクエストパラメータが指定されていません。',
        'P01081100' => 'パラメータが指定されていません。 (Amt)',
        'P01081101' => 'パラメータが指定されていません。 (MaxAmt)',
        'P01081102' => 'パラメータが指定されていません。 (ReturnURL)',
        'P01081103' => 'パラメータが指定されていません。 (NotifyURL)',
        'P01081104' => 'パラメータが指定されていません。 (CancelURL)',
        'P01081105' => 'パラメータが指定されていません。 (ShipToStreet)',
        'P01081106' => 'パラメータが指定されていません。 (ShipToStreet2)',
        'P01081107' => 'パラメータが指定されていません。 (ShipToCity)',
        'P01081108' => 'パラメータが指定されていません。 (ShipToState)',
        'P01081109' => 'パラメータが指定されていません。 (ShipToZip)',
        'P01081110' => 'パラメータが指定されていません。 (Country)',
        'P01081111' => 'パラメータが指定されていません。 (ReqConfirmShipping)',
        'P01081112' => 'パラメータが指定されていません。 (NoShipping)',
        'P01081113' => 'パラメータが指定されていません。 (AddrOverride)',
        'P01081114' => 'パラメータが指定されていません。 (LocaleCode)',
        'P01081115' => 'パラメータが指定されていません。 (PaymentAction)',
        'P01081116' => 'パラメータが指定されていません。 (Email)',
        'P01081117' => 'パラメータが指定されていません。 (Token)',
        'P01081118' => 'パラメータが指定されていません。 (PayerID)',
        'P01081119' => 'パラメータが指定されていません。 (ItemAmt)',
        'P01081120' => 'パラメータが指定されていません。 (ShippingAmt)',
        'P01081121' => 'パラメータが指定されていません。 (HandlingAmt)',
        'P01081122' => 'パラメータが指定されていません。 (TaxAmt)',
        'P01081123' => 'パラメータが指定されていません。 (IPAddress)',
        'P01081124' => 'パラメータが指定されていません。 (ShipToName)',
        'P01081125' => 'パラメータが指定されていません。 (L_Amt)',
        'P01081126' => 'パラメータが指定されていません。 (Amt)',
        'P01081127' => 'パラメータが指定されていません。 (L_TaxAmt)',
        'P01081128' => 'パラメータが指定されていません。 (AuthorizationID)',
        'P01081129' => 'パラメータが指定されていません。 (CompleteType)',
        'P01081130' => 'パラメータが指定されていません。 (CurrencyCode)',
        'P01081131' => 'パラメータが指定されていません。 (TransactionID)',
        'P01081132' => 'パラメータが指定されていません。 (TransactionEntity)',
        'P01081133' => 'パラメータが指定されていません。 (Acct)',
        'P01081134' => 'パラメータが指定されていません。 (ExpDate)',
        'P01081135' => 'パラメータが指定されていません。 (FirstName)',
        'P01081136' => 'パラメータが指定されていません。 (LastName)',
        'P01081137' => 'パラメータが指定されていません。 (Street)',
        'P01081138' => 'パラメータが指定されていません。 (Street2)',
        'P01081139' => 'パラメータが指定されていません。 (City)',
        'P01081140' => 'パラメータが指定されていません。 (State)',
        'P01081141' => 'パラメータが指定されていません。 (Zip)',
        'P01081142' => 'パラメータが指定されていません。 (CountryCode)',
        'P01081143' => 'パラメータが指定されていません。 (RefundType)',
        'P01081144' => 'パラメータが指定されていません。 (StartDate)',
        'P01081145' => 'パラメータが指定されていません。 (EndDate)',
        'P01081146' => 'パラメータが指定されていません。 (MPID)',
        'P01081147' => 'パラメータが指定されていません。 (CreditCardType)',
        'P01081148' => 'パラメータが指定されていません。 (User)',
        'P01081149' => 'パラメータが指定されていません。 (Pwd)',
        'P01081150' => 'パラメータが指定されていません。 (Version)',
        'P01081200' => '無効なパラメータです。 (Amt)',
        'P01081201' => '無効なパラメータです。 (MaxAmt)',
        'P01081203' => '無効なパラメータです。 (NotifyURL)',
        'P01081205' => '無効なパラメータです。 (ShipToStreet)',
        'P01081206' => '無効なパラメータです。 (ShipToStreet2)',
        'P01081207' => '無効なパラメータです。 (ShipToCity)',
        'P01081208' => '無効なパラメータです。 (ShipToState)',
        'P01081209' => '無効なパラメータです。 (ShipToZip)',
        'P01081210' => '無効なパラメータです。 (Country)',
        'P01081211' => '無効なパラメータです。 (ReqConfirmShipping)',
        'P01081212' => '無効なパラメータです。 (Noshipping)',
        'P01081213' => '無効なパラメータです。 (AddrOverride)',
        'P01081214' => '無効なパラメータです。 (LocaleCode)',
        'P01081215' => '無効なパラメータです。 (PaymentAction)',
        'P01081219' => '無効なパラメータです。 (ItemAmt)',
        'P01081220' => '無効なパラメータです。 (ShippingAmt)',
        'P01081221' => '無効なパラメータです。 (HandlingTotal Amt)',
        'P01081222' => '無効なパラメータです。 (TaxAmt)',
        'P01081223' => '無効なパラメータです。 (IPAddress)',
        'P01081224' => '無効なパラメータです。 (ShipToName)',
        'P01081225' => '無効なパラメータです。 (L_Amt)',
        'P01081226' => '無効なパラメータです。 (Amt)',
        'P01081227' => '無効なパラメータです。 (L_TaxAmt)',
        'P01081229' => '無効なパラメータです。 (CompleteType)',
        'P01081230' => '無効なパラメータです。 (CurrencyCode)',
        'P01081232' => '無効なパラメータです。 (TransactionEntity)',
        'P01081234' => '無効なパラメータです。 (ExpDate)',
        'P01081235' => '無効なパラメータです。 (FirstName)',
        'P01081236' => '無効なパラメータです。 (LastName)',
        'P01081237' => '無効なパラメータです。 (Street)',
        'P01081238' => '無効なパラメータです。 (Street2)',
        'P01081239' => '無効なパラメータです。 (City)',
        'P01081243' => '無効なパラメータです。 (RefundType)',
        'P01081244' => '無効なパラメータです。 (StartDate)',
        'P01081245' => '無効なパラメータです。 (EndDate)',
        'P01081247' => '無効なパラメータです。 (CreditCardType)',
        'P01081248' => '無効なパラメータです。 (Username)',
        'P01081249' => '無効なパラメータです。 (Password)',
        'P01081250' => '無効なパラメータです。 (Version)',
        'P01081251' => '内部エラーが発生しました。',
        'P02000001' => 'PayPalセンターとの通信に失敗しました。',
        'P02000002' => 'PayPalセンターとの通信に失敗しました。',
        'P03000003' => 'PayPalの支払操作をユーザがキャンセルしました。',
        'N01001001' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N01001002' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N01001003' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N01001004' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N01001005' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N01001006' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N01001007' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N01001008' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N01001009' => '実行中にエラーが発生しました。処理は開始されませんでした。',
        'N10000001' => '該当する取引がありません。',
        'N0C030C01' => 'しばらくしてからやり直してください。',
        'N0C030C03' => 'しばらくしてからやり直してください。',
        'N0C030C12' => 'しばらくしてからやり直してください。',
        'N0C030C13' => 'しばらくご利用になれません。',
        'N0C030C14' => 'しばらくしてからやり直してください。',
        'N0C030C15' => 'しばらくしてからやり直してください。',
        'N0C030C16' => 'しばらくしてからやり直してください。',
        'N0C030C33' => 'しばらくしてからやり直してください。',
        'N0C030C34' => 'しばらくしてからやり直してください。',
        'N0C030C49' => 'しばらくしてからやり直してください。',
        'N0C030C50' => 'しばらくしてからやり直してください。',
        'N0C030C51' => 'もう一度やり直してください。',
        'N0C030C53' => 'しばらくしてからやり直してください。',
        'N0C030C54' => 'しばらくしてからやり直してください。',
        'N0C030C55' => 'しばらくしてからやり直してください。',
        'N0C030C56' => 'しばらくしてからやり直してください。',
        'N0C030C57' => 'しばらくしてからやり直してください。',
        'N0C030C58' => 'しばらくしてからやり直してください。',
        'N0C030C60' => 'しばらくしてからやり直してください。',
        'N0C030G03' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G12' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G30' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G54' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G55' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G56' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G60' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G61' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G65' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G67' => 'しばらくしてからやり直してください。',
        'N0C030G83' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G85' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G95' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G96' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G97' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0C030G98' => 'もう一度やり直してください。',
        'N0C030G99' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N010007' => 'お客様の携帯電話ではサービスをご利用いただけません。',
        'N0N010008' => 'お客様の携帯電話ではサービスをご利用いただけません。',
        'N0N010009' => 'お客様の携帯電話ではサービスをご利用いただけません。',
        'N0N010013' => 'しばらくご利用になれません。店舗までお問合せください。',
        'N0N010024' => 'しばらくご利用になれません。店舗までお問合せください。',
        'N0N010032' => 'しばらくご利用になれません。店舗までお問合せください。',
        'N0N020014' => 'エラーが発生しました。店舗までお問合せください。',
        'N0N020017' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N020018' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N020019' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N020020' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N020021' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N020022' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N020023' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N030038' => '在このカードはお取扱できません。カード会社にお問合せください。',
        'N0N040014' => 'エラーが発生しました。店舗までお問合せください。',
        'N0K040026' => 'もう一度やり直してください。',
        'N0K040027' => 'エラーが発生しました。店舗までお問合せください。',
        'N0K040028' => 'エラーが発生しました。店舗までお問合せください。',
        'N0K040029' => 'エラーが発生しました。店舗までお問合せください。',
        'N0N040031' => 'しばらくご利用になれません。店舗までお問合せください。',
        'N0K040037' => 'しばらくご利用になれません。店舗までお問合せください。',
        'N0T000001' => 'もう一度やり直してください。',
        'N0T000002' => 'ご利用可能なカードが設定されていないため、お支払を継続できません。なお、iDでお支払いただくには事前にカードを設定いただく必要がありますj。',
        'N0T000003' => 'ご利用可能なカードが設定されていないため、お支払を継続できません。なお、iDでお支払いただくには事前にカードを設定いただく必要がありますj。',
        'N0T000004' => 'パスワード入力間違いが規定回数を超えたため、iDでのお支払を継続できません。なお、iDを再度ご利用いただくには、iDアプリを再度起動しリセットを実行した後、カードを設定しなおしていただく必要があります。',
        'N0T000005' => 'ICカードロックを設定されている場合は、一旦iDアプリを終了し、ICカードロックを解除してから再度iDアプリを起動してください。ICカードロックを解除してもご利用いただけない場合はカード会社へお問合せください。',
        'N0T000006' => 'エラーが発生しました。店舗までお問合せください。',
        'N0T000007' => 'もう一度やり直してください。',
        'N0T000008' => 'もう一度やり直してください。',
        'N0T000009' => '現在このカードはお取扱できません。カード会社にお問合せください。',
        'N0T000010' => 'もう一度やり直してください。',
        'WM1000001' => '【決済要求】後続決済センターとの通信パラメータでエラーが発生しました。(メッセージダイジェスト)',
        'WM1000002' => '【決済要求】後続決済センターで許可されていない接続です。',
        'WM1000003' => '【決済要求】後続決済センターで決済モジュールの実行に失敗しました。',
        'WM1000004' => '【決済要求】後続決済センターとの通信パラメータでエラーが発生しました。(受信パラメータ)',
        'WM1000005' => '【決済要求】後続決済センターとの通信に失敗しました。',
        'WM1000006' => '【決済結果受信】後続決済センターとの通信パラメータでエラーが発生しました。(受信パラメータ)',
        'WM1000007' => '【決済結果受信】後続決済センターで二重入金が発生しました。',
        'WM1000008' => '【決済結果受信】内部エラーが発生しました。(遷移)',
        'WM1000009' => '【ユーザーキャンセル受信】入金済みの取引に対し、ユーザーの支払操作がキャンセルされた通知を受信しました。',
        'WM1000010' => '【ユーザーキャンセル受信】内部エラーが発生しました。(遷移)',
        'AU1000001' => '【決済要求】後続決済センターとの通信に失敗しました。',
        'AU1000002' => '【決済要求】後続決済センターとの通信パラメータでエラーが発生しました。(受信パラメータ)',
        'AU1000003' => '【決済要求】後続決済センターで障害取消が実施されました。',
        'AU1000004' => '【決済要求】auかんたんOpenID連携解除でエラーが発生しました。',
        'AU1000005' => '【操作キャンセル】auかんたん決済でお客様がお支払をキャンセルしました。',
        'AMPL40505' => '有効なクレジットカードではありません。',
        'AMPL40506' => '後続センターにてシステムエラーが発生しました。',
        'AMPL90000' => '後続センターにてシステムエラーが発生しました。',
        'AU1000001' => '【決済要求】後続決済センターとの通信に失敗しました。',
        'AU1000002' => '【決済要求】後続決済センターとの通信パラメータでエラーが発生しました。(受信パラメータ)',
        'AU1000003' => '【決済要求】後続決済センターで処理取消が実施されました。',
        'AU1000004' => '【決済要求】auかんたんOpenID連携解除でエラーが発生しました。',
        'AU1000005' => '【操作キャンセル】auかんたん決済でお客様がお支払をキャンセルしました。',
        'AU1000006' => '【決済要求】後続決済センターで処理取消を実行し、取消に失敗しました。',
        'AMPL40505' => '有効なクレジットカードではありません。',
        'AMPL40506' => '後続センターにてシステムエラーが発生しました。',
        'AMPL90000' => '後続センターにてシステムエラーが発生しました。',
        'AMPL40010' => '後続センターにて処理取消エラーが発生し、有効な初回課金が残りました。',
        'DC1000001' => '【決済要求】後続決済センターで確定処理が失敗しました。',
        'DC1000002' => '【取消要求】後続決済センターで取消処理が失敗しました。',
        'DC1000003' => '【決済中止】お客様がドコモケータイ払いを中止しました。',
        'DC1000004' => '【決済失敗】ドコモケータイ払いが失敗しました。',
        'DC1000005' => '【決済中止】お客様がドコモ継続課金の申込を中止しました。',
        'DC1000006' => '【変更中止】お客様がドコモ継続課金の変更を中止しました。',
        'DC1000007' => '【終了中止】お客様がドコモ継続課金の終了を中止しました。',
        'DC1000008' => '【決済失敗】ドコモ継続課金の申込が失敗しました。',
        'DC1000009' => '【変更失敗】ドコモ継続課金の変更が失敗しました。',
        'DC1000010' => '【終了失敗】ドコモ継続課金の終了が失敗しました。',
        'SB1000001' => '【決済要求】後続決済センターで確定処理が失敗しました。',
        'SB1000002' => '【取消要求】後続決済センターで取消処理が失敗しました。',
        'SB1000003' => '【決済中止】お客様がソフトバンクまとめて支払い(B)を中止しました。',
        'SB1000004' => '【決済失敗】ソフトバンクまとめて支払い(B)が失敗しました。',
        'SB1000005' => '【決済要求】後続決済センターとの通信に失敗しました。',
        'J01000001' => '【決済失敗】後続決済センターで決済が失敗しました。',
        'J01000002' => '【決済中止】お客様がじぶん銀行決済を中止しました。',
        'J01000003' => '【決済失敗】後続決済センターで名義不一致により決済が失敗しました。',
        'J01100001' => '【決済失敗】後続決済センターで原因不明エラーにより決済が失敗しました。',
        'J01100002' => '【決済失敗】想定外のエラーコードが返却されました。',
        'JP1000001' => '残高不足',
        'JP1000002' => '入金金額オーバー',
        'JP1000003' => '未アクティベート',
        'JP1000004' => '利用開始前',
        'JP1000005' => '認証番号エラー',
        'JP1000006' => '無効カード',
        'JP1000007' => '会員番号エラー',
        'JP1000008' => '有効期限エラー',
        'JP1000009' => 'サービス対象外カード:券種コードエラー',
        'JP1000010' => 'サービス対象外カード:アライアンス期間外',
        'JP1000011' => 'サービス対象外カード:アライアンス無効',
        'JP1000012' => 'サービス対象外カード:アライアンス許可取引外',
        'JP1000013' => 'サービス対象外カード:その他アライアンス取引エラー',
        'JP1000014' => 'サービス対象外カード:その他アライアンス取引エラー',
        'JP1000015' => 'サービス対象外カード:JCBセンター会社未登録エラー',
        'JP1000016' => 'サービス対象外カード:JCBPOS支店チェックエラー',
        'JP1000017' => 'サービス対象外カード:JCB加盟店有効エラー',
        'JP1000018' => 'サービス対象外カード:JETS端末エラー',
        'JP1000019' => '取消対象取引エラー:取消対象取引なし',
        'JP1000020' => '取消対象取引エラー:既に取消済み',
        'JP1000021' => '取消対象取引エラー:取消対象取引が直近ではない',
        'JP1000022' => '取消対象取引エラー:取消可能時間超過',
        'JP1000023' => '取消対象取引エラー:その他取消対象取引エラー',
        'JP1000024' => '取消対象取引エラー:その他取消対象取引エラー',
        'JP1000025' => '取消対象取引エラー:その他取消対象取引エラー',
        'JP1000026' => '該当自社対象業務エラー:システムエラー',
        'JP1000027' => '該当自社対象業務エラー:システムエラー',
        'JP1000028' => '該当自社対象業務エラー:システムエラー',
        'JP1000029' => '該当自社対象業務エラー:システムエラー',
        'JP1000030' => '該当自社対象業務エラー:システムエラー',
        'JP1000031' => '該当自社対象業務エラー:システムエラー',
        'JP1000032' => '該当自社対象業務エラー:システムエラー',
        'JP1000033' => '接続要求自社受付拒否:発行会社コードエラー',
        'JP1000034' => '接続要求自社受付拒否:発行会社無効',
        'JP1000035' => '接続要求自社受付拒否:有効期限区分が不正',
        'JP1000036' => '接続要求自社受付拒否:リクエストバリデーションエラー',
        'JP1000037' => '接続要求自社受付拒否:認証キー不一致',
        'JP1000038' => '接続要求自社受付拒否:認証キーが有効時間外',
        'JP1000039' => '接続要求自社受付拒否:IPアドレスエラー',
        'JP1000040' => '接続要求自社受付拒否:その他接続要求エラー',
        'JP1000041' => '接続要求自社受付拒否:その他接続要求エラー',
        'JP1000042' => '接続要求自社受付拒否:その他接続要求エラー',
        'JP1000043' => '接続要求自社受付拒否:その他接続要求エラー',
        'JP1000044' => '障害取消対象取引エラー:障害取消対象が直近ではない',
        'JP1000045' => '障害取消対象取引エラー:障害取消可能時間超過',
        'JP1000046' => '障害取消対象取引エラー:その他障害取消対象取引エラー',
        'JP1000047' => '障害取消対象取引エラー:その他障害取消対象取引エラー',
        'JP1000048' => '予期しないエラー',
        'JP1000049' => 'JCBプリカ決済センターとの通信失敗',
        'JP1000050' => 'JCBプリカ決済センターからの戻り値不正',
        'FL1001001' => '会員認証中断',
        'FL1001002' => '会員認証ユーザID形式不正中断',
        'FL1001003' => '会員認証パスワード形式不正中断',
        'FL1001004' => '会員認証リトライ中断',
        'FL1001005' => '会員認証異常',
        'FL1001006' => 'コンテンツ購入不可状態による中断',
        'FL1001007' => '利用者利用停止異常',
        'FL1001008' => '回線認証異常(認証)',
        'FL1001009' => 'SSO認証不正',
        'FL1001998' => '事業者間通信タイムアウト異常',
        'FL1001999' => '事業者による処理中断',
        'FL1002001' => '会員認証中断',
        'FL1002002' => '会員認証ユーザID不形式不正中断',
        'FL1002003' => '会員認証パスワード形式不正中断',
        'FL1002004' => '会員認証リトライ中断',
        'FL1002005' => '会員認証異常',
        'FL1002006' => 'コンテンツ購入不可状態による中断',
        'FL1002007' => '利用者利用停止異常',
        'FL1002008' => '回線認証異常(認証)',
        'FL1002009' => '回線認証異常(課金依頼)',
        'FL1002010' => '課金依頼情報中断',
        'FL1002011' => '課金依頼確認中断',
        'FL1002012' => '上限値超過警告中断',
        'FL1002013' => '上限値超過異常',
        'FL1002014' => '購入確認メール送信中断【NTT東日本単独】',
        'FL1002016' => '購入確認メールチェック不正中断【NTT東日本単独】',
        'FL1002017' => '購入確認メールチェック不正中断【NTT東日本単独】',
        'FL1002018' => '購入確認メールチェック不正中断【NTT東日本単独】',
        'FL1002019' => '購入確認メールチェック不正中断【NTT東日本単独】',
        'FL1002020' => '課金依頼処理の重複検知警告で中断',
        'FL1002021' => '購入確認メールからの課金依頼キャンセル中断【NTT東日本単独】',
        'FL1002023' => '購入確認メール送付',
        'FL1002024' => '処理順の不正エラー',
        'FL1002025' => 'SSO認証不正',
        'FL1002026' => '上限値警告超過',
        'FL1002027' => 'ユーザー未承諾',
        'FL1002996' => 'NGN情報料回収代行システムエラーによる中断',
        'FL1002997' => 'ログイン状態異常',
        'FL1002998' => '事業者間通信タイムアウト異常',
        'FL1002999' => '事業者による処理中断',
        'FL1003001' => '会員認証中断',
        'FL1003002' => '会員認証コンテンツ購入ID不形式不正中断',
        'FL1003003' => '会員認証パスワード形式不正中断',
        'FL1003004' => '会員認証リトライ中断',
        'FL1003005' => '会員認証異常',
        'FL1003006' => 'コンテンツ購入不可状態による中断',
        'FL1003007' => '利用者利用停止異常',
        'FL1003008' => '回線認証異常(認証)',
        'FL1003009' => '購入確認メール送付',
        'FL1003011' => '利用解除確認中断',
        'FL1003014' => '解約確認メール送信中断【NTT東日本単独】',
        'FL1003016' => '解約確認メールチェック不正中断【NTT東日本単独】',
        'FL1003017' => '解約確認メールチェック不正中断【NTT東日本単独】',
        'FL1003019' => '解約確認メールチェック不正中断【NTT東日本単独】',
        'FL1003020' => '利用解除処理の重複検知警告で中断',
        'FL1003021' => '処理順の不正エラー',
        'FL1003022' => 'SSO認証不正',
        'FL1003996' => 'NGN情報料回収代行システムエラーによる中断',
        'FL1003997' => 'ログイン状態異常',
        'FL1003998' => '事業者間通信タイムアウト異常',
        'FL1003999' => '事業者による処理中断',
        'FL1004001' => 'XMLフォーマットチェックエラー',
        'FL1004002' => 'API認証エラー',
        'FL1004101' => 'IF規定違反',
        'FL1004102' => 'IF規定違反',
        'FL1004103' => 'IF規定違反',
        'FL1004104' => 'IF規定違反',
        'FL1004105' => 'IF規定違反',
        'FL1004106' => 'IF規定違反',
        'FL1004107' => 'IF規定違反',
        'FL1004108' => 'IF規定違反',
        'FL1004109' => 'IF規定違反',
        'FL1004110' => 'IF規定違反',
        'FL1004201' => '処理依頼通番重複エラー',
        'FL1004202' => '処理依頼通番連携順序エラー',
        'FL1004203' => 'コンテンツ非存在エラー',
        'FL1004204' => '仮実売上コンテンツエラー',
        'FL1004205' => '事業者ID不一致エラー',
        'FL1004206' => '申込日時エラー',
        'FL1004207' => 'オーダ整合性エラー',
        'FL1004208' => 'コンテンツ購入ID存在チェックエラー',
        'FL1004209' => 'ユーザ状態コードチェックエラー',
        'FL1004210' => 'ユーザ状態コードチェックエラー',
        'FL1004211' => '上限値超過エラー',
        'FL1004901' => '回収代行サーバ定期メンテナンス中',
        'FL1004902' => '回収代行サーバ緊急メンテナンス中',
        'FL1004903' => '回収代行サーバスレッド取得エラー',
        'FL1004904' => '回収代行サーバ処理時間タイムアウト',
        'FL1004905' => '回収代行サーバDBエラー',
        'FL1004906' => '回収代行サーバ内部タイムアウト',
        'FL1004999' => '回収代行サーバその他異常',
        'FL1009001' => 'システムメンテナンス',
        'FL1009002' => '送信パラメータ不正',
        'FL1009901' => 'ログアウトエラー',
        'FL1009999' => 'その他エラー',
        'NC1000001' => '取引IDチェックエラー',
        'NC1000002' => '取引の存在チェックエラー',
        'NC1000003' => 'トークンチェックエラー',
        'NC1000004' => '￼状態遷移チェックエラー (入金済み)',
        'NC1000005' => '状態遷移チェックエラー (期限切れ)',
        'NC1000006' => '状態遷移チェックエラー (不正な遷移)',
        'NC1000007' => '有効期限切れ',
        'NC1000008' => '状態遷移エラー',
        'NC1000009' => '決済NG',
        'NC1000010' => 'MD5チェックエラー',
        'NC1000011' => '決済情報取得エラー',
        'NC1000012' => '決済結果パラメータチェックエラー(決済結果に対しての決済日時の有無)',
        'NC1000013' => '購入情報内容チェックエラー時URLへ遷移',
        'NC2000001' => '決済の不整合(決済が失敗した取引に対しての結果通知)',
        'NC2000002' => 'ショップ特定不可',
        'NC2000003' => 'SCD未設定',
        'NC2000004' => 'NET CASH契約タイプ未設定',
        'NC2000005' => '利用停止チェックエラー',
        'NC2000006' => '紐づく取引が存在しない(購入情報出力)',
        'NC2000007' => '紐づく取引が存在しない(決済結果通知)',
    ];

    public function get_message($no){
        if( array_key_exists($no, $this->map)){
            return $this->map[$no];
        }

        return 'エラーコード表をご確認ください。';
    }
}

class GMO_Exception_api extends APP_Exception
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $this->notified = TRUE;

        parent::__construct($message, $code, $previous);
    }
}