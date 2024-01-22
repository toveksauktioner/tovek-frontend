<?php

require_once PATH_CORE . '/clModuleBase.php';

class clPaymentToCustomerGroup extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'paymentToCustomerGroup';

		$this->oDao = clRegistry::get( 'clPaymentToCustomerGroupDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/payment/models' );
		$this->initBase();
	}

	public function deleteByPayment( $iPaymentId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'criterias' => 'paymentId = ' . $iPaymentId
		);
		return $this->oDao->deleteData( $aParams );
	}
	
	public function readByPayment( $aFields = array(), $mPaymentId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		if( is_array($mPaymentId) ) {
			$aParams['criterias'] = 'paymentId IN(' . implode( ', ', array_map( "intval", $mPaymentId ) ) . ')';
		} else {
			$aParams['criterias'] = 'paymentId = ' . (int) $mPaymentId;
		}
		
		$aParams['fields'] = $aFields;
		return $this->oDao->readData( $aParams );
	}
	
	public function deleteByRelation( $iPaymentId, $iGroupId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'criterias' => 'paymentId = ' . (int) $iPaymentId . ' AND groupId = ' . $this->oDao->oDb->escapeStr( $iGroupId )
		);
		return $this->oDao->deleteData( $aParams );
	}
}
