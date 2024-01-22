<?php

/**
 * Functions to store invoiceQueue data for creation later. Basically a mirroring of clInvoiceQueue
 */

require_once PATH_CORE . '/clModuleBase.php';

class clInvoiceQueue extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'InvoiceQueue';
		$this->sModulePrefix = 'invoiceQueue';
		
		$this->oDao = clRegistry::get( 'clInvoiceQueueDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/invoice/models' );
		
		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}
	
	/* * *
	 *  Override create function to set created all the time
	 * * */
	public function create( $aData ) {
		$aData += array(
			'invoiceCreated' => date( 'Y-m-d H:i:s' )
		);
		
		return parent::create( $aData );
	}
	
}
