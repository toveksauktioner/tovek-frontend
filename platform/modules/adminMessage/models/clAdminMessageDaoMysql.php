<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clAdminMessageDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entAdminMessage' => array(
				'messageId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'messageLabel' => array(
					'type' => 'string',
					'title' => _( 'Label' )
				),
				'messageTitleTextId' => array(
					'type' => 'string',
					'title' => _( 'Title' )
				),
				'messageContentTextId' => array(
					'type' => 'string',
					'title' => _( 'Content' )
				),				
				// Misc
				'messageStatus' => array(
					'type' => 'array',
					'values' => array(
						'inactive' => _( 'Inactive' ),
						'active' => _( 'Active' )
					),
					'title' => _( 'Status' )
				),
				'messageCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'messageUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			),
			'entAdminMessageToUser' => array(
				'userId' => array(
					'type' => 'integer',
					'title' => _( 'User ID' )
				),
				'messageId' => array(
					'type' => 'integer',
					'title' => _( 'Message ID' )
				),
				'messageRead' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Read' )
				),
				'userAccept' => array(
					'type' => 'array',
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					),
					'title' => _( 'Read' )
				),
				'created' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		
		$this->sPrimaryField = 'messageId';
		$this->sPrimaryEntity = 'entAdminMessage';
		$this->aFieldsDefault = '*';
		
		$this->init();
		
		$this->aHelpers = array(
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'messageTitleTextId',
					'messageContentTextId'
				),				
				'sTextEntity' => 'entAdminMessageText'				
			) )
		);
	}
	
	public function readMessageToUser( $mUserId = null ) {
		$aDaoParams = array(
			'entities' => 'entAdminMessageToUser',
			'fields' => array_keys( $this->aDataDict['entAdminMessageToUser'] ),
			'withHelpers' => false
		);
		
		if( $mUserId !== null ) {
			if( is_array($mUserId) ) {
				$aDaoParams['criterias'] = 'entAdminMessageToUser.userId IN(' . implode( ', ', array_map('intval', $mUserId) ) . ')';
			} else {
				$aDaoParams['criterias'] = 'entAdminMessageToUser.userId = ' . (int) $mUserId;
			}
		}
		
		return parent::readData( $aDaoParams );
	}
	
	public function createMessageToUser( $aData ) {
		$aDaoParams = array(
			'entities' => 'entAdminMessageToUser',
			'fields' => array_keys( $aData ),
			'withHelpers' => false,
			'groupKey' => 'createAdminMessage'
		);
		
		return parent::createData( $aData, $aDaoParams );
	}
	
}