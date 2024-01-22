<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentPaypal.php';

require_once PATH_FUNCTION . '/fData.php';

class clPaymentPaypal extends clPaymentBase implements ifPaymentMethod {

	private $aCurlOptions;
	private $sAccessToken;
	private $sTokenType;

	public function __construct() {
		$this->initBase();

		// Core options
		$this->aCurlOptions = array(
			CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 20
		);

		// Get & set access token & token type
		$this->getAccessToken();
	}

	public function getAccessToken() {
		$aQueryOptions = $this->aCurlOptions + array(
			CURLOPT_URL => PAYPAL_ENDPOINT . '/v1/oauth2/token',
			CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
			CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
			CURLOPT_HTTPHEADER => array(
				'content-type: application/x-www-form-urlencoded',
				'accept: application/json',
				'accept-Language: sv_SE'
			)
		);
		$oResult = $this->query( $aQueryOptions );

		if( $oResult ) {
			$this->sAccessToken = $oResult->access_token;
			$this->sTokenType = $oResult->token_type;
			return true;
		}
		return false;
	}

	public function query( $aOptions ) {
		$rPaypal = curl_init();
		curl_setopt_array( $rPaypal, $aOptions );
		$sContent = curl_exec( $rPaypal );
		$iErrNo = curl_errno( $rPaypal );
		$sError  = curl_error( $rPaypal ) ;
		$aHeader  = curl_getinfo( $rPaypal );
		curl_close( $rPaypal );

		// For debuging
		//if( !empty($_SESSION['paypal_access_token']) ) {
		//	echo '<pre>q:';
		//	var_dump($sContent);
		//	var_dump($iErrNo);
		//	var_dump($sError);
		//	var_dump($aHeader);
		//	die;
		//}

		// Error check
		if( $iErrNo > 0 ) {
			clFactory::loadClassFile( 'clLogger' );
			clLogger::log( $sError, 'paypalError.log' );
			return $sError;
		}

		return json_decode( $sContent );
	}

	public function init( $iOrderId, $aParams = array() ) {
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );

