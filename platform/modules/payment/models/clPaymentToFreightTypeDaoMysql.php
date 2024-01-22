<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clPaymentToFreightTypeDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entPaymentToFreightType' => array(
				'relationId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true
				),
				'paymentId' => array(
					'type' => 'integer',
					'index' => true
				),
				'freightTypeId' => array(
					'type' => 'integer',
					'index' => true
				)
			)
		);
		
		$this->sPrimaryField = 'relationId';
		$this->sPrimaryEntity = 'entPaymentToFreightType';
		$this->aFieldsDefault = '*';
		
		$this->init();
	}

}