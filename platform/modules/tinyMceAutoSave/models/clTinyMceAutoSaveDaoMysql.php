<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clTinyMceAutoSaveDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entTinyMceTempData' => array(
				'tempId' => array(
					'type' => 'integer',
					'title' => _( 'ID' ),
					'primary' => true,
					'autoincrement' => true
				),
				'tempContent' => array(
					'type' => 'string',
					'title' => _( 'Content' ),
					'required' => true
				),
				'tempChkSum' => array(
					'type' => 'string',
					'title' => _( 'Content' ),
					'required' => true
				),
				'tempGroupKey' => array(
					'type' => 'string',
					'title' => _( 'Content' )
				),
				'tempCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' ),
					'required' => true
				)
			)
		);

		$this->sPrimaryField = 'tempId';
		$this->sPrimaryEntity = 'entTinyMceTempData';
		$this->aFieldsDefault = '*';

		$this->init();
	}

}