<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentDibsEasy.php';

/**
 * Dibs Easy Checkout based payment method
 */

class clPaymentDibsEasy extends clPaymentBase {

	private $bTestMode = DIBS_EASY_TEST_MODE;
	
	private $sSecretKey = DIBS_EASY_SECRET_KEY;
	private $sCheckoutKey = DIBS_EASY_CHECKOUT_KEY;
	
	private $sUrlApi = DIBS_EASY_API_URL;
	private $sUrlCheckout = DIBS_EASY_CHECKOUT_URL;
	
	private $sCheckoutUrl = LOCAL_CHECKOUT_URL;
	private $sCallbackUrl = LOCAL_CALLBACK_URL;
	private $sConfirmUrl = LOCAL_CONFIRMATION_URL;
	private $sTermsUrl = LOCAL_TERMS_URL;
	
	private $aShippingCountries;
	
	private $oCurl;

	public function __construct() {
		$this->initBase();
		
		$this->aShippingCountries = $GLOBALS['dibsEasyShippingCountries'];
		
		/**
		 * Init cURL for communication
		 */
		$this->oCurl = clFactory::create( 'clCurl', null, array(
			'header' => false
		) );
		$this->oCurl->addSendHeaders( array(
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'Authorization' => $this->sSecretKey
		) );
	}

	/**
	 * Default function to be called by AromaCMS checkout
	 */
	public function renderInSiteView( $aAssembledData = array() ) {
		if( empty($aAssembledData['cart']) || empty($aAssembledData['freight']) || empty($aAssembledData['discount']) ) {
			return false;
		}
		return $this->getCheckoutIframe( $aAssembledData );
	}

