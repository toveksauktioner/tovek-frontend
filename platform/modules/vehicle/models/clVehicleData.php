<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/vehicle/config/cfVehicleData.php';

class clVehicleData extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'VehicleData';
		$this->sModulePrefix = 'vehicleData';

		$this->oDao = clRegistry::get( 'clVehicleDataDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/vehicle/models' );

		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}

	public function createOrUpdateByLicencePlate( $sLicencePlate, $aData ) {
		$aExistingData = current( $this->readByParams( array(
			'vehicleLicencePlate' => $sLicencePlate
		), 'vehicleDataId' ) );

		if( empty($aExistingData) ) {
			return $this->create( $aData );
		} else {
			if( $this->update($aExistingData['vehicleDataId'], $aData) ) return $aExistingData['vehicleDataId'];
		}
	}

	public function readByParams( $aParams, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );

		$aParams += array(
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}
}
