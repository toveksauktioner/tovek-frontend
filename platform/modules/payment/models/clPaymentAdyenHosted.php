<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentAdyenHosted.php';

class clPaymentAdyenHosted extends clPaymentBase implements ifPaymentMethod {

	public function __construct() {
		$this->initBase();
	}
    
    public function init( $iOrderId, $aParams = array() ) {
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );		
		if( empty($aOrderData) ) return false;
			
		// Update order status
		$this->oOrder->update( $iOrderId, array(
			'orderStatus' => 'intermediate'
		) );
		
		// Ship before date
		$sShipDate = date( 'Y-m-d', strtotime('+' . ADYEN_SHIP_BEFORE_DAYS . ' day') );
		
		// Payment session validity
		$sSessionValidityDate = date( 'Y-m-d H:i:s', strtotime('+' . ADYEN_SESSION_VALIDITY . ' minutes') );
		$oDatetime = new DateTime( $sSessionValidityDate );
		$sSessionValidityDate = $oDatetime->format( DateTime::ATOM ); // Updated ISO8601
		
		/**
		 * Post data
		 */
		$aData = array(			
			'sessionValidity' => $sSessionValidityDate, 	 # (string) The payment deadline, ex. 2014-10-11T10:30:00Z
			'shipBeforeDate' => $sShipDate, 				 # (string) (required) The date within which the ordered goods or services need to be shipped or provided to the buyer. ex. 2014-10-20
			'shopperLocale' => $GLOBALS['userLang'], 		 # (string) It specifies the language to use during the transaction. ex. en_GB
			'merchantAccount' => ADYEN_ACCOUNT,				 # (string) (required) The merchant account identifier you want to process the (transaction) request with. ex. TestMerchant
			'paymentAmount' => $aOrderData['orderTotal'], 	 # (integer) (required) The payable amount that can be charged for the transaction, in minor units. ex. 10000
			'currencyCode' => 'EUR', 						 # (string) (required) The three-character ISO currency code.  ex. GBP
			'skinCode' => ADYEN_HPP_SKIN_CODE,  			 # (string) (required) A unique code to identify the skin you want to apply to the HPP in use to process the transaction.
			'merchantReference' => $iOrderId, 				 # (string) (required) A reference to uniquely identify the payment. ex. Internet order 12345
			'shopperReference' => $aOrderData['orderEmail'], # (string) A unique identifier for the shopper, for example a customer ID. ex. test102@gmail.com
			'recurringContract' => 'ONECLICK', 				 # (string) (required) Used to define the type of recurring contract to be used. For CVC-only payments, its value needs to be set to ONECLICK. ex. RECURRING,ONECLICK
			'shopperEmail' => $aOrderData['orderEmail'], 	 # (string) (required) A shopper's email address. ex. test102@gmail.com			
			'merchantReturnData' => 'testar'				 # (string) (required)
			/** 
			 * Additional fields
			 */
			//'allowedMethods' => '', 						 # (string) 			
			//'blockedMethods' => '', 						 # (string) 			
			//'offset' => '', 								 # (integer) An integer value that adds up to the normal fraud score. ex. '0'
			//'shopperStatement' => '', 					 # (string)
			//'billingAddressType' => '', 					 # (string) 			
			//'deliveryAddressType' => '', 					 # (string) 			
			//'brandCode' => '', 							 # (string) 			
			//'countryCode' => '', 							 # (string) 						
			//'orderData' => '', 							 # (string) HTML fragment of order data Compression: GZIP and Encoding: Base64
			//'offerEmail' => '', 							 # (string)
			//'billingAddress.street' => '', 				 # (string) 			
			//'billingAddress.houseNumberOrName' => '', 	 # (string) 			
			//'billingAddress.city' => '', 					 # (string) 			
			//'billingAddress.postalCode' => '', 			 # (string) 			
			//'billingAddress.stateOrProvince' => '', 		 # (string) 			
			//'billingAddress.country' => '', 				 # (string) 			
			//'shopper.firstName' => '', 					 # (string) 			
			//'shopper.infix' => '', 						 # (string) 			
			//'shopper.lastName' => '', 					 # (string) 			
			//'shopper.gender' => '', 						 # (string) 			
			//'shopper.dateOfBirthDayOfMonth' => '', 		 # (string) 			
			//'shopper.dateOfBirthMonth' => '', 			 # (string) 			
			//'shopper.dateOfBirthYear' => '', 				 # (string) 			
			//'shopper.telephoneNumber' => '' 				 # (string)
		);
		$aParams = $aData;
		
