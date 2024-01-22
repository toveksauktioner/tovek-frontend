<?php

// Payson Instant Payment Notification

$oPaymentPaysonBase = clRegistry::get( 'clPaymentPaysonBase', PATH_MODULE . '/payment/models' );
$bResponse = $oPaymentPaysonBase->finalizeOrder( $_POST['custom'] );
if( $bResponse == true ) {
	// Order was validated and payed
	$oOrderHistory = clRegistry::get( 'clOrderHistory', PATH_MODULE . '/order/models' );
	
	$oOrderHistory->create( array(
		'orderHistoryOrderId' => $_POST['custom'],
		'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
		'orderHistoryGroupKey' => 'Payment',
		'orderHistoryMessage' => 'This order was marked as paid by Payson IPN', // Do not use gettext as the string will be permanent
		//'orderHistoryData' => '<pre>' . var_export($aErrors, true) . '</pre>'
	) );
} else {
	// Something was wrong an the order did not make validation
	
}
