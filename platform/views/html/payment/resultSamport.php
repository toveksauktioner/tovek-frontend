<?php

if( !empty($_GET['R']) && !empty($_GET['O']) && !empty($_SESSION['orderId']) && $_GET['O'] == $_SESSION['orderId'] ) {
	
	switch( $_GET['R'] ) {
		// Success
		case '00':
		case 'T0':
			$oRouter->redirect( $oRouter->getPath('userOrderReceipt') );
			break;
		// Denied
		default:
			echo 'Payment unsuccessful';
	}
	
}