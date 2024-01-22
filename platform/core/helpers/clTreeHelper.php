<?php

class clTreeHelper {

	private $aParams = array();
	private $oModule;

	public function __construct( clModuleBase $oModule, $aParams = array() ) {
		$this->oModule = $oModule;

		$aParams += array(
			'helperDao' => 'oTreeHelperDao'
		);
		$this->aParams = $aParams;
	}

	public function create( $aData, $aParams = array() ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		$aParams['groupKey'] = 'create' . $this->oModule->sModuleName;
		$aData['categoryCreated'] = date( 'Y-m-d H:i:s' );
		$aData['categoryLangId'] = $this->oModule->oDao->iLangId;

		$targetNode = !empty( $aData['categoryTarget'] ) ? (int) $aData['categoryTarget'] : null;
		if( !empty($aData['categoryRelation']) ) $aParams['relation'] = $aData['categoryRelation'];

		// Divider param
		if( !empty($aParams['divider']) ) {
			$aParams['dividerCriterias'] = '(' . $this->oModule->oDao->sPrimaryEntity . '.' . $aParams['divider']['field'] . ' = "' . $aParams['divider']['value'] . '")';
		}
		$aParams['position'] = $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->readNodePosition( $targetNode, $aParams );
		
		if( $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->createNode($aData, $aParams) ) return $this->oModule->oDao->oDb->lastId();
		return false;
	}

	public function move( $categoryId, $aData ) {
		$this->oModule->oAcl->hasAccess( 'write' . $this->oModule->sModuleName );
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->move( $categoryId, $aData );
	}

	public function readWithChildren( $categoryId, $aFields = array(), $aParams = array() ) {
		$this->oModule->oAcl->hasAccess( 'read' . $this->oModule->sModuleName );
		$aParams = array(
			'fields' => $aFields
		) + $aParams;
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->readWithChildren( $categoryId, $aParams );
	}

	public function readWithParents( $categoryId, $aFields = array() ) {
		$this->oModule->oAcl->hasAccess( 'read' . $this->oModule->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		return $this->oModule->oDao->aHelpers[$this->aParams['helperDao']]->readWithParents( $categoryId, $aParams );
	}
}