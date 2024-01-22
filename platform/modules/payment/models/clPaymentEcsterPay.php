<?php

/* ********************************************
 * Documentation on https://developer.ecster.se/
 *
 * Functions corresponding with schema on https://developer.ecster.se/integration-guide/preparation/#/page-preparation
 ******************************************** */

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentEcsterPay.php';

class clPaymentEcsterPay extends clPaymentBase implements ifPaymentMethod {

	public function __construct() {
		$this->initBase();
	}

	public function init( $iInvoiceOrderId, $aParams = array() ) {
		$this->invoiceOrderId = $iInvoiceOrderId;
	}
	public function checkStatus() {}
	public function finalizeOrder( $iInvoiceOrderId ) {}



	// API functions

  public function ecsterGetCart( $aCart, $sCustomerType = 'privatePerson' ) {
		$aCartData = [
			'locale' => [
				'language' => 'sv',
				'country' => 'SE'
			],
      'orderReference' => $this->invoiceOrderId,
			'parameters' => [
				'shopTermsUrl' => ECSTER_TOVEK_TERMS_URL,
				'returnUrl' => ECSTER_RECEIPT_URL,
				'purchaseType' => [
					'type' => ( ($sCustomerType == 'company') ? 'B2B' : 'B2C' )
				]
			],
			'notificationUrl' => ECSTER_CALLBACK_URL,
			'cart' => $aCart,
			'platform' => [
				'reference' => ECSTER_PLATFORM_REFERENCE
			]
		];


		return $this->apiCall( 'POST', ECSTER_PAY_RESOURCE, $aCartData );
	}

	public function ecsterGetOrder( $sInternalReference ) {
		$sUrl = ECSTER_ORDER_RESOURCE . '/' . $sInternalReference;
		return $this->apiCall( 'GET', $sUrl );
	}

	public function ecsterSearchOrder( $aData ) {
		return $this->apiCall( 'GET', ECSTER_ORDER_RESOURCE, $aData );
	}

	// Generic API call
	function apiCall( $sMethod, $sUrl, $aData = null ) {
    $rCurlHandle = curl_init();
		$aCurlOptions = [];

    switch( $sMethod ) {
      case "POST":
        $aCurlOptions[CURLOPT_POST] = true;

        if( !empty($aData) ) {
	        $aCurlOptions[CURLOPT_POSTFIELDS] = json_encode($aData);
				}
        break;

      case "PUT":
				// From PHP manual regarding CURLOPT_PUT:
				// true to HTTP PUT a file. The file to PUT must be set with CURLOPT_INFILE and CURLOPT_INFILESIZE.
				// https://www.php.net/manual/en/function.curl-setopt.php
        $aCurlOptions[CURLOPT_PUT] = true;
        break;

      default:
        if( !empty($aData) ) {
          $sUrl = sprintf( "%s?%s", $sUrl, http_build_query($aData) );
				}
    }

		$aCurlOptions += array(
			CURLOPT_URL 						=>  $sUrl,
			CURLOPT_PORT 						=>  443,
			CURLOPT_HTTPHEADER			=> [
		    'x-api-key: ' . ECSTER_API_KEY,
		    'x-merchant-key: ' . ECSTER_MERCHANT_KEY,
				'Content-Type: application/json'
			],
			CURLOPT_RETURNTRANSFER  => true,
			CURLINFO_HEADER_OUT			=> true,
			// CURLOPT_HEADER  				=>  true,
			// CURLOPT_ENCODING 				=>  'UTF-8'
		);

		curl_setopt_array( $rCurlHandle, $aCurlOptions );
    $oResult = curl_exec( $rCurlHandle );

		// $iErrNo = curl_errno( $rCurlHandle );
		// $sError  = curl_error( $rCurlHandle ) ;
		// $aHeader  = curl_getinfo( $rCurlHandle );

    curl_close( $rCurlHandle );

    return json_decode( $oResult, true );
	}

}
