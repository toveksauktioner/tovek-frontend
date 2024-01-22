<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentKlarnaCheckout.php';
require_once PATH_MODULE . '/payment/api/klarnaCheckout/Checkout.php';

/**
 * Klarna Checkout
 * 
 * Notes:
 * Does not support additional payment costs, as klarna advises against this (and probably enforces it)
 * 
 * Currently supporting:
 * Sweden 		Swedish 	SE 	SEK 	sv-se
 * Finland 		Finnish 	FI 	EUR 	fi-fi
 * Finland 		Swedish 	FI 	EUR 	sv-fi
 * Norway 		Norwegian 	NO 	NOK 	nb-no
 */

class clPaymentKlarnaCheckout extends clPaymentBase {
	
	/**
	 * Currencies in use
	 */
	public $aPlatformLocalesToKlarnaLocales;

	public function __construct() {
		$this->initBase();
		$this->aPlatformLocalesToKlarnaLocales = $GLOBALS['platformLocalesToKlarnaLocales'];
	}
	
	public function init() {
		$oRouter->redirect( KLARNA_CHECKOUT_URI_CHECKOUT );
	}

	public function getCheckoutIframe( $aAssembledData = array() ) {
		/**
		 * Basic Klarna flow
		 * Step 1: Create Checkout order at Klarna
		 * Step 2: Previous step returned a Klarna checkout order URI. Fetch that resource.
		 * Step 3: Render Klarna’s Checkout in an iframe
		 * 
		 * ['merchant_reference']['orderid1'] is used for the order id
		 * ['merchant_reference']['orderid2'] is used for the platform user, or empty if guest order
		 */
		
		$oRouter = clRegistry::get( 'clRouter' );
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
		
		if( empty($aAssembledData) ) {
			return;
			//$oRouter->redirect( $oRouter->getPath('guestProductCart') );
		}
		
		/**
		 * Read countries
		 */
		$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
		$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
			'countryId',
			'countryName',
			'countryIsoCode2',
			'countryIsoCode3'
		) );
		$aCountriesById = array();
		foreach( $aCountries as $aEntry ) {
			$aCountriesById[ $aEntry['countryId'] ] = $aEntry;
		}
		
		$aKlarnaCart = array();
		$fTotalProductPrice = 0;
		$fTotalPrice = 0;
		
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
			
			$aKlarnaCart[] = array(
				'type' => 'physical', // Type. `physical` by default, alternatively `discount`, `shipping_fee`
				//'ean' => $aEntry['productEan'], // Not used on this shop
				'reference' => $aEntry['productId'], //  Reference, usually the article number (we are using productId to be able to properly identify the product later)
				'name' => $aEntry['templateTitleTextId'] . ( !empty($aEntry['attributes']) ? ' (' . implode(', ', $aEntry['attributes']) . ')' : '' ),
				'uri' => (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . $aEntry['routePath'],
				// 'image_uri' => '',
				'quantity' => (int) $aEntry['itemQuantity'],
				'unit_price' => $fProductUnitPriceWithVat * 100, // Unit price in cents, including tax
				
				//'total_price_excluding_tax' => $fProductUnitPriceWithoutVat * $cartEntry['quantity'], // Total price (excluding tax) in cents (READ ONLY)
				//'total_tax_amount' => $fVatTotal, // Total tax amount, in cents (READ ONLY)
				//'total_price_including_tax' => $fProductUnitPriceWithVat * $cartEntry['quantity'], // Total price (including tax) in cents (READ ONLY)
				
				//'discount_rate' => '', // Percentage of discount, multiplied by 100 and provided as an integer. i.e. 9.57% should be sent as 957
				'tax_rate' => $aEntry['productVat'] * 100 * 100 // Percentage of tax rate, multiplied by 100 and provided as an integer. i.e. 13.57% should be sent as 1357
			);			
		}
		
		/**
		 * Add the special discount product if applicable
		 */
		if( !empty($aAssembledData['discount']['codeData']) && $aAssembledData['discount']['codeData']['codeDiscountType'] === 'fixedAmount' && $aAssembledData['discount']['codeData']['codeRange'] == 'global' ) {			
			$aCodeData =& $aAssembledData['discount']['codeData'];
			
			// Check and adjust discount so that we won't give discount on the freight
			//if( $aCodeData['codeDiscount'] > $fTotalProductPrice ) {
			//	$aCodeData['codeDiscount'] = $fTotalProductPrice;
			//}
			
			$aKlarnaCart[] = array(
				'type' => 'discount', // Type. `physical` by default, alternatively `discount`, `shipping_fee`
				//'ean' => $aEntry['productEan'], // Not used on this shop
				'reference' => $aCodeData['codeKey'], //  Reference, usually the article number (we are using productId to be able to properly identify the product later)
				'name' => _( 'Discount code' ),
				//'uri' => (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . $aEntry['routePath'],
				// 'image_uri' => '',
				'quantity' => 1,
				'unit_price' => -$aCodeData['codeDiscount'] * 100, // Unit price in cents, including tax				
				
				//'total_price_excluding_tax' => $fProductUnitPriceWithoutVat * $cartEntry['quantity'], // Total price (excluding tax) in cents (READ ONLY)
				//'total_tax_amount' => $fVatTotal, // Total tax amount, in cents (READ ONLY)
				//'total_price_including_tax' => $fProductUnitPriceWithVat * $cartEntry['quantity'], // Total price (including tax) in cents (READ ONLY)
				
				//'discount_rate' => '', // Percentage of discount, multiplied by 100 and provided as an integer. i.e. 9.57% should be sent as 957
				'tax_rate' => 2500 // Percentage of tax rate, multiplied by 100 and provided as an integer. i.e. 13.57% should be sent as 1357
			);
			
			$fTotalPrice -= $aCodeData['codeDiscount'];
			if( $fTotalPrice < 0 ) $fTotalPrice = 0;
		}
		
		/**
		 * Add shipping/freight fee
		 */
		if( !empty($aAssembledData['freight']) ) {
			$aKlarnaCart[] = array(
				'type' => 'shipping_fee',
				'name' => $aAssembledData['freight']['title'],
				'reference' => $aAssembledData['freight']['id'],
				'quantity' => 1,
				'unit_price' => $aAssembledData['freight']['price'] * 100, // In cents, including tax
				'tax_rate' => 25 * 100
			);
		}
		
		$aCreate = array();
		$aUpdate = array();
		
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
		
		$oOrder = null;
		
		if( KLARNA_CHECKOUT_KEEP_SESSIONS === false ) {
			unset( $_SESSION['klarna_order_id'] );
		}
		
		/**
		 * Existing checkout check
		 */
		if( array_key_exists('klarna_order_id', $_SESSION) ) {
			// Resume session
			$oOrder = new Klarna_Checkout_Order(
				$oConnector,
				$_SESSION['klarna_order_id']
			);
			
			try {
				$oOrder->fetch();
				
				/**
				 * Update shipping country
				 * Note: Apparantly klarna can't update shipping country. Unset the klarna session and redirect to this page again
				 */
				if( strtolower($oOrder['purchase_country']) != strtolower(
					array_key_exists($aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'], $this->aPlatformLocalesToKlarnaLocales) ?
					$this->aPlatformLocalesToKlarnaLocales[ $aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'] ]['purchase_country'] : 'SE' )
				) {
					unset($_SESSION['klarna_order_id']);
					$oRouter->redirect( $oRouter->sPath . '?' . stripGetStr() );
				}
				
				/**
				 * Reset cart and update freight
				 * Note: freight is stored as a product
				 */
				$aUpdate = array(
					'cart' => array(
						'items' => $aKlarnaCart
					),
					'purchase_country' => (
						array_key_exists($aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'], $this->aPlatformLocalesToKlarnaLocales) ?
						$this->aPlatformLocalesToKlarnaLocales[ $aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'] ]['purchase_country'] : 'SE'
					),
					'purchase_currency' => (
						array_key_exists($aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'], $this->aPlatformLocalesToKlarnaLocales) ?
						$this->aPlatformLocalesToKlarnaLocales[ $aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'] ]['purchase_currency'] : 'SEK'
					),
					'locale' => (
						array_key_exists($aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'], $this->aPlatformLocalesToKlarnaLocales) ?
						$this->aPlatformLocalesToKlarnaLocales[ $aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'] ]['locale'] : 'sv-se'
					)
				);				
				
				//$create['locale'] = 'sv-se';
				
				// Update
				//$oOrder->update( $aUpdate );
				
			} catch( Klarna_Checkout_ApiErrorException $oException ) {			
				// Reset session
				$oOrder = null;
				unset( $_SESSION['klarna_order_id'] );
				echo 'Caught exception: ',  $oException->getMessage(), "\n"; // TODO prettify
			}
		}
		
		/**
		 * New checkout
		 */
		if( $oOrder == null ) {
			// Start new session
			$aCreate = array(
				'purchase_country' => (
					array_key_exists($aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'], $this->aPlatformLocalesToKlarnaLocales) ?
					$this->aPlatformLocalesToKlarnaLocales[ $aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'] ]['purchase_country'] : 'SE'
				),
				'purchase_currency' => (
					array_key_exists($aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'], $this->aPlatformLocalesToKlarnaLocales) ?
					$this->aPlatformLocalesToKlarnaLocales[ $aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'] ]['purchase_currency'] : 'SEK'
				),
				'locale' => (
					array_key_exists($aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'], $this->aPlatformLocalesToKlarnaLocales) ?
					$this->aPlatformLocalesToKlarnaLocales[ $aCountriesById[ $GLOBALS['checkout_paymentCountry'] ]['countryIsoCode2'] ]['locale'] : 'sv-se'
				),  //'locale' => 'sv-se'
				'merchant' => array(
					'id' => KLARNA_CHECKOUT_ID,
					'terms_uri' => KLARNA_CHECKOUT_URI_TERMS,
					'checkout_uri' => KLARNA_CHECKOUT_URI_CHECKOUT,
					'confirmation_uri' => KLARNA_CHECKOUT_URI_CONFIRMATION,
					// You can not receive push notification on non publicly available uri
					'push_uri' => KLARNA_CHECKOUT_URI_PUSH
				),
				'merchant_reference' => array(
					// is used for the order id
					//'orderid1' => !empty($_SESSION['orderId']) ? (string) $_SESSION['orderId'] : '0',
					
					// is used for the platform user, or empty if guest order
					'orderid2' => (
						// TODO Test guest order
						!empty($_SESSION['userId']) ? (string) $_SESSION['userId'] : '0'
					)					
				),
				'cart' => array(
					'items' => $aKlarnaCart
				)
			);
			
			// Create
			$oOrder = new Klarna_Checkout_Order( $oConnector );
			$oOrder->create( $aCreate );
			
			// Get created
			$oOrder->fetch();
		}
		
		/**
		 * Store order id of checkout session
		 */
		$_SESSION['klarna_order_id'] = $sessionId = $oOrder['id'];
		
		/**
		 * Keep extra track of the checkout
		 */
		$oCheckoutPending = clRegistry::get( 'clCheckoutPending', PATH_MODULE . '/checkout/models' );
		$oCheckoutPending->keepTrack( $aAssembledData['payment']['id'], $oOrder['id'] );
		
		/**
		 * Display checkout
		 */
		$sSnippet = $oOrder['gui']['snippet'];
		// DESKTOP: Width of containing block shall be at least 750px
		// MOBILE: Width of containing block shall be 100% of browser window (No padding or margin)
		return $sSnippet;
	}

	public function getCheckoutConfirmation() {		
		if( empty($_GET['klarna_order_id']) ) {
			// Not a proper order
			$oRouter = clRegistry::get( 'clRouter' );
			$oRouter->redirect( '/' );
			return;		
		}
		
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
		
		// Fetch Klarna order data
		$oOrder = new Klarna_Checkout_Order( $oConnector, $_GET['klarna_order_id'] );
		$oOrder->fetch();
		
		if( KLARNA_CHECKOUT_DEBUG ) {
			clFactory::loadClassFile( 'clLogger' );
			clLogger::log( $oOrder, 'klarnaCheckoutTest.log' );
		}
		
		if( $oOrder['status'] != 'created' ) {
			$oRouter = clRegistry::get( 'clRouter' );
			$oRouter->redirect( KLARNA_CHECKOUT_URI_CONFIRMATION . '?klarna_order_id=' . $_GET['klarna_order_id'] );
			return;
		}
		
		/**
		 * Extra finalize check / fallback
		 */
		$oCheckoutPending = clRegistry::get( 'clCheckoutPending', PATH_MODULE . '/checkout/models' );
		$oCheckoutPending->finalizeCheck( $oOrder['merchant_reference']['orderid1'] );
		
		// Clean up all notification
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->aNotifications = array();
		
		if( !empty($_SESSION['klarna_order_id']) ) {
			// End Klarna checkout session
			unset( $_SESSION['klarna_order_id'] );
		}
		
		/**
		 * Output
		 */
		$sSnippet = $oOrder['gui']['snippet'];
		// DESKTOP: Width of containing block shall be at least 750px
		// MOBILE: Width of containing block shall be 100% of browser window (No padding or margin)		
		return $sSnippet;
	}

	public function renderInSiteView( $aAssembledData = array() ) {
		if( empty($aAssembledData['cart']) || empty($aAssembledData['freight']) || empty($aAssembledData['discount']) ) {
			return false;
		}
		return $this->getCheckoutIframe( $aAssembledData );
	}
	
	public function checkStatus() {
		return true;
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
