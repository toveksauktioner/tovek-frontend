<?php

$aErr = '';
$sOutput = '';
$sLicencePlate = '';
$bOnlyForm = ( !empty($_GET['onlyForm']) );

$oVehicle = clRegistry::get( 'clVehicleData', PATH_MODULE . '/vehicle/models' );
	$aVehicleDataDict = $oVehicle->oDao->getDataDict();

// Connect to item
if( !empty($_GET['frmConnectToItem']) && isset($_GET['itemId']) && !empty($_GET['vehicleDataId']) ) {
	$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

	$aVehicleData = current( $oVehicle->read('*', $_GET['vehicleDataId']) );


	$aData = array(
		'itemVehicleDataId' => $_GET['vehicleDataId']
	);

	if( !empty($aVehicleData) ) {
		$sType = ( !empty($GLOBALS['VEHICLE_TYPE_BY_CODE'][ $aVehicleData['vehicleType'] ]) ? $GLOBALS['VEHICLE_TYPE_BY_CODE'][ $aVehicleData['vehicleType'] ] : '' );

		$sItemTitle  = ( !empty($sType) ? ucfirst($sType) . ', ' : '' );
		$sItemTitle .= $aVehicleData['vehicleBrand'] . ', ' . $aVehicleData['vehicleModel'] . ', ' . $aVehicleData['vehicleModelYear'] . ', ' . $aVehicleData['vehicleFuel'];
		$aData['itemTitle'] = $sItemTitle;

		$sVehicleFormButton = '
			<a href="#" class="ajax editVehicle popupTrigger icon iconText iconEdit selected" data-item-id="' . ( !empty($_GET['itemId']) ? $_GET['itemId'] : '' ) .  '" data-vehicle-data-id="' . $aVehicleData['vehicleDataId'] . '">Ändra</a>';

		// Create vehicle data table
		$aIgnoreFields = array(
			'vehicleDataId',
			'vehicleLicencePlate',
			'vehicleDataCreated',
			'vehicleDataUpdated'
		);

		$sVehicleInfo .= '
			<table class="data">
				<tbody>';

		$bEven = true;
		foreach( $aVehicleData as $sKey => $sValue ) {
			if( !empty($sValue) && !in_array($sKey, $aIgnoreFields) ) {
				if( $bEven ) $sVehicleInfo .= '<tr>';

				$sVehicleInfo .= '
						<td class="title">' . $aVehicleDataDict['entVehicleData'][ $sKey ]['title'] . '</td>
						<td class="value">' . ( !empty($aVehicleDataDict['entVehicleData'][ $sKey ]['values'][ $sValue ]) ? $aVehicleDataDict['entVehicleData'][ $sKey ]['values'][ $sValue ] : $sValue ) . '</td>';

				if( !$bEven ) $sVehicleInfo .= '</tr>';
				$bEven = !$bEven;
			}
		}

		if( $bEven ) $sVehicleInfo .= '</tr>';

		$sVehicleInfo .= '
				</tbody>
			</table>';

	} else {
		$sItemTitle = '';
		$sVehicleFormButton = '';
		$sVehicleInfo = '';
	}

	if( !empty($_GET['itemId']) ) {
		$oAuctionEngine->update( 'AuctionItem', $_GET['itemId'], $aData );
		$aErr = clErrorHandler::getValidationError( 'updateAuctionItem' );
	}

	if( empty($aErr) ) {
		if( !empty($aVehicleData) ) {
			$aResult = array(
				'result' => 'success',
				'vehicleDataId' => $aVehicleData['vehicleDataId'],
				'vehicleLicencePlate' => $aVehicleData['vehicleLicencePlate'],
				'notification' => _( 'Fordonet har kopplats till rop' ),
				'itemTitle' => $sItemTitle,
				'itemFormVehicleButton' => $sVehicleFormButton,
				'vehicleInfo' => $sVehicleInfo
			);

		} else {
			$aResult = array(
				'result' => 'error',
				'notification' => _( 'Fordonet hittades inte' )
			);
		}

	} else {
		$aResult = array(
			'result' => 'error',
			'notification' => current( $aErr )
		);
	}

	echo json_encode( $aResult );
	exit;
}

// Search vehicle
if( !empty($_GET['licencePlateSearchQuery']) ) {
	$aVehicleData = $oVehicle->readByParams( array(
		'vehicleLicencePlate' => $_GET['licencePlateSearchQuery']
	),'vehicleDataId' );

	if( !empty($aVehicleData) ) {
		$bOnlyForm = true;
		$_GET['vehicleDataId'] = current( current($aVehicleData) );

	} else {
		echo json_encode( array(
			'bilvisionButton' => '<button type="button" id="lookupBilvision">' . _('Sök hos Bilvision') . '</button>',
			'vehicleFormContainer' => ''
		) );
		exit;
	}
}

// Notification module
$oNotification = clRegistry::get( 'clNotificationHandler' );

