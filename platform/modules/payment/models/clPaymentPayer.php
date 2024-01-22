<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentPayer.php';

require_once PATH_FUNCTION . '/fData.php';

class clPaymentPayerCard extends clPaymentBase implements ifPaymentMethod {

	protected $oPayerApi;

	public function __construct() {
		$this->initBase();
		
		// Api
		$this->oPayerApi = clRegistry::get( 'payread_post_api', PATH_MODULE . '/payment/api/payer' );
	}
	
	public function init( $iOrderId, $aParams = array() ) {
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
		
		// Test & debuging
		$this->oPayerApi->set_test_mode( PAYER_TEST_MODE );
		$this->oPayerApi->set_debug_mode( PAYER_DEBUG_MODE );
		
		// Setting		
		$this->oPayerApi->set_hide_details( PAYER_DISPLAY_ITEM_SPECIFICATION_LIST );
		$this->oPayerApi->set_language( PAYER_DISPLAY_LANGUAGE );
		$this->oPayerApi->set_message( PAYER_PURCHASE_MESSAGE );
		$this->oPayerApi->setCharSet( 'UTF-8' );
		$this->oPayerApi->set_currency( 'SEK' );
		
		// Security
		## $this->oPayerApi->setKeys( PAYER_KEY1, PAYER_KEY2 );
		## $this->oPayerApi->add_valid_ip( $_SERVER['SERVER_ADDR'] );		
		
		// URLs
		$this->oPayerApi->set_redirect_back_to_shop_url( PAYPAL_CANCEL_URL );		
		$this->oPayerApi->set_authorize_notification_url( PAYER_AUTHORIZE_NOTIFICATION_URL . '?orderId=' . $iOrderId );
		$this->oPayerApi->set_settle_notification_url( PAYER_SETTLE_NOTIFICATION_URL . '?orderId=' . $iOrderId );
		$this->oPayerApi->set_success_redirect_url( PAYPAL_RETURN_URL );
		
		/**
		* Select payment method
		* @param string $theMethod Can be set to sms, card, bank, phone, invoice & auto (required)
		*/
		$this->oPayerApi->add_payment_method( 'card' );
		
		// Order data
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );
		// Set reference ID
		$this->oPayerApi->set_reference_id( $aOrderData['orderId'] );
		// Optional extra information
		$this->oPayerApi->set_description( 'Order ID: ' . $aOrderData['orderId'] );
		
		if( empty($aOrderData) ) {
			return false;
		}
		
		// Update order status
		$this->oOrder->update( $iOrderId, array(
			'orderStatus' => 'intermediate'
		) );
		
		// Order line data
		$aOrderLineData = $this->oOrderLine->readByOrder( $iOrderId, array(
			'lineId',
			'lineProductId',
			'lineProductCustomId',
			'lineProductTitle',
			'lineProductQuantity',
			'lineProductPrice',
			'lineProductVat'
		) );
		
		// Add buyer info
		$this->oPayerApi->add_buyer_info(
			'', # (string) $theFirstName
			'', # (string) $theLastName
			'', # (string) $theAddressLine1
			'', # (string) $theAddressLine2
			'', # (string) $thePostalcode
			'', # (string) $theCity
			'se', # (string) $theCountryCode
			'', # (string) $thePhoneHome
			'', # (string) $thePhoneWork
			'', # (string) $thePhoneMobile
			$aOrderData['orderEmail'] # (string) $theEmail
			# null, # [string $theOrganisation = '']
			# null, # [string $theOrgNr = '']
			# null, # [string $theCustomerId = '']
			# null, # [string $theYourReference = '']
			# null  # [string $theOptions = '']
		);
		
		// Add products (extended version)
		$iLineCount = 1;
		foreach( $aOrderLineData as $aLine ) {
			$fPrice = number_format( calculatePrice( $aLine['lineProductPrice'], array(
				'profile' => 'default',
				'additional' => array(					
					'vat' => $aLine['lineProductVat']
				)
			)), 2 );
			$fPrice = str_replace(',', '', $fPrice);
			
			// Product
			$this->oPayerApi->add_freeform_purchase_ex(
				(string) $iLineCount, # (int) $theLineNumber
				$aLine['lineProductTitle'], # (string) $theDescription
				$aLine['lineProductId'], # (int) $theItemNumber				
				$fPrice, # (float) $thePrice
				number_format( ($aLine['lineProductVat'] * 100), 2 ), # (float) $theVat
				$aLine['lineProductQuantity']  # (int) $theQuantity
				# null, # [string $theUnit = null]
				# null, # [string $theAccount = null]
				# null  # [string $theDistAgentId = null]
			);
			
			// Additional for usability for customer at Payer webpage
			/*
			$this->oPayerApi->add_info_line(
				$iLineCount, # (int) $theLineNumber
				$aLine['lineProductTitle'], # (string) $theText
			)
			*/
			
			++$iLineCount;
		}
		
