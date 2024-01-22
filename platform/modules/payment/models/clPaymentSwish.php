<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentSwish.php';

/**
 *
 * Swish
 * 
 */

class clPaymentSwish extends clPaymentBase implements ifPaymentMethod {
	
	private $oCurl;
	
	public function __construct() {
		// Init payment base
		$this->initBase();
		
		/**
		 * cURL resource object
		 */
		$this->oCurl = clFactory::create( 'clCurl', null, array(
			'header' => true,
			'port' => 443,								
			'sslversion' => '1', # CURL_SSLVERSION_TLSv1(1), CURL_SSLVERSION_TLSv1_0(4), CURL_SSLVERSION_TLSv1_1(5), CURL_SSLVERSION_TLSv1_2(6)
			'ssl_cipher_list' => 'TLSv1', # SSLv3, TLSv1
			'ssl_verifypeer' => false,
			'ssl_verifyhost' => '2',			
			'sslcert' => SWISH_CERT_CRT_PATH,			
			'sslkey' => SWISH_CERT_KEY_PATH,			
			'sslkeypasswd' => SWISH_CERT_KEY_PASSWD
		) );		
	}
	
	/**
	 * Init payment by order
	 *
	 * @return string Location URL of created payment
	 */
	public function init( $iOrderId, $aParams = array() ) {		
		// Order data
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );		
		if( empty($aOrderData) ) return false;
		
		// Update order status
		$this->oOrder->update( $iOrderId, array(
			'orderStatus' => 'intermediate'
		) );
		
		// Format order price
		$fPrice = number_format( $aOrderData['orderTotal'], 2, '.', '' );
		
		// Assamble payment message
		$sPaymentMessage = sprintf( _( 'Purchase with ref. Order ID: %s' ), $aOrderData['orderId'] );		
		
		/**
		 * Create payment request
		 */
		$sLocation = $this->createPaymentRequest( array(
			'payeePaymentReference' => 'orderID:' . $iOrderId,
			'payerAlias' => $aOrderData['orderPaymentPhone'],
			'amount' => $fPrice,
			'message' => $sPaymentMessage
		) );
		
