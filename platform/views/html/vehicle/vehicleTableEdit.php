<?php

$sOutput = '';

$oVehicle = clRegistry::get( 'clVehicleData', PATH_MODULE . '/vehicle/models' );


// Sorting
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oVehicle->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('vehicleDataUpdated' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'vehicleLicencePlate' => array(
    'title' => _( 'Reg.nr.' )
  ),
	'vehicleDataCreated' => array(
    'title' => _( 'Skapad' )
  ),
	'vehicleDataUpdated' => array(
    'title' => _( 'Uppdaterad' )
  )
) );


$aVehicles = $oVehicle->read( array(
  'vehicleDataId',
  'vehicleLicencePlate',
  'vehicleDataCreated',
  'vehicleDataUpdated'
) );

if( !empty($aVehicles) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oVehicle->oDao->aDataDict );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'vehicleControls' => array(
			'title' => ''
		)
	) );

	foreach( $aVehicles as $aVehicle ) {
		$row = array(
			'vehicleLicencePlate' => $aVehicle['vehicleLicencePlate'],
			'vehicleDataCreated' => $aVehicle['vehicleDataCreated'],
			'vehicleDataUpdated' => $aVehicle['vehicleDataUpdated'],
			'vehicleControls' => '
				<a href="?vehicleDataId=' . $aVehicle['vehicleDataId'] . '" class="ajax editVehicle popupTrigger icon iconText iconEdit" data-vehicle-data-id="' . $aVehicle['vehicleDataId'] . '">' . _( 'Edit' ) . '</a>'
		);

		$oOutputHtmlTable->addBodyEntry( $row );
	}

	$sOutput = $oOutputHtmlTable->render();

}

$oTemplate->addScript( array(
	'key' => 'vehicleFormFunctions',
	'src' => '/js/vehicleFunctions.js'
) );

echo '
  <div class="view vehicle vehicleTableEdit">
		<h1>' . _( 'Fordon' ) . '</h1>
		<div class="tableFormTools">
			<a href="#" class="addVehicle popupTrigger ajax icon iconAdd iconText">' . _( 'Add' ) . '</a>
		</div>
    ' . $sOutput . '
		<div id="vehicleForm" class="popup">
			<div class="arrow-up"></div>
			<div class="content">
				<div id="vehicleFormContent" class="form"></div>
				<hr>
				<button class="closePopup">' . _( 'Close' ) . '</button>
			</div>
		</div>
  </div>';
