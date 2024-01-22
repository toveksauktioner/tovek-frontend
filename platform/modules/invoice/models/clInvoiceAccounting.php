<?php

require_once PATH_CORE . '/clModuleBase.php';

class clInvoiceAccounting extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'InvoiceAccounting';
		$this->sModulePrefix = 'invoiceAccounting';
		
		$this->oDao = clRegistry::get( 'clInvoiceAccountingDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/invoice/models' );
		
		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}
	
}
