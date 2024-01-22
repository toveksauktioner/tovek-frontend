<?php

if( !empty($_GET['R']) && !empty($_GET['O']) ) {
	
	switch( $_GET['R'] ) {
		// Success
		case '00':
		case 'T0':
			$oPaymentSamport = clRegistry::get( 'clPaymentSamportHosted', PATH_MODULE . '/payment/models' );
			$oPaymentSamport->finalizeOrder( $_GET['O'] );
			break;
		// Denied
		default:
	}
	
}