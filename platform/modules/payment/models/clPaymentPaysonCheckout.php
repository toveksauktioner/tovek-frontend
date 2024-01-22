<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentPaysonCheckout.php';
require_once PATH_MODULE . '/payment/api/paysonCheckout/lib/paysonapi.php';

/**
 * Payson Checkout module for Payson Checkout 2.0 API
 *
 * Notes:
 * Step 1: Set up details
 * Step 2 Create checkout
 * Step 3 Get checkout object
 * Step 4 Print out checkout html
 */

class clPaymentPaysonCheckout extends clPaymentBase {

	private $sMerchantId = PAYSON_CHECKOUT_MERCHANT_ID;
	private $sApiKey = PAYSON_CHECKOUT_API_KEY;
	private $bTestEnvironment = PAYSON_CHECKOUT_TEST_MODE;

	private $sCheckoutUri = PAYSON_CHECKOUT_URI_CHECKOUT;
	private $sConfirmationUri = PAYSON_CHECKOUT_URI_CONFIRMATION;
	private $sNotificationUri = PAYSON_CHECKOUT_URI_NOTIFICATION;
	private $sTermsUri = PAYSON_CHECKOUT_URI_TERMS;
	
	private $sGuiLocale = PAYSON_CHECKOUT_LOCALE;
	private $sGuiColorScheme = PAYSON_CHECKOUT_COLOR_SCHEME;
	private $sGuiVerfication = PAYSON_CHECKOUT_VERFICATION;
	private $bGuiRequestPhone = PAYSON_CHECKOUT_REQUEST_PHONE;
	
	private $oApi;
	
