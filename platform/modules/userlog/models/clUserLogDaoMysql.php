<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clUserLogDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entUserLog' => array(
				'userlogId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'username' => array(
					'type' => 'string',
					'title' => _( 'Username' )
				),
				'userlogParentType' => array(
					'type' => 'string',
					'title' => _( 'Module' )
				),
				'userlogParentId' => array(
					'type' => 'string',
					'title' => _( 'ID' )
				),
				'userlogEvent' => array(
					'type' => 'string',
					'title' => _( 'Event' )
				),
				// Misc
                'userlogCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
                'userlogUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);
		
		$this->sPrimaryField = 'userlogId';
		$this->sPrimaryEntity = 'entUserLog';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}	
}