<?php

require_once PATH_CORE . '/clModuleBase.php';

class clParentChild extends clModuleBase {

	protected $oModule;

	public function __construct( clModuleBase $oModule ) {
		$this->oModule = $oModule;
	}

	public function createChild( $parentId, $aData ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		$aParams['groupKey'] = 'create' . $this->oModule->sModuleName;
		$aData[$this->oModule->sModulePrefix . 'Created'] = date( 'Y-m-d H:i:s' );

		if( $this->oModule->oParentChildDao->createChild($aData, $aParams) ) return $this->oModule->oParentChildDao->oDb->lastId();
		return false;
	}

	public function readChildren( $parentId, $aFields = array(), $childId = null ) {
		$this->oModule->oAcl->hasAccess( 'read' . $this->oModule->sModuleName );
		$aParams = array(
			'parentId' => $parentId,
			'fields' => $aFields,
			'childId' => $childId
		);
		return $this->oModule->oParentChildDao->read( $aParams );
	}

}
