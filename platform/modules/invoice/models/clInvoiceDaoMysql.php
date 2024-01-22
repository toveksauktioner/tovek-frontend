<?php

/* * * *
 * Filename: clInvoiceDaoMysql.php
 * Created: 28/03/2014 by Markus
 * Reference: database-overview.mwb
 * Description: See clInvoice.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clInvoiceDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = INVOICE_DATADICT;
		$this->sPrimaryEntity = 'entInvoice';
		$this->sPrimaryField = 'invoiceId';
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
		$aEntitiesExtended = array();

		$aParams += array(
			'invoiceId' => null,
			'invoiceNo' => null,
			'invoiceAuctionId' => null,
			'invoiceAuctionPartId' => null,
			'invoiceUserId' => null,
			'invoiceLockedByUserId' => null,
			'invoiceOrderId' => null,
			'invoiceVismaOrderId' => null,
			'invoiceFreightRequestId' => null,
			'invoiceParentInvoiceId' => null,
			'invoiceOcrNumber' => null,
			'invoiceParentType' => null
		);

		$aDaoParams['fields'] = $aParams['fields'];

		if( $aParams['invoiceId'] !== null ) {
			if( is_array($aParams['invoiceId']) ) {
				$aCriterias[] = 'invoiceId IN(' . implode( ', ', array_map('intval', $aParams['invoiceId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceId = ' . (int) $aParams['invoiceId'];
			}
		}

		if( $aParams['invoiceNo'] !== null ) {
			if( is_array($aParams['invoiceNo']) ) {
				$aCriterias[] = 'invoiceNo IN(' . implode( ', ', array_map('intval', $aParams['invoiceNo']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceNo = ' . (int) $aParams['invoiceNo'];
			}
		}

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

		if( $aParams['invoiceLockedByUserId'] !== null ) {
			if( is_array($aParams['invoiceLockedByUserId']) ) {
				$aCriterias[] = 'invoiceLockedByUserId IN(' . implode( ', ', array_map('intval', $aParams['invoiceLockedByUserId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceLockedByUserId = ' . (int) $aParams['invoiceLockedByUserId'];
			}
		}

		if( $aParams['invoiceOrderId'] !== null ) {
			if( is_array($aParams['invoiceOrderId']) ) {
				$aCriterias[] = 'invoiceOrderId IN(' . implode( ', ', array_map('intval', $aParams['invoiceOrderId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceOrderId = ' . (int) $aParams['invoiceOrderId'];
			}
		}

		if( $aParams['invoiceVismaOrderId'] !== null ) {
			if( is_array($aParams['invoiceVismaOrderId']) ) {
				$aCriterias[] = 'invoiceVismaOrderId IN(' . implode( ', ', array_map('intval', $aParams['invoiceVismaOrderId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceVismaOrderId = ' . (int) $aParams['invoiceVismaOrderId'];
			}
		}

		if( $aParams['invoiceFreightRequestId'] !== null ) {
			if( is_array($aParams['invoiceFreightRequestId']) ) {
				$aCriterias[] = 'invoiceFreightRequestId IN(' . implode( ', ', array_map('intval', $aParams['invoiceFreightRequestId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceFreightRequestId = ' . (int) $aParams['invoiceFreightRequestId'];
			}
		}

		if( $aParams['invoiceParentInvoiceId'] !== null ) {
			if( is_array($aParams['invoiceParentInvoiceId']) ) {
				$aCriterias[] = 'invoiceParentInvoiceId IN(' . implode( ', ', array_map('intval', $aParams['invoiceParentInvoiceId']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceParentInvoiceId = ' . (int) $aParams['invoiceParentInvoiceId'];
			}
		}

		if( $aParams['invoiceOcrNumber'] !== null ) {
			if( is_array($aParams['invoiceOcrNumber']) ) {
				$aCriterias[] = 'invoiceOcrNumber IN(' . implode( ', ', array_map('intval', $aParams['invoiceOcrNumber']) ) . ')';
			} else {
				$aCriterias[] = 'invoiceOcrNumber = ' . (int) $aParams['invoiceOcrNumber'];
			}
		}

		if( $aParams['invoiceParentType'] !== null ) {
			if( is_array($aParams['invoiceParentType']) ) {
				$aCriterias[] = "invoiceParentType IN('" . implode( "', '", $aParams['invoiceParentType'] ) . "')";
			} else {
				$aCriterias[] = 'invoiceParentType = ' . $this->oDb->escapeStr( $aParams['invoiceParentType'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		if( !empty($aEntitiesExtended) ) $aDaoParams['entitiesExtended'] = implode( ' ', $aEntitiesExtended );

		return $this->readData( $aDaoParams );
	}

}
