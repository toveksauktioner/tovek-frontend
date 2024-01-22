<?php

$oRouter = clRegistry::get( 'clRouter' );
$oPaymentNets = clRegistry::get( 'clPaymentNets', PATH_MODULE . '/payment/models' );
 
if( !empty( $_GET['responseCode'] ) ) {  
	// Check respons
	switch( $_GET['responseCode'] ) {
		case 'OK':
			// Success
			if( $oPaymentNets->finalizeOrder( $_GET['orderId'] ) ) {
				$oRouter->redirect( $oRouter->getPath('userOrderReceipt') );
			}			
			break;
		
		case 'Cancel':	  
			// Return customer back to cart
			$oRouter->redirect( $oRouter->getPath('guestProductCart') );
			break;
		
		default:
			// Error message?
			$sQueryRequest = new QueryRequest(
				$sTransactionId
			); 
			$oQueryRespons = $oPaymentNets->query( 'Query', $sQueryRequest );
			$oQueryResult = $oQueryRespons->QueryResult; 
			
			if( !empty($oQueryResult->ErrorLog->PaymentError->ResponseText) ) {
				echo '
					<h2>' . _( 'Someting went wrong with your payment' ) . '</h2>
					<p><strong>' . _( 'Message from Nets' ) . ':</strong>
					' . $oQueryResult->ErrorLog->PaymentError->ResponseText . '
					</p>';
			}
			break;
  } 
} else {
	// Error, didn't get any respons code...  
	echo 'Error...';
}