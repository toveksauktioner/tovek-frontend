<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';

class clPaymentDefault extends clPaymentBase implements ifPaymentMethod {

	public function __construct() {
		$this->initBase();
	}

	public function init( $iOrderId, $aParams = array() ) {
		$this->finalizeOrder( $iOrderId );
	}

	public function checkStatus() {
		return true;
	}

	public function finalizeOrder( $iOrderId ) {
		parent::finalizeOrder( $iOrderId );
		$oRouter = clRegistry::get( 'clRouter' );
		$oRouter->redirect( $oRouter->getPath('userOrderReceipt') );
		return true;
	}

}
