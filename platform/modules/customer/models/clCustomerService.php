<?php

require_once PATH_CORE . '/clModuleBase.php';

class clCustomerService extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'Customer';
		$this->sModulePrefix = 'customer';
		
		$this->oDao = clRegistry::get( 'clCustomerServiceDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/customer/models' );
		
		$this->initBase();		
	}

}