<?php

require_once PATH_CORE . '/clModuleBase.php';

class clFreightWeight extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'freightWeight';

		$this->oDao = clRegistry::get( 'clFreightWeightDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/freight/models' );
		$this->initBase();
	}

	/**
	 * Reads data by a freight type
	 * 
	 * @param integer $iFreightTypeId The freight id
	 * @param array $aFields What fields to read
	 * 
	 * @return mixed Return array with data or false if not valid freight type
	 */
	public function readByFreightType( $iFreightTypeId = null, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		if( $iFreightTypeId === null ) return false;

		$aParams = array(
			'fields' => $aFields,
			'freightTypeId' => $iFreightTypeId
		);

		return $this->oDao->read( $aParams );
	}
	
	public function readByWeight( $iGrams, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'weight' => $iGrams
		);
		return $this->oDao->read( $aParams );
	}
	
	public function readByWeightAndType( $iGrams, $iType, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'weight' => $iGrams,
			'freightTypeId' => $iType
		);
		return $this->oDao->read( $aParams );
	}
	
}