	/**
	 * Initiate and assamble data to get iFrame snippet
	 */
	private function getCheckoutIframe( $aAssembledData = array() ) {
		if( empty($aAssembledData) ) return false;
		
		$fTotalProductPrice = 0;
		$fTotalPrice = 0;
		$aItems = array();
		
		/**
		 * Order items
		 */
		foreach( $aAssembledData['cart']['items'] as $aEntry ) {
			// Calculate some prices
			$fProductPrice = removeVat( $aEntry['productPriceWithDiscount'], $aEntry['productVat'] );
			$fProductUnitPriceWithVat = calculatePrice( $fProductPrice, array(
				'profile' => 'default',
				'additional' => array(
					'vatInclude' => true,
					'vat' => $aEntry['productVat'],
					'decimals' => 6
				)
			) );
			$fProductUnitPriceWithoutVat = calculatePrice( $fProductPrice, array(
				'profile' => 'default',
				'additional' => array(
					'vatInclude' => false,
					'decimals' => 6
				)
			) );
			
			//$fVatTotal = ($fProductUnitPriceWithVat * $cartEntry['quantity']) - ($fProductUnitPriceWithoutVat * $cartEntry['quantity']);
			$fTotalProductPrice += ($fProductUnitPriceWithVat * $aEntry['itemQuantity']);
			$fTotalPrice += ($fProductUnitPriceWithVat * $aEntry['itemQuantity']);
			
			// Product title
			$sProductTitle = $aEntry['templateTitleTextId'] . ( !empty($aEntry['attributes']) ? ' (' . implode(', ', $aEntry['attributes']) . ')' : '' );
			
			// Product reference
			$sProductReference = ( !empty($aEntry['productEan']) ? $aEntry['productEan'] : (!empty($aEntry['productSku']) ? $aEntry['productSku'] : $aEntry['productId']) );
			
			$fTaxAmount = $aEntry['productVat'] * ( $aEntry['productPrice'] * $aEntry['itemQuantity'] );
			$fTaxAmount = calculatePrice( $fTaxAmount );
			
			/**
			 * Add item
			 */
			$aItems[] = array(
				'reference' => $aEntry['productId'],
				'name' => $sProductTitle,
				'quantity' => $aEntry['itemQuantity'],
				'unit' => $aEntry['productWeight'] . 'g',
				'unitPrice' => $aEntry['productPrice'] * 100,
				'taxRate' => $aEntry['productVat'] * 10000,
				'taxAmount' => $fTaxAmount * 100,
				'grossTotalAmount' => ( calculatePrice( $aEntry['productPrice'], array(
					'profile' => 'default',
					'additional' => array(
						'vatInclude' => true,
						'vat' => $aEntry['productVat'],
						'decimals' => 2
					) ) ) * $aEntry['itemQuantity'] ) * 100,
				'netTotalAmount' => ( $aEntry['itemQuantity'] * $aEntry['productPrice'] ) * 100
			);
		}
		
		/**
		 * Add the special discount product if applicable
		 */
		if( !empty($aAssembledData['discount']['codeData']) && $aAssembledData['discount']['codeData']['codeDiscountType'] === 'fixedAmount' && $aAssembledData['discount']['codeData']['codeRange'] == 'global' ) {		
			$aCodeData =& $aAssembledData['discount']['codeData'];
			
			$aItems[] = array(
				'reference' => _( 'Discount' ),
				'name' => $aCodeData['codeKey'],
				'quantity' => 1,
				'unit' => 0,
				'unitPrice' => -$aCodeData['codeDiscount'],
				'taxRate' => 0,
				'taxAmount' => 10000,
				'grossTotalAmount' => -$aCodeData['codeDiscount'],
				'netTotalAmount' => -$aCodeData['codeDiscount']
			);
			
			$fTotalPrice -= $aCodeData['codeDiscount'];
		}
		
		/**
		 * Add shipping/freight fee
		 */
		if( !empty($aAssembledData['freight']) ) {
			$aItems[] = array(
				'reference' => _( 'Freight' ),
				'name' => $aAssembledData['freight']['title'],
				'quantity' => 1,
				'unit' => 0,
				'unitPrice' => removeVat( $aAssembledData['freight']['price'], 0.25 ) * 100,
				'taxRate' => ( 25 * 100 ),
				'taxAmount' => (25) * (removeVat( $aAssembledData['freight']['price'], 0.25 )),
				'grossTotalAmount' => $aAssembledData['freight']['price'] * 100, 
				'netTotalAmount' => number_format( removeVat( $aAssembledData['freight']['price'], 0.25 ) * 100, 0, '.', '' )
			);
			
			$fTotalPrice += $aAssembledData['freight']['price'];
		}
		
		/**
		 * Transform order amount format.
		 * update the total order-amount in the data-array
		 */
		$fTotalPrice = $fTotalPrice * 100;
		
		/**
		 * Reference,
		 * customer and user ID
		 */
		$sReference = '';
		if( !empty($_SESSION['customer']['customerId']) ) {
			$sReference .= $_SESSION['customer']['customerId'];
		} elseif( !empty($_SESSION['checkout']['customerId']) ) {
			$sReference .= $_SESSION['checkout']['customerId'];
		} else {
			$sReference .=	'0';
		}
		if( !empty($_SESSION['userId']) ) {
			$sReference .= '-' . $_SESSION['userId'];
		} elseif( !empty($_SESSION['checkout']['userId']) ) {
			$sReference .= '-' . $_SESSION['checkout']['userId'];
		} else {
			$sReference .=	'-0';
		}
		
		/**
		 * Assamble data
		 */
		$aData = array(
			'order' => array(
				'items' => $aItems,
				'amount' => $fTotalPrice,
				'currency' => $_SESSION['currency'],
				'reference' => $sReference
			),
			'checkout' => array(
				'url' => $this->sCheckoutUrl, // sCheckoutUrl,
				'termsUrl' => $this->sTermsUrl,
				'ShippingCountries' => $this->aShippingCountries
			)
		);
		
		/**
		 * Send data to Dibs
		 */
		$mResult = $this->oCurl->post( $aData, $this->sUrlApi . '/payments', 'json_encode' );
		
		switch( $mResult['info']['http_code'] ) {
			/**
			 * Success
			 */
			case 201:	
				$sResult = $mResult['data']['raw'];
				$sResult = json_decode( $sResult, true );
				
				$_SESSION['dibsEasyPaymentId'] = $sResult['paymentId'];
				
				if( DIBS_EASY_DEBUG === true ) {
					// Log
					clFactory::loadClassFile( 'clLogger' );
					clLogger::log( $mResult, 'dibsEasySuccessData.log' );
				}
				
				break;
			
			/**
			 * Error
			 */
			default:
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				
				if( !empty($mResult['data']['content']->errors) ) {					
					foreach( $mResult['data']['content']->errors as $oError ) {
						$oNotification->addError( $oError[0] );
					}
					
				} else {
					/**
					 * Unkown error
					 */
					$oNotification->addError( _( 'Unknown error' ) );
				}
				
				if( DIBS_EASY_DEBUG === true ) {
					// Log
					clFactory::loadClassFile( 'clLogger' );
					clLogger::log( $mResult, 'dibsEasyErrorData.log' );
				}
				
				break;
		}
		
		if( empty($_SESSION['dibsEasyPaymentId']) ) {
			return false;
		}
		
		$sSnippet = '
			<script type="text/javascript" src="' . $this->sUrlCheckout . '/checkout.js?v=1"></script>
			<div id="dibs-complete-checkout"></div>
			<script>
				/**
				 * Init Dibs Easy Checkout 
				 */
				var checkoutOptions = {
					checkoutKey: "' . $this->sCheckoutKey . '",
					paymentId: "' . $_SESSION['dibsEasyPaymentId'] . '",
					containerId: "dibs-complete-checkout"
					//language: "en-GB"
				};				
				var checkout = new Dibs.Checkout( checkoutOptions );
				
				/**
				 * On submit order (click "pay")
				 */
				checkout.on( "pay-initialized", function(response) {
					//checkout.send( "payment-order-finalized", true );
					
					/**
					 * Call callback for creating order
					 */
					var jqxhr = $.get( "' . $this->sCallbackUrl . '?paymentId=' . $_SESSION['dibsEasyPaymentId'] . '", function( data ) {
						//console.log( "success" );
					} )
					.done( function( data ) {
						console.log( "second success" );
						console.log( data );
						checkout.send( "payment-order-finalized", true );
					} )
					.fail( function( data ) {
						console.log( "error" );
						checkout.send( "payment-order-finalized", false );
					} )
					.always( function( data ) {
						console.log( "finished" );
					} );					
				} );
				
				/**
				 * On completed payment
				 */
				checkout.on( "payment-completed", function(response) {				
					window.setTimeout(
						window.location = "'. $this->sConfirmUrl . '",
						6000
					);
				} );
			</script>
		';
		
		/**
		 * Keep extra track of the checkout
		 */
		$oCheckoutPending = clRegistry::get( 'clCheckoutPending', PATH_MODULE . '/checkout/models' );
		$oCheckoutPending->keepTrack( $aAssembledData['payment']['id'], $_SESSION['dibsEasyPaymentId'], array(
			// Additional data
			'pendingPaymentCheckoutData' => json_encode( $aData ),
			'pendingFreightId' => $aAssembledData['freight']['id'],
			'pendingDiscountCode' => !empty($aAssembledData['discount']['codeData']) ? $aAssembledData['discount']['codeData']['codeKey'] : ''
		) );
		
		/**
		 * Display checkout
		 */
		return $sSnippet;
	}
	
