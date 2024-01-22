<?php

$aResult = array();

$oVehicleLookup = clRegistry::get( 'clVehicleLookup', PATH_MODULE . '/vehicle/models' );
$oBilvision = clRegistry::get( 'clVehicleLookupBilvision', PATH_MODULE . '/vehicle/models' );

$aLookupIds = array();

if( !empty($_GET['vehicleLicencePlate']) ) {
	// Reads vehicle info via two requests to Bilvision API

	// Grundfraga
	$oResult = $oBilvision->getBilvisionData( 'Grundfraga', array(
		'Regnr' => $_GET['vehicleLicencePlate']
	) );
	$aLookupIds[] = $oBilvision->iLookupId;

	// TeknikfragaV2
	$oResult = $oBilvision->getBilvisionData( 'TeknikfragaV2', array(
		'Regnr' => $_GET['vehicleLicencePlate']
	) );
	$aLookupIds[] = $oBilvision->iLookupId;

} else if (!empty($_GET['createVehicleData'])) {
	// Insertion of vehicle by a previous lookup
	// Can be accessed from other view
	$aLookupIds = $_GET['createVehicleData'];
}

if( !empty($aLookupIds) ) {
	$aLookups = $oVehicleLookup->read( array(
		'lookupId',
		'lookupServiceFunction',
		'lookupResultData'
	), $aLookupIds );
}


// Add vehicle data from lookup
if( !empty($aLookups) ) {
	$aVehicleAddData = array();

	foreach( $aLookups as $aLookupData ) {
		$aResultData = $aLookupData['lookupResultData'];

		// Clear multiple spaces and trim the ends
		foreach( $aResultData as &$aThisData ) {
			if( !is_array($aThisData) ) $aThisData = trim( preg_replace('!\s+!', ' ', $aThisData) );
		}

		switch( $aLookupData['lookupServiceFunction'] ) {
			case 'Grundfraga':
				$aVehicleAddData += array(
					'vehicleLicencePlate' => $aResultData['Regnr'],
					'vehicleOwnerCount' => $aResultData['AntalBrukare'],
					'vehicleRegStatus' => $aResultData['Regstatus'],
					'vehicleInspectionApprovedDate' => ( !empty($aResultData['DatgodBes']) ? substr(date('Y'), 0, 2) . substr($aResultData['DatgodBes'], 0, 2) . '-' . substr($aResultData['DatgodBes'], 2, 2) . '-' . substr($aResultData['DatgodBes'], 4, 2) : '' ),
					'vehicleProhibitedForTraffic' => ( ($aResultData['kforbud'] == 'N') ? 'no' : 'yes' ),
					'vehicleAnnualTax' => $aResultData['fskatt']
				);
				break;

			case 'TeknikfragaV2':
				$sBrand = '';
				$sModel = '';

				foreach( $GLOBALS['BILVISION_BRAND_MULTIPLE_WORDS'] as $sMultipleWordBrand ) {
					if( mb_substr($aResultData['fabrikat'], 0, mb_strlen($sMultipleWordBrand)) == $sMultipleWordBrand ) {
						$sBrand = mb_substr( $aResultData['fabrikat'], 0, mb_strlen($sMultipleWordBrand) );
						$sModel = trim( mb_substr( $aResultData['fabrikat'], mb_strlen($sMultipleWordBrand) ) );
					}
				}

				if( empty($sBrand) ) {
					$aBrandData = explode( ' ', $aResultData['fabrikat'] );
					$sBrand = $aBrandData[0];
					unset( $aBrandData[0] );
					$sModel = implode( ' ', $aBrandData );
				}

				$aVehicleAddData += array(
					'vehicleLicencePlate' => $aResultData['regnr'],
					'vehicleType' => $aResultData['fslag'],
					'vehicleBrand' => $sBrand,
					'vehicleModel' => $sModel,
					'vehicleModelYear' => $aResultData['FordonsAr'],
					'vehicleColor' => $aResultData['farg'],
					'vehicleInspectionDate' => ( !empty($aResultData['registrbes']) ? substr($aResultData['registrbes'], 0, 4) . '-' . substr($aResultData['registrbes'], 4, 2) . '-' . substr($aResultData['registrbes'], 6, 2) : '' ),
					'vehicleLength' => $aResultData['langd'],
					'vehicleWidth' => $aResultData['bredd'],
					'vehicleOutput' => $aResultData['effekt'],
					'vehicleCubicCapacity' => $aResultData['cylindervolym'],
					'vehicleTransmission' => $aResultData['vexellada'],
					'vehicleFuel' => $aResultData['drivmedel'],
				);
				break;
		}
	}

	if( !empty($aVehicleAddData) ) {
		$oVehicleData = clRegistry::get( 'clVehicleData', PATH_MODULE . '/vehicle/models' );
		$iVehicleDataId = $oVehicleData->createOrUpdateByLicencePlate( $aVehicleAddData['vehicleLicencePlate'], $aVehicleAddData );

		if( !empty($iVehicleDataId) ) {
			$oVehicleLookup->update( $aLookupIds, array(
				'lookupVehicleDataId' => $iVehicleDataId
			) );

			$aResult['result'] = 'success';
			$aResult['vehicleDataId'] = $iVehicleDataId;

		} else {
			$aErr = clErrorHandler::getValidationError( 'createVehicleData' );
			$aResult['result'] = 'fail';
			$aResult['err'] = $aErr;
		}
	} else {
		$aResult['result'] = 'fail';
		$aResult['err'] = _( 'No vehicle data' );
	}

} else {
	$aResult['result'] = 'fail';
	$aResult['err'] = _( 'No lookups' );
}


echo json_encode( $aResult );
