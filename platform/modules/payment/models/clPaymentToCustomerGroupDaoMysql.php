<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clPaymentToCustomerGroupDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entPaymentToCustomerGroup' => array(
				'relationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'paymentId' => array(
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
		$this->sPrimaryEntity = 'entPaymentToCustomerGroup';
		$this->aFieldsDefault = '*';
		
		$this->init();
	}

}