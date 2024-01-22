<?php

// Payson API version 1.5 integration

require_once PATH_MODULE . '/payment/config/cfPaymentPayson.php';
require_once PATH_MODULE . '/payment/models/clPaymentBase.php';

class clPaymentPaysonBase extends clPaymentBase implements ifPaymentMethod {

	protected $fPaymentPrice;
	protected $aPaymentType;
	
	public function __construct() {
		$this->initBase();
	}

	public function initBase() {
		parent::initBase();
	}

	public function init( $iOrderId, $aParams = array() ) {
		// Update order status
		$this->oOrder->update( $iOrderId, array(
			'orderStatus' => 'intermediate'
		) );
		
		$sToken = $this->requestToken( $iOrderId );
		if( $sToken !== false ) {
			$this->oOrder->update( $iOrderId, array(
				'orderPaymentToken' => $sToken
			) );
			$oRouter = clRegistry::get( 'clRouter' );
			$oRouter->redirect( PAYSON_URL_FORWARD . '?token=' . $sToken );
		}
		
	}

	public function checkStatus() {
		// This function is called on the reciept page,
		// no validation possible from payson without a PaymentDetails request
		$sToken = current($this->oOrder->read( 'orderPaymentToken', $_SESSION['orderId'] ));
		if( empty($sToken['orderPaymentToken']) ) {
			$this->aErr = array( _( 'There was an error while processing your order. ' ) );
			return false;
		} else {			
			$sToken = current($sToken);	
		}
		
		$sRequestData = http_build_query( array( 'token' => $sToken ) );
		
		// HTTP Headers
		$aHttpHeaders = array(
			'PAYSON-SECURITY-USERID' 	=> PAYSON_AGENT_ID,
			'PAYSON-SECURITY-PASSWORD' 	=> PAYSON_MD5
		);		
		if( PAYSON_APPLICATION_ID !== false ) $aHttpHeaders[ 'PAYSON-APPLICATION-ID' ] = PAYSON_APPLICATION_ID;
		$sHeaders = 'Content-type: application/x-www-form-urlencoded' . "\r\n";
		foreach( $aHttpHeaders as $sKey => $sValue ) {
			$sHeaders .= $sKey . ': ' . $sValue . "\r\n";
		}
	
		// Build and send query
		$aHttpOpts = array (
			'http' => array(
				'method' => "POST",
				'header' => $sHeaders,
				'content' => $sRequestData
			)
		);
		$context = stream_context_create($aHttpOpts);
		$sRequest = file_get_contents( PAYSON_URL_PAYDETAILS, false, $context );
		
		// Decode answer, check for failures
		parse_str($sRequest, $aRequest); // parse_str does replace dots with underscores, so remember that
		
		if( !empty($aRequest['responseEnvelope_ack']) && $aRequest['responseEnvelope_ack'] == 'SUCCESS' ) {
			switch( $aRequest['status'] ) {
				case 'CREATED':
					break;
				
				case 'PENDING':
					break;
				
				case 'PROCESSING':
					break;
				
				case 'COMPLETED':
					$this->oOrder->update( $_SESSION['orderId'], array(
						'orderStatus' => 'new',
						'orderPaymentStatus' => 'paid',
						'orderPaymentCustomId' => ( !empty($aRequest['purchaseId']) ? $aRequest['purchaseId'] : '' )
					) );
					$oNotificationHandler = clRegistry::get( 'clNotificationHandler' );
					$oNotificationHandler->aNotifications = array();
					break;
				
				case 'CREDITED':
					break;
				
				case 'INCOMPLETE':
					$this->aErr = array( _( 'Some transfers succeeded and some failed for a parallel payment.' ) );
					return false;
					break;
				
				case 'ERROR':
					$this->aErr = array( _( 'The payment failed and all attempted transfers failed or all completed transfers were successfully reversed.' ) );
					return false;
					break;
				
				case 'ERROR':
					$this->aErr = array( _( 'A payment requiring approval was not executed within 3 hours.' ) );
					return false;
					break;
				
				case 'REVERSALERROR':
					$this->aErr = array( _( 'One or more transfers failed when attempting to reverse a payment.' ) );
					return false;
					break;
				
				case 'ABORTED':
					$this->aErr = array( _( 'The payment was aborted before any money were transferred.' ) );
					return false;
					break;				
			}

		} else {
			$this->aErr = array( _( 'There was an error while processing your order. ' ) );
			return false;
		}
		
		return true;
	}

