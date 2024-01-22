<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clArgoCronDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entArgoCron' => array(
				'cronId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'cronLayoutKeyTrigger' => array(
					'type' => 'string',
					'title' => _( 'Layout key trigger' )
				),
				'cronTimeInterval' => array(
					'type' => 'integer',
					'title' => _( 'Time interval' )
				),
				'cronType' => array(
					'type' => 'array',
					'title' => _( 'Type' ),
					'values' => array(
						'file' => _( 'File' ),
						'event' => _( 'Event' )
					)
				),
				'cronTypeRelation' => array(
					'type' => 'string',
					'title' => _( 'Type relation' )
				),
				'cronLastRun' => array(
					'type' => 'integer', # Unix timestamp
					'title' => _( 'Last run' )
				),
				'cronCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		
		$this->sPrimaryField = 'cronId';
		$this->sPrimaryEntity = 'entArgoCron';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}	
}