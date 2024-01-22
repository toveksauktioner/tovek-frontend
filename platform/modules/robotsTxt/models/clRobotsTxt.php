<?php

require_once PATH_CORE . '/clModuleBase.php';

class clRobotsTxt extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'RobotsTxt';
		$this->sModulePrefix = 'robotsTxt';

		$this->oDao = clRegistry::get( 'clRobotsTxtDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/robotsTxt/models' );
		$this->initBase();
	}

	public function updateSort() {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aPrimaryIds = func_get_args();
		return $this->oDao->updateSort( $aPrimaryIds );
	}

	// Read all currently active rules
	public function readActive( $aFields = array(), $primaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		// Current site status based on SITE_RELEASE_MODE
		$sCurrentStatus = SITE_RELEASE_MODE ? 'on-released' : 'on-not-released';

		$aParams = array(
			'fields' => $aFields,
			'criterias' => 'ruleActivation = "always" OR ruleActivation = "' . $sCurrentStatus . '"'
		);

		if( $primaryId !== null ) return $this->oDao->readDataByPrimary($primaryId, $aParams);
		$return =  $this->oDao->readData( $aParams );
		return $return;
	}

}
