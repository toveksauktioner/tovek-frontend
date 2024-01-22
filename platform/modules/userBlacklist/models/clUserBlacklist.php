<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/userBlacklist/config/cfUserBlacklist.php';

class clUserBlacklist extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'UserBlacklist';
		$this->sModulePrefix = 'userBlacklist';
		
		$this->oDao = clRegistry::get( 'clUserBlacklistDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/userBlacklist/models' );
		
		$this->initBase();		
		
		$this->oDao->switchToSecondary();
	}

	public function blacklistUser( $iUserId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		$oUserManager = clRegistry::get( 'clUserManager' );
		
		// User data
		$aUserData = current( $oUserManager->read( array(
			'userPin',
			'userEmail',
			'userLastIp'
		), $iUserId ) );
		
		if( !empty($aUserData) ) {
			// Block user
			$oUserManager->oDao->updateDataByPrimary( $iUserId, array(
				'userGrantedStatus' => 'blocked'
			) );
			$result = clErrorHandler::getValidationError( 'updateUser' );
			
			// Blacklist data
			if( $result !== false ) {
				$aData = array(
					'blackUserId' => $iUserId,
					'blackUserPin' => $aUserData['userPin'],
					'blackEmail' => $aUserData['userEmail'],
					'blackIpAddress' => $aUserData['userLastIp'],
					'blackCreated' => date( 'Y-m-d H:i:s' )
				);
				$this->oDao->createData( $aData, array(
					'groupKey' => 'createUserBlacklist'
				) );
				$result = clErrorHandler::getValidationError( 'createUserBlacklist' );
				
				if( $result !== false ) {
					$oNotification = clRegistry::get( 'clNotificationHandler' );
					$oNotification->set( array(
						'dataSaved' => _( 'User has been blocked and data has been added to blacklist' )
					) );
					return true;
				}
			}
		}
		
		return false;
	}
	
}