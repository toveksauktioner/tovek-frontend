<?php

$aErr = array();
$bValidContinent = false;

# Get continents
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$aContinents = $oContinent->read();

# Get all defined vats
$oVat = clRegistry::get( 'clVat', PATH_MODULE . '/vat/models' );
$oVat->oDao->aSorting = array(
	'vatCountryId' => 'ASC'
);
$aVats = $oVat->read();

echo '
	<h2>' . _( 'Countries without VAT' ) . '</h2>';

if( empty($aVats) ) {
	echo '
	<p><strong>' . _( 'There are no items to show' ) . '</strong></p>';
	return;
}

# Get all countries to display
$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName'
), arrayToSingle($aVats, null, 'vatCountryId') );
$aCountries = arrayToSingle( $aCountries, 'countryId', 'countryName' );

echo '
	<div class="vatTable view">';

clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $oVat->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( array(
	'vatCountryId' => array(),
	'vatControls' => array(
		'title' => ''
	)
) );

$sEditUrl = $oRouter->getPath( 'adminNewsAdd' );

foreach( $aVats as $entry ) {
	$row = array(
		'vatCountryId' => $aCountries[$entry['vatCountryId']],
		'vatControls' => '<a class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '" href="' . $oRouter->sPath . '?event=deleteVat&amp;deleteVat=' . $entry['vatId'] . '&amp;' . stripGetStr( array('event', 'deleteVat') ) . '">' . _( 'Delete' ) . '</a>'
	);
	$oOutputHtmlTable->addBodyEntry( $row );
}

echo '
		' . $oOutputHtmlTable->render() . '
	</div>';