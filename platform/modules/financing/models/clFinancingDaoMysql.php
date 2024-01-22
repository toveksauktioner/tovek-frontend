<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once dirname( dirname(__FILE__) ) . '/config/cfFinancing.php';

class clFinancingDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = FINANCING_DATA_DICT;
		$this->sPrimaryEntity = 'entFinancing';
		$this->sPrimaryField = 'financingId';
		$this->aFieldsDefault = '*';

		$this->init();
	}
}
