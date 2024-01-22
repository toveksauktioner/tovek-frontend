<?php

require_once PATH_CORE . '/clModuleBase.php';

class clUserSettings extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'userSettings';
		
		$this->oDao = clRegistry::get( 'clUserSettingsDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/userSettings/models' );
		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}
	
	public function readWithSubstitution( $aFields = array(), $primaryId = null, $aSubstitutionData = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aData = $this->read( $aFields, $primaryId );
		if( !empty($aData) ) {
			foreach( $aData as $key => $value ) {
				foreach( $aSubstitutionData as $sSearch => $sReplace ) {
					$aData[$key] = str_replace( '{' . $sSearch . '}', $sReplace, $aData[$key] );
				}
			}
		}

		return $aData;
	}
	
	public function readByUserGroup( $mGroup = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByUserGroup( $mGroup );
	}
	
	public function createUserSetting( $iUserId, $sSettingsKey, $sValue ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );		
		return $this->oDao->createUserSetting( $iUserId, $sSettingsKey, $sValue );
	}	
	
	public function deleteUserSetting( $iUserId, $sSettingsKey ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$result = $this->oDao->deleteUserSetting( $iUserId, $sSettingsKey );
		if( !empty($result) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
		}
		return $result;
	}
	
	public function readUserSetting( $iUserId, $sSettingsKey = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aReturn = $this->oDao->readUserSettings( $iUserId, $sSettingsKey );
		
		if( !empty($sSettingsKey) && !is_array($sSettingsKey) && !empty($aReturn) ) {
			return $aReturn[0]['settingsValue'];
		} else {
			return $aReturn;
		}
	}
	
	public function updateUserSetting( $iUserId, $sSettingsKey, $sValue ) {		
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( empty($sSettingsKey) ) return false;
		
		$result = $this->oDao->updateUserSettings( $iUserId, $sSettingsKey, $sValue );
		if( $result !== false ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
		}
		return $result;
	}

}