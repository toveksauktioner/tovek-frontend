<?php

require_once PATH_CONFIG . '/cfUser.php';
require_once PATH_FUNCTION . '/fUser.php';

class clUserBackend {

	public $aGroups = array();
	public $aData = array();
	public $iId = 0;
	public $oAcl; // Access Request Object
	public $oAclGroups;
	public $oDao;

	public function __construct( $iUserId = null ) {
		$this->oDao = clRegistry::get( 'clUserBackendDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/userBackend/models' );
		clFactory::loadClassFile( 'clAcl' );
		if( !empty($iUserId) ) {
			$this->setUser( $iUserId );
			if( !empty($_SESSION['userId']) && $_SESSION['userId'] == $iUserId ) $this->isOnline();
		} else {
			$this->oAcl = new clAcl();
			$this->oAclGroups = new clAcl();
		}

        $this->oDao->switchToSecondary();
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

	/**
	 * Check whether this user is online.
	 * - Check that there is a user with status 'online'
	 * - Check userLastActive with constant USER_TIMEOUT
	 * - Check userLastIp against the current IP to prevent session hijacking
	 * @return boolean
	 */
	public function isOnline() {
		if(	empty($this->iId) || empty($this->aData) ||	$this->aData['userStatus'] != 'online') {
			$this->logout();
			return false;
		}

		if( (time() - strtotime($this->aData['userLastActive'])) > USER_TIMEOUT || $this->aData['userLastIp'] != getUserLongIp() ) {
			$this->logout();
			return false;
		}

		return $this->updateStatus( 'online' );
	}

	public function login( $sUsername, $sUserPass ) {
		$sUserEmail = $this->oDao->readDataByUsername( $sUsername, array('userEmail') );

		$aData = $this->oDao->read( array(
			'fields' => 'userId',
			'username' => $sUsername,
			'userPass' => hashUserPass($sUserPass, $sUserEmail),
			'userGrantedUsage' => 'yes'
		) );

		if( !empty($aData) ) {
			$this->loginInit( current(current($aData)) );
			return true;
		}
		return false;
	}

	public function loginByEmail( $sUserEmail, $sUserPass ) {
		$aData = $this->oDao->read( array(
			'fields' => 'userId',
			'userEmail' => $sUserEmail,
			'userPass' => hashUserPass($sUserPass, $sUserEmail),
			'userGrantedUsage' => 'yes'
		) );
		if( !empty($aData) ) {
			$this->loginInit( current(current($aData)) );
			return true;
		}
		return false;
	}

	public function loginInit( $iUserId ) {
		$iUserId = (int) $iUserId;

		unset( $_SESSION['user'] );
		$this->setUser( $iUserId );

		// User data
		$_SESSION['userId'] = $iUserId;
		$_SESSION['user'] = array(
			'acl' => $this->oAcl->aAcl,
			'aclGroups' => $this->oAclGroups->aAcl,
			'groups' => $this->aGroups
		);

		if( file_exists(PATH_MODULE . '/customer') ) {
			// Customer data
			$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
			$aCustomer = $oCustomer->readByUserId( $iUserId, array(
				'customerId',
				'customerNumber',
				'groupId'
			) );
			if( !empty($aCustomer) ) {
				$_SESSION['customer'] = current( $aCustomer );
			}
		}

		$this->updateStatus( 'online' );
	}

	public function logout() {
		$this->updateStatus( 'offline' );
		session_destroy();
		header( 'Location: /' );
		exit;
	}

	/**
	 * Read and return user's data from the given data field names. If data is not already loaded in $this->aData, then fetch from db.
	 * @return array
	 * @param object $aFields
	 */
	public function readData( $aFields = array() ) {
		$aFields = (array) $aFields;
		$aFieldsFromDb = array_diff( $aFields, array_keys($this->aData) );

		if( empty($aFields) || !empty($aFieldsFromDb) ) {
			$aDataFromDb = current( $this->oDao->read(array(
				'userId' => $this->iId,
				'fields' => $aFieldsFromDb
			)) );
			if( $aDataFromDb !== false ) $this->aData += $aDataFromDb;
		}

		if( count($aFields) == 1 ) return $this->aData[current($aFields)];
		if( !empty($aFields) ) return array_intersect_key( $this->aData, array_flip($aFields) );
		return $this->aData;
	}

	/**
	 * Set the user's own ACL object.
	 * - Check the user's groups' ACL. 'deny' always overwrite 'allow'
	 * - Check the user's ACL which always overwrite the groups' ACL.
	 * $param array $aAcl If is provided, set the user's ACL to this parameter
	 */
	public function setAcl( $aAcl = array() ) {
		if( empty($this->oAcl) || !is_object($this->oAcl) ) $this->oAcl = new clAcl();

		if( !empty($aAcl) ) {
			$this->oAcl->setAcl( (array) $aAcl );
		} else {
			$this->oAcl->setAro( $this->iId, 'user' );
			$this->oAcl->readToAclByAro();

			$aUserGroupAcl = array();

			$oTmpUserGroupAcl = new clAcl();
			$oTmpUserGroupAcl->setAro( array_keys($this->aGroups), 'userGroup' );
			$oTmpUserGroupAcl->readToAclByAro();

			foreach( $oTmpUserGroupAcl->aAcl as $sAcoKey => $sAcoAccess ) {
				if( !isset($oUserGroupAcl[$sAcoKey]) || $oUserGroupAcl[$sAcoKey] == 'allow' ) $aUserGroupAcl[$sAcoKey] = $sAcoAccess;
			}

			$this->oAcl->setAcl( $aUserGroupAcl + $this->oAcl->aAcl );
		}

		if( empty($this->oAclGroups) || !is_object($this->oAclGroups) ) $this->oAclGroups = new clAcl();
		// Read user groups ACL
		$this->oAclGroups->setAro( $this->iId, 'user' );
		$this->oAclGroups->setAro( array_keys($this->aGroups), 'userGroup' );
		$this->oAclGroups->readToAclByAro( 'userGroup' );

	}

	public function setGroup( $aGroups = array() ) {
		if( !empty($aGroups) ) {
			$this->aGroups = (array) $aGroups;
			$this->setAcl();
		} else {
			$aGroups = $this->oDao->readUserGroup( $this->iId );
			foreach( $aGroups as $entry ) {
				$this->aGroups[$entry['groupKey']] = $entry['groupTitle'];
			}

		}
	}

	/**
	 *
	 * @return
	 * @param object $aData[optional] If passed, check if there are keys userPass and userEmail, then hash the password and store it.
	 */
	public function setData( $aData = array() ) {
		if( !empty($aData) ) {
			if( isset($aData['userPass']) && isset($aData['userEmail']) ) {
				$aData['userPass'] = hashUserPass( $aData['userPass'], $aData['userEmail'] );
			} else {
				if( isset($aData['userPass']) ) unset( $aData['userPass'] );
			}
			$this->aData = (array) $aData;
		} else {
			$this->readData();
		}
	}

	public function setUser( $iUserId, $aData = array() ) {
		$this->iId = (int) $iUserId;
		$this->setGroup();
		$this->setAcl();
		$this->setData( $aData );
	}

	public function updateGroup() {
		$this->oDao->deleteUserToGroup( $this->iId );
		return $this->oDao->createUserToGroup( $this->iId, array_keys($this->aGroups) );
	}

	public function updateStatus( $sUserStatus ) {
		$aData = array(
			'userLastActive' => date( 'Y-m-d H:i:s' ),
			'userLastIp' => getUserLongIp(),
			'userLastSessionId' => session_id(),
			'userStatus' => $sUserStatus
		);
		return $this->oDao->updateDataByPrimary( $this->iId, $aData );
	}

	public function updateData( $aData ) {
		if( array_key_exists('username', $aData) || array_key_exists('userEmail', $aData) ) {
			// Validate so no other users has this username or email
			$aParams = array(
				'fields' => array( 'userId' ),
				'entities' => array( 'entUser' ),
			);

			$aCriterias = array();
			if( array_key_exists('username', $aData) ) {
				$aCriterias[] = 'username = ' . $this->oDao->oDb->escapeStr($aData['username']) . ' OR userEmail = ' . $this->oDao->oDb->escapeStr($aData['username']);
			}
			if( array_key_exists('userEmail', $aData) ) {
				$aCriterias[] = 'username = ' . $this->oDao->oDb->escapeStr($aData['userEmail']) . ' OR userEmail = ' . $this->oDao->oDb->escapeStr($aData['userEmail']);
			}
			$aParams['criterias'] = implode( ' OR ', $aCriterias );

			$aUserIdCheck = $this->oDao->readData( $aParams );
			if( !empty($aUserIdCheck) ) {
				foreach( $aUserIdCheck as &$aUser ) {
					if( $aUser['userId'] != $this->iId ) {
						clErrorHandler::setValidationError( array(
							'updateUser' => array(
								'username' => array(
									'type' => 'invalid',
									'title' => _( 'Username' )
								)
							)
						) );
						return false;
					}
				}
			}
		}

		// $aData['userUpdated'] = date( 'Y-m-d H:i:s' );

		if( $this->oDao->updateUserData($this->iId, $aData) !== false ) {
			$this->readData( array_diff(array_keys($aData), array_keys($this->aData)) );
			return true;
		}
		return false;
	}

	public function updateEmail( $sNewEmail, $sPass ) {
		$sUserEmail = $this->readData( 'userEmail' );
		// Check valid pass
		$aData = $this->oDao->read( array(
			'fields' => 'userId',
			'userEmail' => $sUserEmail,
			'userPass' => hashUserPass($sPass, $sUserEmail)
		) );
		if( !empty($aData) ) {
			$sPass = hashUserPass( $sPass, $sNewEmail );
			return $this->oDao->updateUserData( $this->iId, array('userPass' => $sPass, 'userEmail' => $sNewEmail), 'updateUserEmail' );
		} else {
			clErrorHandler::setValidationError( array(
				'updateUserEmail' => array(
					'userPass' => array(
						'type' => 'invalid',
						'title' => _( 'Password' )
					)
				)
			) );
		}
		return false;
	}

	public function updatePass( $sPass ) {
		$sUserEmail = $this->readData( 'userEmail' );
		$sPass = hashUserPass( $sPass, $sUserEmail );
		return $this->oDao->updateUserData( $this->iId, array('userPass' => $sPass), 'updateUserPass' );
	}

}
