<?php
require_once 'com/gmo_pg/client/input/EntryTranFletsInput.php';
require_once 'com/gmo_pg/client/input/ExecTranFletsInput.php';
/**
 * <b>フレッツ登録・決済一括実行　入力パラメータクラス</b>
 *
 * @package com.gmo_pg.client
 * @subpackage input
 * @see inputPackageInfo.php
 * @author GMO PaymentGateway
 */
class EntryExecTranFletsInput {

	/**
	 * @var EntryTranFletsInput フレッツ登録入力パラメータ
	 */
	var $entryTranFletsInput;/* @var $entryTranInput EntryTranFletsInput */

	/**
	 * @var ExecTranFletsInput フレッツ実行入力パラメータ
	 */
	var $execTranFletsInput;/* @var $execTranInput ExecTranFletsInput */

	/**
	 * コンストラクタ
	 *
	 * @param array $params    入力パラメータ
	 */
	function EntryExecTranFletsInput($params = null) {
		$this->__construct($params);
	}

	/**
	 * コンストラクタ
	 *
	 * @param array $params    入力パラメータ
	 */
	function __construct($params = null) {
		$this->entryTranFletsInput = new EntryTranFletsInput($params);
		$this->execTranFletsInput = new ExecTranFletsInput($params);
	}

	/**
	 * フレッツ取引登録入力パラメータ取得
	 *
	 * @return EntryTranFletsInput 取引登録時パラメータ
	 */
	function &getEntryTranFletsInput() {
		return $this->entryTranFletsInput;
	}

	/**
	 * フレッツ実行入力パラメータ取得
	 * @return ExecTranFletsInput 決済実行時パラメータ
	 */
	function &getExecTranFletsInput() {
		return $this->execTranFletsInput;
	}

	/**
	 * ショップID取得
	 * @return string ショップID
	 */
	function getShopID() {
		return $this->entryTranFletsInput->getShopID();

	}
	/**
	 * ショップパスワード取得
	 * @return string ショップパスワード
	 */
	function getShopPass() {
		return $this->entryTranFletsInput->getShopPass();

	}
	/**
	 * オーダーID取得
	 * @return string オーダーID
	 */
	function getOrderID() {
		return $this->entryTranFletsInput->getOrderID();

	}
	/**
	 * 処理区分取得
	 * @return string 処理区分
	 */
	function getJobCd() {
		return $this->entryTranFletsInput->getJobCd();
	}
	/**
	 * 利用料金取得
	 * @return integer 利用料金
	 */
	function getAmount() {
		return $this->entryTranFletsInput->getAmount();
	}
	/**
	 * 税送料取得
	 * @return integer 税送料
	 */
	function getTax() {
		return $this->entryTranFletsInput->getTax();
	}
	/**
	 * 取引ID取得
	 * @return string 取引ID
	 */
	function getAccessID() {
		return $this->execTranFletsInput->getAccessID();
	}
	/**
	 * 取引パスワード取得
	 * @return string 取引パスワード
	 */
	function getAccessPass() {
		return $this->execTranFletsInput->getAccessPass();
	}
	/**
	 * 加盟店自由項目1取得
	 * @return string 加盟店自由項目1
	 */
	function getClientField1() {
		return $this->execTranFletsInput->getClientField1();
	}
	/**
	 * 加盟店自由項目2取得
	 * @return string 加盟店自由項目2
	 */
	function getClientField2() {
		return $this->execTranFletsInput->getClientField2();
	}
	/**
	 * 加盟店自由項目3取得
	 * @return string 加盟店自由項目3
	 */
	function getClientField3() {
		return $this->execTranFletsInput->getClientField3();
	}
	/**
	 * センターコード取得
	 * @return string センターコード
	 */
	function getCenterCode() {
		return $this->execTranFletsInput->getCenterCode();
	}
	/**
	 * 決済結果戻しURL取得
	 * @return string 決済結果戻しURL
	 */
	function getRetURL() {
		return $this->execTranFletsInput->getRetURL();
	}
	/**
	 * 処理NG時URL取得
	 * @return string 処理NG時URL
	 */
	function getErrorRcvURL() {
		return $this->execTranFletsInput->getErrorRcvURL();
	}
	/**
	 * 商品ID取得
	 * @return string 商品ID
	 */
	function getItemId() {
		return $this->execTranFletsInput->getItemId();
	}
	/**
	 * 商品名取得
	 * @return string 商品名
	 */
	function getItemName() {
		return $this->execTranFletsInput->getItemName();
	}
	/**
	 * 支払開始秒取得
	 * @return integer 支払開始秒
	 */
	function getPaymentTermSec() {
		return $this->execTranFletsInput->getPaymentTermSec();
	}

