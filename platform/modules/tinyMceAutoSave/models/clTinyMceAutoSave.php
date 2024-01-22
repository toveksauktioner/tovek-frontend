<?php

require_once PATH_CORE . '/clModuleBase.php';

class clTinyMceAutoSave extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'tinyMceAutoSave';
		$this->sModuleName = 'TinyMceAutoSave';

		$this->oDao = clRegistry::get( 'clTinyMceAutoSaveDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/tinyMceAutoSave/models' );
		$this->initBase();
	}
	
	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		$aData['tempCreated'] = date( 'Y-m-d H:i:s' );

		if( $this->oDao->createData($aData, $aParams) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
			return $this->oDao->oDb->lastId();
		}
		return false;
	}
	
	public function readByGroupKey( $sGroupKey, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'fields' => $aFields,
			'criterias' => "tempGroupKey = '" . $sGroupKey . "'"
		);
		
		return $this->oDao->readData( $aParams );
	}
	
	public function readByChkSum( $sChkSum, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'fields' => $aFields,
			'criterias' => "tempChkSum = '" . $sChkSum . "'"
		);
		
		return $this->oDao->readData( $aParams );
	}
	
	public function deleteByChkSum( $sChkSum ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$aChkSum = explode( '|', $sChkSum );
		
		$aParams = array(
			'criterias' => "tempChkSum = '" . $aChkSum[0] . "'"
		);
		
		$result = $this->oDao->deleteData( $aParams );
		
		return $result;
	}
	
}