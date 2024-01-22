<?php

require_once PATH_CORE . '/clModuleBase.php';

class clVat extends clModuleBase {

	public function __construct() {
		$this->sModulePrefix = 'vat';

		$this->oDao = clRegistry::get( 'clVatDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/vat/models' );
		$this->initBase();
	}

	public function deleteByCountry( $countryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteByCountry( $countryId );
	}

	public function readByCountry( $countryId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByCountry( $countryId, $aFields );
	}

	public function updateByCountry( $countryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->updateByCountry( $countryId, $aData );
	}

}