	public function __construct() {
		$this->initBase();
		
		// Initiate Payson API
		$this->oApi = new  PaysonEmbedded\PaysonApi( $this->sMerchantId, $this->sApiKey, $this->bTestEnvironment );
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
		
		/**
		 * Step 1: Set up details
		 */
		$oPaysonMerchant = new  PaysonEmbedded\Merchant( $this->sCheckoutUri, $this->sConfirmationUri, $this->sNotificationUri, $this->sTermsUri );		
		
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
		$oPaysonMerchant->reference = $sReference;
		
		// partnerId?
		//$oPaysonMerchant->partnerId = !empty($_SESSION['userId']) ? (string) $_SESSION['userId'] : '0';
		
		/**
		 * Currency
		 */
		switch( $_SESSION['currency'] ) {
			case 'SEK':
				$oPayData = new  PaysonEmbedded\PayData( PaysonEmbedded\CurrencyCode::SEK );
				break;
			default:
				$oPayData = new  PaysonEmbedded\PayData( PaysonEmbedded\CurrencyCode::SEK );
				break;
		}
		
		/**
		 * Order items
		 */
		$fTotalProductPrice = 0;
		$fTotalPrice = 0;
		$aItems = array();
		
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
			$sProductReference = ( !empty($aEntry['productEan']) ? $aEntry['productEan'] : (!empty($aEntry['productSku']) ? $aEntry['productSku'] : null) );
			
			/**
			 * @param string name - Name of order item. Max 128 characters,
			 * @param float unitPrice - Unit price incl. VAT,
			 * @param integer quantity - Quantity of this item. Can have at most 2 decimal places,
			 * @param float taxRate - Tax value. Not actual percentage. For example, 25% has to be entered as 0.25,
			 * @param string reference - Custom ID,
			 * @param string type - Order item type. Default 'OrderItemType::PHYSICAL',
			 * @param string discountRate - Discount. Default 'null',
			 * @param string ean,
			 * @param string uri,
			 * @param string imageUri
			 */
			$aItems[] = new  PaysonEmbedded\OrderItem(
				$sProductTitle, 
				($fProductUnitPriceWithVat * $aEntry['itemQuantity']), 
				$aEntry['itemQuantity'], 
				$aEntry['productVat'], 
				$aEntry['productId'],
				'physical',
				0,
				$sProductReference,
				(SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . $aEntry['routePath'],
				null
			);
		}
		
		/**
		 * Add discount
		 */
		
		/**
		 * Add the special discount product if applicable
		 */
		if( !empty($aAssembledData['discount']['codeData']) && $aAssembledData['discount']['codeData']['codeDiscountType'] === 'fixedAmount' && $aAssembledData['discount']['codeData']['codeRange'] == 'global' ) {		
			$aCodeData =& $aAssembledData['discount']['codeData'];

			// Check and adjust discount so that we won't give discount on the freight
			//if( $aCodeData['codeDiscount'] > $fTotalProductPrice ) {
			//	$aCodeData['codeDiscount'] = $fTotalProductPrice;
			//}

			$aItems[] = new  PaysonEmbedded\OrderItem(
				_( 'Discount code' ),
				-$aCodeData['codeDiscount'],
				1,
				0.25,
				$aCodeData['codeKey'],
				'discount'
			);
			
			$fTotalPrice -= $aCodeData['codeDiscount'];
			if( $fTotalPrice < 0 ) $fTotalPrice = 0;
		}
		
		
		/**
		 * Add shipping/freight fee
		 */
		if( !empty($aAssembledData['freight']) ) {			
			$aItems[] = new  PaysonEmbedded\OrderItem(
				$aAssembledData['freight']['title'],
				$aAssembledData['freight']['price'],
				1,
				0.25,
				$aAssembledData['freight']['id'],
				'fee'
			);
		}
		
		// Customer type
		$sCustomerType = 'person';
		if( !empty($_SESSION['checkout']['customerInfo']['groupId']) && $_SESSION['checkout']['customerInfo']['groupId'] != 1 ) {
			$sCustomerType = 'business';
		}
		
		if( PAYSON_CHECKOUT_DEBUG === true ) {
			// Reset Payson Checkout
			//unset( $_SESSION['payson_checkout'] );
		}
		
		/**
		 * Update
		 */
		if( !empty($_SESSION['payson_checkout']) ) {
			$oCheckout = $this->oApi->GetCheckout( $_SESSION['payson_checkout'] );
		
			if( $oCheckout->status == 'canceled' ) {
				unset( $_SESSION['payson_checkout'] );
				$oRouter = clRegistry::get( 'clRouter' );
				$oRouter->redirect( $oRouter->sPath );
			}
			
			// Update items
			$oCheckout->payData->items = array();			
			foreach( $aItems as $aItem ) {
				$oCheckout->payData->items[] = $aItem;
			}
			
			$oCheckout->merchant->reference = !empty($_SESSION['userId']) ? (string) $_SESSION['userId'] : '0';
			//$oCheckout->merchant->partnerId = !empty($_SESSION['userId']) ? (string) $_SESSION['userId'] : '0';
			
			/**
			 * Step 2 & 3 Update checkout
			 */
			$oCheckout = $this->oApi->UpdateCheckout( $oCheckout );
			
		
		/**
		 * Create
		 */	
		} else {
			foreach( $aItems as $aItem ) {
				$oPayData->AddOrderItem( $aItem );
			}
			
			$oGui = new  PaysonEmbedded\Gui( $this->sGuiLocale, $this->sGuiColorScheme, $this->sGuiVerfication, $this->bGuiRequestPhone );
			$oCustomer = new  PaysonEmbedded\Customer( 'Guest' );
			$oCustomer->type = $sCustomerType;
			$oCheckout = new  PaysonEmbedded\Checkout( $oPaysonMerchant, $oPayData, $oGui, $oCustomer );
			
			/**
			 * Step 2 Create checkout
			 */
			$mCheckoutId = $this->oApi->CreateCheckout( $oCheckout );
			
			/**
			 * Step 3 Get checkout object
			 */
			$oCheckout = $this->oApi->GetCheckout( $mCheckoutId );
			
			/**
			 * Store location of checkout session
			 */
			$_SESSION['payson_checkout'] = $oCheckout->id;
		}
		
		if( !empty($oCheckout) ) {
			/**
			 * Keep extra track of the checkout
			 */
			$oCheckoutPending = clRegistry::get( 'clCheckoutPending', PATH_MODULE . '/checkout/models' );
			$oCheckoutPending->keepTrack( $aAssembledData['payment']['id'], $oCheckout->id );
			
			/**
			 * Display checkout
			 */
			$sSnippet = $oCheckout->snippet;
			return $sSnippet;
		}
		
		return false;
	}
	
