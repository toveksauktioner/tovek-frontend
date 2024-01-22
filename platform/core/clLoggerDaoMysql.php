<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clLoggerDaoMysql extends clDaoBaseSql {
	
	public function __construct() {
		$this->aDataDict = array(
			'entLog' => array(
				'logId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'logLabel' => array(
					'type' => 'string',
					'title' => _( 'Label' )
				),
				'logData' => array(
					'type' => 'string',
					'title' => _( 'Data' )
				),
				'logCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);
		
		$this->sPrimaryField = 'logId';
		$this->sPrimaryEntity = 'entLog';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}
	
	/**
	 * Create log row in database
	 **/
	public function create( $aData ) {
		return parent::createData( $aData );
	}
	
	/**
	 * Read log from database based on label
	 **/
	public function readByLabel( $sLabel ) {
		return parent::readData( array(
			'criterias' => 'logLabel = ' . $this->oDb->escapeStr( $sLabel )
		) );
	}
	
}