	/**
	 * フレッツ取引登録入力パラメータ設定
	 *
	 * @param EntryTranFletsInput entryTranFletsInput  取引登録入力パラメータ
	 */
	function setEntryTranFletsInput(&$entryTranFletsInput) {
		$this->entryTranFletsInput = $entryTranFletsInput;
	}

	/**
	 * フレッツ実行入力パラメータ設定
	 *
	 * @param ExecTranFletsInput  execTranFletsInput   決済実行入力パラメータ
	 */
	function setExecTranFletsInput(&$execTranFletsInput) {
		$this->execTranFletsInput = $execTranFletsInput;
	}

	/**
	 * ショップID設定
	 *
	 * @param string $shopID
	 */
	function setShopID($shopID) {
		$this->entryTranFletsInput->setShopID($shopID);
		$this->execTranFletsInput->setShopID($shopID);

	}
	/**
	 * ショップパスワード設定
	 *
	 * @param string $shopPass
	 */
	function setShopPass($shopPass) {
		$this->entryTranFletsInput->setShopPass($shopPass);
		$this->execTranFletsInput->setShopPass($shopPass);

	}
	/**
	 * オーダーID設定
	 *
	 * @param string $orderID
	 */
	function setOrderID($orderID) {
		$this->entryTranFletsInput->setOrderID($orderID);
		$this->execTranFletsInput->setOrderID($orderID);

	}
	/**
	 * 処理区分設定
	 *
	 * @param string $jobCd
	 */
	function setJobCd($jobCd) {
		$this->entryTranFletsInput->setJobCd($jobCd);
	}
	/**
	 * 利用料金設定
	 *
	 * @param integer $amount
	 */
	function setAmount($amount) {
		$this->entryTranFletsInput->setAmount($amount);
	}
	/**
	 * 税送料設定
	 *
	 * @param integer $tax
	 */
	function setTax($tax) {
		$this->entryTranFletsInput->setTax($tax);
	}
	/**
	 * 取引ID設定
	 *
	 * @param string $accessID
	 */
	function setAccessID($accessID) {
		$this->execTranFletsInput->setAccessID($accessID);
	}
	/**
	 * 取引パスワード設定
	 *
	 * @param string $accessPass
	 */
	function setAccessPass($accessPass) {
		$this->execTranFletsInput->setAccessPass($accessPass);
	}
	/**
	 * 加盟店自由項目1設定
	 *
	 * @param string $clientField1
	 */
	function setClientField1($clientField1) {
		$this->execTranFletsInput->setClientField1($clientField1);
	}
	/**
	 * 加盟店自由項目2設定
	 *
	 * @param string $clientField2
	 */
	function setClientField2($clientField2) {
		$this->execTranFletsInput->setClientField2($clientField2);
	}
	/**
	 * 加盟店自由項目3設定
	 *
	 * @param string $clientField3
	 */
	function setClientField3($clientField3) {
		$this->execTranFletsInput->setClientField3($clientField3);
	}
	/**
	 * センターコード設定
	 *
	 * @param string $centerCode
	 */
	function setCenterCode($centerCode) {
		$this->execTranFletsInput->setCenterCode($centerCode);
	}
	/**
	 * 決済結果戻しURL設定
	 *
	 * @param string $retURL
	 */
	function setRetURL($retURL) {
		$this->execTranFletsInput->setRetURL($retURL);
	}
	/**
	 * 処理NG時URL設定
	 *
	 * @param string $errorRcvURL
	 */
	function setErrorRcvURL($errorRcvURL) {
		$this->execTranFletsInput->setErrorRcvURL($errorRcvURL);
	}
	/**
	 * 商品ID設定
	 *
	 * @param string $itemId
	 */
	function setItemId($itemId) {
		$this->execTranFletsInput->setItemId($itemId);
	}
	/**
	 * 商品名設定
	 *
	 * @param string $itemName
	 */
	function setItemName($itemName) {
		$this->execTranFletsInput->setItemName($itemName);
	}
	/**
	 * 支払開始秒設定
	 *
	 * @param integer $paymentTermSec
	 */
	function setPaymentTermSec($paymentTermSec) {
		$this->execTranFletsInput->setPaymentTermSec($paymentTermSec);
	}

}
?>
