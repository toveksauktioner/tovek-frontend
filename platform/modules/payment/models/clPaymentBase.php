<?php

interface ifPaymentMethod {

	public function init( $iOrderId, $aParams = array() );
	public function checkStatus();
	public function finalizeOrder( $iOrderId );

}

class clPaymentBase {

	protected $oPayment;
	protected $oInvoiceEngine;
	protected $oOrder;
	protected $oOrderLine;
	protected $oOrderHistory;
	protected $oCart;
	protected $oAcl;
	protected $aErr;	

	public function initBase() {
		$this->oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );
		$this->oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
		/*$this->oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
		$this->oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );
		$this->oOrderHistory = clRegistry::get( 'clOrderHistory', PATH_MODULE . '/order/models' );		
		$this->oCart = clRegistry::get( 'clCart', PATH_MODULE . '/cart/models' );*/
		
		clFactory::loadClassFile( 'clAcl' );
		
		$this->oAcl = new clAcl();
		$this->oAcl->setAcl( array(
			'readPayment' => 'allow',
			
			//'readOrder' => 'allow',
			//'writeOrder' => 'allow',
			//
			//'readOrderLine' => 'allow',
			//'writeOrderLine' => 'allow',
			//
			//'readOrderHistory' => 'allow',
			//'writeOrderHistory' => 'allow',
			//
			//'readCart' => 'allow',
			//'writeCart' => 'allow'
		) );
		$this->oPayment->setAcl( $this->oAcl );
		//$this->oOrder->setAcl( $this->oAcl );
		//$this->oOrderLine->setAcl( $this->oAcl );
		//$this->oOrderHistory->setAcl( $this->oAcl );
		//$this->oCart->setAcl( $this->oAcl );
	}

	public function finalizeOrder( $iOrderId ) {
		$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
		$oOrder->sendMail( $oOrder->generateOrderReceiptHtml($iOrderId), $iOrderId );
		
		//// Finalize cart
		//$this->oCart->finalizeCart( $iOrderId );
		//
		//// Update product quantity
		//if( ORDER_ADJUST_PRODUCT_QUANTITY == 'checkout' ) {
		//	$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );
		//	$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
		//	
		//	// Set temporary access
		//	clFactory::loadClassFile( 'clAcl' );
		//	$oAcl = new clAcl();
		//	$oAcl->setAcl( array(
		//		'readProduct' => 'allow',
		//		'writeProduct' => 'allow'
		//	));
		//	$oProduct->setAcl( $oAcl );
		//	
		//	$aOrderLines = $oOrderLine->readByOrder( $iOrderId, array(
		//		'lineId',
		//		'lineProductId',
		//		'lineProductQuantity'
		//	) );
		//	foreach( $aOrderLines as $entry ) {
		//		$oProduct->decreaseQuantity( $entry['lineProductId'], $entry['lineProductQuantity'] );
		//	}
		//	$oOrder->update( $_POST['orderId'], array(
		//		'orderQuantityUpdated' => 'yes'
		//	) );
		//}
	}

	public function finalizeInvoiceOrder( $iInvoiceOrderId ) {
		// This function needs to be created
		// It should send the user a receipt of the payment
		// $oInvoiceEngine->sendInvoiceOrderMail( $oInvoiceEngine->generateInvoiceOrderReceiptHtml_in_InvoiceOrder($iInvoiceOrderId), $iInvoiceOrderId );
	}
	
	public function readError() {
		return $this->aErr;
	}

	public function sendPostData( $aData, $sUrl ) {

		$sData = '';
		foreach( $aData as $key => $value ) {
			$sData .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
		}

		ob_end_clean();
		echo '<!DOCTYPE html>
<html>
<head>
	<title>' . _( 'Posting data' ) . '</title>
	<meta charset="utf-8">
	<?php echo $sTop; ?>
</head>
<body onload="document.forms[0].submit();">
	<p>' . _( 'Sending' ) . '...</p>
	<form name="formPost" action="' . $sUrl . '" method="post">
		' . $sData . '
		<button type="submit">' . _( 'Send' ) . '</button>
	</form>
</body>
</html>';
		exit;
	}

	/**
	 * Function for checking payment current status,
	 * if payment method supports it.
	 */
	public function checkPaymentStatus( $iOrderId ) {		
		$this->initBase();
		
		// Payment data in order
		$aOrderPaymentData = current( $this->oOrder->read( array(
			'orderId',
			'orderPaymentType',			
			'orderPaymentCustomId',
			'orderPaymentUrl',
			'orderPaymentToken'
		), $iOrderId ) );		
		
		if( !empty($aOrderPaymentData) ) {		
			$sPaymentMethodClass = current( current($this->oPayment->read('paymentClass', $aOrderPaymentData['orderPaymentType'])) );
			if( $sPaymentMethodClass == 'clPaymentDefault' ) return true;
			$oPaymentMethod = clRegistry::get( $sPaymentMethodClass, PATH_MODULE . '/payment/models' );
			
			if( method_exists($oPaymentMethod, 'checkPaymentStatus') ) {			
				$aResult = $oPaymentMethod->checkPaymentStatus( $aOrderPaymentData );
				
				if( !empty($aResult) && !empty($aResult['id']) ) {			
					/**
					 * Created history data should always contain these fields.
					 * If any data is omitted by payment method, leave it empty.
					 * 
					 * @var array aResult(
					 * 	'id'
					 * 	'state'
					 * 	'created'
					 * 	'updated'
					 * 	'payer' array(
					 * 		'method'
					 * 		'status'
					 * 		'email'
					 * 	)
					 * )
					 */					
					$this->oOrderHistory->create( array(
						'orderHistoryOrderId' => $iOrderId,
						'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
						'orderHistoryGroupKey' => 'Payment',
						'orderHistoryMessage' => 'A payment check up was performed', # Do not use gettext as the string will be permanent
						'orderHistoryData' => var_export( $aResult, true )
					) );			
					return true;
				}
				
			} else {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataWarning' => _( 'The payment method did not support this action' )
				) );
			}
		}
		
		return false;
	}
	
	/**
	 * Function for refunding payment
	 */
	public function refundPayment( $iOrderId ) {		
		$this->initBase();
		
		// Payment data in order
		$aOrderPaymentData = current( $this->oOrder->read( array(
			'orderId',
			'orderTotal',
			'orderPaymentType',			
			'orderPaymentCustomId',
			'orderPaymentUrl',
			'orderPaymentToken'
		), $iOrderId ) );		
		
		if( !empty($aOrderPaymentData) ) {		
			$sPaymentMethodClass = current( current($this->oPayment->read('paymentClass', $aOrderPaymentData['orderPaymentType'])) );
			$oPaymentMethod = clRegistry::get( $sPaymentMethodClass, PATH_MODULE . '/payment/models' );
			
			if( method_exists($oPaymentMethod, 'refundPayment') ) {			
				$mResult = $oPaymentMethod->refundPayment( $aOrderPaymentData );
				
				if( !empty($mResult) && !empty($mResult['id']) ) {			
					$this->oOrderHistory->create( array(
						'orderHistoryOrderId' => $iOrderId,
						'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
						'orderHistoryGroupKey' => 'Payment',
						'orderHistoryMessage' => 'Payment was refunded', # Do not use gettext as the string will be permanent
						'orderHistoryData' => var_export( $mResult, true )
					) );			
					return true;
				}
				
			} else {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataWarning' => _( 'The payment method did not support this action' )
				) );
			}
		}
		
		return false;
	}
	
}
