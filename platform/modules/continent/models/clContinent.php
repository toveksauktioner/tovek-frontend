<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_HELPER . '/clParentChildHelper.php';

class clContinent extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'continent';

		$this->oDao = clRegistry::get( 'clContinentDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/continent/models' );
		$this->initBase();

		$this->aHelpers = array(
			'oParentChildHelper' => new clParentChildHelper( $this ),
		);
	}

}