<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentNets.php';

require_once PATH_FUNCTION . '/fData.php';

class clPaymentNets extends clPaymentBase implements ifPaymentMethod {
	
	protected $rNetsClient;
	
	public function __construct() {
		$this->initBase();
		
		$sWsdl = NETS_WSDL_URL;
		
		if( isset($_GET["wsdl"]) ) {
			$sWsdl = $_GET["wsdl"];
		}
		
		$aParams = array();
		if( strpos($_SERVER["HTTP_HOST"], 'uapp') > 0 ) {
			// Client having proxy
			$aParams = array(
				'proxy_host' => "isa4",
				'proxy_port' => 8080,				
				'trace' => true,
				'exceptions' => true
			);
		} else {
			// Client without proxy
			$aParams = array(			
				'trace' => true,
				'exceptions' => true
			);
		}
		
		$this->rNetsClient = new SoapClient( $sWsdl, $aParams );
		
		// Dependency files
		require_once( PATH_MODULE . '/payment/api/nets/ClassOrder.php' );
		require_once( PATH_MODULE . '/payment/api/nets/ClassItem.php' );
		require_once( PATH_MODULE . '/payment/api/nets/ClassArrayOfItem.php' );
		require_once( PATH_MODULE . '/payment/api/nets/ClassTerminal.php' );
		require_once( PATH_MODULE . '/payment/api/nets/ClassRegisterRequest.php' );
		require_once( PATH_MODULE . '/payment/api/nets/ClassEnvironment.php' );		
		require_once( PATH_MODULE . '/payment/api/nets/ClassQueryRequest.php' );
		require_once( PATH_MODULE . '/payment/api/nets/ClassProcessRequest.php' );
		require_once( PATH_MODULE . '/payment/api/nets/ClassCustomer.php' );
	}
	
	public function query( $sQueryType, $sQuery ) {
		return $this->rNetsClient->__call( $sQueryType, array(
			'parameters' => array(
				'token'       => NETS_TOKEN,
				'merchantId'  => NETS_MERCHANT_ID,
				'request'     => $sQuery 
			)
		) );
	}
	
	public function init( $iOrderId, $aParams = array() ) {
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
		
		// Environment
		$oEnvironment = new Environment(
			$GLOBALS['userLang'], # Language           
			'', # OS                 
			NETS_WEB_SERVICE_PLATFORM # WebServicePlatform 
		);
		
		// Order data
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );
		
		// Update order status
		$this->oOrder->update( $iOrderId, array(
			'orderStatus' => 'intermediate'
		) );
		
		// Terminal
		$oTerminal = new Terminal(
			'', # autoAuth
			implode(',', $GLOBALS['NETS_PAYMENT_METHOD_LIST']), # paymentMethodList
			NETS_LANGUAGE, # language
			'Order ID: ' . $aOrderData['orderId'], # orderDescription
			NETS_CALLBACK_URL, # redirectOnError
			NETS_CALLBACK_URL . '?orderId=' . $aOrderData['orderId'] # redirect_url
		);
		
		// Customer data
		$oCustomer = new Customer(
			'', # Address1
			'', # Address2
			'', # CompanyName
			'', # CompanyRegistrationNumber
			'', # Country
			'', # CustomerNumber
			$aOrderData['orderEmail'], # Email
			'', # FirstName
			'', # LastName
			'', # PhoneNumber
			'', # Postcode
			'', # SocialSecurityNumber
			''  # Town
		);
		
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
		