	public function updatePaymentReference( $sPaymentId, $sReference ) {
		/**
		 * Send data to Dibs
		 */
		$aData = array(
			'reference' => $sReference,
			'checkoutUrl' => 'https://checkout.dibspayment.eu' // $this->sCallbackUrl // sCheckoutUrl, # to fix
		);
		$mResult = $this->oCurl->put( $aData, $this->sUrlApi . '/payments/' . $sPaymentId . '/referenceinformation', 'json_encode' );
		return true;
	}
	
	/**
	 * Dibs Easy do not have an own confirmation page
	 */
	//public function getCheckoutConfirmation() {}
	
	/**
	 * Fetch data about payment from Dibs
	 */
	public function fetchPaymentData( $sPaymentId ) {
		/**
		 * Send data to Dibs
		 */
		$mResult = $this->oCurl->get( $this->sUrlApi . '/payments/' . $sPaymentId, 'json_encode' );
		
		switch( $mResult['info']['http_code'] ) {
			/**
			 * Success
			 */
			case 200:	
				return json_decode( $mResult['data']['raw'], true );				
				break;
			
			/**
			 * Error
			 */
			default:
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				
				if( !empty($mResult['data']['content']->errors) ) {					
					foreach( $mResult['data']['content']->errors as $oError ) {
						$oNotification->addError( $oError[0] );
					}
					
				} else {
					/**
					 * Unkown error
					 */
					$oNotification->addError( _( 'Unknown error' ) );
				}
				
				if( DIBS_EASY_DEBUG === true ) {
					// Log
					clFactory::loadClassFile( 'clLogger' );
					clLogger::log( $mResult, 'dibsEasyErrorData.log' );
				}
				
				break;
		}
		
		return false;
	}
	
	/**
	 * This is called from receipt page and init finalize of order
	 */
	public function checkStatus() {
		if( empty($_SESSION['orderId']) ) return false;
		
		$sPaymentId = current(current( $this->oOrder->read( 'orderPaymentTransactionId', $_SESSION['orderId'] ) ));
		
		if( !empty($sPaymentId) ) {
			// Fetch payment data
			$aPaymentData = current( $this->fetchPaymentData( $sPaymentId ) );
			if( !empty($aPaymentData['summary']) ) {
				// If summery not empty we see this as successful
				$this->finalizeOrder( $_SESSION['orderId'] );
				
				/**
				 * Extra finalize check / fallback
				 */
				$oCheckoutPending = clRegistry::get( 'clCheckoutPending', PATH_MODULE . '/checkout/models' );
				$oCheckoutPending->finalizeCheck( $_SESSION['orderId'] );
				
				return true;
			}
		}
		
		return false;
	}

	/**
	 * This is probably not needed any more
	 */
	//public function getPaymentStatus( $sPaymentID ) {
	//	$oCurl = clRegistry::get( 'clCurl', null, array(
	//		'header' => false
	//	) );
	//	$aOptions = array(
	//		'Content-Type' => 'application/json',
	//		'Accept' => 'application/json',
	//		'Authorization' => DIBS_LIVE_SECRET
	//	);
	//	$oCurl->addSendHeaders( $aOptions );
	//	$oCurl->setEndPoint( DIBS_EASY_URL . '/' . $sPaymentID . '');
	//	$mResult = $oCurl->get();
	//	$_SESSION['DibsEasyReturn'] = $mResult;
	//	return $mResult;
	//}

	/**
	 * Finalize order
	 */
	public function finalizeOrder( $iOrderId ) {
		 $aData = array(
			'orderStatus' => 'new',
			'orderPaymentStatus' => 'paid'
		);
		$this->oOrder->update( $iOrderId, $aData );
		
		parent::finalizeOrder( $iOrderId );
		
		return true;
	}

}