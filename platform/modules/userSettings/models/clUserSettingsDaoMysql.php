<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clUserSettingsDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entUserSettings' => array(
				'settingsKey' => array(
					'type' => 'string',
					'primary' => true
				),
				'settingsTitle' => array(
					'type' => 'string',
					'required' => true,
					'title' => _( 'Title' )
				),
				'settingsMessage' => array(
					'type' => 'string',
					'title' => _( 'Message' )
				),
				'settingsType' => array(
					'type' => 'array',
					'required' => true,
					'values' => array(
						'text' => _( 'Text' ),
						'radio' => _( 'Alternatives' ),
						'select' => _( 'Choices' ),
						'checkbox' => _( 'Checkboxes' ),
						'global' => _( 'Global setting' ),
					),
					'title' => _( 'Type' )
				),
				'settingsValues' => array(
					'type' => 'string',
					'title' => _( 'Alternatives/Default value' )
				),
				'settingsGroupKey' => array(
					'type' => 'string',
					'title' => _( 'Group' )
				),
				'settingsUserGroupKey' => array(
					'type' => 'array',
					'title' => _( 'User group' ),
					'values' => array(
						'super' => _( 'Super user' ),
						'admin' => _( 'Admin' ),
						'user' => _( 'User' ),
						'global' => _( 'Global setting' ),
					)
				),
				'settingsOverrideDefconLevel' => array(
					'type' => 'array',
					'title' => _( 'Kör ner till DEFCON' ),
					'values' => array(
						5 => '5',
						4 => '4',
						3 => '3',
						2 => '2',
						1 => '1'
					)
				)
			),
			'entUserSettingsToUser' => array(
				'settingsKey' => array(
					'type' => 'string',
					'primary' => true
				),
				'userId' => array(
					'type' => 'integer',
					'required' => true
				),
				'settingsValue' => array(
					'type' => 'string'
				)
			)
		);

		$this->sPrimaryField = 'settingsKey';
		$this->sPrimaryEntity = 'entUserSettings';

		$this->init();
	}
	
	public function readByUserGroup( $mGroup = null ) {		
		if( $mGroup !== null ) {
			if( is_array($mGroup) ) {
				$aParams['criterias'] = "settingsUserGroup IN ('" . implode( "', '", array_map('strval', $mGroup) ) . "')";
			} else {
				$aParams['criterias'] = 'settingsUserGroup = ' . $this->oDb->escapeStr( $mGroup );
			}
		}
		
		return $this->readData( $aParams );
	}

	public function createUserSetting( $iUserId, $sSettingsKey, $sValue ) {
		$aParams = array(
			'entities' => 'entUserSettingsToUser',
			'groupKey' => 'createUserSettingsToUser'
		);
		$aData = array(
			'settingsKey' => $sSettingsKey,
			'userId' => $iUserId,
			'settingsValue' => $sValue
		);
		return ( $this->createData( $aData, $aParams ) ? $this->lastId() : false );
	}	
	
	public function deleteUserSetting( $iUserId, $sSettingsKey ) {
		$aParams = array(
			'entities' => 'entUserSettingsToUser',
			'criterias' => 'entUserSettingsToUser.userId = ' . $this->oDb->escapeStr( $iUserId ) . ' AND entUserSettingsToUser.settingsKey = ' . $this->oDb->escapeStr( $sSettingsKey )
		);

		return $this->deleteData( $aParams );
	}
	
	public function readUserSettings( $iUserId, $sSettingsKey = null ) {
		$aParams = array(
			'entities' => 'entUserSettingsToUser'
		);
		$aCriterias = array();
		
		$aCriterias[] = 'userId = ' . (int)$iUserId;
		
		if( $sSettingsKey !== null ) {
			if( is_array($sSettingsKey) ) {
				$aCriterias[] = "settingsKey IN (" . implode( ', ', array_map(array($this->oDb, 'escapeStr'), $sSettingsKey) ) . ")";
			} else {
				$aCriterias[] = 'settingsKey = ' . $this->oDb->escapeStr( $sSettingsKey );
			}
		}
		
		if( !empty($aCriterias) ) $aParams['criterias'] = implode( ' AND ', $aCriterias );
		
		return $this->readData( $aParams );
	}
	
	public function updateUserSettings( $iUserId, $sSettingsKey, $sValue ) {
		$aParams = array(
			'entities' => 'entUserSettingsToUser',
			'groupKey' => 'updateUserSettingsToUser',
			'criterias' => 'entUserSettingsToUser.userId = ' . $this->oDb->escapeStr( $iUserId ) . ' AND entUserSettingsToUser.settingsKey = ' . $this->oDb->escapeStr( $sSettingsKey )
		);
		$aData = array(
			'settingsKey' => $sSettingsKey
		);
		return $this->updateData( $aData, $aParams );
	}

}