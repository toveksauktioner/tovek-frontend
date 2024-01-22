<?php

require_once PATH_CORE . '/clModuleBase.php';

class clContact extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'Contact';
		$this->sModulePrefix = 'contact';
		$this->oDao = clRegistry::get( 'clContactDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/contact/models' );

		$this->initBase();
	}

}
