<?php

$oPaymentPayer = clRegistry::get( 'clPaymentPayer', PATH_MODULE . '/payment/models' );

if( $oPaymentPayer->validateIpAddress() ) {	
	if( !empty($_GET['orderId']) && !empty($_GET['payer_merchant_reference_id']) && $_GET['orderId'] == $_GET['payer_merchant_reference_id'] ) {
		// It seems that payment is valid, continue with validation...
		if( $oPaymentPayer->finalizeOrder( $_GET['orderId'] ) ) {		
			die("TRUE");
		}
	}
}
die("FALSE");