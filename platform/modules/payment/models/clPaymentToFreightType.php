<?php

require_once PATH_CORE . '/clModuleBase.php';

class clPaymentToFreightType extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'PaymentToFreightType';
		$this->sModulePrefix = 'paymentToFreightType';
		
		$this->oDao = clRegistry::get( 'clPaymentToFreightTypeDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/payment/models' );
		$this->initBase();
	}
	
	public function deleteByPayment( $iPaymentId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'criterias' => 'paymentId = ' . $iPaymentId
		);
		return $this->oDao->deleteData( $aParams );
	}
	
	public function readByPayment( $aFields = array(), $iPaymentId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'criterias' => 'paymentId = ' . $iPaymentId
		);
		return $this->oDao->readData( $aParams );
	}
	
}