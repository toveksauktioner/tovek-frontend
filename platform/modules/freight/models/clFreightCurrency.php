<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFreightCurrency extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'FreightCurrency';
		$this->sModulePrefix = 'freightCurrency';
		
		$this->oDao = clRegistry::get( 'clFreightCurrencyDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/freight/models' );
		
		$this->initBase();		
	}

	public function read( $aFields = array(), $iEntryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'entryId' => $iEntryId
		);
		return $this->oDao->read( $aParams );
	}
	
	public function readAll( $aFields = array(), $iEntryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'entryId' => $iEntryId,
			'status' => '*'
		);
		return $this->oDao->read( $aParams );
	}
	
	public function readByCurrency( $mCurrency, $aFields = array(), $sStatus = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'currency' => $mCurrency,
			'fields' => $aFields,
			'status' => $sStatus	
		);
		return $this->oDao->read( $aParams );
	}
	
	public function readByFreightType( $iFreightTypeId, $aFields = array(), $sStatus = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'freightTypeId' => $iFreightTypeId,
			'fields' => $aFields,
			'status' => $sStatus	
		);
		return $this->oDao->read( $aParams );
	}
	
}