<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clFreightTypeToCustomerGroupDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entFreightTypeToCustomerGroup' => array(
				'relationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'freightTypeId' => array(
					'type' => 'integer',
					'index' => true
				),
				'groupId' => array(
					'type' => 'integer',
					'index' => true,
					'title' => _( 'Customer type' )
				)
			)
		);
		
		$this->sPrimaryField = 'relationId';
		$this->sPrimaryEntity = 'entFreightTypeToCustomerGroup';
		$this->aFieldsDefault = '*';
		
		$this->init();
	}

}