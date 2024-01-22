<?php

/* * * *
 * Filename: clInvoiceLineDaoMysql.php
 * Created: 28/03/2014 by Markus
 * Reference: database-overview.mwb
 * Description: See clInvoiceLine.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clInvoiceLineDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entInvoiceLine' => array(
				'invoiceLineId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'Invoice line ID' )
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
				'invoiceLineInvoiceId' => array(
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
				),
				'invoiceLineParentLineId' => array(
					'type' => 'integer'
				),
				'invoiceLineParentType' => array(
					'type' => 'array',
					'values' => array(
						'credit' => _( 'Kredit' ),
						'copy' => _( 'Kopia' )
					)
				),
				'invoiceLineSubmissionRememberLineId' => array(
					'type' => 'integer'
				),
			)
		);
		$this->sPrimaryEntity = 'entInvoiceLine';
		$this->sPrimaryField = 'invoiceLineId';
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
			'invoiceLineInvoiceId' => null,
			'invoiceLineUserId' => null,
			'invoiceLineAuctionId' => null,
			'invoiceLineItemId' => null,
			'invoiceLineParentLineId' => null,
			'invoiceLineParentType' => null,
			'invoiceLineSubmissionRememberLineId' => null
		);

		$aDaoParams['fields'] = $aParams['fields'];

		if( $aParams['invoiceLineAccountingCode'] !== null ) {
			if( is_array($aParams['invoiceLineAccountingCode']) ) {
				$aCriterias[] = 'invoiceLineAccountingCode IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineAccountingCode']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineAccountingCode = ' . (int) $aParams['invoiceLineAccountingCode'];
			}
		}

		if( $aParams['invoiceLineInvoiceId'] !== null ) {
			if( is_array($aParams['invoiceLineInvoiceId']) ) {
				$aCriterias[] = 'invoiceLineInvoiceId IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineInvoiceId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineInvoiceId = ' . (int) $aParams['invoiceLineInvoiceId'];
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

		if( $aParams['invoiceLineParentLineId'] !== null ) {
			if( is_array($aParams['invoiceLineParentLineId']) ) {
				$aCriterias[] = 'invoiceLineParentLineId IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineParentLineId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineParentLineId = ' . (int) $aParams['invoiceLineParentLineId'];
			}
		}

		if( $aParams['invoiceLineParentType'] !== null ) {
			if( is_array($aParams['invoiceLineParentType']) ) {
				$aCriterias[] = "invoiceLineParentType IN('" . implode( "', '", $aParams['invoiceLineParentType'] ) . "')";
			} else {
				$aCriterias[] = 'invoiceLineParentType = ' . $this->oDb->escapeStr( $aParams['invoiceLineParentType'] );
			}
		}

		if( $aParams['invoiceLineSubmissionRememberLineId'] !== null ) {
			if( is_array($aParams['invoiceLineSubmissionRememberLineId']) ) {
				$aCriterias[] = 'invoiceLineSubmissionRememberLineId IN(' . implode( ', ', array_map('intval', $aParams['invoiceLineSubmissionRememberLineId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLineSubmissionRememberLineId = ' . (int) $aParams['invoiceLineSubmissionRememberLineId'];
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}
}
