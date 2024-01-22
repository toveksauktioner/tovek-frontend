<?php

/* * * *
 * Filename: clInvoicePaymentDaoMysql.php
 * Created: 22/05/2014 by Markus
 * Reference: database-overview.mwb
 * Description: See clInvoicePayment.php
 * * * */

require_once PATH_CORE . '/clDaoBaseSql.php';

class clInvoicePaymentLogDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = INVOICE_DATADICT;
		$this->sPrimaryEntity = 'entInvoicePaymentLog';
		$this->sPrimaryField = 'logId';
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
			'logId' => null,
			'logInvoiceId' => null,
			'logBgFile' => null,
			'logActive' => 'yes',
			'logOriginalLogId' => null,
		);

		$aDaoParams['fields'] = $aParams['fields'];

		if( $aParams['logId'] !== null ) {
			if( is_array($aParams['logId']) ) {
				$aCriterias[] = 'logId IN(' . implode( ', ', array_map('intval', $aParams['logId']) ) . ')';
			} else {
				$aCriterias[] = 'logId = ' . (int) $aParams['logId'];
			}
		}

		if( $aParams['logInvoiceId'] !== null ) {
			if( is_array($aParams['logInvoiceId']) ) {
				$aCriterias[] = 'logInvoiceId IN(' . implode( ', ', array_map('intval', $aParams['logInvoiceId']) ) . ')';
			} else {
				$aCriterias[] = 'logInvoiceId = ' . (int) $aParams['logInvoiceId'];
			}
		}

		if( $aParams['logBgFile'] !== null ) {
			if( is_array($aParams['logBgFile']) ) {
				$aCriterias[] = 'logBgFile IN("' . implode( '", "', array_map('strval', $aParams['logBgFile']) ) . '")';
			} else {
				$aCriterias[] = 'logBgFile = "' . (string) $aParams['logBgFile'] . '"';
			}
		}

		if( $aParams['logActive'] !== null ) {
			$aCriterias[] = 'logActive = "' . (string) $aParams['logActive'] . '"';
		}

		if( $aParams['logOriginalLogId'] !== null ) {
			if( is_array($aParams['logOriginalLogId']) ) {
				$aCriterias[] = 'logOriginalLogId IN(' . implode( ', ', array_map('intval', $aParams['logOriginalLogId']) ) . ')';
			} else {
				$aCriterias[] = 'logOriginalLogId = ' . (int) $aParams['logOriginalLogId'];
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData( $aDaoParams );
	}

}