	public function getCheckoutConfirmation() {
		if( empty($_SESSION['payson_checkout']) ) {
			return false;
		}
		
		// Fetch data
		$oCheckout = $this->getCheckout( $_SESSION['payson_checkout'] );
		
		if( $oCheckout->status == 'readyToShip' ) {
			if( PAYSON_CHECKOUT_DEBUG === true ) {
				clFactory::loadClassFile( 'clLogger' );
				clLogger::log( 'Order ID returned by Payson: ' . $oCheckout->merchant->reference, 'paysonCheckoutCallback.log' );
			}
			
			$iAttempts = 1;
			while( strpos( $oCheckout->merchant->reference, 'orderID' ) === false ) {
				// Wait 2 seconds between attempts
				sleep( 2 );
				
				// Re-fetch data
				$oCheckout = $this->getCheckout( $_SESSION['payson_checkout'] );
				
				if( PAYSON_CHECKOUT_DEBUG === true ) {
					clFactory::loadClassFile( 'clLogger' );
					clLogger::log( 'Re-try, Order ID returned by Payson: ' . $oCheckout->merchant->reference, 'paysonCheckoutCallback.log' );
				}
				
				$iAttempts++;
				
				if( $iAttempts >= 5 ) {
					// Timeout after 10 seconds			
					break;
				}
			}
			
			if( strpos( $oCheckout->merchant->reference, 'orderID' ) === false ) {
				// Something went wrong here..
				$oRouter = clRegistry::get( 'clRouter' );
				unset( $_SESSION['payson_checkout'] );
				$oRouter->redirect( PAYSON_CHECKOUT_URI_CHECKOUT . '?error=true&token=' . $_SESSION['payson_checkout'] );
				return false;
			}
			
			$aReference = explode( '-', $oCheckout->merchant->reference );			
			$this->finalizeOrder( $aReference[1] );
			
			/**
			 * Extra finalize check / fallback
			 */
			$oCheckoutPending = clRegistry::get( 'clCheckoutPending', PATH_MODULE . '/checkout/models' );
			$oCheckoutPending->finalizeCheck( $aReference[1] );
			
			// Clean up all notification
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->aNotifications = array();
			
			/**
			 * Display checkout confirmation
			 */			
			$sSnippet = $oCheckout->snippet;
			unset( $_SESSION['payson_checkout'] );
			return $sSnippet;
		}
		
		return false;
	}
	
	public function getCheckout( $sCheckoutId ) {
		return $this->oApi->GetCheckout( $sCheckoutId );
	}

	public function updateReference( $sCheckoutId, $sReference ) {
		$oCheckout = $this->oApi->GetCheckout( $sCheckoutId );
		$oCheckout->merchant->reference = $sReference;
		return $this->oApi->UpdateCheckout( $oCheckout );
	}
	
	public function shipCheckout( $sCheckoutId ) {
		$oCheckout = $this->oApi->GetCheckout( $sCheckoutId );
		$oCheckout->status = 'shipped';
		return $this->oApi->UpdateCheckout( $oCheckout );
	}
	
	public function cancelCheckout( $sCheckoutId ) {
		return $this->oApi->CancelCheckout( $this->getCheckout( $sCheckoutId ) );
	}
	
	public function checkStatus() {
		return true;
	}

	private function validateCredentials() {
		return $this->oApi->Validate();
	}
	
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
