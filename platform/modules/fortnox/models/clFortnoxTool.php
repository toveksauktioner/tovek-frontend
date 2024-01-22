<?php

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';

class clFortnoxTool extends clFortnoxBase {

	public $oResource;

	public function __construct() {
		$this->sModuleName = 'FortnoxTool';
		$this->sModulePrefix = 'fortnoxTool';
		
		$this->initBase();
	}
	
	public function finishAccountCreation() {
		return $this->getAccessToken();
	}
	
}