		return $sLocation;
	}
	
	/**
	 * Init manual payment
	 *
	 * @param array $aSwishData
	 * 		payeePaymentReference 	[required]			# Payment reference of the payee, which is the Merchant that receives the payment. This reference could be order id or similar.
	 * 		callbackUrl				[not required]		# Callback URL
	 * 		payerAlias 				[required] 			# Customer's phone numnber (ex. 467123345678) 
	 * 		payeeAlias				[not required]		# Merchant Swish number
	 * 		amount 					[required]			# Price, between 1 & 999999999999.99
	 * 		currency				[not required]		# Currency (Only supported value currently is SEK)
	 * 		message 				[required]			# Merchant supplied message about the payment/order. Max 50 chars.
	 * 
	 * @return string Location URL of created payment
	 */
	public function initManualPayment( $aPaymentData ) {
		// Create payment request	
		return $this->createPaymentRequest( $aPaymentData );
	}
	
	/**
	 * Check status
	 */
	public function checkStatus() {
		$aOrderData = current( $this->oOrder->read('orderPaymentStatus', $_SESSION['orderId']) );
		if( empty($aOrderData) ) return false;
		return $aOrderData['orderPaymentStatus'] == 'paid' ? true : false;
	}
	
	/**
	 * Check payment status
	 */
	public function checkPaymentStatus( $iSwishPaymentId ) {
		// Fetch data
		$this->oCurl->get( SWISH_URL_PAYMENT . '/' . $iSwishPaymentId );
		
		/**
		 * Respons
		 */
		if( $this->oCurl->aLastRespons['info']['http_code'] == 200 ) {
			/**
			 * Success
			 */
			return $this->oCurl->aLastRespons['data']['content'];
		} else {
			/**
			 * Error
			 */
			$this->responsErrorHandler( $this->oCurl->aLastRespons );
		}
		
		return false;
	}
	
	/**
	 * Finalize order
	 */
	public function finalizeOrder( $iOrderId ) {
		$this->oOrderHistory->create( array(
			'orderHistoryOrderId' => $iOrderId,
			'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
			'orderHistoryGroupKey' => 'Payment',
			'orderHistoryMessage' => "The order was marked as Paid by Swish callback", // Do not use gettext as the string will be permanent
			'orderHistoryData' => var_export( $_GET, true )
		) );
		
		$aData = array(
			'orderStatus' => 'new',
			'orderPaymentStatus' => 'paid',
			'orderPaymentUrl' => SWISH_URL_PAYMENT . '/' . (!empty($_GET['id']) ? $_GET['id'] : ''),
			'orderPaymentTransactionId' => (!empty($_GET['paymentReference":']) ? $_GET['paymentReference":'] : ''),
			'orderPaymentCustomId' => (!empty($_GET['id']) ? $_GET['id'] : '0') . '-' .  (!empty($_GET['payeeAlias']) ? $_GET['payeeAlias'] : '0') 
		);
		$this->oOrder->update( $iOrderId, $aData );
		
		parent::finalizeOrder( $iOrderId );
	}
	
	/**
	 * Create payment request
	 *
	 * @param array $aSwishData
	 * 		payeePaymentReference 	[required]			# Payment reference of the payee, which is the Merchant that receives the payment. This reference could be order id or similar.
	 * 		callbackUrl				[not required]		# Callback URL
	 * 		payerAlias 				[required] 			# Customer's phone numnber (ex. 467123345678) 
	 * 		payeeAlias				[not required]		# Merchant Swish number
	 * 		amount 					[required]			# Price, between 1 & 999999999999.99
	 * 		currency				[not required]		# Currency (Only supported value currently is SEK)
	 * 		message 				[required]			# Merchant supplied message about the payment/order. Max 50 chars.
	 * 
	 * @return string Location URL of created payment
	 */
	public function createPaymentRequest( $aSwishData ) {		
		if( count(array_diff_key( array(
			'payeePaymentReference' => array(),
			'payerAlias' => array(),
			'amount' => array(),
			'message' => array()
		), $aSwishData )) > 0 ) {
			/**
			 * Missing required fields
			 */
			return false;
		}
		
		// Return variable
		$mLocation = false;
		
		/**
		 * Supplementary data
		 */
		$aSwishData += array(
			'callbackUrl' => SWISH_URL_CALLBACK, 		# Callback URL
			'payeeAlias' => SWISH_MERCHANT_NUMBER,		# Merchant Swish number
			'currency' => 'SEK',						# Currency (Only supported value currently is SEK)
		);
		
		/**
		 * Send data
		 */
		$this->oCurl->setSendHeader( 'Content-Type', 'application/json' );
		$this->oCurl->post( $aSwishData, SWISH_URL_PAYMENT, 'json_encode' );
		
		/**
		 * Respons
		 */
		if( $this->oCurl->aLastRespons['info']['http_code'] == 201 ) {
			/**
			 * Success
			 */
			$mLocation = $this->oCurl->aLastRespons['data']['headers']['Location'];
			
		} else {
			/**
			 * Error
			 */
			$this->responsErrorHandler( $this->oCurl->aLastRespons );
			
		}
		
		return $mLocation;
	}
	
	/**
	 * Make refund request
	 */
	public function makeRefund( $iOrderId, $fPrice = null ) {
		// Order data
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );		
		if( empty($aOrderData) ) return false;
		
		$aPaymentCustomId = explode( '-', $aOrderData['orderPaymentCustomId'] );
		
		$fPriceAmount = $fPrice != null ? $fPrice : $aOrderData['orderTotal'];
		
		// Data
		$aSwishData = array(
			'payeePaymentReference' => $aOrderData['orderId'], 		# Order ID
			'originalPaymentReference' => $aPaymentCustomId[0], 	# Original payment reference
			'callbackUrl' => SWISH_URL_CALLBACK, 					# Callback URL			
			'payerAlias' => $aOrderData['orderPaymentPhone'], 		# Customer's phone numnber (ex. 467123345678) 
			'amount' => $fPriceAmount,								# Price, between 1 & 999999999999.99
			'currency' => 'SEK',									# Currency (Only supported value currently is SEK)
			'message' => $sPaymentMessage							# Merchant supplied message about the payment/order. Max 50 chars.
		);
		
		// Send data
		$this->oCurl->setSendHeader( 'Content-Type', 'application/json' );
		$this->oCurl->post( $aSwishData, SWISH_URL_REFUND, 'json_encode' );
		
		/**
		 * Respons
		 */
		if( $this->oCurl->aLastRespons['info']['http_code'] == 201 ) {
			/**
			 * Success
			 */
			return $this->oCurl->aLastRespons['data']['headers']['Location'];
		} else {
			/**
			 * Error
			 */
			$this->responsErrorHandler( $this->oCurl->aLastRespons );
		}
		
		return false;
	}
	
	/**
	 * Fetch refund callback
	 */
	public function fetchRefundCallback( $iOrderId ) {
		// Order data
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );		
		if( empty($aOrderData) ) return false;
		
		$aPaymentCustomId = explode( '-', $aOrderData['orderPaymentCustomId'] );
		
		// Fetch data
		$this->oCurl->get( SWISH_URL_REFUND . '/' . $aPaymentCustomId[0] );
		
		/**
		 * Respons
		 */
		if( $this->oCurl->aLastRespons['info']['http_code'] == 200 ) {
			/**
			 * Success
			 */
			return $this->oCurl->aLastRespons['data']['content'];
		} else {
			/**
			 * Error
			 */
			$this->responsErrorHandler( $this->oCurl->aLastRespons );
		}
	}
	
	/**
	 * - Still todo!
	 * Create QR codes using Swish QR code generator APIs
	 */
	public function createSwishQrCode( $aSwishData = array() ) {
		return false;		
		//$aData = array(
		//	'$schema' => 'http://json-schema.org/draft-04/schema#',
		//	'title' => 'Swish pre-filled qr code generator',
		//	'description' => 'REST interface to get a QR code that the Swish app will interpret as a pre filled code',
		//	'definitions' => array(
		//		'editable' => array(
		//			'description ' => 'Controls if user can modify this value in Swish app or not',
		//			'type' => 'object',
		//			'properties' => array(
		//				'editable' => array(
		//					'type' => 'boolean',
		//					'default' => false,
		//				),
		//			),
		//		),
		//		'swishString' => array(
		//			'type' => 'object',
		//			'properties' => array(
		//				'value' => array(
		//					'type' => 'string',
		//					'maxLength' => 70,
		//				),
		//			),
		//			'required' => array(
		//				0 => 'value',
		//			),
		//		),
		//		'swishNumber' => array(
		//			'type' => 'object',
		//			'properties' => array(
		//				'value' => array(
		//					'type' => 'number',
		//				),
		//			),
		//			'required' => array(
		//				0 => 'value',
		//			),
		//		),
		//	),
		//	'type' => 'object',
		//	'properties' => array(
		//		'format' => array(
		//			'enum' => array(
		//				0 => 'jpg',
		//				1 => 'png',
		//				2 => 'svg',
		//			),
		//		),
		//		'payee' => array(
		//			'description' => 'Payment receiver',
		//			'allOf' => array(
		//				0 => array(
		//					'$ref' => '#/definitions/editable',
		//				),
		//				1 => array(
		//					'$ref' => '#/definitions/swishString',
		//				),
		//			),
		//		),
		//		'amount' => array(
		//			'description' => 'Payment amount',
		//			'allOf' => array(
		//				0 => array(
		//					'$ref' => '#/definitions/editable',
		//				),
		//				1 => array(
		//					'$ref' => '#/definitions/swishNumber',
		//				),
		//			),
		//		),
		//		'message' => array(
		//			'description' => 'Message for payment',
		//			'allOf' => array(
		//				0 => array(
		//					'$ref' => '#/definitions/editable',
		//				),
		//				1 => array(
		//					'$ref' => '#/definitions/swishString',
		//				),
		//			),
		//		),
		//		'size' => array(
		//			'description' => 'Size of the QR code. The code is a square, so width and height are the same. Not required is the format is svg',
		//			'value' => 'number',
		//			'minimum' => 300,
		//		),
		//		'border' => array(
		//			'description' => 'Width of the border.',
		//			'type' => 'number',
		//		),
		//		'transparent' => array(
		//			'description' => 'Select background color to be transparent. Do not work with jpg format.',
		//			'type' => 'boolean',
		//		),
		//	),
		//	'required' => array(
		//		0 => 'format',
		//	),
		//	'anyOf' => array(
		//		0 => array(
		//			'required' => array(
		//				0 => 'payee',
		//			),
		//		),
		//		1 => array(
		//			'required' => array(
		//				0 => 'amount',
		//			),
		//		),
		//		2 => array(
		//			'required' => array(
		//				0 => 'message',
		//			),
		//		),
		//	),
		//	'additionalProperties' => false,
		//	'maxProperties' => 5,
		//);
		//
		//// Send data
		//$this->oCurl->setSendHeader( 'Content-Type', 'application/json' );
		//$this->oCurl->post( $aSwishData, SWISH_URL_QRCODE, 'json_encode' );
		//
		//echo '<pre>';
		//var_dump( $this->oCurl->aLastRespons );
		//die;
		//
		///**
		// * Respons
		// */
		//if( $this->oCurl->aLastRespons['info']['http_code'] == 201 ) {
		//	/**
		//	 * Success
		//	 */
		//	$sLocation = $this->oCurl->aLastRespons['data']['headers']['Location'];
		//} else {
		//	/**
		//	 * Error
		//	 */
		//	$this->responsErrorHandler( $this->oCurl->aLastRespons );
		//}
	}
	
	/**
	 * This function converts a phone number
	 * to internationall format, without leading zeros.
	 */
	public function convertToInternational( $sNumber ) {
		// First, remove all spaces
		$sNumber = preg_replace( '/\s+/', '', $sNumber );
		
		// International beginning?
		if( substr($sNumber, 0, 2) == '00' ) {
			// Return number, without any special characters.
			return preg_replace( '/[^0-9]/', '', $sNumber );
		}
		
		// Special char beginning?
		if( substr($sNumber, 0, 1) == '+' ) {
			// Return number with plus char replaced
			// and any other specail char removed.
			return preg_replace( '/[^0-9]/', '', str_replace('+', '00', $sNumber) );
		}
		
		// Pre-remove any special characters
		$sNumber = preg_replace( '/[^0-9]/', '', $sNumber );
		
		// Format to international, depending on length
		switch( strlen($sNumber) ) {
			case 9:
				$sNumber = '46' . substr( $sNumber, 1, 8 );			
				break;
			case 10:
				$sNumber = '46' . substr( $sNumber, 1, 9 );
				break;
			case 11:
				$sNumber = '46' . substr( $sNumber, 1, 10 );
				break;
			default:
				$sNumber = '';
				break;
		}
		
		// Return number
		return $sNumber;
	}
	
	/**
	 * Log bad payment (not same as system error)
	 */
	public function logBadPayment( $aRequest ) {
		$this->oOrderHistory->create( array(
			'orderHistoryOrderId' => ( !empty($aRequest['payeePaymentReference']) ? $aRequest['payeePaymentReference'] : '' ),
			'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
			'orderHistoryGroupKey' => 'Payment',
			'orderHistoryMessage' => 'Callback Swish', // Do not use gettext as the string will be permanent
			'orderHistoryData' => var_export( $aRequest, true )
		) );
	}
	
	/**
	 * Respons error handler
	 */
	public function responsErrorHandler( $mRespons ) {
		/**
		 * Swish respons
		 */
		switch( $mRespons['info']['http_code'] ) {
			/**
			 * Unsuccess / error
			 */
			case 400: // Bad Request
			case 401: // Unauthorized
			case 403: // Forbidden
			case 415: // Unsupported Media Type
			case 422: // Unprocessable Entity
			case 500: // Internal Server Error
				if( !empty($mRespons['data']['content']) && is_array($mRespons['data']['content']) ) {
					$oRouter = clRegistry::get( 'clRouter' );
					
					$aErr = array();
					foreach( $mRespons['data']['content'] as $mEntry ) {
						if( is_object($mEntry) && !empty($mEntry->errorCode) ) {
							$aErr[] = sprintf( 'Error (%s, %s) (%s): %s', $mRespons['info']['http_code'], $oRouter->oDao->aDataDict['entRouteHttpStatus']['statusCode']['values'][ $mRespons['info']['http_code'] ], $mEntry->errorCode, $mEntry->errorMessage );							
						}
					}
					
					if( !empty($aErr) ) {
						echo implode( "<br />", $aErr );
						echo '<pre>';
						var_dump( $aSwishData );
						die();
					}
				}		
				break;
			
			/**
			 * Unkown error
			 */
			default:
				throw new Exception( 'Unkown error' );
				break;
		}		
	}
	
}