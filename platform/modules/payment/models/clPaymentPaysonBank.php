<?php

require_once PATH_MODULE . '/payment/models/clPaymentPaysonBase.php';

class clPaymentPaysonBank extends clPaymentPaysonBase {
	
	public function __construct() {
		$this->initBase();
		
		$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/product/models' );
		$this->fPaymentPrice = (float) current( current($oPayment->readByClass('clPaymentPaysonBank', 'paymentPrice')) );
		
		$this->aPaymentType = array(
			'bank'
		);
	}

}