		/**
		 * Process post data
		 */
		$aParams['merchantSig'] = $this->assambleMerchantSig( $aParams ); # (string) (required) The signature in Base64 encoded format.
		
		/**
		 * Directory lookup,
		 * fetch available payment methods
		 */
		$oCurl = clRegistry::get( 'clCurl' );
		$oCurl->setEndpoint( (ADYEN_HPP_SKIP_DIRECTORY ? ADYEN_HPP_DETAILS_URL : ADYEN_HPP_DIRECTORY_URL) );
		$oCurl->post( $aParams, null, 'http_build_query' );
		$aAvailablePaymentMethods = array();
		if( ADYEN_HPP_SKIP_DIRECTORY === false && !empty($oCurl->aLastRespons['data']['content']) ) {
			$oResults = json_decode( $oCurl->aLastRespons['data']['content'] );			
			foreach( current($oResults) as $oResult ) {
				$aAvailablePaymentMethods[ $oResult->brandCode ] = $oResult->name;
			}
		}
		
		/**
		 * Set additional params
		 */
		if( empty($aParams['allowedMethods']) && !empty($aAvailablePaymentMethods) ) {
			$aParams['allowedMethods'] = implode( ',', array_keys($aAvailablePaymentMethods) );
		}		
		$aParams['resURL'] = ADYEN_RESULT_URL;
		
		/**
		 * Process post data
		 */
		unset( $aParams['merchantSig'] );
		$aParams['merchantSig'] = $this->assambleMerchantSig( $aParams ); # (string) (required) The signature in Base64 encoded format.	
		