		$fProductPrice = 0;
		$aProductContainer = array();		
		foreach( $aOrderLineData as $aLine ) {			
			$iPrice = number_format( calculatePrice( $aLine['lineProductPrice'], array(
				'profile' => 'default',
				'additional' => array(
					'vat' => $aLine['lineProductVat'],
				)
			) ), 2, '.', '' );			
			$sPrice = str_replace( '.', '', $iPrice );
			
			$fProductVat = number_format( $aLine['lineProductVat'], 2 );
			
			// Data 
			$aData = array(
				'amount' 		 =>   $sPrice,
				'artNo' 		 =>   (int) $aLine['lineProductId'],
				'discount' 		 =>   '0.00',
				'handling' 		 =>   true,
				'isVatIncluded'  =>   true,
				'quantity' 		 =>   (int) $aLine['lineProductQuantity'],
				'shipping' 		 =>   true,
				'title' 		 =>   $aLine['lineProductTitle'],
				'vat' 			 =>   $fProductVat
			);
			
			$oItemnContainer = new Item(
				$aData['amount'], # (string) amount
				$aData['artNo'], # (string) articleNumber
				$aData['discount'], # (float) discount
				$aData['handling'], # (boolean) handling
				$aData['isVatIncluded'], # (boolean) isVatIncluded
				$aData['quantity'], # (int) quantity
				$aData['shipping'], # (boolean) shipping
				$aData['title'], # (string) title
				$aData['vat']  # (float) VAT
			);
			
			array_push( $aProductContainer, $oItemnContainer );
		}
		
		// Freight
		$fFreightPrice = number_format( calculatePrice($aOrderData['orderFreightPrice']), 2 );
		$sFreightPrice = str_replace( '.', '', $fFreightPrice );
		$fFreightVat = number_format( $aOrderData['orderFreightPriceVat'], 2 );
		
		$aData = array(
			'amount' 		 =>   $sFreightPrice,
			'artNo' 		 =>   0,
			'discount' 		 =>   '0.00',
			'handling' 		 =>   true,
			'isVatIncluded'  =>   true,
			'quantity' 		 =>   (int) 1,
			'shipping' 		 =>   true,
			'title' 		 =>   $aOrderData['orderFreightTypeTitle'],
			'vat' 			 =>   $fFreightVat
		);
		$oFreightContainer = new Item(
			$aData['amount'], # (string) amount
			$aData['artNo'], # (string) articleNumber
			$aData['discount'], # (float) discount
			$aData['handling'], # (boolean) handling
			$aData['isVatIncluded'], # (boolean) isVatIncluded
			$aData['quantity'], # (int) quantity
			$aData['shipping'], # (boolean) shipping
			$aData['title'], # (string) title
			$aData['vat']  # (float) VAT
		);
		array_push( $aProductContainer, $oFreightContainer );

		// Payment price
		$fPaymentPrice = number_format( calculatePrice($aOrderData['orderPaymentPrice']), 2 );
		$fPaymentPrice = str_replace( '.', '', $fPaymentPrice );
		$fPaymentVat = number_format( $aOrderData['orderPaymentPriceVat'], 2 );
		
		$aData = array(
			'amount' 		 =>   $fPaymentPrice,
			'artNo' 		 =>   0,
			'discount' 		 =>   '0.00',
			'handling' 		 =>   true,
			'isVatIncluded'  =>   true,
			'quantity' 		 =>   (int) 1,
			'shipping' 		 =>   true,
			'title' 		 =>   $aOrderData['orderPaymentTypeTitle'],
			'vat' 			 =>   $fPaymentVat
		);
		$oPaymentContainer = new Item(
			$aData['amount'], # (string) amount
			$aData['artNo'], # (string) articleNumber
			$aData['discount'], # (float) discount
			$aData['handling'], # (boolean) handling
			$aData['isVatIncluded'], # (boolean) isVatIncluded
			$aData['quantity'], # (int) quantity
			$aData['shipping'], # (boolean) shipping
			$aData['title'], # (string) title
			$aData['vat']  # (float) VAT
		);
		array_push( $aProductContainer, $oPaymentContainer );
		
		$aArrayOfItem = new ArrayOfItem(
			$aProductContainer
		);
		
		$fOrderTotal = number_format( calculatePrice($aOrderData['orderTotal']), 2 );
		$sOrderTotal = str_replace( array('.', ',', ' '), '', $fOrderTotal );	
	
		// Order
		$oOrderContainer = new Order(
			$sOrderTotal, # amount
			$aOrderData['orderCurrency'], # currencyCode
			NETS_FORCE_3D_SECURE, # force3DSecure
			$aArrayOfItem, # ArrayOfItem
			$aOrderData['orderId'], # orderNumber
			null  # UpdateStoredPaymentInfo
		);
		
