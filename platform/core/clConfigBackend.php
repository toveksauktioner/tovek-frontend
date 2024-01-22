<?php

require_once PATH_CORE . '/clModuleBase.php';

class clConfigBackend extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'config';

		$this->oDao = clRegistry::get( 'clConfigBackendDao' . DAO_TYPE_DEFAULT_ENGINE );
		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}
	
	public function readByGroupKey( $sGroupKey ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByGroupKey( $sGroupKey );
	}

}
