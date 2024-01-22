<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clUserBlacklistDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entUserBlacklist' => array(
				'blackId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'blackUserId' => array(
					'type' => 'integer',
					'title' => _( 'User ID' )
				),
				'blackUserPin' => array(
					'type' => 'string',
					'title' => _( 'PIN/Company ID' )
				),
				'blackEmail' => array(
					'type' => 'string',
					'title' => _( 'Email' )
				),
				'blackIpAddress' => array(
					'type' => 'string',
					'title' => _( 'IP address' )
				),
				'blackCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		
		$this->sPrimaryField = 'blackId';
		$this->sPrimaryEntity = 'entUserBlacklist';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}	
}