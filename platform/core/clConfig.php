<?php

require_once PATH_CORE . '/clModuleBase.php';

class clConfig extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'config';

		$this->oDao = clRegistry::get( 'clConfigDao' . DAO_TYPE_DEFAULT_ENGINE );
		$this->initBase();
	}
	
	public function readByGroupKey( $sGroupKey ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByGroupKey( $sGroupKey );
	}

}
