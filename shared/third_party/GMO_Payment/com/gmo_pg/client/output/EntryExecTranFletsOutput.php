<?php
require_once 'com/gmo_pg/client/output/EntryTranFletsOutput.php';
require_once 'com/gmo_pg/client/output/ExecTranFletsOutput.php';
/**
 * <b>フレッツ登録・決済一括実行  出力パラメータクラス</b>
 * 
 * @package com.gmo_pg.client
 * @subpackage output
 * @see outputPackageInfo.php
 * @author GMO PaymentGateway
 */
class EntryExecTranFletsOutput {

	/**
	 * @var EntryTranFletsOutput フレッツ登録出力パラメータ
	 */
	var $entryTranFletsOutput;/*@var $entryTranFletsOutput EntryTranFletsOutput */

	/**
	 * @var ExecTranFletsOutput フレッツ実行出力パラメータ
	 */
	var $execTranFletsOutput;/*@var $execTranFletsOutput ExecTranFletsOutput */

	/**
	 * コンストラクタ
	 *
	 * @param IgnoreCaseMap $params    入力パラメータ
	 */
	function EntryExecTranFletsOutput($params = null) {
		$this->__construct($params);
	}

	/**
	 * コンストラクタ
	 *
	 * @param IgnoreCaseMap $params    入力パラメータ
	 */
	function __construct($params = null) {
		$this->entryTranFletsOutput = new EntryTranFletsOutput($params);
		$this->execTranFletsOutput = new ExecTranFletsOutput($params);
	}

	/**
	 * フレッツ登録出力パラメータ取得
	 * @return EntryTranFletsOutput フレッツ登録出力パラメータ
	 */
	function &getEntryTranFletsOutput() {
		return $this->entryTranFletsOutput;
	}

	/**
	 * フレッツ実行出力パラメータ取得
	 * @return ExecTranFletsOutput フレッツ実行出力パラメータ
	 */
	function &getExecTranFletsOutput() {
		return $this->execTranFletsOutput;
	}

	/**
	 * フレッツ登録出力パラメータ設定
	 *
	 * @param EntryTranFletsOutput  $entryTranFletsOutput フレッツ登録出力パラメータ
	 */
	function setEntryTranFletsOutput(&$entryTranFletsOutput) {
		$this->entryTranFletsOutput = $entryTranFletsOutput;
	}

	/**
	 * フレッツ決済実行出力パラメータ設定
	 *
	 * @param ExecTranFletsOutput $execTranFletsOutput フレッツ実行出力パラメータ
	 */
	function setExecTranFletsOutput(&$execTranFletsOutput) {
		$this->execTranFletsOutput = $execTranFletsOutput;
	}

	/**
	 * 取引ID取得
	 * @return string 取引ID
	 */
	function getAccessID() {
		return $this->entryTranFletsOutput->getAccessID();

	}
	/**
	 * 取引パスワード取得
	 * @return string 取引パスワード
	 */
	function getAccessPass() {
		return $this->entryTranFletsOutput->getAccessPass();

	}
	/**
	 * トークン取得
	 * @return string トークン
	 */
	function getToken() {
		return $this->execTranFletsOutput->getToken();

	}

	/**
	 * 取引ID設定
	 *
	 * @param string $accessID
	 */
	function setAccessID($accessID) {
		$this->entryTranFletsOutput->setAccessID($accessID);
		$this->execTranFletsOutput->setAccessID($accessID);

	}
	/**
	 * 取引パスワード設定
	 *
	 * @param string $accessPass
	 */
	function setAccessPass($accessPass) {
		$this->entryTranFletsOutput->setAccessPass($accessPass);

	}
	/**
	 * トークン設定
	 *
	 * @param string $token
	 */
	function setToken($token) {
		$this->execTranFletsOutput->setToken($token);

	}

	/**
	 * 取引登録エラーリスト取得
	 * @return  array エラーリスト
	 */
	function &getEntryErrList() {
		return $this->entryTranFletsOutput->getErrList();
	}

	/**
	 * 決済実行エラーリスト取得
	 * @return array エラーリスト
	 */
	function &getExecErrList() {
		return $this->execTranFletsOutput->getErrList();
	}

	/**
	 * 取引登録エラー発生判定
	 * @return boolean 取引登録時エラー有無(true=エラー発生)
	 */
	function isEntryErrorOccurred() {
		$entryErrList =& $this->entryTranFletsOutput->getErrList();
		return 0 < count($entryErrList);
	}

	/**
	 * 決済実行エラー発生判定
	 * @return boolean 決済実行時エラー有無(true=エラー発生)
	 */
	function isExecErrorOccurred() {
		$execErrList =& $this->execTranFletsOutput->getErrList();
		return 0 < count($execErrList);
	}

	/**
	 * エラー発生判定
	 * @return boolean エラー発生有無(true=エラー発生)
	 */
	function isErrorOccurred() {
		return $this->isEntryErrorOccurred() || $this->isExecErrorOccurred();
	}

}
?>