if( !empty($_POST['frmVehicleAdd']) ) {
	if( !empty($_GET['vehicleDataId']) ) {
		// Update vehicle
		$oVehicle->update( $_GET['vehicleDataId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateVehicleData' );
		if( empty($aErr) ) {
			$aResult = array(
				'result' => 'success',
				'notification' => _( 'Fordonets uppgifter har uppdaterats' )
			);
		} else {
			$aResult = array(
				'result' => 'error',
				'notification' => current( $aErr )
			);
		}

		echo json_encode( $aResult );
		exit;

	} else {
		// Create vehicle
		$oVehicle->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createVehicleData' );
		if( empty($aErr) ) {
			$aResult = array(
				'result' => 'success',
				'notification' => _( 'Fordonets uppgifter har sparats' )
			);
		} else {
			$aResult = array(
				'result' => 'error',
				'notification' => current( $aErr )
			);
		}

		echo json_encode( $aResult );
		exit;
	}
}


// The form is only shown on ajax requests. Otherwise just processed
if( empty($_GET['ajax']) ) return;

// Data
if( !empty($_GET['vehicleDataId']) ) {
	$aData = current( $oVehicle->read(null, $_GET['vehicleDataId']) );
	$sLicencePlate = $aData['vehicleLicencePlate'];
	$sTitle = _( 'Change' );
} else {
	$aData = $_POST;
	$sTitle = _( 'Add' );
}

if( !empty($_GET['itemId']) ) {
	$aData['itemId'] = $_GET['itemId'];
}

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aVehicleDataDict, array(
	'attributes' => array(
		'class' => 'vertical fourCols',
		'id' => 'vehicleFormAdd'
	),
	'data' => $aData,
	'action' => '?ajax=1&view=vehicle/vehicleFormAdd.php' . ( !empty($_GET['vehicleDataId']) ? '&vehicleDataId=' . $_GET['vehicleDataId'] : '' ),
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => $sTitle
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'vehicleType' => array(),
	'vehicleBrand' => array(),
	'vehicleModel' => array(),
	'vehicleModelYear' => array(),
	'vehicleMileageDistance' => array(),
	'vehicleMileageTime' => array(),
	'vehicleColor' => array(),
	'vehicleInspectionDate' => array(
		'fieldAttributes' => array(
			'class' => 'datepicker'
		)
	),
	'vehicleLength' => array(),
	'vehicleWidth' => array(),
	'vehicleOutput' => array(),
	'vehicleCubicCapacity' => array(),
	'vehicleTransmission' => array(),
	'vehicleFuel' => array(),
	'vehicleOwnerCount' => array(),
	/*'vehicleRegStatus' => array(),*/
	'vehicleInspectionApprovedDate' => array(
		'fieldAttributes' => array(
			'class' => 'datepicker'
		)
	),
	'vehicleProhibitedForTraffic' => array(),
	'vehicleAnnualTax' => array(),
	'vehicleComment' => array(
		'fieldAttributes' => array(
			'class' => 'fullwidth'
		)
	),
	'vehicleRegCertArrived' => array(),
	'vehicleLicencePlate' => array(
		'type' => 'hidden'
	),
	'itemId' => array(
		'type' => 'hidden'
	),
	'frmVehicleAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );
$oOutputHtmlForm->setGroups( array(
	'info' => array(
		'title' => _( 'Grundinfo' ),
		'fields' => array(
			'vehicleType',
			'vehicleBrand',
			'vehicleModel',
			'vehicleModelYear',
			'vehicleMascusCatalog',
			'vehicleMascusProductDefinition',
			'vehicleRegCertArrived'
		)
	),
	'measurement' => array(
		'title' => _( 'Data' ),
		'fields' => array(
			'vehicleLength',
			'vehicleWidth',
			'vehicleColor',
			'vehicleTransmission',
			'vehicleFuel',
			'vehicleCubicCapacity',
			'vehicleOutput'
		)
	),
	'usage' => array(
		'title' => _( 'Användning' ),
		'fields' => array(
			'vehicleMileageDistance',
			'vehicleMileageTime',
			'vehicleInspectionDate',
			'vehicleInspectionApprovedDate',
			'vehicleOwnerCount',
			'vehicleRegStatus',
			'vehicleProhibitedForTraffic',
			'vehicleAnnualTax'
		)
	)
) );

$sOutput .= $oOutputHtmlForm->render();

if( $bOnlyForm ) {
	echo json_encode( array(
		'bilvisionButton' => '<button type="button" id="lookupBilvision">' . _('Uppdatera med Bilvision') . '</button>',
		'vehicleFormContainer' => $sOutput,
		'connectToItemButton' => ( !empty($_GET['vehicleDataId']) ? '<button type="button" id="connectToItem" data-vehicle-data-id="' . $_GET['vehicleDataId'] . '">' . _( 'Koppla till rop' ) . '</button>' : '' )
	) );

} else {
	echo '
		<div class="view vehicle vehicleFormAdd">
			<div class="licencePlateSearch tools">
				<input type="text" name="vehicleLicencePlate" id="vehicleLicencePlate" class="text" placeholder="' . _( "Reg.nr." ) . '" value="' . $sLicencePlate . '">
				<button type="button" id="searchVehicleBtn" data-item-id="' . ( !empty($_GET['itemId']) ? $_GET['itemId'] : '' ) . '">' . _( 'Search' ) . '</button>
			</div>
			<div class="tools">
				<span id="bilvisionButton"></span>
				<span id="connectToItemButton" data-item-id="' . ( !empty($_GET['itemId']) ? $_GET['itemId'] : '' ) . '"></span>
			</div>
			<div id="vehicleFormNotifications"></div>
			<div id="vehicleFormContainer">
				' . ( !empty($sLicencePlate) ? $sOutput : '' ) . '
			</div>
		</div>';
}
