<?php

$aErr = array();

clFactory::loadClassFile( 'clAcl' );
clFactory::loadClassFile( 'clLogger' );

$oPaymentKlarnaCheckout = clRegistry::get( 'clPaymentKlarnaCheckout', PATH_MODULE . '/payment/models' );
$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );
$oDiscountCode = clRegistry::get( 'clDiscountCode', PATH_MODULE . '/discountCode/models' );
$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );
$oOrderHistory = clRegistry::get( 'clOrderHistory', PATH_MODULE . '/order/models' );

if( KLARNA_CHECKOUT_DEBUG === true ) {
	clLogger::log( sprintf( 'Started callback by %s', $_SERVER['REQUEST_METHOD'] ), 'klarnaCheckoutCallback.log' );
	clLogger::log( 'GET: ' . var_export( $_GET, true ), 'klarnaCheckoutCallback.log' );
	clLogger::log( 'POST: ' . var_export( $_POST, true ), 'klarnaCheckoutCallback.log' );
}

try {
	/**
	 * Klarna init
	 */
	if( KLARNA_CHECKOUT_TEST_MODE ) {
		$oConnector = Klarna_Checkout_Connector::create(
			KLARNA_CHECKOUT_SECRET_KEY,
			Klarna_Checkout_Connector::BASE_TEST_URL
		);
		
	} else {
		$oConnector = Klarna_Checkout_Connector::create(
			KLARNA_CHECKOUT_SECRET_KEY,
			Klarna_Checkout_Connector::BASE_URL
		);
	}
	
	/**
	 * Fetch order from Klarna
	 */	
	@$sKlarnaOrderId = $_GET['klarna_order_id'];
	$oKlarnaOrder = new Klarna_Checkout_Order( $oConnector, $sKlarnaOrderId );
	$oKlarnaOrder->fetch();
	
	if( KLARNA_CHECKOUT_DEBUG === true ) {
		clLogger::log( 'Klarna: ' . var_export( $oKlarnaOrder, true ), 'klarnaCheckoutCallback.log' );
	}
	
	if( $oKlarnaOrder['status'] == 'checkout_complete' ) {
		/**
		 * Temporary write access
		 */
		$oAcl = new clAcl();
		$oAcl->setAcl( array(
			'readOrderHistory' => 'allow',
			'writeOrderHistory' => 'allow',		
			'readDiscountCode' => 'allow',
			'writeDiscountCode' => 'allow'
		) );
		$oOrderHistory->setAcl( $oAcl );
		$oDiscountCode->setAcl( $oAcl );
		
		/**
		 * Country name handling function
		 */
		function klarnaCountryToFulltext( $sKlarnaCountry = '' ) {
			$aKlarnaCountryToFulltext = array(
				'se' => 'Sweden',
				'fi' => 'Finland',
				'no' => 'Norway',
			);
			$sKlarnaCountry = strtolower( $sKlarnaCountry );
			$sCountryTitle = $aKlarnaCountryToFulltext[ $sKlarnaCountry ];
			
			/**
			 * All countries list
			 */
			$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
			$aCountries = arrayToSingle( $oContinent->aHelpers['oParentChildHelper']->readChildren( null, '*' ), 'countryName', 'countryId' );
			
			if( array_key_exists($sCountryTitle, $aCountries) ) return $aCountries[$sCountryTitle];
			else return ( array_key_exists($sKlarnaCountry, $aKlarnaCountryToFulltext) ? $aKlarnaCountryToFulltext[$sKlarnaCountry] : '' );
		}
		
		/**
		 * Note from Klarna:
		 * At this point make sure the order is created in your system and send a
		 * confirmation email to the customer.
		 */
		
		$aPaymentKlarnaData = current( $oPayment->readByClass( 'clPaymentKlarnaCheckout' ) );
		
		/**
		 * $oKlarnaOrder['merchant_reference']['orderid1'], equal to order ID
		 * empty equal to create order
		 */
		if( empty($oKlarnaOrder['merchant_reference']['orderid1']) ) {
			// Create order
			$aOrderData = array(
				'orderUserId' => $oKlarnaOrder['merchant_reference']['orderid2'],
				'orderEmail' => $oKlarnaOrder['billing_address']['email'],
				'orderMessage' => '',
				'orderUserPin' => '',
				'orderCustomerType' => 'privatePerson', // klarna only accepts "person" customer.type
				
				'orderDeliveryName' => $oKlarnaOrder['shipping_address']['given_name'] . " " . $oKlarnaOrder['shipping_address']['family_name'],
				'orderDeliveryAddress' => $oKlarnaOrder['shipping_address']['street_address'],
				'orderDeliveryZipCode' => $oKlarnaOrder['shipping_address']['postal_code'],
				'orderDeliveryCity' => $oKlarnaOrder['shipping_address']['city'],
				'orderDeliveryCountry' => klarnaCountryToFulltext($oKlarnaOrder['shipping_address']['country']),
				'orderDeliveryPhone' => $oKlarnaOrder['shipping_address']['phone'],
				
				'orderPaymentName' => $oKlarnaOrder['billing_address']['given_name'] . " " . $oKlarnaOrder['billing_address']['family_name'],
				'orderPaymentAddress' => $oKlarnaOrder['billing_address']['street_address'],
				'orderPaymentZipCode' => $oKlarnaOrder['billing_address']['postal_code'],
				'orderPaymentCity' => $oKlarnaOrder['billing_address']['city'],
				'orderPaymentCountry' => klarnaCountryToFulltext($oKlarnaOrder['billing_address']['country']),
				'orderPaymentPhone' => $oKlarnaOrder['billing_address']['phone'],
				//'orderPaymentCellPhone' => $oKlarnaOrder['billing_address'][''],
				//'orderPaymentReference' => $oKlarnaOrder['billing_address'][''],
				
				'orderPaymentType' => $aPaymentKlarnaData['paymentId'],
				'orderPaymentTypeTitle' => 'Klarna checkout',
				'orderPaymentCustomId' => $oKlarnaOrder['reference'], // End-consumer friendly reference
				'orderPaymentUrl' => (KLARNA_CHECKOUT_TEST_MODE ? KLARNA_CHECKOUT_TEST_MODE_URI : KLARNA_CHECKOUT_URI_BASE) . '/' . $oKlarnaOrder['id'],
				'orderPaymentTransactionId' => $oKlarnaOrder['reservation'], // Unique identifier of the Klarna Checkout Order
				
				//'orderPackageId' => '',
				
				'orderPaymentPrice' => '0',
				'orderPaymentPriceVat' => '0',
				
				'orderTotal' => ($oKlarnaOrder['cart']['total_price_excluding_tax'] / 100) + ($oKlarnaOrder['cart']['total_tax_amount'] / 100), // Should be with vat
				'orderVatTotal' => $oKlarnaOrder['cart']['total_tax_amount'] / 100, // Amount of vat in currency, not percentage
				'orderCurrency' => 'SEK', // TODO currency support
				'orderCurrencyRate' => '1', // TODO currency support
				
				'orderStatus' => 'intermediate',
				'orderPaymentStatus' => 'unpaid'
			);
			
			/**
			 * Order lines from Klarna order
			 */
			$aOrderLines = array();
			if( is_array($oKlarnaOrder['cart']['items']) ) {
				foreach( $oKlarnaOrder['cart']['items'] as $aCartEntry ) {
					/**
					 * Type. `physical` by default, alternatively `discount`, `shipping_fee`
					 */
					if( $aCartEntry['type'] == 'physical' ) {
						// Regular product
						// TODO test the vat calculation with more vat types
						$fPriceWithVat = $aCartEntry['unit_price'] / 100;
						$fVat = $aCartEntry['tax_rate'] / 100 / 100;
						$fPriceWithoutVat = removeVat( $fPriceWithVat, $fVat );
						
						$aProductData = current($oProduct->read( array('productCustomId'), $aCartEntry['reference'] ));
						
						$aOrderLines[] = array(
							'lineOrderId' => null, // We fill in this later
							'lineProductId' => $aCartEntry['reference'],
							'lineProductCustomId' => ( !empty($aProductData) ? $aProductData['productCustomId'] : '' ),
							'lineProductTitle' => $aCartEntry['name'],
							'lineProductQuantity' => $aCartEntry['quantity'],
							//'lineProductDeliveryTime' => $entry['productDeliveryTime'],
							//'lineProductWeight' => $entry['productWeight'],
							'lineProductPrice' => $fPriceWithoutVat, // One product, Price without VAT
							'lineProductVat' => $fVat // In percentage (0.25)
						);
					
					/**
					 * Type. `shipping_fee` by default, alternatively `discount`, `shipping_fee`
					 */	
					} elseif( $aCartEntry['type'] == 'shipping_fee' ) {
						// Shipping fee
						$aOrderData += array(
							'orderFreightType' => $aCartEntry['reference'],
							'orderFreight' => $aCartEntry['total_price_including_tax'] / 100, // Price with vat
							'orderFreightVat' => $aCartEntry['total_tax_amount'] / 100, // Vat as a price
							'orderFreightTypeTitle' => $aCartEntry['name']
						);
					
					/**
					 * Type. `discount` by default, alternatively `discount`, `shipping_fee`
					 */		
					} elseif( $aCartEntry['type'] == 'discount' ) {
						if( KLARNA_CHECKOUT_DEBUG === true ) {
							clLogger::log( 'Started processing discount product ' . var_export($aCartEntry, true), 'klarnaCheckoutCallback.log' );
						}
						
						$aCodeData = current( $oDiscountCode->read('*', $aCartEntry['reference']) );
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
						
					} else {
						/**
						 * Error:
						 * Something unhandled and unexpected
						 */
						$aErr[] = 'Unexpected item value: ' . var_export( $aCartEntry, true );
					}
				}
			}
			
			/**
			 * Create order
			 */
			if( $iOrderId = $oOrder->create($aOrderData) ) {
				if( KLARNA_CHECKOUT_DEBUG === true ) {
					clLogger::log( 'Created order ' . $iOrderId, 'klarnaCheckoutCallback.log' );
				}
				
				// Create order lines
				foreach( $aOrderLines as $aOrderLine ) {
					$aOrderLine['lineOrderId'] = $iOrderId;
					$iOrderLineId = $oOrderLine->create( $aOrderLine );	
				}
				
				// Order history
				$oOrderHistory->create( array(
					'orderHistoryOrderId' => $iOrderId,
					'orderHistoryUserId' => $oKlarnaOrder['merchant_reference']['orderid2'],
					'orderHistoryGroupKey' => 'Payment',
					'orderHistoryMessage' => _( "Order created by Klarna's callback" ), # The order was created by the Klarna callback.
					'orderHistoryData' => var_export( $oKlarnaOrder, true )
				) );
				
			} else {
				/**
				 * Something went wrong when creating the order
				 */
				$aErr += clErrorHandler::getValidationError( 'createOrder' );		
			}
		
		/**
		 * $oKlarnaOrder['merchant_reference']['orderid1'], equal to order ID
		 * not empty equal to update order
		 */
		} else {
			// Order ID from Klarna reference
			$iOrderId = $oKlarnaOrder['merchant_reference']['orderid1'];
			
			$oOrder->update( $iOrderId, array(
				'orderPaymentCustomId' => $oKlarnaOrder['reference'], // End-consumer friendly reference
				'orderPaymentUrl' => (KLARNA_CHECKOUT_TEST_MODE ? KLARNA_CHECKOUT_TEST_MODE_URI : KLARNA_CHECKOUT_URI_BASE) . '/' . $oKlarnaOrder['id'],
				'orderPaymentTransactionId' => $oKlarnaOrder['reservation'] // Unique identifier of the Klarna Checkout Order
			) );
			
			// Order history
			$oOrderHistory->create( array(
				'orderHistoryOrderId' => $iOrderId,
				'orderHistoryUserId' => $oKlarnaOrder['merchant_reference']['orderid2'],
				'orderHistoryGroupKey' => 'Payment',
				'orderHistoryMessage' => _( "Order updated by Klarna's callback" ), # The order was created by the Klarna callback.
				'orderHistoryData' => var_export( $oKlarnaOrder, true )
			) );
		}
		
		if( empty($aErr) ) {		
			/**
			 * Update Klarna order
			 */
			$aUpdate = array(
				'status' => 'created',				
				'merchant_reference' => array(
					// Update Klarna with order id's.
					// Note that we update orderid2 again with customer id because omitting the field will null it in Klarna
					'orderid1' => $iOrderId,
					'orderid2' => $oKlarnaOrder['merchant_reference']['orderid2'] // TODO test guest order, zero values etc
				)
			);
			$oKlarnaOrder->update( $aUpdate );
			
			/**
			 * Add to order history
			 */
			$oOrderHistory->create( array(
				'orderHistoryOrderId' => $iOrderId,
				'orderHistoryUserId' => ( !empty($aOrderData['orderUserId']) ? $aOrderData['orderUserId'] : 0 ),
				'orderHistoryGroupKey' => 'Payment',
				'orderHistoryMessage' => _( "Klarna's System was notified that the order has been created" ),
				'orderHistoryData' => ''
			) );
			
			// Finalize the order and send mail reciept
			$oPaymentKlarnaCheckout->finalizeOrder( $iOrderId );
		}
	} 
	
	if( !empty($aErr) ) {
		if( KLARNA_CHECKOUT_DEBUG === true ) {
			clLogger::log( $aErr, 'klarnaCheckoutCallback.log' );
			clLogger::log( $oKlarnaOrder, 'klarnaCheckoutCallback.log' );
		}
		
		// Notify developer by mail
		clLogger::log( 'Error creating order: ' . var_export($aErr, true), 'klarnaCheckoutCallback.log' );
		$sMail = 'Error creating order: ' . var_export( $aErr, true ) . ' ' . var_export( $oKlarnaOrder, true );
		@mail( KLARNA_CHECKOUT_ERROR_EMAIL, 'KlarnaCheckoutError', $sMail ); // TODO remove after watch period
	}
	
	clLogger::logRotate( 'klarnaCheckoutCallback.log', '8M' );

} catch( Throwable $oThrowable ) {
	/**
	 * Exception
	 */
	if( KLARNA_CHECKOUT_DEBUG === true ) {
		clLogger::log( 'Exception when updating order ' . $iOrderId . ': ' . $oThrowable->getMessage() . ' at line ' . $oThrowable->getLine() . ' in file ' . $oThrowable->getFile(), 'klarnaCheckoutCallbackException.log' );
		clLogger::logRotate( 'klarnaCheckoutCallbackException.log', '8M' );
	}
	
	// Notify developer by mail
	$sMail = 'Exception when updating order ' . $iOrderId . ': ' . $oThrowable->getMessage() . ' at line ' . $oThrowable->getLine() . ' in file ' . $oThrowable->getFile();
	@mail( KLARNA_CHECKOUT_ERROR_EMAIL, 'KlarnaCheckoutError',  $sMail ); // TODO remove after watch period
	
} catch( Klarna_Checkout_ApiErrorException $oException ) {
	/**
	 * Exception
	 */
	if( KLARNA_CHECKOUT_DEBUG === true ) {
		clLogger::log( 'Exception when updating order ' . $iOrderId . ': ' . $oException->getMessage() . ' at line ' . $oException->getLine() . ' in file ' . $oException->getFile(), 'klarnaCheckoutCallbackException.log' );
		clLogger::logRotate( 'klarnaCheckoutCallbackException.log', '8M' );
	}
	
	// Notify developer by mail
	$sMail = 'Exception when updating order ' . $iOrderId . ': ' . $oException->getMessage() . ' at line ' . $oException->getLine() . ' in file ' . $oException->getFile();
	@mail( KLARNA_CHECKOUT_ERROR_EMAIL, 'KlarnaCheckoutError',  $sMail ); // TODO remove after watch period
}