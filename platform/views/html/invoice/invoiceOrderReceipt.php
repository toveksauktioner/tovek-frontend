<?php

/* * * *
 * Filename: invoiceOrderReceipt.php
 * Created: 08/10/2014 by Renfors
 * Reference:
 * Description: View file for showing a receipt of the paid invoice order
 * * * */

/*$aAdminIps = array(
	'kontoret' => '213.88.134.199'
);
$sIpAddress = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
if( in_array($sIpAddress, $aAdminIps) ) {
	$_SESSION['userId'] = 61793;
$_SESSION['invoiceOrderId'] = 21716;
	$GLOBALS['debug'] = true;
}*/

// mail("renfors@argonova.se", "Tovek - DIBS-kvitto", print_r($_SESSION, true) );

if( !isset($_SESSION['userId']) || !isset($_SESSION['invoiceOrderId']) ) {
	$oRouter->redirect( '/' );
}

require_once PATH_FUNCTION . '/fData.php';

$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );

$aInvoiceOrderData = current( $oInvoiceEngine->read('InvoiceOrder', '*', $_SESSION['invoiceOrderId']) );
$aCurrentPayment = current( $oPayment->read('*', $aInvoiceOrderData['invoiceOrderPaymentId']) );

$oPaymentMethod = clRegistry::get( $aCurrentPayment['paymentClass'], PATH_MODULE . '/payment/models' );

if( $oPaymentMethod->checkStatus() ) {
	$sOutput = $oInvoiceEngine->generateInvoiceOrderReceiptHtml( $_SESSION['invoiceOrderId'] );
	echo $sOutput;

} else {
	echo '<ul>';
	foreach( $oPaymentMethod->readError() as $value ) {
		echo '<li>' . $value . '</li>';
	}
	echo '</ul>';
}
