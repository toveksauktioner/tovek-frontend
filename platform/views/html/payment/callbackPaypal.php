<?php 

$aErr = array();

$oRouter = clRegistry::get( 'clRouter' );
$oPaymentPaypal = clRegistry::get( 'clPaymentPaypal', PATH_MODULE . '/payment/models' );
$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );

if( !empty($_SESSION['paypal_access_token']) ) {
	$aToken = explode( ' ', $_SESSION['paypal_access_token'] );	
	$sCheckSum = md5( $_GET['token'] . '||' . $aToken[1] );
	
	// Order data
	$oOrder->oDao->setCriterias( array(
		'orderPaymentCustomId' => array(
			'type' => '=',
			'value' => $sCheckSum,
			'fields' => array( 'orderPaymentToken' )
		)
	) );
	$aOrderData = current( $oOrder->read( array(
		'orderId',
		'orderPaymentToken',
		'orderPaymentCustomId'
	) ) );
	
	if( !empty($aOrderData) ) {
		$_SESSION['paypal_payer_id'] = $_GET['PayerID'];
		if( $oPaymentPaypal->finalizeOrder( $aOrderData['orderId'] ) ) {
			$oRouter->redirect( $oRouter->getPath('userOrderReceipt') );
		} else {
			// Error
		}
	} 
}

if( !empty($_GET['token']) ) {
	$oRouter->redirect( $oRouter->getPath('guestProductCart') . '?token=' . $_GET['token'] );
} else {
	$oRouter->redirect( $oRouter->getPath('guestProductCart') );
}