	public function finalizeOrder( $iOrderId ) {
		if( empty($iOrderId) ) return false;
		
		$sRequestData = file_get_contents('php://input');
		
		if( $_POST['status'] == 'ERROR' ) {
			// There was a error completing this order
			return false;
		} else {
			// Validate the request
			
			// HTTP Headers
			$aHttpHeaders = array(
				'PAYSON-SECURITY-USERID' 	=> PAYSON_AGENT_ID,
				'PAYSON-SECURITY-PASSWORD' 	=> PAYSON_MD5
			);		
			if( PAYSON_APPLICATION_ID !== false ) $aHttpHeaders[ 'PAYSON-APPLICATION-ID' ] = PAYSON_APPLICATION_ID;
			$sHeaders = '';
			foreach( $aHttpHeaders as $sKey => $sValue ) {
				$sHeaders .= $sKey . ': ' . $sValue . "\r\n";
			}
		
			// Build and send query
			$aHttpOpts = array (
				'http' => array(
					'method' => "POST",
					'header' => $sHeaders,
					'content' => $sRequestData
				)
			);
			$context = stream_context_create($aHttpOpts);
			
			$sResponse = file_get_contents( PAYSON_URL_VALIDATE, false, $context );
			
			if( $sResponse == 'VERIFIED' ) {
				// Verification is valid
				parent::finalizeOrder( $iOrderId );
				$aData = array(
					'orderStatus' => 'new',
					'orderPaymentStatus' => 'paid',
					'orderPaymentCustomId' => ( !empty($_POST['purchaseId']) ? $_POST['purchaseId'] : '' )
				);
				return $this->oOrder->update( $iOrderId, $aData );
			} else {
				// Failed verification				
				return false;
			}
		}
	}
	
	public function requestToken( $iOrderId ) {
		if( empty($this->aPaymentType) ) {
			return false;
			
		} elseif( !is_array($this->aPaymentType) ) {
			$this->aPaymentType = (array) $this->aPaymentType;
			
		}
		
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );
		$aOrderLineData = $this->oOrderLine->readByOrder( $iOrderId, array(
			'lineProductId',
			'lineProductCustomId',
			'lineProductTitle',
			'lineProductQuantity',
			'lineProductPrice',
			'lineProductVat'
		) );
		
		$aAllowedTypes = array();
		foreach( $this->aPaymentType as $sPaymentType ) {
			switch( $sPaymentType ) {
				case 'bank':
					$aAllowedTypes[] = 'BANK';
					break;
				
				case 'card':
					$aAllowedTypes[] = 'CREDITCARD';
					break;
				
				case 'invoice':
					$aAllowedTypes[] = 'INVOICE';
					break;
				
				case 'combined':
					$aAllowedTypes = array(
						'BANK',
						'CREDITCARD'
					);
					break;
				
				default:
					return false;
					break;			
			}
		}
		
		require_once PATH_FUNCTION . '/fData.php';
		$oRouter = clRegistry::get( 'clRouter' );
		
		// Build data
		$aData = array(
			'returnUrl' => PAYSON_RETURN_URL,
			'cancelUrl' => PAYSON_CANCEL_URL,
			'memo' => sprintf( PAYSON_PURCHASE_DESCRIPTION, $iOrderId ),
			'ipnNotificationUrl' => PAYSON_IPN_URL,
			'localeCode' => 'SV', // Can be SV, EN and FI TODO dynamic and default
			'currencyCode' => 'SEK' // Can be SEK or EUR TODO dynamic			
		);
		
		// Payment method
		foreach( $aAllowedTypes as $key => $sPaymentMethodName ) {
			$aData['fundingList.fundingConstraint(' . $key . ').constraint'] = $sPaymentMethodName;
		}
		
		// Split name to find out first and last name
		$aNameData = explode( ' ', trim($aOrderData['orderPaymentName']) );
		
		$sFirstName = '-';
		$sLastName = '-';
		if( isset($aNameData[0]) ) {
			$sFirstName = $aNameData[0];
			if( count($aNameData) > 1 ) $sLastName = end( $aNameData );
		}

		$aData += array(
			'feesPayer' => PAYSON_TRANSACTION_PAYMENT_FEE,
			'custom' => $iOrderId, // Must be orderId so we can validate the request later
			'trackingId' => '',
			'guaranteeOffered' => PAYSON_GUARANTEE_OFFERED,
			## Sender Details 
			'senderEmail' => $aOrderData['orderEmail'],
			'senderFirstName' => $sFirstName,
			'senderLastName' => $sLastName,
			## Receiver Details
			'receiverList.receiver(0).email' => PAYSON_RECEIVER_EMAIL,
			'receiverList.receiver(0).amount' => $aOrderData['orderTotal'],
			'receiverList.receiver(0).primary' => 'true',
			'receiverList.receiver(0).firstName' => '',
			'receiverList.receiver(0).lastName' => ''
		);
		
		if( in_array('invoice', $this->aPaymentType)  ) {
			// The invoice fee can only be specified for invoices
			$aData['invoiceFee'] = $this->fPaymentPrice;
			$aData['feesPayer'] = 'PRIMARYRECEIVER';
		}
		
