<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clEmailQueueDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = EMAIL_QUEUE_DATADICT;
		$this->sPrimaryEntity = 'entEmailQueue';
		$this->sPrimaryField = 'queueId';
		$this->aFieldsDefault = '*';

		$this->init();
	}

}
