<?php

if( empty($_GET['checkout']) ) return;

/**
 * Fetch the checkoutID that are returned
 */
$sCheckoutId = &$_GET['checkout'];

$oPaymentPaysonCheckout = clRegistry::get( 'clPaymentPaysonCheckout', PATH_MODULE . '/payment/models' );

if( PAYSON_CHECKOUT_DEBUG === true ) {
	clFactory::loadClassFile( 'clLogger' );
	clLogger::log( 'Started callback', 'paysonCheckoutCallback.log' );
}

// Fetch external checkout data
$oCheckout = $oPaymentPaysonCheckout->getCheckout( $sCheckoutId );

$aErr = array();

if( $oCheckout->status == 'readyToShip' ) {
	clFactory::loadClassFile( 'clAcl' );
	
	$oOrderHistory = clRegistry::get( 'clOrderHistory', PATH_MODULE . '/order/models' );
	$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );
	$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
	$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
	$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );
	
	if( PAYSON_CHECKOUT_DEBUG === true ) {		
		clLogger::log( $_SERVER['REQUEST_METHOD'], 'paysonCheckoutCallback.log' );
		clLogger::log( $oCheckout, 'paysonCheckoutCallback.log' );
	}
	
	// Payment data
	$aPaymentPaysonData = current( $oPayment->readByClass( 'clPaymentPaysonCheckout' ) );
	
	// Reference
	$aReference = explode( '-', $oCheckout->merchant->reference );
	
	// Create order
	$aOrderData = array(
		'orderCustomerId' => $aReference[0],
		'orderUserId' => $aReference[1],
		'orderEmail' => $oCheckout->customer->email,
		'orderMessage' => '',
		'orderUserPin' => '',
		'orderCustomerType' => 'privatePerson', // payson only accepts "person" customer.type

		'orderDeliveryName' => $oCheckout->customer->firstName . ' ' . $oCheckout->customer->lastName,
		'orderDeliveryAddress' => $oCheckout->customer->street,
		'orderDeliveryZipCode' => $oCheckout->customer->postalCode,
		'orderDeliveryCity' => $oCheckout->customer->city,
		'orderDeliveryCountry' => 210,
		'orderDeliveryPhone' => $oCheckout->customer->phone,
	
		'orderPaymentName' => $oCheckout->customer->firstName . ' ' . $oCheckout->customer->lastName,
		'orderPaymentAddress' => $oCheckout->customer->street,
		'orderPaymentZipCode' => $oCheckout->customer->postalCode,
		'orderPaymentCity' => $oCheckout->customer->city,
		'orderPaymentCountry' => 210,
		'orderPaymentPhone' => $oCheckout->customer->phone,
		//'orderPaymentCellPhone' => $oKlarnaOrder['billing_address'][''],
		//'orderPaymentReference' => $oKlarnaOrder['billing_address'][''],

		'orderPaymentType' => $aPaymentPaysonData['paymentId'],
		'orderPaymentTypeTitle' => 'Payson checkout',
		'orderPaymentCustomId' => $oCheckout->purchaseId, // End-consumer friendly reference
		'orderPaymentUrl' => '',
		'orderPaymentToken' => $oCheckout->id, // Unique identifier of the Klarna Checkout Order
		
		//'orderPackageId' => '',
		
		'orderPaymentPrice' => '0',
		'orderPaymentPriceVat' => '0',
		
		'orderTotal' => $oCheckout->payData->totalPriceIncludingTax,
		'orderVatTotal' => $oCheckout->payData->totalTaxAmount, // Amount of vat in currency, not percentage
		'orderCurrency' => 'SEK', // TODO currency support
		'orderCurrencyRate' => '1', // TODO currency support
		
		'orderStatus' => 'intermediate',
		'orderPaymentStatus' => 'unpaid'
	);
	
	/**
	 * Order lines from Klarna order
	 */
	$aOrderLines = array();
	if( is_array($oCheckout->payData->items) ) {
		foreach( $oCheckout->payData->items as $aCartEntry ) {
			/**
			 * Type. `physical` by default, alternatively `discount`, `fee`
			 */
			if( $aCartEntry->type == 'physical' ) {
				// Regular product
				// TODO test the vat calculation with more vat types
				$fPriceWithVat = $aCartEntry->unitPrice;
				$fVat = $aCartEntry->taxRate;
				$fPriceWithoutVat = removeVat( $fPriceWithVat, $fVat );
				
				$aProductData = current($oProduct->read( array('productCustomId'), $aCartEntry->reference ));
				
				$aOrderLines[] = array(
					'lineOrderId' => null, // We fill in this later
					'lineProductId' => $aCartEntry->reference,
					'lineProductCustomId' => ( !empty($aProductData) ? $aProductData['productCustomId'] : '' ),
					'lineProductTitle' => $aCartEntry->name,
					'lineProductQuantity' => $aCartEntry->quantity,
					//'lineProductDeliveryTime' => $entry['productDeliveryTime'],
					//'lineProductWeight' => $entry['productWeight'],
					'lineProductPrice' => $fPriceWithoutVat, // One product, Price without VAT
					'lineProductVat' => $fVat // In percentage (0.25)
				);
			
			/**
			 * Type. `shipping_fee` by default, alternatively `discount`, `fee`
			 */	
			} elseif( $aCartEntry->type == 'fee' ) {
				// Shipping fee
				$aOrderData += array(
					'orderFreightType' => $aCartEntry->reference,
					'orderFreight' => $aCartEntry->unitPrice, // Price with vat
					'orderFreightVat' => $aCartEntry->taxRate, // Vat as a price
					'orderFreightTypeTitle' => $aCartEntry->name
				);
			
			/**
			 * Type. `discount` by default, alternatively `discount`, `shipping_fee`
			 */		
			} elseif( $aCartEntry->type == 'discount' ) {
				if( PAYSON_CHECKOUT_DEBUG === true ) {
					clLogger::log( 'Started processing discount product ' . var_export($aCartEntry, true), 'paysonCheckoutCallback.log' );
				}
				
				// Discount
				$oDiscountCode = clRegistry::get( 'clDiscountCode', PATH_MODULE . '/discountCode/models' );
				
				// Set temporary access
				$oAcl = new clAcl();
				$oAcl->setAcl( array(
					'writeDiscountCode' => 'allow',
					'readDiscountCode' => 'allow'
				));
				$oDiscountCode->setAcl( $oAcl );

				$aCodeData = current( $oDiscountCode->read('*', $aCartEntry->reference) );
				$aOrderData += array(
					'orderDiscountCodeKey' => $aCodeData['codeKey'],
					'orderDiscountCodeDiscount' => $aCodeData['codeDiscount'],
					'orderDiscountCodeType' => $aCodeData['codeDiscountType']
				);
				$oDiscountCode->update( $aCodeData['codeKey'], array(
					'codeCount' => $aCodeData['codeCount'] + 1
				) );
				
				// Reset access to default
				$oDiscountCode->setAcl( $oUser->oAcl );
				
				$aErr += clErrorHandler::getValidationError( 'updateDiscountCode' );
				if( !empty($aErr) ) {
					if( PAYSON_CHECKOUT_DEBUG === true ) {
						clLogger::log( $aErr, 'paysonCheckoutCallback.log' );
					}
				}
				
			} else {
				/**
				 * Error:
				 * Something unhandled and unexpected
				 */
				if( PAYSON_CHECKOUT_DEBUG === true ) {
					clLogger::log( 'Unexpected item value: ' . var_export( $aCartEntry, true ), 'paysonCheckoutCallback.log' );
				}
			}
		}
	}
	
	// Temporary write access	
	$this->oAcl = new clAcl();
	$this->oAcl->setAcl( array(
		'readOrderHistory' => 'allow',
		'writeOrderHistory' => 'allow'
	) );
	$oOrderHistory->setAcl( $this->oAcl );
	
	/**
	 * Create order
	 */
	if( $iOrderId = $oOrder->create($aOrderData) ) {
		if( PAYSON_CHECKOUT_DEBUG === true ) {
			clLogger::log( 'Created order ' . $iOrderId, 'paysonCheckoutCallback.log' );
		}
		
		// Create order lines
		foreach( $aOrderLines as $aOrderLine ) {
			$aOrderLine['lineOrderId'] = $iOrderId;
			$iOrderLineId = $oOrderLine->create( $aOrderLine );	
		}
		
		// Order history
		$oOrderHistory->create( array(
			'orderHistoryOrderId' => $iOrderId,
			'orderHistoryUserId' => ( !empty($aOrderData['orderUserId']) ? $aOrderData['orderUserId'] : 0 ),
			'orderHistoryGroupKey' => 'Payment',
			'orderHistoryMessage' => _( "Order created by Klarna's callback" ), # The order was created by the Klarna callback.
			'orderHistoryData' => ''
		) );	
		
	} else {
		/**
		 * Something went wrong when creating the order
		 */
		$aErr += clErrorHandler::getValidationError( 'createOrder' );		
		clLogger::log( 'Error creating order: ' . var_export($aErr, true), 'paysonCheckoutCallback.log' );		
		@mail(PAYSON_CHECKOUT_ERROR_EMAIL, 'PaysonCheckoutError',  'Error creating order: ' . var_export($aErr, true) . ' ' . var_export($oKlarnaOrder, true) ); // TODO remove after watch period
	}

	if( empty($aErr) ) {
		try {
			/**
			 * Update external order data
			 */
			$bResult = $oPaymentPaysonCheckout->updateReference( $sCheckoutId, 'orderID-' . $iOrderId );
			if( PAYSON_CHECKOUT_DEBUG === true ) {
				clLogger::log( 'Updated order reference: ' . $bResult, 'paysonCheckoutCallback.log' );
			}
			
			/**
			 * Add order history
			 */
			$oOrderHistory->create( array(
				'orderHistoryOrderId' => $iOrderId,
				'orderHistoryUserId' => ( !empty($aOrderData['orderUserId']) ? $aOrderData['orderUserId'] : 0 ),
				'orderHistoryGroupKey' => 'Payment',
				'orderHistoryMessage' => _( "Payson's System was notified that the order has been created" ),
				'orderHistoryData' => ''
			) );
			
		} catch( Exception $e ) {
			/**
			 * Exception
			 */
			if( PAYSON_CHECKOUT_DEBUG === true ) {
				clLogger::log( 'Exception when updating order ' . $iOrderId . ': ' . $e->getMessage(), 'paysonCheckoutCallback.log' );			
				@mail( PAYSON_CHECKOUT_ERROR_EMAIL, 'PaysonCheckoutError',  'Exception when updating order ' . $iOrderId . ': ' . $e->getMessage() ); // TODO remove after watch period
			}
		}
	}
	
	if( PAYSON_CHECKOUT_DEBUG === true ) {
		clLogger::log( 'Ended callback', 'paysonCheckoutCallback.log' );
	}
	
	
} else {
	
	if( $oCheckout->status == 'canceled' ) {
		// Purchase has been canceled		
		#print_r($checkout);
		
	} elseif( $oCheckout->status == 'denied' ) {
		// The purchase is denied by any reason
		#print_r($checkout);
			
	} else {
		// Something happened when
		#print_r($checkout);
		
	}
	
}