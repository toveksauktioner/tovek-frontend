<?php

/* * * *
 * Filename: clInvoiceOrderDaoMysql.php
 * Created: 22/05/2014 by Markus
 * Reference: database-overview.mwb
 * Description: See clInvoiceOrder.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clInvoiceOrderDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entInvoiceOrder' => array(
				'invoiceOrderId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Payment no' )
				),
				'invoiceOrderCustomId' => array(
					'type' => 'string',
					'title' => _( 'Custom ID' )
				),
				'invoiceOrderUrl' => array(
					'type' => 'string',
					'title' => _( 'Payment URL' )
				),
				'invoiceOrderToken' => array(
					'type' => 'string',
					'title' => _( 'Token' )
				),
				'invoiceOrderTotalAmount' => array(
					'type' => 'float',
					'title' => _( 'Total Amount' )
				),
				'invoiceOrderTotalVat' => array(
					'type' => 'float',
					'title' => _( 'Total VAT' )
				),
				'invoiceOrderStatus' => array(
					'type' => 'array',
					'values' => array(
						'new' => _( 'New' ),
						'intermediate' => _( 'Intermediate' ),
						'processed' => _( 'Processed' ),
						'completed' => _( 'Completed' ),
						'cancelled' => _( 'Cancelled' )
					),
					'title' => _( 'Payment status' )
				),
				'invoiceOrderCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Payment created' )
				),
				// Foreign key's
				'invoiceOrderUserId' => array(
					'type' => 'integer'
				),
				'invoiceOrderPaymentId' => array(
					'type' => 'integer'
				)
			)
		);
		$this->sPrimaryEntity = 'entInvoiceOrder';
		$this->sPrimaryField = 'invoiceOrderId';		
		$this->aFieldsDefault = array( '*' );
		
		$this->init();
	}

	/* * *
	 * Combined dao function for reading data
	 * based on foreign key's
	 * * */
	public function readByForeignKey( $aParams ) {
		$aDaoParams = array();
		$sCriterias = array();
		
		$aParams += array(
			'invoiceOrderUserId' => null,
			'invoiceOrderPaymentId' => null
		);
		
		$aDaoParams['fields'] = $aParams['fields'];
		
		if( $aParams['invoiceOrderUserId'] !== null ) {
			if( is_array($aParams['invoiceOrderUserId']) ) {
				$aCriterias[] = 'invoiceOrderUserId IN(' . implode( ', ', array_map('intval', $aParams['invoiceOrderUserId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceOrderUserId = ' . (int) $aParams['invoiceOrderUserId'];
			}
		}
		
		if( $aParams['invoiceOrderPaymentId'] !== null ) {
			if( is_array($aParams['invoiceOrderPaymentId']) ) {
				$aCriterias[] = 'invoiceOrderPaymentId IN(' . implode( ', ', array_map('intval', $aParams['invoiceOrderPaymentId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceOrderPaymentId = ' . (int) $aParams['invoiceOrderPaymentId'];
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->readData( $aDaoParams );
	}
	
}
