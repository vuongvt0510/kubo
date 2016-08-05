<?php
require_once 'com/gmo_pg/client/output/EntryExecTranFletsOutput.php';
require_once 'com/gmo_pg/client/tran/EntryTranFlets.php';
require_once 'com/gmo_pg/client/tran/ExecTranFlets.php';
/**
 * <b>フレッツ登録・決済一括実行　実行クラス</b>
 * 
 * @package com.gmo_pg.client
 * @subpackage tran
 * @see tranPackageInfo.php
 * @author GMO PaymentGateway
 */
class EntryExecTranFlets {
	/**
	 * @var Log ログ
	 */
	var $log;

	/**
	 * @var GPayException 例外
	 */
	var $exception;

	/**
	 * コンストラクタ
	 */
	function EntryExecTranFlets() {
		$this->__construct();
	}

	/**
	 * コンストラクタ
	 */
	function __construct() {
		$this->log = new Log(get_class($this));
	}

	/**
	 * 例外の発生を判定する
	 *
	 * @param mixed $target    判定対象
	 */
	function errorTrap(&$target) {
		if (is_null($target->exception)) {
			return false;
		}
		$this->exception = $target->exception;
		return true;
	}

	/**
	 * 例外の発生を判定する
	 *
	 * @return  boolean 判定結果(true=エラーアリ)
	 */
	function isExceptionOccured() {
		return false == is_null($this->exception);
	}

	/**
	 * 例外を返す
	 *
	 * @return  GPayException 例外
	 */
	function &getException() {
		return $this->exception;
	}

	/**
	 * フレッツ登録・決済を実行する
	 *
	 * @param EntryExecTranFletsInput $input    フレッツ登録・決済入力パラメータ
	 * @return  EntryExecTranFletsOutput フレッツ登録・決済出力パラメータ
	 * @exception GPayException
	 */
	function exec(&$input) {
		// フレッツ取引登録入力パラメータを取得
		$entryTranFletsInput =& $input->getEntryTranFletsInput();
		// フレッツ決済実行入力パラメータを取得
		$execTranFletsInput =& $input->getExecTranFletsInput();

		// フレッツ登録・決済出力パラメータを生成
		$output = new EntryExecTranFletsOutput();

		// 取引ID、取引パスワードを取得
		$accessId = $execTranFletsInput->getAccessId();
		$accessPass = $execTranFletsInput->getAccessPass();

		// 取引ID、取引パスワードが設定されていないとき
		if (is_null($accessId) || 0 == strlen($accessId) || is_null($accessPass)) {
			// フレッツ取引登録を実行
			$this->log->debug("フレッツ取引登録実行");
			$entryTranFlets = new EntryTranFlets();
			$entryTranFletsOutput = $entryTranFlets->exec($entryTranFletsInput);

			if ($this->errorTrap($entryTranFlets)) {
				return $output;
			}

			// 取引ID、取引パスワードを決済実行用のパラメータに設定
			$accessId = $entryTranFletsOutput->getAccessId();
			$accessPass = $entryTranFletsOutput->getAccessPass();
			$execTranFletsInput->setAccessId($accessId);
			$execTranFletsInput->setAccessPass($accessPass);

			$output->setEntryTranFletsOutput($entryTranFletsOutput);
		}

		$this->log->debug("取引ID : [$accessId]  取引パスワード : [$accessPass]");

		// 取引登録でエラーが起きたとき決済を実行せずに戻る
		if ($output->isEntryErrorOccurred()) {
			$this->log->debug("<<<取引登録失敗>>>");
			return $output;
		}

		// 決済実行
		$this->log->debug("決済実行");
		$execTranFlets = new ExecTranFlets();
		$execTranFletsOutput = $execTranFlets->exec($execTranFletsInput);

		$output->setExecTranFletsOutput($execTranFletsOutput);

		$this->errorTrap($execTranFlets);

		return $output;
	}
	

}
?>