		/* Add subscription
		 * - this can be useful in the future
		 */
		/* $iLineCount = 1;
		foreach( $aOrderLineData as $aLine ) {
			// Product
			$this->oPayerApi->add_subscription_purchase(
				'', # (int) $theLineNumber
				'', # (string) $theDescription
				'', # (int) $theItemNumber
				'', # (float) $theInitialPrice
				'', # (float) $theRecurringPrice
				'', # (float) $theVat
				'', # (int) $theQuantity
				'', # (int) $theUnit
				'', # (string) $theAccount
				'', # (string) $theStartDate
				'', # (string) $theStopDate
				'', # (int) $theCount
				'', # (string) $thePeriodicity
				''  # (int) $theCancelDays
			);
			
			++$iLineCount;
		} */
		
		// Freight fee
		if( !empty($aOrderData['orderFreightPrice']) ) {
			$fFreightPrice = number_format( calculatePrice($aOrderData['orderFreightPrice']), 2 );
			$this->oPayerApi->add_freeform_purchase_ex(
				(string) $iLineCount, # (int) $theLineNumber
				_( 'Freight' ) . ': ' . $aOrderData['orderFreightTypeTitle'], # (string) $theDescription
				'', # (int) $theItemNumber				
				$fFreightPrice, # (float) $thePrice
				'25.00', # number_format( ($aOrderData['orderFreightPriceVat']), 2 ), # (float) $theVat
				'1' # (int) $theQuantity
				# null, # [string $theUnit = null]
				# null, # [string $theAccount = null]
				# null  # [string $theDistAgentId = null]
			);
		}
		
		// Payment fee
		if( !empty($aOrderData['orderPaymentPrice']) ) {
			$aOrderData['orderPaymentPrice'] = (float) $aOrderData['orderPaymentPrice'];
			
			if( $aOrderData['orderPaymentPrice'] > 0 ) {
				$this->oPayerApi->set_fee(
					$aOrderData['orderPaymentTypeTitle'], # (string) $theDescription
					$aOrderData['orderPaymentPrice'], # (float) $thePrice
					$aOrderData['orderPaymentType'] # (int) $theItemNumber
					# null, # [float $theVat = 25]
					# null  # [int $theQuantity = 1]
				);
			}
		}
		
		// Data
		$aData = array(
			'payer_agentid' => $this->oPayerApi->get_agentid(),
			'payer_xml_writer' => $this->oPayerApi->get_api_version(),
			'payer_data' => $this->oPayerApi->get_xml_data(),
			'payer_checksum' => $this->oPayerApi->get_checksum()
		);
		if( $this->oPayerApi->get_charset() !== null ) {
			$aData['payer_charset'] = $this->oPayerApi->get_charset();
		}
		
		// Store the checksum
		$this->oOrder->update( $iOrderId, array(
			'orderPaymentToken' => $aData['payer_checksum']
		) );
		
		$this->sendPostData( $aData, $this->oPayerApi->get_server_url() );
	}
	
	public function checkStatus() {
		$aOrderData = current( $this->oOrder->read('orderPaymentStatus', $_SESSION['orderId']) );
		if( $aOrderData['orderPaymentStatus'] == 'paid' ) {
			return true;
		}
		return false;
	}

	public function finalizeOrder( $iOrderId ) {		
		$sCallback = $this->oPayerApi->get_request_url();
		
		$sStrippedCallback = substr( $sCallback, 0, strpos($sCallback, "&md5sum") );
		
		$sChecksum = strtolower( md5( PAYER_KEY1 . $sStrippedCallback . PAYER_KEY2 ) );
		
		if( strpos( strtolower($sCallback), $sChecksum ) >= 7 ) {
			/* Payment valid */
			
			$_SESSION['orderId'] = $_GET['orderId'];
			
			$this->oOrderHistory->create( array(
				'orderHistoryOrderId' => $iOrderId,
				'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
				'orderHistoryGroupKey' => 'Payment',
				'orderHistoryMessage' => "The order was marked as Paid by Payer callback", // Do not use gettext as the string will be permanent
				'orderHistoryData' => ''
			) );
			
			$aData = array(
				'orderStatus' => 'new',
				'orderPaymentStatus' => 'paid',
				'orderPaymentCustomId' => ( !empty($_GET['payer_payment_id']) ? $_GET['payer_payment_id'] : '' )
			);
			$this->oOrder->update( $iOrderId, $aData );
			
			parent::finalizeOrder( $iOrderId );
			
			return true;
		}
		$this->aErr[] = _( 'Payment could not be completed' );
		return false;
	}

	public function validateIpAddress() {
		return $this->oPayerApi->is_valid_ip();
	}
	
	public function validateCallback() {
		return $this->oPayerApi->is_valid_callback();
	}
	
}
