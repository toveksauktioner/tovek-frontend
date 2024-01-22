<?php

/* * * *
 * Filename: clInvoiceQueueLineDaoMysql.php
 * Description: See clInvoiceQueueLine.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clInvoiceQueueLineDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entInvoiceQueueLine' => array(
				'invoiceQueueLineId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Invoice queue line ID' )
				),
				'invoiceLineTitle' => array(
					'type' => 'string',
					'title' => _( 'Title' ),
				),
				'invoiceLineQuantity' => array(
					'type' => 'integer',
					'title' => _( 'Quantity' )
				),
				'invoiceLinePrice' => array(
					'type' => 'float',
					'title' => _( 'Price' )
				),
				'invoiceLineVatValue' => array(
					'type' => 'integer',
					'title' => _( 'VAT value' )
				),
				'invoiceLineFee' => array(
					'type' => 'float',
					'title' => _( 'Fee' )
				),
				// Foreign key's
				'invoiceLineAccountingCode' => array(
					'title' => _( 'Accounting code' ),
					'type' => 'integer'
				),
				'invoiceLineInvoiceQueueId' => array(
					'type' => 'integer'
				),
				'invoiceLineUserId' => array(
					'type' => 'integer'
				),
				'invoiceLineAuctionId' => array(
					'type' => 'integer'
				),
				'invoiceLineItemId' => array(
					'type' => 'integer'
				)
			)
		);
		$this->sPrimaryEntity = 'entInvoiceQueueLine';
		$this->sPrimaryField = 'invoiceQueueLineId';		
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
			'invoiceLineAccountingCode' => null,
			'invoiceLineInvoiceQueueId' => null,
			'invoiceLineUserId' => null,
			'invoiceLineAuctionId' => null,
			'invoiceLineItemId' => null
		);
		
		$aDaoParams['fields'] = $aParams['fields'];
		
		if( $aParams['invoiceLineAccountingCode'] !== null ) {
			if( is_array($aParams['invoiceLineAccountingCode']) ) {
				$aCriterias[] = 'invoiceLineAccountingCode IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineAccountingCode']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineAccountingCode = ' . (int) $aParams['invoiceLineAccountingCode'];
			}
		}
		
		if( $aParams['invoiceLineInvoiceQueueId'] !== null ) {
			if( is_array($aParams['invoiceLineInvoiceQueueId']) ) {
				$aCriterias[] = 'invoiceLineInvoiceQueueId IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineInvoiceQueueId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineInvoiceQueueId = ' . (int) $aParams['invoiceLineInvoiceQueueId'];
			}
		}
		
		if( $aParams['invoiceLineUserId'] !== null ) {
			if( is_array($aParams['invoiceLineUserId']) ) {
				$aCriterias[] = 'invoiceLineUserId IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineUserId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineUserId = ' . (int) $aParams['invoiceLineUserId'];
			}
		}
		
		if( $aParams['invoiceLineAuctionId'] !== null ) {
			if( is_array($aParams['invoiceLineAuctionId']) ) {
				$aCriterias[] = 'invoiceLineAuctionId IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineAuctionId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineAuctionId = ' . (int) $aParams['invoiceLineAuctionId'];
			}
		}
		
		if( $aParams['invoiceLineItemId'] !== null ) {
			if( is_array($aParams['invoiceLineItemId']) ) {
				$aCriterias[] = 'invoiceLineItemId IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineItemId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineItemId = ' . (int) $aParams['invoiceLineItemId'];
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->readData( $aDaoParams );
	}	
}