		try {
			// Post to Adyen
			$this->sendPostData( $aParams, ADYEN_HPP_SINGLE_URL );
			
		} catch( Exception $oException ) {
			$this->exceptionHandling( $oException );
			
		}	
	}
    
	private function testHMACcalculation( $aParams ) {
		// Test HMAC Calculation
		$this->sendPostData( $aParams, ADYEN_CA_CHECK_HMAC_URL );
	}
	
    public function checkStatus() {
		$aOrderData = current( $this->oOrder->read('orderPaymentStatus', $_SESSION['orderId']) );
		if( $aOrderData['orderPaymentStatus'] == 'paid' ) {
			return true;
		}
		return false;
	}
	
    public function finalizeOrder( $aParams ) {
		if( empty($aParams['authResult']) ) return false;
		
		/**
		 * Verify that received data is valid and have not been tampered in the process
		 */
		$aParams2 = $aParams; unset( $aParams2['merchantSig'] );
		if( $aParams['merchantSig'] != $this->assambleMerchantSig( $aParams2 ) ) {
			// Error, not valid respons
			
			// Order history
			$this->oOrderHistory->create( array(
				'orderHistoryOrderId' => $aParams['merchantReference'],
				'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
				'orderHistoryGroupKey' => 'Payment',
				'orderHistoryMessage' => "Respons from Adyen was invalid", // Do not use gettext as the string will be permanent
				'orderHistoryData' => var_export( $aParams )
			) );
				
			$this->badPayment( 'INVALID', $aParams + array(
				'merchantSigMatch' => $this->assambleMerchantSig( $aParams2 )
			) );
			return false;
		}
				
		switch( $aParams['authResult'] ) {
			case 'AUTHORISED':
				/**
				 * Valid payment, the payment authorisation was successfully completed.
				 */
				
				if( !empty($aParams['merchantReference']) && ctype_digit($aParams['merchantReference']) ) {
					// Update order
					$this->oOrder->update( $aParams['merchantReference'], array(
						'orderStatus' => 'new',
						'orderPaymentStatus' => 'paid',
						'orderPaymentCustomId' => $aParams['pspReference'],
						'orderPaymentTransactionId' => $aParams['merchantSig']
					) );
					
					// Order history
					$this->oOrderHistory->create( array(
						'orderHistoryOrderId' => $aParams['merchantReference'],
						'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
						'orderHistoryGroupKey' => 'Payment',
						'orderHistoryMessage' => "The order was marked as Paid by Adyen", // Do not use gettext as the string will be permanent
						'orderHistoryData' => var_export( $aParams )
					) );
					
					// Run base finalizing
					parent::finalizeOrder( $aParams['merchantReference'] );
					
					return true;
				}
				
				break;
			
			case 'REFUSED':
				// The payment was refused. Payment authorisation was unsuccessful.
				
				// Order history
				$this->oOrderHistory->create( array(
					'orderHistoryOrderId' => $aParams['merchantReference'],
					'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
					'orderHistoryGroupKey' => 'Payment',
					'orderHistoryMessage' => "Adyen respond with 'REFUSED'", // Do not use gettext as the string will be permanent
					'orderHistoryData' => var_export( $aParams )
				) );
				
				$this->badPayment( 'REFUSED', $aParams );
				return false;
				break;
			
			case 'CANCELLED':
				// The payment was cancelled by the shopper before completion, or the shopper returned to the
				// merchant's site before completing the transaction.
				
				// Order history
				$this->oOrderHistory->create( array(
					'orderHistoryOrderId' => $aParams['merchantReference'],
					'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
					'orderHistoryGroupKey' => 'Payment',
					'orderHistoryMessage' => "Adyen respond with 'CANCELLED'", // Do not use gettext as the string will be permanent
					'orderHistoryData' => var_export( $aParams )
				) );
				
				$this->badPayment( 'CANCELLED', $aParams );
				return false;
				break;
			
			case 'PENDING':
				// It is not possible to obtain the final status of the payment. This can happen if the systems
				// providing final status information for the payment are unavailable, or if the shopper needs
				// to take further action to complete the payment.
				
				// Order history
				$this->oOrderHistory->create( array(
					'orderHistoryOrderId' => $aParams['merchantReference'],
					'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
					'orderHistoryGroupKey' => 'Payment',
					'orderHistoryMessage' => "Adyen respond with 'PENDING'", // Do not use gettext as the string will be permanent
					'orderHistoryData' => var_export( $aParams )
				) );
				
				$this->badPayment( 'PENDING', $aParams );
				return false;
				break;
			
			case 'ERROR':
			default:
				// An error occurred during the payment processing.
				
				// Order history
				$this->oOrderHistory->create( array(
					'orderHistoryOrderId' => $aParams['merchantReference'],
					'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
					'orderHistoryGroupKey' => 'Payment',
					'orderHistoryMessage' => "Adyen respond with 'ERROR'", // Do not use gettext as the string will be permanent
					'orderHistoryData' => var_export( $aParams )
				) );
				
				$this->badPayment( 'ERROR', $aParams );
				return false;
				break;
			
		}
			
		return false;
	}
    
	/**
	 * Handle unsuccessful payments
	 */
	private function badPayment( $sStatus, $aData = array() ) {
		$sMessage = sprintf( 'Bad payment (%s): %s', $sStatus, var_export( $aData ) );
		
		if( ADYEN_LOGGING === true ) {
			/**
			 * Log error
			 */
			$oLogger = clRegistry::get( 'clLogger' );					
			$oLogger->log( $sMessage, 'AdyenBadPayment.log' );
		}
		if( ADYEN_ERROR_NOTIFY === true ) {
			/**
			 * Notify developer by mail
			 */
			@mail( ADYEN_ERROR_EMAIL, 'AdyenBadPayment',  $sMessage ); // TODO remove after watch period
		}
		if( ADYEN_DEBUG === true ) {
			/**
			 * Throw error
			 */
			throw new Exception( $sMessage );		
		}
	}
	
	public function checkPaymentStatus( $iOrderId ) {
		// Payment data in order
		$aOrderPaymentData = current( $this->oOrder->read( array(
			'orderId',
			'orderPaymentStatus'
		), $iOrderId ) );		
		
		if( $aOrderPaymentData['orderPaymentStatus'] == 'paid' ) {
			return true;
		}
		return false;
	}
	
	/**
	 * Fetch remote payment result page
	 */
	public function getReceiptHtml( $aParams ) {
		$oCurl = clRegistry::get( 'clCurl' );
		$oCurl->setEndpoint( ADYEN_HPP_RESULT_URL );
		$oCurl->post( $aParams, null, 'http_build_query' );			
		if( !empty($oCurl->aLastRespons['data']['content']) ) {
			return $oCurl->aLastRespons['data']['content'];
		}
		return false;
	}
	
	/**
	 * Validation method
	 */
	private function assambleMerchantSig( $aParams ) {
		// The character escape function
		$fEscapeval = function( $val ) {
			return str_replace( ':', '\\:', str_replace('\\', '\\\\', $val) );
		};
     
		// Sort the array by key using SORT_STRING order
		ksort( $aParams, SORT_STRING );
		 
		// Generate the signing data string
		$sSignData = implode( ":", array_map( $fEscapeval, array_merge(array_keys($aParams), array_values($aParams)) ) );
		
		// base64-encode the binary result of the HMAC computation
		return base64_encode( hash_hmac('sha256', $sSignData, pack("H*" , ADYEN_HPP_SKIN_HMAC), true) );		
	}
	
	/**
	 * Error handling
	 */
	private function errorHandling( $oError ) {
		if( ADYEN_LOGGING === true ) {
			/**
			 * Log error
			 */
			$oLogger = clRegistry::get( 'clLogger' );
			$sLog = sprintf( '(%s) %s', $oError->code, $oError->message );
			$oLogger->log( $sLog, 'AdyenError.log' );
		}
		if( ADYEN_ERROR_NOTIFY === true ) {
			/**
			 * Notify developer by mail
			 */
			$sMail = sprintf( '(%s) %s', $oError->code, $oError->message );
			@mail( ADYEN_ERROR_EMAIL, 'AdyenError',  $sMail ); // TODO remove after watch period
		}
		if( ADYEN_DEBUG === true ) {
			/**
			 * Throw error
			 */
			throw new Exception( sprintf( '%s in %s on %s', $oError->getMessage(), $oError->getFile(), $oError->getLine() ) );		
		}
	}
	
	/**
	 * Exception handling
	 */
	private function exceptionHandling( $oException ) {
		if( ADYEN_LOGGING === true ) {
			/**
			 * Log error
			 */
			$oLogger = clRegistry::get( 'clLogger' );
			$sLog = sprintf( 'Exception: %s in %s at line %s', $oException->getMessage(), $oException->getFile(), $oException->getLine() );
			$oLogger->log( $sLog, 'AdyenError.log' );
		}
		if( ADYEN_ERROR_NOTIFY === true ) {
			/**
			 * Notify developer by mail
			 */
			$sMail = sprintf( 'Exception: %s in %s at line %s', $oException->getMessage(), $oException->getFile(), $oException->getLine() );
			@mail( ADYEN_ERROR_EMAIL, 'AdyenError',  $sMail ); // TODO remove after watch period
		}
		if( ADYEN_DEBUG === true ) {
			/**
			 * Throw error
			 */
			throw new Exception( sprintf( '%s in %s on %s', $oException->getMessage(), $oException->getFile(), $oException->getLine() ) );			
			//echo sprintf( '%s in %s on %s', $oException->getMessage(), $oException->getFile(), $oException->getLine() ); die();
		}
	}
	
}