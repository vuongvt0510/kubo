<?php
require_once ('com/gmo_pg/client/output/BaseOutput.php');
/**
 * <b>フレッツ金額変更　出力パラメータクラス</b>
 * 
 * @package com.gmo_pg.client
 * @subpackage output
 * @see outputPackageInfo.php
 * @author GMO PaymentGateway
 */
class FletsChangeOutput extends BaseOutput {

	/**
	 * @var string オーダーID
	 */
	var $orderID;
	/**
	 * @var string 現状態
	 */
	var $status;
	/**
	 * @var integer 利用金額
	 */
	var $amount;
	/**
	 * @var integer 税送料
	 */
	var $tax;
	/**
	 * @var integer 変更前利用金額
	 */
	var $previousAmount;
	/**
	 * @var integer 変更前税送料
	 */
	var $previousTax;
	/**
	 * @var string 処理日時
	 */
	var $processDate;

	/**
	 * コンストラクタ
	 *
	 * @param IgnoreCaseMap $params  出力パラメータ
	 */
	function FletsChangeOutput($params = null) {
		$this->__construct($params);
	}

	
	/**
	 * コンストラクタ
	 *
	 * @param IgnoreCaseMap $params  出力パラメータ
	 */
	function __construct($params = null) {
		parent::__construct($params);
		
		// 引数が無い場合は戻る
		if (is_null($params)) {
            return;
        }
		
        // マップの展開
		$this->setOrderID($params->get('OrderID'));
		$this->setStatus($params->get('Status'));
		$this->setAmount($params->get('Amount'));
		$this->setTax($params->get('Tax'));
		$this->setPreviousAmount($params->get('PreviousAmount'));
		$this->setPreviousTax($params->get('PreviousTax'));
		$this->setProcessDate($params->get('ProcessDate'));

	}

	/**
	 * オーダーID取得
	 * @return string オーダーID
	 */
	function getOrderID() {
		return $this->orderID;
	}
	/**
	 * 現状態取得
	 * @return string 現状態
	 */
	function getStatus() {
		return $this->status;
	}
	/**
	 * 利用金額取得
	 * @return integer 利用金額
	 */
	function getAmount() {
		return $this->amount;
	}
	/**
	 * 税送料取得
	 * @return integer 税送料
	 */
	function getTax() {
		return $this->tax;
	}
	/**
	 * 変更前利用金額取得
	 * @return integer 変更前利用金額
	 */
	function getPreviousAmount() {
		return $this->previousAmount;
	}
	/**
	 * 変更前税送料取得
	 * @return integer 変更前税送料
	 */
	function getPreviousTax() {
		return $this->previousTax;
	}
	/**
	 * 処理日時取得
	 * @return string 処理日時
	 */
	function getProcessDate() {
		return $this->processDate;
	}

	/**
	 * オーダーID設定
	 *
	 * @param string $orderID
	 */
	function setOrderID($orderID) {
		$this->orderID = $orderID;
	}
	/**
	 * 現状態設定
	 *
	 * @param string $status
	 */
	function setStatus($status) {
		$this->status = $status;
	}
	/**
	 * 利用金額設定
	 *
	 * @param integer $amount
	 */
	function setAmount($amount) {
		$this->amount = $amount;
	}
	/**
	 * 税送料設定
	 *
	 * @param integer $tax
	 */
	function setTax($tax) {
		$this->tax = $tax;
	}
	/**
	 * 変更前利用金額設定
	 *
	 * @param integer $previousAmount
	 */
	function setPreviousAmount($previousAmount) {
		$this->previousAmount = $previousAmount;
	}
	/**
	 * 変更前税送料設定
	 *
	 * @param integer $previousTax
	 */
	function setPreviousTax($previousTax) {
		$this->previousTax = $previousTax;
	}
	/**
	 * 処理日時設定
	 *
	 * @param string $processDate
	 */
	function setProcessDate($processDate) {
		$this->processDate = $processDate;
	}

	/**
	 * 文字列表現
	 * <p>
	 *  現在の各パラメータを、パラメータ名=値&パラメータ名=値の形式で取得します。
	 * </p>
	 * @return string 出力パラメータの文字列表現
	 */
	function toString() {
		$str ='';
		$str .= 'OrderID=' . $this->encodeStr($this->getOrderID());
		$str .='&';
		$str .= 'Status=' . $this->encodeStr($this->getStatus());
		$str .='&';
		$str .= 'Amount=' . $this->encodeStr($this->getAmount());
		$str .='&';
		$str .= 'Tax=' . $this->encodeStr($this->getTax());
		$str .='&';
		$str .= 'PreviousAmount=' . $this->encodeStr($this->getPreviousAmount());
		$str .='&';
		$str .= 'PreviousTax=' . $this->encodeStr($this->getPreviousTax());
		$str .='&';
		$str .= 'ProcessDate=' . $this->encodeStr($this->getProcessDate());

	    
	    if ($this->isErrorOccurred()) {
            // エラー文字列を連結して返す
            $errString = parent::toString();
            $str .= '&' . $errString;
        }	    
	    
        return $str;
	}

}
?>
