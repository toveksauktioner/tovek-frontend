<?php

$sOutput = '';

$oVehicleLookup = clRegistry::get( 'clVehicleLookup', PATH_MODULE . '/vehicle/models' );
$oBilvision = clRegistry::get( 'clVehicleLookupBilvision', PATH_MODULE . '/vehicle/models' );


// Add vehicle data
if( !empty($_GET['createVehicleData']) ) {
	$aData = current( $oVehicleLookup->read( array(
		'lookupId',
		'lookupService'
	), $_GET['createVehicleData'] ) );

	if( !empty($aData) ) {
		switch( $aData['lookupService'] ) {
			case 'Bilvision':
				$oLayout = clRegistry::get( 'clLayoutHtml' );
				$oLayout->renderView( 'vehicle/lookupServiceBilvision.php' );
				break;
		}
	}
}


// Filter to only successfull
$oVehicleLookup->oDao->setCriterias( array(
	'onlySuccessful' => array(
		'type' => '=',
		'value' => 'success',
		'fields' => 'lookupStatus'
	)
) );


// Sorting
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oVehicleLookup->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('lookupCreated' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'lookupSearchParam' => array(
    'title' => _( 'Sökparameter' )
  ),
	'lookupServiceFunction' => array(
    'title' => _( 'Function' )
  ),
	'lookupCreated' => array(
    'title' => _( 'Skapad' )
  )
) );


$aVehicleLookups = $oVehicleLookup->read( array(
	'lookupId',
	'lookupServiceFunction',
	'lookupSearchParam',
	'lookupVehicleDataId',
	'lookupCreated'
) );

$aVehicleLookupData = array();
foreach( $aVehicleLookups as $aLookup ) {
	$aVehicleLookupData[ $aLookup['lookupSearchParam'] ][ $aLookup['lookupServiceFunction'] ] = $aLookup;
}


if( !empty($aVehicleLookupData) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oVehicleLookup->oDao->aDataDict );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'lookupControls' => array(
			'title' => ''
		)
	) );

	foreach( $aVehicleLookupData as $sSearchParam => $aDataRows ) {
		foreach( $aDataRows as $aDataRow ) {
			if( !empty($aDataRow['lookupVehicleDataId']) ) continue;

			$row = array(
				'lookupSearchParam' => $aDataRow['lookupSearchParam'],
				'lookupServiceFunction' => $aDataRow['lookupServiceFunction'],
				'lookupCreated' => $aDataRow['lookupCreated'],
				'lookupControls' => '
					<a href="?createVehicleData=' . $aDataRow['lookupId'] . '" class="ajax icon iconText iconAdd">' . _( 'Add' ) . '</a>'
			);

			$oOutputHtmlTable->addBodyEntry( $row );
		}
	}

	$sOutput = $oOutputHtmlTable->render();

}

if( !empty($sOutput) ) {
	echo '
		<div class="view vehicle lookupTableEdit">
			<h1>' . _( 'Uppslag' ) . '</h1>
			' . $sOutput . '
		</div>';
}
