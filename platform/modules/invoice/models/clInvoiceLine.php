<?php

/* * * *
 * Filename: clInvoiceLine.php
 * Created: 01/04/2014 by Renfors
 * Reference: database-overview.mwb
 * Description:
 * * * */

require_once PATH_CORE . '/clModuleBase.php';

class clInvoiceLine extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'InvoiceLine';
		$this->sModulePrefix = 'invoiceLine';

		$this->oDao = clRegistry::get( 'clInvoiceLineDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/invoice/models' );

		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}

	public function delete( $iInvoiceLineId ) {
		// Get invoice data
		$aData = current( $this->read('invoiceLineInvoiceId', $iInvoiceLineId) );

		// Delete the line
		parent::delete($iInvoiceLineId);

		// Update the sums for the invoice
		if( !empty($aData) ) {
			$oInvoice = clRegistry::get( 'clInvoice', PATH_MODULE . '/invoice/models' );
			$oInvoice->setTotalAmount( $aData['invoiceLineInvoiceId'] );
		}
	}

	public function readByInvoice( $iInvoiceId, $aFields = null ) {
		$aParams = array(
			'invoiceLineInvoiceId' => $iInvoiceId,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readByItem( $iItemId, $aFields = null ) {
		$aParams = array(
			'invoiceLineItemId' => $iItemId,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readByParams( $aParams ) {
		$aParams += [
			'fields' => null
		];

		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readByParent( $iParentId, $sParentType = null, $aFields = null ) {
		$aParams = array(
			'invoiceLineParentLineId' => $iParentId,
			'invoiceLineParentType' => $sParentType,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readBySubmissionRememberLine( $iRememberLIneId, $aFields = null ) {
		$aParams = array(
			'invoiceLineSubmissionRememberLineId' => $iRememberLIneId,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

}