		$iCount = 0;
		foreach( $aOrderLineData as $entry ) {
			$aData['orderItemList.orderItem(' . $iCount . ').description'] = $entry['lineProductTitle'];
			$aData['orderItemList.orderItem(' . $iCount . ').sku'] = ( !empty($entry['lineProductCustomId']) ? $entry['lineProductCustomId'] : '-' );
			$aData['orderItemList.orderItem(' . $iCount . ').quantity'] = $entry['lineProductQuantity'];
			$aData['orderItemList.orderItem(' . $iCount . ').unitPrice'] = $entry['lineProductPrice'];
			$aData['orderItemList.orderItem(' . $iCount . ').taxPercentage'] = $entry['lineProductVat'];
			++$iCount;
		}

		// Add payment if not invoice
		if( !in_array('invoice', $this->aPaymentType)  ) {
			$aData['orderItemList.orderItem(' . $iCount . ').description'] = $aOrderData['orderPaymentTypeTitle'];
			$aData['orderItemList.orderItem(' . $iCount . ').sku'] = _( 'Payment price' );
			$aData['orderItemList.orderItem(' . $iCount . ').quantity'] = '1';
			$aData['orderItemList.orderItem(' . $iCount . ').unitPrice'] = number_format( ($aOrderData['orderPaymentPrice'] - $aOrderData['orderPaymentPriceVat']), 2 );
			$aData['orderItemList.orderItem(' . $iCount . ').taxPercentage'] = '0.25';
			++$iCount;
		}
		// Add freight
		$aData['orderItemList.orderItem(' . $iCount . ').description'] = $aOrderData['orderFreightTypeTitle'];
		$aData['orderItemList.orderItem(' . $iCount . ').sku'] = _( 'Freight' );
		$aData['orderItemList.orderItem(' . $iCount . ').quantity'] = '1';
		$aData['orderItemList.orderItem(' . $iCount . ').unitPrice'] = number_format( ($aOrderData['orderFreightPrice'] - $aOrderData['orderFreightPriceVat']), 2 );
		$aData['orderItemList.orderItem(' . $iCount . ').taxPercentage'] = $aOrderData['orderFreightPriceVat'] != '0' ? '0.25' : '0';
		++$iCount;

		// HTTP Headers
		$aHttpHeaders = array(
			'Content-type' => 			'application/x-www-form-urlencoded',
			'PAYSON-SECURITY-USERID' 	=> PAYSON_AGENT_ID,
			'PAYSON-SECURITY-PASSWORD' 	=> PAYSON_MD5
		);		
		if( PAYSON_APPLICATION_ID !== false ) $aHttpHeaders[ 'PAYSON-APPLICATION-ID' ] = PAYSON_APPLICATION_ID;
		$sHeaders = '';
		foreach( $aHttpHeaders as $sKey => $sValue ) {
			$sHeaders .= $sKey . ': ' . $sValue . "\r\n";
		}
	
		// Build and send query
		$aHttpOpts = array (
			'http' => array(
				'method' => "POST",
				'header' => $sHeaders,
				'content' => http_build_query( $aData )
			)
		);
		$context = stream_context_create($aHttpOpts);		
		$aResponse = file_get_contents( PAYSON_URL_API . '/1.0/Pay/', false, $context );
		if( $aResponse === false ) {
			return false;
		}
		
		// Decode answer, check for failures
		parse_str($aResponse, $aResponse); // parse_str does replace dots with underscores, so remember that
		
		if( !empty($aResponse['responseEnvelope_ack']) && $aResponse['responseEnvelope_ack'] == 'FAILURE' ) {
			// A fail occured, loop thru error messages
			$aErrors = array();
			foreach( $aResponse as $sResponseKey => $sResponseValue ) {
				if( strstr($sResponseKey, 'errorList_error' ) ) {
					// This is an error, log all three response parameters
					if( preg_match( '/^errorList_error\((.*)\)_(.*)$/', $sResponseKey, $aMatches ) ) {
						$aErrors[ $aMatches[1] ][ $aMatches[2] ] = $sResponseValue;
					}
				}
			}
			
			$this->oOrderHistory->create( array(
				'orderHistoryOrderId' => $iOrderId,
				'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
				'orderHistoryGroupKey' => 'Payment',
				'orderHistoryMessage' => "There was an error processing this order with Payson", // Do not use gettext as the string will be permanent
				'orderHistoryData' => '<pre>' . var_export($aErrors, true) . '</pre>'
			) );
			throw new Exception( _('There was an error processing your order when contacting the payment gateway') );
			return false;		
		}
		
		return $aResponse['TOKEN'];
	}

}
