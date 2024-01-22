<?php

require_once PATH_CORE . '/clModuleBase.php';

class clAdminMessage extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'AdminMessage';
		$this->sModulePrefix = 'adminMessage';
		
		$this->oDao = clRegistry::get( 'clAdminMessageDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/adminMessage/models' );
		
		$this->initBase();		
	}
	
	public function read( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'criterias' => 'messageStatus = "active"'
		);
		if( $primaryId !== null ) return $this->oDao->readDataByPrimary($primaryId, $aParams);
		return $this->oDao->readData( $aParams );
	}
	
	public function readAll( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		if( $primaryId !== null ) return $this->oDao->readDataByPrimary($primaryId, $aParams);
		return $this->oDao->readData( $aParams );
	}
	
	public function readByUser( $iUserId ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aMessages = valueToKey( 'messageId', $this->read() );
		$aMessageToUser = valueToKey( 'messageId', $this->oDao->readMessageToUser( $iUserId ) );
		return array_diff_key( $aMessages, $aMessageToUser );
	}

	public function createMessageToUser( $aData ) {
		return $this->oDao->createMessageToUser( $aData );
	}
	
}