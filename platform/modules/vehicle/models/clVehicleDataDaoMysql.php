<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clVehicleDataDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entVehicleData' => array(
				'vehicleDataId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'vehicleLicencePlate' => array(
					'type' => 'string',
					'title' => _( 'Reg.nr.' )
				),
				'vehicleType' => array(
					'type' => 'string',
					'title' => _( 'Fordonsslag' )
				),
				'vehicleBrand' => array(
					'type' => 'string',
					'title' => _( 'Märke' )
				),
				'vehicleModel' => array(
					'type' => 'string',
					'title' => _( 'Modell' )
				),
				'vehicleModelYear' => array(
					'type' => 'int',
					'title' => _( 'Modellår' )
				),
				'vehicleMileageDistance' => array(
					'type' => 'string',
					'title' => _( 'Mätarställning (km)' )
				),
				'vehicleMileageTime' => array(
					'type' => 'string',
					'title' => _( 'Mätarställning (timmar)' )
				),
				'vehicleColor' => array(
					'type' => 'string',
					'title' => _( 'Färg' )
				),
				'vehicleInspectionDate' => array(
					'type' => 'date',
					'title' => _( 'Registrerad' )
				),
				'vehicleLength' => array(
					'type' => 'int',
					'title' => _( 'Längd (mm)' )
				),
				'vehicleWidth' => array(
					'type' => 'int',
					'title' => _( 'Bredd (mm)' )
				),
				'vehicleOutput' => array(
					'type' => 'int',
					'title' => _( 'Effekt (kW)' )
				),
				'vehicleCubicCapacity' => array(
					'type' => 'int',
					'title' => _( 'Cylindervolym  (cm<sup>3</sup>)' )
				),
				'vehicleTransmission' => array(
					'type' => 'string',
					'title' => _( 'Växellåda' )
				),
				'vehicleFuel' => array(
					'type' => 'string',
					'title' => _( 'Drivmedel' )
				),
				'vehicleOwnerCount' => array(
					'type' => 'int',
					'title' => _( 'Antal brukare' )
				),
				'vehicleRegStatus' => array(
					'type' => 'string',
					'title' => _( 'Registreringsstatus' )
				),
				'vehicleInspectionApprovedDate' => array(
					'type' => 'date',
					'title' => _( 'Godkänd besiktning' )
				),
				'vehicleProhibitedForTraffic' => array(
					'type' => 'array',
					'title' => _( 'Körförbud' ),
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					)
				),
				'vehicleAnnualTax' => array(
					'type' => 'int',
					'title' => _( 'Skatt' )
				),
				'vehicleComment' => array(
					'type' => 'string',
					'title' => _( 'Kommentar' )
				),
				'vehicleNoKeys' => array(
					'type' => 'int',
					'title' => _( 'Antal nycklar' )
				),
				'vehicleRegCertArrived' => array(
					'type' => 'array',
					'title' => _( 'Har registreringsbevis inkommit?' ),
					'values' => array(
						'no' => _( 'No' ),
						'yes' => _( 'Yes' )
					)
				),
				'vehicleDataCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'vehicleDataUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);
		$this->sPrimaryEntity = 'entVehicleData';
		$this->sPrimaryField = 'vehicleDataId';
		$this->aFieldsDefault = array( '*' );

		$this->init();
	}

	/* * *
	 * Combined dao function for reading data
	 * based on foreign key's
	 * * */
	public function readByForeignKey( $aParams ) {
		$aDaoParams = array();
		$sCriterias = array();
		$aEntitiesExtended = array();

		$aParams += array(
			'vehicleLicencePlate' => null
		);

		$aDaoParams['fields'] = $aParams['fields'];

		if( $aParams['vehicleLicencePlate'] !== null ) {
			if( is_array($aParams['vehicleLicencePlate']) ) {
				$aCriterias[] = "vehicleLicencePlate IN('" . implode( "', '", $aParams['vehicleLicencePlate'] ) . "')";
			} else {
				$aCriterias[] = 'vehicleLicencePlate = ' . $this->oDb->escapeStr( $aParams['vehicleLicencePlate'] );
			}
		}

		if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
		if( !empty($aEntitiesExtended) ) $aDaoParams['entitiesExtended'] = implode( ' ', $aEntitiesExtended );

		return $this->readData( $aDaoParams );
	}

}
