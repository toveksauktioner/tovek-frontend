<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/customer/config/cfCustomerCredit.php';

class clCustomerCredit extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'CustomerCredit';
		$this->sModulePrefix = 'customerCredit';
		
		$this->oDao = clRegistry::get( 'clCustomerCreditDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/customer/models' );
		
		$this->initBase();		
	}

	public function readCreditTransactions( $mCreditId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readTransactions( array(
			'creditId' => $mCreditId
		) );
	}
	
	public function readByCustomerId( $iCustomerId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->read( array(
			'customerId' => $iCustomerId
		) );
	}
	
	public function deposit( $fValue, $iCreditId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->makeTransaction( array(
			'creditId' => $iCreditId,
			'value' => $fValue,
			'type' => 'deposit'
		) );
	}
	
	public function withdrawal( $fValue, $iCreditId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->makeTransaction( array(
			'creditId' => $iCreditId,
			'value' => $fValue,
			'type' => 'withdrawal'
		) );
	}
	
}