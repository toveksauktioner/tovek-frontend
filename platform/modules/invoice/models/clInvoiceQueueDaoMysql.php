<?php

/* * * *
 * Filename: clInvoiceQueueDaoMysql.php
 * Description: See clInvoiceQueue.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clInvoiceQueueDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entInvoiceQueue' => array(
				'invoiceQueueId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Invoice Queue ID' )
				),
				'invoiceStatus' => array(
					'type' => 'array',
					'values' => array(
						'unpaid' => _( 'Unpaid' ),
						'partpaid' => _( 'Part paid' ),
						'paid' => _( 'Paid' )
					),
					'title' => _( 'Invoice status' )
				),
				'invoiceType' => array(
					'type' => 'array',
					'values' => array(
						'invoice' => _( 'Faktura' ),
						'credit' => _( 'Credit' ),
						'interest' => _( 'Interest' )
					),
					'title' => _( 'Invoice type' )
				),
				'invoiceInformation' => array(
					'type' => 'string',
					'title' => _( 'Invoice information' )
				),
				'invoiceFirstname' => array(
					'type' => 'string',
					'title' => _( 'Firstname' )
				),
				'invoiceSurname' => array(
					'type' => 'string',
					'title' => _( 'Surname' )
				),
				'invoiceCompanyName' => array(
					'type' => 'string',
					'title' => _( 'Company name' )
				),
				'invoiceAddress' => array(
					'type' => 'string',
					'title' => _( 'Address' )
				),
				'invoiceZipCode' => array(
					'type' => 'string',
					'title' => _( 'Zip Code' )
				),
				'invoiceCity' => array(
					'type' => 'string',
					'title' => _( 'City' )
				),
				'invoiceCountryCode' => array(
					'type' => 'string',
					'title' => _( 'Country code' )
				),
				'invoiceFee' => array(
					'type' => 'float',
					'title' => _( 'Administrative fee' )
				),
				'invoiceLateInterest' => array(
					'type' => 'int',
					'title' => _( 'Late interest' )
				),
				'invoiceVat' => array(
					'type' => 'array',
					'values' => array(
						'yes' => _( 'Yes' ),
						'no' => _( 'No' )
					),
					'title' => _( 'VAT' )
				),
				'invoiceCreditDays' => array(
					'type' => 'integer',
					'title' => _( 'Days of credit' )
				),
				'invoiceTotalAmount' => array(
					'type' => 'float',
					'title' => _( 'Amount' )
				),
				'invoiceTotalVat' => array(
					'type' => 'float',
					'title' => _( 'VAT' )
				),
				'invoiceDate' => array(
					'type' => 'date',
					'title' => _( 'Invoice date' )
				),
				'invoiceDueDate' => array(
					'type' => 'date',
					'title' => _( 'Due date' )
				),
				'invoiceNotes' => array(
					'type' => 'string',
					'title' => _( 'Invoice notes' )
				),
				'invoiceCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Invoice created' )
				),
				'invoiceUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Invoice updated' )
				),
				// Foreign key's
				'invoiceUserId' => array(
					'type' => 'integer'
				),
				'invoiceAuctionId' => array(
					'type' => 'integer'
				),
				'invoiceAuctionPartId' => array(
					'type' => 'integer'
				),
				'invoiceOrderId' => array(
					'type' => 'integer'
				)
			)
		);
		$this->sPrimaryEntity = 'entInvoiceQueue';
		$this->sPrimaryField = 'invoiceQueueId';		
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
			'invoiceAuctionId' => null,
			'invoiceAuctionPartId' => null,
			'invoiceUserId' => null,
			'invoiceOrderId' => null
		);
		
		$aDaoParams['fields'] = $aParams['fields'];
		
		if( $aParams['invoiceAuctionId'] !== null ) {
			if( is_array($aParams['invoiceAuctionId']) ) {
				$aCriterias[] = 'invoiceAuctionId IN(' . implode( ', ', array_map('intval', $aParams['invoiceAuctionId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceAuctionId = ' . (int) $aParams['invoiceAuctionId'];
			}
		}
		
		if( $aParams['invoiceAuctionPartId'] !== null ) {
			if( is_array($aParams['invoiceAuctionPartId']) ) {
				$aCriterias[] = 'invoiceAuctionPartId IN(' . implode( ', ', array_map('intval', $aParams['invoiceAuctionPartId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceAuctionPartId = ' . (int) $aParams['invoiceAuctionPartId'];
			}
		}
		
		if( $aParams['invoiceUserId'] !== null ) {
			if( is_array($aParams['invoiceUserId']) ) {
				$aCriterias[] = 'invoiceUserId IN(' . implode( ', ', array_map('intval', $aParams['invoiceUserId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceUserId = ' . (int) $aParams['invoiceUserId'];
			}
		}
		
		if( $aParams['invoiceOrderId'] !== null ) {
			if( is_array($aParams['invoiceOrderId']) ) {
				$aCriterias[] = 'invoiceOrderId IN(' . implode( ', ', array_map('intval', $aParams['invoiceOrderId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceOrderId = ' . (int) $aParams['invoiceOrderId'];
			}
		}
		
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->readData( $aDaoParams );
	}
	
}
