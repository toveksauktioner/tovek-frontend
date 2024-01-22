<?php

$oContinentGroup = clRegistry::get( 'clContinentGroup', PATH_MODULE . '/continentGroup/models' );

// Sort
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oContinentGroup->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('entryCreated' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'entryGroupKey' => array(),
	'entryCreated' => array()
) );

// Data
$aData = $oContinentGroup->readAllGroups( array( 'entryGroupKey', 'entryCreated' ) );

// Table
clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $oContinentGroup->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
	'entryControls' => array(
		'title' => ''
	)
) );

$sEditUrl = $oRouter->getPath( 'adminContinentGroup' );

foreach( $aData as $entry ) {
	$row = array(
		'entryGroupKey' => $entry['entryGroupKey'],
		'entryCreated' => substr( $entry['entryCreated'], 0, 16 ),
		'entryControls' => '
			<a href="' . $sEditUrl . '?continentGroupKey=' . $entry['entryGroupKey'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
			<a href="' . $oRouter->sPath . '?event=deleteContinentGroup&amp;deleteContinentGroup=' . $entry['entryGroupKey'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
	);

	$oOutputHtmlTable->addBodyEntry( $row );
}

echo '
	<div class="view adminContinentGroupTable">
		<h1>' . _( 'Continent groups' ) . '</h1>
		' . $oOutputHtmlTable->render() . '
	</div>';
