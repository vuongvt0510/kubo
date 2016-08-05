<?php
require_once ('com/gmo_pg/client/common/Cryptgram.php');
require_once ('com/gmo_pg/client/common/GPayException.php');
require_once ('com/gmo_pg/client/output/FletsSalesOutput.php');
require_once ('com/gmo_pg/client/tran/BaseTran.php');
/**
 * <b>フレッツ売上確定　実行クラス</b>
 * 
 * @package com.gmo_pg.client
 * @subpackage tran
 * @see tranPackageInfo.php
 * @author GMO PaymentGateway
 */
class FletsSales extends BaseTran {

	/**
	 * コンストラクタ
	 */
	function FletsSales() {
	    parent::__construct();
	}
	
	/**
	 * 売上確定を実行する
	 *
	 * @param  FletsSalesInput $input  入力パラメータ
	 * @return FletsSalesOutput $output 出力パラメータ
	 * @exception GPayException
	 */
	function exec(&$input) {
	    
        // 接続しプロトコル呼び出し・結果取得
        $resultMap = $this->callProtocol($input->toString());
	    // 戻り値がnullの場合、nullを戻す
        if (is_null($resultMap)) {
		    return null;
        }
	    
        // FletsSalesOutput作成し、戻す
	    return new FletsSalesOutput($resultMap);
	}
}
?>
