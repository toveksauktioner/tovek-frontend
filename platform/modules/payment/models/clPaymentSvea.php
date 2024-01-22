<?php

/* ********************************************
 * Documentation on https://checkoutapi.svea.com/docs/#/getting-started
 ******************************************** */

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentSvea.php';
include PATH_PLATFORM . '/composer/vendor/autoload.php';

class clPaymentSvea extends clPaymentBase implements ifPaymentMethod {

	public function __construct() {
		$this->initBase();
	}

	public function init( $iInvoiceOrderId, $aParams = array() ) {
		$this->invoiceOrderId = $iInvoiceOrderId;
	}
	public function checkStatus() {}
	public function finalizeOrder( $iInvoiceOrderId ) {}



	// API functions

  public function sveaGetCart( $aCart ) {
		$aCartData = [
			'countryCode' => 'SE',
			'currency' => 'SEK',
			'locale' => 'sv-SE',
      'clientOrderNumber' => $this->invoiceOrderId,
			'merchantSettings' => [
				'termsUri' => SVEA_TERMS_URI,
				'checkoutUri' => SVEA_CHECKOUT_URI,
				'confirmationUri' => SVEA_CONFIRM_URI . '?iOI=' . $this->invoiceOrderId,
				'pushUri' => SVEA_PUSH_URI . '?sveaOrderId={checkout.order.uri}'
			],
			'cart' => $aCart
		];

		try {
			$sBaseUrl = ( SVEA_TEST_MODE ? \Svea\Checkout\Transport\Connector::TEST_BASE_URL : \Svea\Checkout\Transport\Connector::PROD_BASE_URL );
	    $oConn = \Svea\Checkout\Transport\Connector::init( SVEA_MERCHANT_ID, SVEA_CHECKOUT_SECRET, $sBaseUrl );
	    $oCheckoutClient = new \Svea\Checkout\CheckoutClient( $oConn );

	    /**
	     * Example of creating the order and getting the response data
	     */

	    $oResponse = $oCheckoutClient->create( $aCartData );

	    return [
	    	'result' => 'success',
	    	'response' => $oResponse
	    ];

		} catch (\Svea\Checkout\Exception\SveaApiException $ex) {
		    return [
		    	'result' => 'failure',
		    	'error' => 'Api errors',
		    	'errorDetailed' => $ex->getCode() . ': ' . $ex->getMessage()
		    ];

		} catch (\Svea\Checkout\Exception\SveaConnectorException $ex) {
		    return [
		    	'result' => 'failure',
		    	'error' => 'Conn errors',
		    	'errorDetailed' => $ex->getCode() . ': ' . $ex->getMessage()
		    ];

		} catch (\Svea\Checkout\Exception\SveaInputValidationException $ex) {
		    return [
		    	'result' => 'failure',
		    	'error' => 'Input data errors',
		    	'errorDetailed' => $ex->getCode() . ': ' . $ex->getMessage()
		    ];

		} catch (Exception $ex) {
		    return [
		    	'result' => 'failure',
		    	'error' => 'General errors',
		    	'errorDetailed' => $ex->getCode() . ': ' . $ex->getMessage()
		    ];
		}
	}

	public function sveaGetOrder( $sSveaOrderId ) {
		try {
			$sBaseUrl = ( SVEA_TEST_MODE ? \Svea\Checkout\Transport\Connector::TEST_BASE_URL : \Svea\Checkout\Transport\Connector::PROD_BASE_URL );
	    $oConn = \Svea\Checkout\Transport\Connector::init( SVEA_MERCHANT_ID, SVEA_CHECKOUT_SECRET, $sBaseUrl );
	    $oCheckoutClient = new \Svea\Checkout\CheckoutClient( $oConn );

	    /**
	     * Example of creating the order and getting the response data
	     */

	    $oResponse = $oCheckoutClient->get( [
	    	'orderId' => $sSveaOrderId
	    ] );

	    return [
	    	'result' => 'success',
	    	'response' => $oResponse
	    ];

		} catch (\Svea\Checkout\Exception\SveaApiException $ex) {
		    return [
		    	'result' => 'failure',
		    	'error' => 'Api errors',
		    	'errorDetailed' => $ex->getCode() . ': ' . $ex->getMessage()
		    ];

		} catch (\Svea\Checkout\Exception\SveaConnectorException $ex) {
		    return [
		    	'result' => 'failure',
		    	'error' => 'Conn errors',
		    	'errorDetailed' => $ex->getCode() . ': ' . $ex->getMessage()
		    ];

		} catch (\Svea\Checkout\Exception\SveaInputValidationException $ex) {
		    return [
		    	'result' => 'failure',
		    	'error' => 'Input data errors',
		    	'errorDetailed' => $ex->getCode() . ': ' . $ex->getMessage()
		    ];

		} catch (Exception $ex) {
		    return [
		    	'result' => 'failure',
		    	'error' => 'General errors',
		    	'errorDetailed' => $ex->getCode() . ': ' . $ex->getMessage()
		    ];
		}
	}

}
