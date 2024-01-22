<?php

/* * * *
 * Filename: clInvoiceQueueLine.php
 * Description: Functions to handle queueing of invoice lines for later creation
 * Used with clInvoiceQueue
 * * * */

require_once PATH_CORE . '/clModuleBase.php';

class clInvoiceQueueLine extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'InvoiceQueueLine';
		$this->sModulePrefix = 'invoiceQueueLine';
		
		$this->oDao = clRegistry::get( 'clInvoiceQueueLineDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/invoice/models' );
		
		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}
	
	public function readByInvoiceQueue( $iInvoiceQueueId, $aFields = null ) {
		$aParams = array(
			'invoiceLineInvoiceQueueId' => $iInvoiceQueueId,
			'fields' => $aFields
		);
		
		return $this->oDao->readByForeignKey( $aParams );
	}
	
}
