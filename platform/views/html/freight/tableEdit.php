<?php

$aErr = array();
$bValidContinent = false;

# Get general freight
$oConfig = clRegistry::get( 'clConfig' );
$fGeneralFreight = current( current($oConfig->read('configValue', 'generalFreightFee')) );

# Get continents
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$aContinents = $oContinent->read();

# Get all defined freights
$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );
$oFreight->oDao->aSorting = array(
	'freightCountryId' => 'ASC'
);
$aFreights = $oFreight->read();

echo '
	<h2>' . _( 'Freight' ) . '</h2>';

if( empty($aFreights) ) {
	echo '
	<p><strong>' . _( 'There are no items to show' ) . '</strong></p>';
	return;
}

# Get all countries to display
$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName'
), arrayToSingle($aFreights, null, 'freightCountryId') );
$aCountries = arrayToSingle( $aCountries, 'countryId', 'countryName' );

echo '
	<div class="freightTable view">';

clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $oFreight->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( array(
	'freightCountryId' => array(),
	'freightValue' => array(
		'title' => _( 'Freight addition')
	),
	'freightTotal' => array(
		'title' => _( 'Total freight')
	),
	'freightControls' => array(
		'title' => ''
	)
) );

$sEditUrl = $oRouter->getPath( 'adminNewsAdd' );

foreach( $aFreights as $entry ) {
	$row = array(
		'freightCountryId' => $aCountries[$entry['freightCountryId']],
		'freightValue' => $entry['freightValue'],
		'freightTotal' => $entry['freightValue'] + $fGeneralFreight,
		'freightControls' => '<a class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '" href="' . $oRouter->sPath . '?event=deleteFreight&amp;deleteFreight=' . $entry['freightId'] . '&amp;' . stripGetStr( array('event', 'deleteFreight') ) . '"><span>' . _( 'Delete' ) . '</span></a>'
	);
	$oOutputHtmlTable->addBodyEntry( $row );
}

	echo '
		<section>' . $oOutputHtmlTable->render() . '</section>
	</div>';