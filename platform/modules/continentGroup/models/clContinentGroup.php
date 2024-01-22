<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_HELPER . '/clParentChildHelper.php';
require_once PATH_MODULE . '/continentGroup/config/cfContinentGroup.php';

class clContinentGroup extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'ContinentGroup';
		$this->sModulePrefix = 'continentGroup';
		
		$this->oDao = clRegistry::get( 'clContinentGroupDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/continentGroup/models' );
		$this->initBase();
		
		$this->aHelpers = array(
			'oParentChildHelper' => new clParentChildHelper( $this )
		);
	}
	
	public function readContinent( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
		return $oContinent->read( $aFields, $primaryId );
	}
	
	public function readGroup( $aFields = array(), $sContinentGroupKey ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readGroup( $aFields, $sContinentGroupKey );
	}
	
	public function readAllGroups( $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readAllGroups( $aFields );
	}
	
	public function deleteByGroupAndContinent( $sContinentGroupKey, $sContinentCode ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->deleteByGroupAndContinent( $sContinentGroupKey, $sContinentCode );
	}
	
	public function updateSort() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aPrimaryIds = func_get_args();		
		return $this->oDao->updateSort( $aPrimaryIds, $_SESSION['continentGroupKey'] );
	}
	
}