<?php

/**
 * Export module for Fortnox
 * - export data to fortnox
 */

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';
 
class clFortnoxExport extends clFortnoxBase {
	
	public function __construct() {
		$this->sModuleName = 'FortnoxExport';
		$this->sModulePrefix = 'fortnoxExport';
		
		$this->initBase();
	}
	
	/**
	 * Export customer(s) to Fortnox
	 * - requires custom field 'customerFortnoxUrl' at entCustomer
	 */
	public function exportCustomer( $mCustomerId ) {
		$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
		$oFortnoxCustomer = clRegistry::get( 'clFortnoxCustomer', PATH_MODULE . '/fortnox/models/resources' );
		
		/**
		 * Customer data
		 */
		$aCustomers = $oCustomer->read( '*', $mCustomerId );	
		foreach( $aCustomers as $aCustomer ) {
			if( empty($aCustomer['customerFortnoxUrl']) ) {
				$aData = $this->convertDataKeys( $aCustomer, $oFortnoxCustomer->oDao->aDataDict );				
				$mResult = $oFortnoxCustomer->post( $aData );
				if( !empty($mResult['@url']) ) {
					$oCustomer->update( $aCustomer['customerId'], array(
						'customerFortnoxUrl' => $mResult['@url']
					) );
				} else {
					return false;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Export product(s) to Fortnox
	 */
	public function exportProduct( $mProductId ) {
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );		
		$oFortnoxArticle = clRegistry::get( 'clFortnoxArticle', PATH_MODULE . '/fortnox/models/resources' );
		
		/**
		 * Todo: finish product export
		 */
		die();
		
		//$aProducts = $oProduct->read( '*', $mProductId );
	}
	
	/**
	 * Export order line(s) to Fortnox as it's own article
	 * - requires custom field 'lineFortnoxArticleUrl' at entOrderLine
	 */
	public function exportCustomOrderLine( $mLineId ) {
		$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );		
		$oFortnoxArticle = clRegistry::get( 'clFortnoxArticle', PATH_MODULE . '/fortnox/models/resources' );
		
		/**
		 * Order line data
		 */
		$aOrderLines = $oOrderLine->read( '*', $mLineId );
		
		foreach( $aOrderLines as $aLine ) {
			$aData = array(
				'ArticleNumber' => $aLine['lineProductCustomId'],
				'Description' => $aLine['lineProductTitle'],
				//'PurchasePrice' => $aLine['lineProductPrice'],
				//'VAT' => $aLine['lineProductVat'],
				'Type' => 'SERVICE',
				'WebshopArticle' => true
			);			
			$mResult = $oFortnoxArticle->post( $aData );
			if( !empty($mResult['@url']) ) {
				$oOrderLine->update( $aLine['lineId'], array(
					'lineFortnoxArticleUrl' => $mResult['@url']
				) );
			} else {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Export order(s) to Fortnox
	 */
	public function exportOrder( $mOrderId ) {
		$aErr = array();
		
		$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
		$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );		
		$oFortnoxOrder = clRegistry::get( 'clFortnoxOrder', PATH_MODULE . '/fortnox/models/resources' );
		
		/**
		 * Order data
		 */
		$aOrders = $oOrder->read( '*', $mOrderId );
		$aLinesByOrder = groupByValue( 'lineOrderId', $oOrderLine->readByOrder( $mOrderId, '*' ) );
		
		foreach( $aOrders as $aOrder ) {
			if( !empty($aOrder['orderCustomerId']) ) {
				// Make sure customer exists at Fortnox
				if( !$this->exportCustomer( $aOrder['orderCustomerId'] ) ) {
					$aErr[] = _( 'Error during customer export' );
				}
			}
			
			$aOrderLines = &$aLinesByOrder[ $aOrder['orderId'] ];
			
			/**
			 * Todo: finish product export and then this order export
			 */
			die();
			
			// Make sure products exists at Fortnox
			if( !$this->exportProduct( arrayToSingle($aOrderLines, null, 'lineProductId') ) ) {
				$aErr[] = _( 'Error during product export' );
			}
		}
		
		return true;
	}
	
	/**
	 * Export order(s) invoice to Fortnox
	 */
	public function exportOrderInvoice( $mOrderId, $bStandalone = true ) {
		$aErr = array();
		
		$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
		$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );
		$oOrderInvoice = clRegistry::get( 'clOrderInvoice', PATH_MODULE . '/order/models' );
		
		$oFortnoxInvoice = clRegistry::get( 'clFortnoxInvoice', PATH_MODULE . '/fortnox/models/resources' );
		
		/**
		 * Order invoice data
		 */
		$aInvoiceByOrder = valueToKey( 'invoiceOrderId', $oOrderInvoice->readByOrder( $mOrderId ) );
		
		if( empty($aInvoiceByOrder) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataError' => _( 'No invoice found' )
			) );
			return false;
		} else {
			/**
			 * Order data
			 */
			$aOrders = valueToKey( 'orderId', $oOrder->read( '*', $mOrderId ) );
			$aLinesByOrder = groupByValue( 'lineOrderId', $oOrderLine->readByOrder( $mOrderId, '*' ) );
		}
		
		foreach( $aInvoiceByOrder as $iOrderId => $aInvoice ) {
			if( !empty($aInvoice['invoiceFortnoxUrl']) ) {
				if( count($aInvoiceByOrder) == 1 ) {
					$oNotification = clRegistry::get( 'clNotificationHandler' );
					$oNotification->set( array(
						'dataInformation' => _( 'This order has already been sent to Fortnox' )
					) );
					return true;
				}
			}
			
			/**
			 * Invoice rows
			 */
			$aInvoiceRows = array();
			foreach( $aLinesByOrder[ $iOrderId ] as $aLine ) {
				// Make sure product exist at Fortnox
				if( $bStandalone === true ) {
					$this->exportCustomOrderLine( $aLine['lineId'] );
				} else {
					$this->exportOrderLine( $aLine['lineId'] );
				}
				
				$aInvoiceRows[] = array(
					'ArticleNumber' => $aLine['lineProductCustomId'],
					'DeliveredQuantity' => $aLine['lineProductQuantity']
				);
			}
			
			$aData = array(
				'CustomerNumber' => $aOrders[ $iOrderId ]['orderCustomerId'],
				'InvoiceRows' => $aInvoiceRows
			);
			$mResult = $oFortnoxInvoice->post( $aData );
			if( !empty($mResult['@url']) ) {
				$oOrderInvoice->update( $aInvoice['invoiceId'], array(
					'invoiceFortnoxUrl' => $mResult['@url']
				) );
			} else {
				return false;
			}
		}
		
		return true;
	}
	
}