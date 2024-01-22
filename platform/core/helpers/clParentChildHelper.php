<?php

class clParentChildHelper {

	public $aParams = array();
	public $oModule;

	public function __construct( clModuleBase $oModule, $aParams = array() ) {
		$this->oModule = $oModule;

		$aParams += array(
			'helperDao' => 'oParentChildHelperDao'
		);
		$this->aParams = $aParams;
	}

	public function createChild( $aData ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		$aParams['groupKey'] = 'createChild' . $this->oModule->sModuleName;

		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->createChild($aData, $aParams);
	}

	public function createParent( $aData ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		$aParams['groupKey'] = 'createParent' . $this->oModule->sModuleName;

		if( $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->createParent($aData, $aParams) )
			return $this->oModule->oDao->oDb->lastId();

		return false;
	}

	public function deleteChild( $childId ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->deleteChild( $childId );
	}

	public function deleteChildrenByParent( $parentId ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );

		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->deleteChildrenByParent( $parentId );
	}

	public function deleteParent( $parentId ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->deleteParent( $parentId );
	}
	
	public function readChildren( $parentId, $aFields = array(), $primaryId = null ) {
		$this->oModule->oAcl->hasAccess( 'read' . $this->oModule->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'childId' => $primaryId
		);
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->readChildren( $parentId, $aParams );
	}

	public function readParents( $aFields = array(), $primaryId = null ) {
		$this->oModule->oAcl->hasAccess( 'read' . $this->oModule->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'parentId' => $primaryId
		);
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->readParents( $aParams );
	}

	public function readRelations() {
		$this->oModule->oAcl->hasAccess( 'read' . $this->oModule->sModuleName );
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->readRelations();
	}

	public function updateChild( $childId, $aData ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		$aParams['groupKey'] = 'updateChild' . $this->oModule->sModuleName;
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->updateChild( $childId, $aData, $aParams );
	}

	public function updateParent( $parentId, $aData ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		$aParams['groupKey'] = 'updateParent' . $this->oModule->sModuleName;
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->updateParent( $parentId, $aData, $aParams );
	}

}