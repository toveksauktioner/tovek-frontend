<?php

//clFactory::loadClassFile( 'clLogger' );
//clLogger::log( $_GET, 'dibsEasyCallbackStart.log' );

// paymentId is requiered
if( empty($_GET['paymentId']) ) return;

$aErr = array();

$oPaymentDibsEasy = clRegistry::get( 'clPaymentDibsEasy', PATH_MODULE. '/payment/models' );

// Fetch payment data
$aPaymentData = current( $oPaymentDibsEasy->fetchPaymentData( $_GET['paymentId'] ) );

if( !empty($aPaymentData['paymentId']) ) {
	/**
	 * Reserved amount gets niche as successful
	 */
	
	$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );
	$oCheckoutPending = clRegistry::get( 'clCheckoutPending', PATH_MODULE . '/checkout/models' );
	$oCart = clRegistry::get( 'clCart', PATH_MODULE . '/cart/models' );
	$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
	$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );
	$oOrderHistory = clRegistry::get( 'clOrderHistory', PATH_MODULE . '/order/models' );
	$oDiscountCode = clRegistry::get( 'clDiscountCode', PATH_MODULE . '/discountCode/models' );
	$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
	
	/**
	 * Temporary write access
	 */
	$oAcl = new clAcl();
	$oAcl->setAcl( array(
		'readCheckoutPending' => 'allow',
		'writeCheckoutPending' => 'allow',
		
		'readOrder' => 'allow',
		'writeOrder' => 'allow',
		
		'readOrderLine' => 'allow',
		'writeOrderLine' => 'allow',
		
		'readOrderHistory' => 'allow',
		'writeOrderHistory' => 'allow',		
		
		'readDiscountCode' => 'allow',
		'writeDiscountCode' => 'allow'
	) );
	$oCheckoutPending->setAcl( $oAcl );
	$oOrder->setAcl( $oAcl );
	$oOrderLine->setAcl( $oAcl );
	$oOrderHistory->setAcl( $oAcl );
	$oDiscountCode->setAcl( $oAcl );
	
	// Find payment ID for Dibs Easy
	$aPayment = current( $oPayment->readByClass( 'clPaymentDibsEasy' ) );
	
	// Read ongoing (pending) checkout
	$aCheckoutPending = current( $oCheckoutPending->readByPaymentCheckout( $aPayment['paymentId'], $aPaymentData['paymentId'] ) );
	
	// May not use this after all
	//$aCart = $oCart->readCartItems( $aCheckoutPending['pendingCartId'] );
	
	// Get data sent to Dibs
	$oCheckoutData = json_decode( $aCheckoutPending['pendingPaymentCheckoutData'] );
	$oOrderData = $oCheckoutData->order;
	
	/**
	 * Extract product IDs
	 */
	$aProductIds = array();
	foreach( $oOrderData->items as $oItem ) {
		if( ctype_digit($oItem->reference) ) $aProductIds[] = $oItem->reference;
	}
	
	// Additonal product data
	$aProductData = valueToKey( 'productId', $oProduct->read( '*', $aProductIds ) );
	
	// We need to calculate VAT again..
	$iTotalTax = 0;
	
	/**
	 * Assamble order lines
	 */
	$aOrderLines = array();
	foreach( $oOrderData->items as $oItem ) {
		if( !ctype_digit($oItem->reference) ) continue;
		
		$aProduct = $aProductData[ $oItem->reference ];
		
		$aOrderLines[] = array(
			'lineOrderId' => null, // We fill in this later
			'lineProductId' => $aProduct['productId'],
			'lineProductCustomId' => $aProduct['productCustomId'],
			'lineProductTitle' => $oItem->name,
			'lineProductQuantity' => $oItem->quantity,
			//'lineProductDeliveryTime' => $entry['productDeliveryTime'],
			//'lineProductWeight' => $entry['productWeight'],
			'lineProductPrice' => $aProduct['productPrice'], // One product, Price without VAT
			'lineProductVat' => $aProduct['productVat'] // In percentage (0.25)
		);
		
		$iTotalTax += $oItem->taxAmount;
	}
	
	// Extract customer & user ID from reference
	list( $iCustomerId, $iUserId ) = explode( '-', $aPaymentData['orderDetails']['reference'] );
	
	/**
	 * Determ customer type
	 */
	if( !empty($aPaymentData['consumer']['company']['email']) ) {
		// Company type
		$aCustomerData = $aPaymentData['consumer']['company'];
	} else {
		// Private person type
		$aCustomerData = $aPaymentData['consumer']['privatePerson'];
	}
	
	/**
	 * Assamble order data
	 */
	$aOrderData = array(
		'orderCustomerId' => $iCustomerId,
		'orderUserId' => $iUserId,
		'orderEmail' => $aCustomerData['email'],
		'orderMessage' => '',
		'orderUserPin' => '', // date( 'ymd', $aCustomerData['dateOfBirth'] ) . '0000',
		//'orderCustomerType' => 'privatePerson', // klarna only accepts "person" customer.type
		
		'orderDeliveryName' => $aCustomerData['firstName'] . ' ' . $aCustomerData['lastName'],
		'orderDeliveryAddress' => !empty($aPaymentData['consumer']['shippingAddress']['addressLine1']) ? $aPaymentData['consumer']['shippingAddress']['addressLine1'] : '',
		'orderDeliveryZipCode' => !empty($aPaymentData['consumer']['shippingAddress']['postalCode']) ? $aPaymentData['consumer']['shippingAddress']['postalCode'] : '',
		'orderDeliveryCity' => !empty($aPaymentData['consumer']['shippingAddress']['city']) ? $aPaymentData['consumer']['shippingAddress']['city'] : '',
		'orderDeliveryCountry' => !empty($aPaymentData['consumer']['shippingAddress']['country']) ? $aPaymentData['consumer']['shippingAddress']['country'] : '',
		'orderDeliveryPhone' => $aCustomerData['phoneNumber']['prefix'] . '' . $aCustomerData['phoneNumber']['number'],
		
		'orderPaymentName' => $aCustomerData['firstName'] . ' ' . $aCustomerData['lastName'],
		'orderPaymentAddress' => !empty($aPaymentData['consumer']['billingAddress']['addressLine1']) ? $aPaymentData['consumer']['billingAddress']['addressLine1'] : $aPaymentData['consumer']['shippingAddress']['addressLine1'],
		'orderPaymentZipCode' => !empty($aPaymentData['consumer']['billingAddress']['postalCode']) ? $aPaymentData['consumer']['billingAddress']['postalCode'] : $aPaymentData['consumer']['shippingAddress']['addressLine1'],
		'orderPaymentCity' => !empty($aPaymentData['consumer']['billingAddress']['city']) ? $aPaymentData['consumer']['billingAddress']['city'] : $aPaymentData['consumer']['shippingAddress']['addressLine1'],
		'orderPaymentCountry' => !empty($aPaymentData['consumer']['billingAddress']['country']) ? $aPaymentData['consumer']['billingAddress']['country'] : $aPaymentData['consumer']['shippingAddress']['addressLine1'],
		'orderPaymentPhone' => $aCustomerData['phoneNumber']['prefix'] . '' . $aCustomerData['phoneNumber']['number'],
		//'orderPaymentCellPhone' => '',
		//'orderPaymentReference' => '',
		
		'orderPaymentType' => $aPayment['paymentId'],
		'orderPaymentTypeTitle' => $aPayment['paymentTitleTextId'],
		'orderPaymentCustomId' => $aPaymentData['paymentId'], // End-consumer friendly reference
		'orderPaymentUrl' => '',
		'orderPaymentTransactionId' => $aPaymentData['paymentId'], // Unique identifier of the Klarna Checkout Order
		
		//'orderPackageId' => '',
		
		'orderPaymentPrice' => '0',
		'orderPaymentPriceVat' => '0',
		
		'orderTotal' => ($aPaymentData['orderDetails']['amount'] / 100), // Should be with vat
		'orderVatTotal' => ($iTotalTax / 100), // Amount of vat in currency, not percentage
		'orderCurrency' => $aPaymentData['orderDetails']['currency'], // TODO currency support
		'orderCurrencyRate' => '1', // TODO currency support
		
		'orderStatus' => 'intermediate',
		'orderPaymentStatus' => 'unpaid'
	);
	
	/**
	 * Create order
	 */
	if( $iOrderId = $oOrder->create($aOrderData) ) {
		foreach( $aOrderLines as $aOrderLine ) {
			$aOrderLine['lineOrderId'] = $iOrderId;
			$iOrderLineId = $oOrderLine->create( $aOrderLine );
		}
		
		// Order history
		$oOrderHistory->create( array(
			'orderHistoryOrderId' => $iOrderId,
			'orderHistoryUserId' => $iUserId,
			'orderHistoryGroupKey' => 'Payment',
			'orderHistoryMessage' => _( "Order created by Klarna's callback" ), # The order was created by the Klarna callback.
			'orderHistoryData' => var_export( $aPaymentData, true )
		) );
	} else {
		/**
		 * Something went wrong when creating the order
		 */
		$aErr += clErrorHandler::getValidationError( 'createOrder' );
		echo '<pre>';
		var_dump( $aErr );
		var_dump( $aOrderData );
		die;
	}
	
	if( empty($aErr) ) {
		$_SESSION['userId'] = $iUserId;
		$_SESSION['orderId'] = $iOrderId;
		
		// Update payment reference
		if( $oPaymentDibsEasy->updatePaymentReference( $aPaymentData['paymentId'], $iOrderId ) ) {
			// Updated successfully
			echo json_encode( array(
				'result' => 'successful',
				'orderId' => $iOrderId
			) );
			return;
		} else {
			// Updated unsuccessfully
			echo json_encode( array(
				'result' => 'unsuccessful',
				'orderId' => $iOrderId
			) );
			return;
		}		
	}
	
} else {
	/**
	 * No payment found at Dibs
	 */
	$aErr['dibsEasy'] = _( 'No payment found at Dibs' );
}

/**
 * Error
 */
echo json_encode( array(
	'result' => 'unsuccessful',
	'orderId' => null
) );
return;