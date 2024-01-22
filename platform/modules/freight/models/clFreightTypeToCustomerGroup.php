<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFreightTypeToCustomerGroup extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'freightTypeToCustomerGroup';

		$this->oDao = clRegistry::get( 'clFreightTypeToCustomerGroupDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/freight/models' );
		$this->initBase();
	}

	public function deleteByFreight( $iFreightTypeId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'criterias' => 'freightTypeId = ' . $iFreightTypeId
		);
		return $this->oDao->deleteData( $aParams );
	}
	
	public function readByFreight( $aFields = array(), $mFreightTypeId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		if( is_array($mFreightTypeId) ) {
			$aParams['criterias'] = 'freightTypeId IN(' . implode( ', ', array_map( "intval", $mFreightTypeId ) ) . ')';
		} else {
			$aParams['criterias'] = 'freightTypeId = ' . (int) $mFreightTypeId;
		}
		
		$aParams['fields'] = $aFields;
		return $this->oDao->readData( $aParams );
	}
	
	public function deleteByRelation( $iFreightTypeId, $iGroupId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'criterias' => 'freightTypeId = ' . (int) $iFreightTypeId . ' AND groupId = ' . $this->oDao->oDb->escapeStr( $iGroupId )
		);
		return $this->oDao->deleteData( $aParams );
	}
}
