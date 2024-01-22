<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_CONFIG . '/cfUser.php';
require_once PATH_FUNCTION . '/fUser.php';

class clUserManager extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'user';

		$this->oDao = clRegistry::get( 'clUserDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/user/models' );
		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}

	public function create( $oUser ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$oUser->aData['userCreated'] = date( 'Y-m-d H:i:s' );
		$iUserId = $this->oDao->createUser( $oUser->aData );
		if( $iUserId !== false ) {
			$oUser->setUser( $iUserId );
			return true;
		}
		return false;
	}

	public function createUserToGroup( $iUserId, $aGroupKeys ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createUserToGroup( $iUserId, $aGroupKeys );
	}

	public function createUserWithInfo( $oUser ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createUserWithInfo( $oUser );
	}

	public function delete( $iUserId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// Check allowed groups
		$aUserGroups = $this->oDao->readUserGroup( $iUserId );
		if( empty($aUserGroups) ) return false;
		require_once PATH_FUNCTION . '/fData.php';
		$aUserGroups = arrayToSingle( $aUserGroups, 'groupKey', 'groupTitle' );
		$oUser = clRegistry::get( 'clUser' );
		if( !$oUser->oAclGroups->isAllowed('superuser') ) {
			$aDiffGroups = array_diff_key( $aUserGroups, $oUser->oAclGroups->aAcl );
			if( !empty($aDiffGroups) ) return false;
		}
		
		if( is_dir(PATH_MODULE . '/customer') ) {
			$this->oEventHandler->triggerEvent( array(
				'preDeleteUser' => array( $iUserId, $this->sModuleName ),
			), 'internal' );
		}

		$this->oEventHandler->removeEvent( 'deleteUser' );
		return $this->oDao->delete( $iUserId );
	}

	public function deleteUserToGroup( $iUserId, $aGroupKeys = array() ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$this->oDao->deleteUserToGroup( $iUserId, $aGroupKeys );
	}

	public function isUser( $aFields, $sCriteriaType = 'OR' ) {
		return $this->oDao->isUser( $aFields, $sCriteriaType );
	}

	public function isUserWithPrimary( $iUserId ) {
		return (bool) $this->oDao->readDataByPrimary( (int) $iUserId, array('fields' => 'userId') );
	}

	public function isUserOnline( $iUserId ) {
		$aParams = array(
			'fields' => array(
				'userLastActive',
				'userLastIp',
				'userLastSessionId'
			),
			'userStatus' => 'online',
			'userId' => $iUserId
		);

		$aResult = $this->oDao->read($aParams);
		if( empty($aResult) ) return false;

		$aResult = current( $aResult );
		if( (time() - strtotime($aResult['userLastActive'])) > USER_TIMEOUT ) {
			$this->updateStatus( $iUserId, 'offline' );
			return false;
		}

		return true;
	}

	public function read( $aFields = array(), $userId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'userId' => $userId
		);
		return $this->oDao->read( $aParams );
	}
	
	public function readByEmail( $sEmail, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'userEmail' => $sEmail
		);
		return $this->oDao->read( $aParams );
	}
	
	public function readByEmailAndPassword( $sEmail, $sPassword, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'userEmail' => $sEmail,
			'userPass' => hashUserPass($sPassword, $sEmail)
		);
		return $this->oDao->read( $aParams );
	}

	public function readByPhone( $sPhone, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'infoCellPhone' => $sPhone
		);
		$aData = $this->oDao->read( $aParams );
		if( empty($aData) ) {
			$aParams = array(
				'fields' => $aFields,
				'infoPhone' => $sPhone
			);
			$aData = $this->oDao->read( $aParams );
		}
		return $aData;
	}

	public function readByUsername( $sUsername, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'username' => $sUsername
		);
		return $this->oDao->read( $aParams );
	}
	
	public function readByGroup( $aGroups, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'userGroups' => $aGroups
		);
		return $this->oDao->read( $aParams );
	}

	public function readGroup() {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readGroup();
	}
	
	/**
	 * Update user groups
	 * - delete existing user to group associations
	 * - create user to group assocations
	 * @return boolean
	 * @param object $iUserId
	 * @param object $aGroupKeys
	 */
	public function updateGroup( $iUserId, $aGroupKeys ) {
		$this->oAcl->hasAccess( 'update' . $this->sModuleName );
		$this->oDao->deleteUserToGroup( $iUserId );
		return $this->oDao->createUserToGroup( $iUserId, $aGroupKeys );
	}

	/**
	 *
	 * @return string random password
	 * @param string $sUserEmail
	 * @param array $aParams[optional]
	 */
	public function updateRandomPass( $iUserId, $sUserEmail, $aParams = array() ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams += array(
			'passLength' => 8,
		);

		$sNewPass = generateRandomPass( $aParams['passLength'] );

		$this->oDao->updateUserData( $iUserId, array(
			'userPass' => hashUserPass($sNewPass, $sUserEmail))
		);
		return $sNewPass;
	}

	public function updateStatus( $iUserId, $sUserStatus ) {
		$aData = array(
			'userStatus' => $sUserStatus
		);
		return $this->oDao->updateDataByPrimary( $iUserId, $aData );
	}

}
