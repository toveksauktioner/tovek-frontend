<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clSystemTextDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = SYSTEM_TEXT_DATA_DICT;

		$this->sPrimaryField = 'systemTextId';
		$this->sPrimaryEntity = 'entSystemText';

		$this->init();
	}

}
