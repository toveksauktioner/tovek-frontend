<?php

abstract class clModuleBase {

	public $oAcl;
	public $oDao;
	public $sModuleName;
	
	public $bConfig;
	public $aConfig;
	
	protected $aEvents = array();
	protected $oEventHandler;
	protected $sModulePrefix;

	protected function initBase() {
		$oUser = clRegistry::get( 'clUser' );
		$this->setAcl( $oUser->oAcl );
		
		if( empty($this->sModuleName) ) $this->sModuleName = ucfirst( $this->sModulePrefix );
		$this->oEventHandler = clRegistry::get( 'clEventHandler' );
		$this->oEventHandler->addListener( $this, $this->aEvents );
		
		if( $this->bConfig === true ) {
			$oConfig = clFactory::create( 'clConfig' );
			$this->aConfig = arrayToSingle( $oConfig->readByGroupKey( $this->sModuleName ), 'configKey', 'configValue' );
		}
		
		if( !empty($this->oDao) ) {
			$this->oDao->sModuleName = $this->sModuleName;
		}
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		$aData[$this->sModulePrefix . 'Created'] = date( 'Y-m-d H:i:s' );
		
		if( $this->oDao->createData($aData, $aParams) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
			
			$iLastId = $this->oDao->oDb->lastId();
			
			//$oUserLog = clRegistry::get( 'clUserLog', PATH_MODULE . '/userlog/models' );
			//$oUserLog->setParams( array(
			//	'parentType' => $this->sModuleName,
			//	'parentId' => $iLastId,
			//	'event' => 'create'
			//) );
			//$oUserLog->create();
			
			return $iLastId;
		}
		return false;
	}

	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$result = $this->oDao->deleteDataByPrimary( $primaryId );
		if( !empty($result) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
			
			//$oUserLog = clRegistry::get( 'clUserLog', PATH_MODULE . '/userlog/models' );
			//$oUserLog->setParams( array(
			//	'parentType' => $this->sModuleName,
			//	'parentId' => $primaryId,
			//	'event' => 'delete'
			//) );
			//$oUserLog->create();
		}
		return $result;
	}

	public function read( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		if( $primaryId !== null ) return $this->oDao->readDataByPrimary($primaryId, $aParams);
		return $this->oDao->readData( $aParams );
	}

	public function setAcl( $oAcl ) {
		$this->oAcl = $oAcl;
	}

	public function update( $primaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'update' . $this->sModuleName;
		$aData[$this->sModulePrefix . 'Updated'] = date( 'Y-m-d H:i:s' );
		
		$result = $this->oDao->updateDataByPrimary( $primaryId, $aData, $aParams );
		if( $result !== false ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
			
			//$oUserLog = clRegistry::get( 'clUserLog', PATH_MODULE . '/userlog/models' );
			//$oUserLog->setParams( array(
			//	'parentType' => $this->sModuleName,
			//	'parentId' => $primaryId,
			//	'event' => 'update'
			//) );
			//$oUserLog->create();
		}
		return $result;
	}

	public function upsert( $primary, $aData, $aParams = array() ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		//$oUserLog = clRegistry::get( 'clUserLog', PATH_MODULE . '/userlog/models' );
		//$oUserLog->setParams( array(
		//	'parentType' => $this->sModuleName,
		//	'parentId' => $primary,
		//	'event' => 'upsert'
		//) );
		//$oUserLog->create();
		
		return $this->oDao->upsert( $primary, $aData, $aParams = array() );
	}
	
}