		// Register request 
		$oRegisterRequest = new RegisterRequest(
			'', # AvtaleGiro
			'', # CardInfo
			$oCustomer, # Customer
			'', # description
			'', # DnBNorDirectPayment
			$oEnvironment, # Environment
			'', # MicroPayment
			$oOrderContainer, # Order
			'', # Recurring
			'B', # serviceType
			$oTerminal, # Terminal
			'', # transactionId
			''  # transactionReconRef
		);
		
		// Register
		$oRegisterCall = $this->query( 'Register', $oRegisterRequest );
		
		// Register result
		$aRegisterResult = $oRegisterCall->RegisterResult; 
		
		// For extended security
		$sPaymentToken = md5(
			$aRegisterResult->TransactionId . $aOrderData['orderId'] . SITE_DOMAIN . $aOrderData['orderEmail']
		);
		
		// Store the created payment token
		$this->oOrder->update( $iOrderId, array(
			'orderPaymentToken' => $sPaymentToken
		) );
		
		$oRouter = clRegistry::get( 'clRouter' );
		$oRouter->redirect( NETS_TERMINAL_URL . '?merchantId=' . NETS_MERCHANT_ID . '&transactionId=' .  $aRegisterResult->TransactionId );
	}
	
	public function checkStatus() {
		$aOrderData = current( $this->oOrder->read('orderPaymentStatus', $_SESSION['orderId']) );
		if( $aOrderData['orderPaymentStatus'] == 'paid' ) {
			return true;
		}
		return false;
	}
	
	public function finalizeOrder( $iOrderId ) {
		if( empty($_GET['transactionId']) ) return false;
		else $sTransactionId = $_GET['transactionId'];
		
		// Data
		$aOrderData = current( $this->oOrder->read( array(
			'orderId',
			'orderEmail',
			'orderTotal',
			'orderPaymentToken'
		), $iOrderId) );
		
		if( !empty($aOrderData['orderPaymentToken']) ) {
			$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
			$fOrderTotal = number_format( calculatePrice($aOrderData['orderTotal']), 2 );
			
			// Calling AUTH
			$oProcessRequest = new ProcessRequest(
				'order ID ' . $aOrderData['orderId'], # description
				'AUTH', # Request type
				'', # Order Total
				$sTransactionId,
				''
			);
			$oProcessCall = $this->query( 'Process', $oProcessRequest );		
			$oProcessResult = $oProcessCall->ProcessResult;
			if( $oProcessResult->ResponseCode == 'OK' ) {
				// Capture
				$oCaptureRequest = new ProcessRequest(
					'order ID ' . $aOrderData['orderId'], # description
					'CAPTURE', # Request type
					'', # Order Total
					$oProcessResult->TransactionId,
					''
				);
				$oCaptureCall = $this->query( 'Process', $oCaptureRequest );
				$oCaptureResult = $oCaptureCall->ProcessResult;
				
				if( $oCaptureResult->ResponseCode == 'OK' ) {			
					// Create token
					$sPaymentToken = md5(
						$sTransactionId . $iOrderId . SITE_DOMAIN . $aOrderData['orderEmail']
					);
					
					// Is the two tokens same?
					if( $sPaymentToken == $aOrderData['orderPaymentToken'] ) {
						$_SESSION['orderId'] = $_GET['orderId'];
						
						$this->oOrderHistory->create( array(
							'orderHistoryOrderId' => $iOrderId,
							'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
							'orderHistoryGroupKey' => 'Payment',
							'orderHistoryMessage' => "The order was marked as Paid by Nets callback", // Do not use gettext as the string will be permanent
							'orderHistoryData' => ''
						) );
						
						$aData = array(
							'orderStatus' => 'new',
							'orderPaymentStatus' => 'paid',
							'orderPaymentCustomId' => $sTransactionId
						);
						$this->oOrder->update( $iOrderId, $aData );
						
						parent::finalizeOrder( $iOrderId );
						
						return true;
					}
				}
			}
		}
		$this->aErr[] = _( 'Payment could not be completed' );
		return false;
	}
	
}