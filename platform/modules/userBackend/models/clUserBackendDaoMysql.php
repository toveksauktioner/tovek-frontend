<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clUserBackendDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entUser' => array(
				'userId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'username' => array(
					'type' => 'string',
					'required' => true,
					'index' => true,
					/*'min' => 3,
					'max' => 50,*/
					'title' => _( 'Username' )
				),
				'userPin' => array(
					'type' => 'string',
					'max' => 13,
					'min' => 10,
					'title' => _( 'PIN/Company ID' ),
					'required' => true
				),
				'userPass' => array(
					'type' => 'string',
					'required' => true,
					'index' => true,
					'min' => 3,
					'max' => 128,
					'title' => _( 'Password' )
				),
				'userEmail' => array(
					'type' => 'string',
					'required' => true,
					/*'extraValidation' => array(
						'Email'
					),*/
					'title' => _( 'Email' )
				),
				'userType' => array(
					'type' => 'array',
					'values' => array(
						'privatePerson' => _( 'Private person' ),
						'company' => _( 'Company' )
					),
					'title' => _( 'Customer type' )
				),
				'userCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'userLastActive' => array(
					'type' => 'datetime',
					'title' => _( 'Last active' )
				),
				'userLastIp' => array(
					'type' => 'integer',
					'title' => _( 'Senaste IP' )
				),
				'userLastSessionId' => array(
					'type' => 'string',
					'min' => 26,
					'max' => 40
				),
				'userStatus' => array(
					'type' => 'array',
					'values' => array(
						'offline' => _('Offline'),
						'online' => _('Online')
					),
					'title' => _( 'Status' )
				),
				'userGrantedStatus' => array(
					'type' => 'array',
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'active' => _( 'Active' ),
						'blocked' => _( 'Blocked' )
					),
					'title' => _( 'Granted status' )
				),
				'userCheckedByAdmin' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Handled by admin (denied)' )
				),
				'userAcceptedAgreementId' => array(
					'type' => 'int'
				),
				'userAcceptedAgreementTime' => array(
					'type' => 'datetime',
					'title' => _( 'Accepterat kundavtal' )
				),
				'userDeletionRequest' => array(
					'type' => 'array',
					'values' => array(
						'requested' => _( 'Requested' ),
						'accepted' => _( 'Accepted' ),
						'declined' => _( 'Declined' )
					),
					'title' => _( 'Begäran om borttag' )
				)
			),
			'entUserInfo' => $GLOBALS['UserInfoDataDict'],
			'entUserGroup' => array(
				'groupKey' => array(
					'type' => 'string',
					'primary' => true,
					'required' => true
				),
				'groupTitle' => array(
					'type' => 'string',
					'max' => 255
				)
			),
			'entUserToGroup' => array(
				'userId' => array(
					'type' => 'integer',
					'required' => true,
					'index' => true
				),
				'groupKey' => array(
					'type' => 'string',
					'required' => true,
					'index' => true
				)
			)
		);
		$this->sPrimaryField = 'userId';
		$this->sPrimaryEntity = 'entUser';
		$this->aFieldsDefault = array(
			'userCreated',
			'userLastActive',
			'userLastIp',
			'userLastSessionId',
			'userStatus'
		);
		$this->init();
	}

	public function create( $aData ) {
		$aParams['groupKey'] = 'createUser';
		if( $this->createData( $aData, $aParams ) > 0 ) return $this->oDb->lastId();
		return false;
	}

	public function createUserInfo( $aData ) {
		$aParams['entities'] = 'entUserInfo';
		if( $this->createData( $aData, $aParams ) > 0 ) return true;
		return false;
	}

	public function createUserToGroup( $iUserId, $aGroups ) {
		$aParams = array(
			'dataEscape' => false,
			'fields' => array(
				'userId',
				'groupKey'
			),
			'entities' => 'entUserToGroup'
		);

		foreach( $aGroups as $sGroupKey ) {
			$aData[] = array( 'userId' => (int) $iUserId, 'groupKey' => $this->oDb->escapeStr($sGroupKey) );
		}

		return $this->createMultipleData( $aData, $aParams );
	}

	public function createUserWithInfo( $oUser ) {
		$aParams['groupKey'] = 'createUser';
		$aLastIds = $this->createDataTransaction( $oUser->aData, array(
			'entUser' => '',
			'entUserInfo' => array(
				'fromEntity' => 'entUser',
				'field' => 'infoUserId',
			)
		), $aParams );

		if( $aLastIds !== false ) $aLastIds = $aLastIds['entUser'];
		return $aLastIds;
	}

	public function delete( $iUserId ) {
		$iUserId = (int) $iUserId;
		$this->deleteUserToGroup( $iUserId );

		// Delete user info
		$aParams = array(
			'entities' => 'entUserInfo',
			'criterias' => 'infoUserId = ' . $iUserId
		);
		$this->deleteData( $aParams );

		$this->deleteDataByPrimary( $iUserId );
	}

	public function deleteUserToGroup( $iUserId, $aGroupKeys = array() ) {
		$aParams = array(
			'entities' => 'entUserToGroup',
			'criterias' => 'userId = ' . (int) $iUserId . ( !empty($aGroupKeys) ? ' AND groupKey IN (' . implode(', ', array_map(array($this->oDb, 'escapeStr'), $aGroupKeys)) . ')' : '' )
		);

		return $this->deleteData( $aParams );
	}

	public function isUser( $aFields ) {
		$aParams = array(
			'fields' => 'userId',
			'entities' => array(
				'entUser',
				'entUserInfo'
			),
			'entitiesExtended' => 'entUser LEFT JOIN entUserInfo ON entUser.userId = entUserInfo.infoUserId'
		);

		foreach( $aFields as $sField => $sValue ) {
			$aFields[$sField] = $sField . " = " . $this->oDb->escapeStr( $sValue );
		}
		$aParams['criterias'] = implode( " OR ", $aFields );

		$iUserId = $this->readData($aParams);
		return !empty($iUserId);
	}

	public function read( $aParams = array() ) {
		$aParams += array(
			'userId' => null,
			'userGroups' => null,
			'username' => null,
			'userPass' => null,
			'userEmail' => null,
			'userStatus' => null,
			//'userGrantedUsage' => null,
			'fields' => array(),
		);
		$aCriterias = array();

		$aDaoParams = array(
			'fields' => $aParams['fields'],
			'entities' => array(
				'entUser',
				'entUserInfo'
			),
			'entitiesExtended' => 'entUser LEFT JOIN entUserInfo ON entUser.userId = entUserInfo.infoUserId'
		);

		if( $aParams['userGroups'] !== null ) {
			$aDaoParams['entities'][] = 'entUserToGroup';
			$aDaoParams['entitiesExtended'] = 'entUserToGroup LEFT JOIN entUser ON entUserToGroup.userId = entUser.userId LEFT JOIN entUserInfo ON entUser.userId = entUserInfo.infoUserId';
			if( is_array($aParams['userGroups']) ) {
				$aCriterias[] = 'entUserToGroup.groupKey IN(' . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $aParams['userGroups']) ) . ')';
			} else {
				$aCriterias[] = 'entUserToGroup.groupKey = ' . $this->oDb->escapeStr( $aParams['userGroups'] );
			}
			$aCriterias[] = 'entUser.userId IS NOT NULL';
			$aDaoParams['groupBy'] = 'entUser.userId';
		}

		if( $aParams['userId'] !== null ) {
			if( is_array($aParams['userId']) ) {
				$aCriterias[] = 'userId IN(' . implode( ', ', array_map('intval', $aParams['userId']) ) . ')';
			} else {
				$aCriterias[] = 'userId = ' . (int) $aParams['userId'];
			}
		}
		if( $aParams['username'] !== null ) $aCriterias[] = 'username = ' . $this->oDb->escapeStr( $aParams['username'] );
		if( $aParams['userPass'] !== null ) $aCriterias[] = 'userPass = ' . $this->oDb->escapeStr( $aParams['userPass'] );
		if( $aParams['userEmail'] !== null ) $aCriterias[] = 'userEmail = ' . $this->oDb->escapeStr( $aParams['userEmail'] );
		if( $aParams['userStatus'] !== null ) $aCriterias[] = 'userStatus = ' . $this->oDb->escapeStr( $aParams['userStatus'] );
		//if( $aParams['userGrantedUsage'] !== null ) $aCriterias[] = 'userGrantedUsage = ' . $this->oDb->escapeStr( $aParams['userGrantedUsage'] );
		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );

		return $this->readData($aDaoParams);
	}

	public function readDataByUsername( $sUsername, $aFields = array() ) {
		$aParams = array(
			'fields' => $aFields,
			'criterias' => 'username = ' . $this->oDb->escapeStr( $sUsername )
		);
		$aData = $this->readData($aParams);
		if( !empty($aData) ) return current( (count($aFields) == 1 ? current($aData) : $aData) );
		return false;
	}

	public function readUserGroup( $iUserId ) {
		$aParams = array(
			'fields' => array(
				't2.groupKey',
				't2.groupTitle'
			),
			'entities' => array(
				'entUserToGroup',
				'entUserGroup'
			),
			'entitiesExtended' => 'entUserToGroup AS t1 LEFT JOIN entUserGroup AS t2 ON t1.groupKey = t2.groupKey',
			'criterias' => "t1.userId = '" . (int) $iUserId . "'"
		);
		return $this->readData( $aParams );
	}

	public function readGroup( $aParams = array() ) {
		$aParams = array(
			'fields' => array(
				'groupKey',
				'groupTitle'
			),
			'entities' => 'entUserGroup'
		);
		return $this->readData($aParams);
	}

	public function updateUserData( $iUserId, $aData, $sGroupKey = 'updateUser' ) {
		if( array_key_exists('username', $aData) || array_key_exists('userEmail', $aData) ) {
			// Validate so no other users has this username or email
			$aParams = array(
				'fields' => array( 'userId' ),
				'entities' => array( 'entUser' ),
			);

			$aCriterias = array();
			if( array_key_exists('username', $aData) ) {
				$aCriterias[] = 'username = ' . $this->oDb->escapeStr($aData['username']) . ' OR userEmail = ' . $this->oDb->escapeStr($aData['username']);
			}
			if( array_key_exists('userEmail', $aData) ) {
				$aCriterias[] = 'username = ' . $this->oDb->escapeStr($aData['userEmail']) . ' OR userEmail = ' . $this->oDb->escapeStr($aData['userEmail']);
			}
			$aParams['criterias'] = implode( ' OR ', $aCriterias );

			$aUserIdCheck = $this->readData( $aParams );
			if( !empty($aUserIdCheck) ) {
				foreach( $aUserIdCheck as &$aUser ) {
					if( $aUser['userId'] != $iUserId ) {
						clErrorHandler::setValidationError( array(
							$sGroupKey => array(
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

		$aParams = array(
			'entities' => array(
				'entUser',
				'entUserInfo'
			),
			'entitiesExtended' => 'entUser INNER JOIN entUserInfo ON entUser.userId = entUserInfo.infoUserId',
			'criterias' => 'entUser.userId = ' . (int) $iUserId,
			'groupKey' => $sGroupKey
		);

		// $aData['userUpdated'] = !empty($aData['userUpdated']) ? $aData['userUpdated'] : date( 'Y-m-d H:i:s' );

		return $this->updateData( $aData, $aParams );
	}

}
