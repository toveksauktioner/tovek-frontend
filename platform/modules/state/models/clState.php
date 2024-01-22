<?php

require_once PATH_CORE . '/clModuleBase.php';

class clState extends clModuleBase {

	public function __construct() {
		$this->sState = 'State';
		$this->sModulePrefix = 'state';
		
		$this->oDao = clRegistry::get( 'clStateDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/state/models' );
		
		$this->initBase();		
	}
    
    public function addCommunal( $iStateId, $mCommunal ) {
        $this->oAcl->hasAccess( 'write' . $this->sModuleName );
        if( !is_array($mCommunal) ) $mCommunal = (array) $mCommunal;
        return $this->oDao->addCommunal( $iStateId, $mCommunal );        
    }
	
    public function readCommunal( $aFields = array() ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );
        return $this->oDao->readCommunal( array(
			'fields' => $aFields
		) );
    }
    
	public function readCommunalByState( $iStateId, $aFields = array() ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );
        return $this->oDao->readCommunal( array(
			'fields' => $aFields,
			'stateId' => $iStateId
		) );
    }
	
	public function updateCommunal( $iCommunalId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'entities' => 'entStateCommunal',
			'criterias' => 'communalId = ' . (int) $iCommunalId
		);
		$mResult = $this->oDao->updateData( $aData, $aParams );
		
		if( $mResult !== false ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
		}
		
		return $mResult;
	}
	
	public function deleteCommunal( $iCommunalId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams = array(
			'entities' => 'entStateCommunal',
			'criterias' => 'communalId = ' . (int) $iCommunalId
		);
		$mResult = $this->oDao->deleteData( $aParams );
		
		if( $mResult !== false ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
		}
		
		return $mResult;
	}
	
}