		// Order data
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );

		// Order line data
		$aOrderLineData = $this->oOrderLine->readByOrder( $iOrderId, array(
			'lineProductId',
			'lineProductCustomId',
			'lineProductTitle',
			'lineProductQuantity',
			'lineProductPrice',
			'lineProductVat'
		) );

		// Update order status
		$this->oOrder->update( $iOrderId, array(
			'orderStatus' => 'intermediate'
		) );

		// Array of transaction information
		$aTransactionParams = array(
			'intent' => 'sale',
			'redirect_urls' => array(
				'return_url' => PAYPAL_RETURN_URL,
				'cancel_url' => PAYPAL_CANCEL_URL
			),
			'payer' => array(
				'payment_method' => 'paypal'
			),
			'transactions' => array(
				0 => array(
					'amount' => array(
						'total' => $aOrderData['orderTotal'],
						'currency' => $aOrderData['orderCurrency']
					),
					'description' => _( 'Order ID' ) . ': ' . $aOrderData['orderId'],
					'item_list' => array(
						'items' => array()
					)
				)
			)
		);

		// Add order lines
		foreach( $aOrderLineData as $aLine ) {
			// Order total in money format
			$fProductPrice = calculatePrice( $aLine['lineProductPrice'], array(
				'profile' => 'default',
				'additional' => array(
					'vat' => $aLine['lineProductVat'],
					'currencyRate' => $aOrderData['orderCurrencyRate']
				)
			) );

			$aTransactionParams['transactions'][0]['item_list']['items'][] = array(
				'quantity' => $aLine['lineProductQuantity'],
				'name' => $aLine['lineProductTitle'],
				'price' => $fProductPrice,
				'currency' => $aOrderData['orderCurrency']
			);
		}

		// Fixed amount discount
		if( $aOrderData['orderDiscountCodeType'] == 'fixedAmount' && !empty( $aOrderData['orderDiscountCodeDiscount'])	) {

			// Format price. Note the conversion to a negative value
			$fDiscountPrice = number_format( calculatePrice(-$aOrderData['orderDiscountCodeDiscount']), 2, '.', '' );

			// Add discount as an item with a negative value
			$aTransactionParams['transactions'][0]['item_list']['items'][] = array(
				'quantity'	=> 1,
				'name'		=> _('Discount'),
				'price'		=> $fDiscountPrice,
				'currency'	=> $aOrderData['orderCurrency'],
			);

		}

		// Add freight
		if( !empty($aOrderData['orderFreightPrice']) ) {
			$aTransactionParams['transactions'][0]['item_list']['items'][] = array(
				'quantity' => '1',
				'name' => _( 'Freight' ),
				'price' => $aOrderData['orderFreightPrice'],
				'currency' => $aOrderData['orderCurrency'],
			);
		}

		// Add payment cost
		if( !empty($aOrderData['orderPaymentPrice']) ) {
			$aTransactionParams['transactions'][0]['item_list']['items'][] = array(
				'quantity' => '1',
				'name' => $aOrderData['orderPaymentTypeTitle'],
				'price' => $aOrderData['orderPaymentPrice'],
				'currency' => $aOrderData['orderCurrency'],
			);
		}

		/**
		 * Web experience profile
		 */
		//$oWebExperienceProfiles = $this->listWebExperienceProfiles();
		//if( !empty($oWebExperienceProfiles) ) {
		//	$oWebExperienceProfiles = current( $oWebExperienceProfiles );
		//	$aTransactionParams['experience_profile_id'] = $oWebExperienceProfiles->id;
		//} else {
		//	$aTransactionParams['experience_profile_id'] = $this->createWebExperienceProfile();
		//}
		
		// Create transaction
		$aQueryOptions = $this->aCurlOptions + array(
			CURLOPT_URL => PAYPAL_ENDPOINT . '/v1/payments/payment',
			CURLOPT_HTTPHEADER => array(
				'content-type: application/json',
				'authorization: ' . $this->sTokenType . ' ' . $this->sAccessToken
			),
			CURLOPT_POSTFIELDS => json_encode( $aTransactionParams )
		);
		$oResult = $this->query( $aQueryOptions );

		if( !empty($oResult) && !empty($oResult->state) && $oResult->state == 'created' ) {
			$sPaypalTerminalUrl = $oResult->links[1]->href;
			$sPaymentToken = substr( $sPaypalTerminalUrl, (strlen($sPaypalTerminalUrl)-20), 20 );

			// Store the created payment token
			$this->oOrder->update( $iOrderId, array(
				'orderPaymentCustomId' => $oResult->id,
				'orderPaymentToken' => md5( $sPaymentToken . '||' . $this->sAccessToken )
			) );
			$_SESSION['paypal_access_token'] = $this->sTokenType . ' ' . $this->sAccessToken;

			$oRouter = clRegistry::get( 'clRouter' );
			$oRouter->redirect( $sPaypalTerminalUrl );
		} else {
			$this->aErr[] = _( 'Payment could not be completed' );
			return false;
		}
	}

	public function checkStatus() {
		$aOrderData = current( $this->oOrder->read('orderPaymentStatus', $_SESSION['orderId']) );
		if( $aOrderData['orderPaymentStatus'] == 'paid' ) {
			return true;
		} elseif( PAYPAL_ACCEPT_PENDING === true && $aOrderData['orderPaymentStatus'] == 'pending' ) {
			return true;
		}
		return false;
	}

	/* *
	 * Paypal call transaction ID for 'Payer ID'
	 */
	public function finalizeOrder( $iOrderId ) {
		$aOrderData = current( $this->oOrder->read( '*', $iOrderId ) );

		if( !empty($aOrderData['orderPaymentCustomId']) && !empty($_SESSION['paypal_payer_id']) && !empty($_SESSION['paypal_access_token']) ) {
			// Execute the payment
			$aQueryOptions = $this->aCurlOptions + array(
				CURLOPT_URL => PAYPAL_ENDPOINT . '/v1/payments/payment/' . $aOrderData['orderPaymentCustomId'] . '/execute/',
				CURLOPT_HTTPHEADER => array(
					'Content-type: application/json',
					'Authorization: ' . $_SESSION['paypal_access_token']
				),
				CURLOPT_POSTFIELDS => '{ "payer_id" : "' . $_SESSION['paypal_payer_id'] . '" }'
			);
			$oResult = $this->query( $aQueryOptions );

			/**
			 * Successful payment
			 */
			if( $oResult->state == 'approved' ) {
				unset( $_SESSION['paypal_access_token'], $_SESSION['paypal_payer_id'] );

				$_SESSION['orderId'] = $iOrderId;

				$this->oOrderHistory->create( array(
					'orderHistoryOrderId' => $iOrderId,
					'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
					'orderHistoryGroupKey' => 'Payment',
					'orderHistoryMessage' => "The order was marked as Paid by Paypal", // Do not use gettext as the string will be permanent
					'orderHistoryData' => serialize( $oResult )
				) );

				$aData = array(
					'orderStatus' => 'new',
					'orderPaymentStatus' => 'paid',
					'orderPaymentCustomId' => $oResult->id,
					'orderPaymentUrl' => $oResult->links[0]->href
				);
				$this->oOrder->update( $iOrderId, $aData );

				parent::finalizeOrder( $iOrderId );

				return true;

			/**
			 * Pending payment
			 */
			} elseif( $oResult->state == 'pending' ) {
				// Find out payment pending reson
				$sPendingReason = $oResult->transactions[0]->related_resources[0]->sale->pending_reason;

				// Does databas have necessary payment status?
				$bDatabasCheck = isset($this->oOrder->oDao->aDataDict['entOrder']['orderPaymentStatus']['values']['pending']) ? true : false;

				if( PAYPAL_ACCEPT_PENDING === true && $bDatabasCheck === true ) {
					unset( $_SESSION['paypal_access_token'], $_SESSION['paypal_payer_id'] );

					$_SESSION['orderId'] = $iOrderId;

					$this->oOrderHistory->create( array(
						'orderHistoryOrderId' => $iOrderId,
						'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
						'orderHistoryGroupKey' => 'Payment',
						'orderHistoryMessage' => "The order was marked as Pending(" . $sPendingReason . ") by Paypal", // Do not use gettext as the string will be permanent
						'orderHistoryData' => serialize( $oResult )
					) );

					$aData = array(
						'orderStatus' => 'new',
						'orderPaymentStatus' => 'pending',
						'orderPaymentCustomId' => $oResult->id,
						'orderPaymentUrl' => $oResult->links[0]->href
					);
					$this->oOrder->update( $iOrderId, $aData );

					parent::finalizeOrder( $iOrderId );

					return true;
				}
				
			/**
			 * Error with payment
			 */
			} else {
				$this->aErr[] = _( 'Payment could not be completed' );
				
				// Error
				$oOrderHistory->oOrderHistory->create( array(
					'orderHistoryOrderId' => $iOrderId,
					'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
					'orderHistoryGroupKey' => 'Payment',
					'orderHistoryMessage' => "Error while processing Paypal payment", // Do not use gettext as the string will be permanent
					'orderHistoryData' => serialize( $oResult )
				) );			
				
				return false;
			}
		}
	}

	/**
	 * @param array aOrderPaymentData {
	 * 	'orderId',
	 *	'orderPaymentType',			
	 *	'orderPaymentCustomId',
	 * 	'orderPaymentUrl',
	 *	'orderPaymentToken'
	 * }
	 */
	public function checkPaymentStatus( $aOrderPaymentData ) {
		if( empty($aOrderPaymentData['orderPaymentCustomId']) ) {
			return false;
		}
		
		// Execute the payment
		$aQueryOptions = array(
			CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,			
			CURLOPT_TIMEOUT => 20,
			CURLOPT_URL => PAYPAL_ENDPOINT . '/v1/payments/payment/' . $aOrderPaymentData['orderPaymentCustomId'],			
			CURLOPT_HTTPHEADER => array(
				'Content-type: application/json',
				'Authorization: ' . $this->sTokenType . ' ' . $this->sAccessToken
			)
		);
		
		$oResult = $this->query( $aQueryOptions );
		
		if( !empty($oResult) && !empty($oResult->id) ) {
			$aPaymentData = array(
				'id' => $oResult->id,
				'state' => $oResult->state,						
				'created' => $oResult->create_time,
				'updated' => $oResult->update_time,
				'payer' => 'Unkown'
			);
			
			if( !empty($oResult->payer) ) {
				$aPaymentData['payer'] = array(
					'method' => $oResult->payer->payment_method,
					'status' => $oResult->payer->status,
					'email' => $oResult->payer->payer_info->email
				);
			}
			
			return $aPaymentData;
		}
		
		return false;
	}
	
	public function listWebExperienceProfiles() {
		$aQueryOptions = array(
			CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,			
			CURLOPT_TIMEOUT => 20,
			CURLOPT_URL => PAYPAL_ENDPOINT . '/v1/payment-experience/web-profiles',			
			CURLOPT_HTTPHEADER => array(
				'Content-type: application/json',
				'Authorization: ' . $this->sTokenType . ' ' . $this->sAccessToken
			)
		);		
		return $this->query( $aQueryOptions );
	}
	
	public function createWebExperienceProfile() {
		$aTransactionParams = array(
			'name' => SITE_TITLE,
			'presentation' => array(
				'brand_name' => SITE_TITLE . ' Paypal',
				'logo_image' => (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . '/images/templates/default/img-logo.png',
				'locale_code' => 'US'
			),
			'input_fields' => array(
				'allow_note' => true
				//'no_shipping' => 0,
				//'address_override' => 1
			),
			'flow_config' => array(
				'landing_page_type' => 'billing',
				'bank_txn_pending_url' => (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN
			)		
		);
		
		$aQueryOptions = $this->aCurlOptions + array(
			CURLOPT_URL => PAYPAL_ENDPOINT . '/v1/payment-experience/web-profiles',
			CURLOPT_HTTPHEADER => array(
				'content-type: application/json',
				'authorization: ' . $this->sTokenType . ' ' . $this->sAccessToken
			),
			CURLOPT_POSTFIELDS => json_encode( $aTransactionParams )
		);
		$oResult = $this->query( $aQueryOptions );
		
		if( !empty($oResult->id) ) return $oResult->id;
		return false;		
	}
	
	public function deleteWebExperienceProfiles( $sProfileId ) {
		$aQueryOptions = array(
			CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'DELETE',
			CURLOPT_TIMEOUT => 20,
			CURLOPT_URL => PAYPAL_ENDPOINT . '/v1/payment-experience/web-profiles/' . $sProfileId,			
			CURLOPT_HTTPHEADER => array(
				'Content-type: application/json',
				'Authorization: ' . $this->sTokenType . ' ' . $this->sAccessToken
			)
		);		
		return $this->query( $aQueryOptions );
	}
	
}