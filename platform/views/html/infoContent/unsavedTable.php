<?php

$oTinyMceAutoSave = clRegistry::get( 'clTinyMceAutoSave', PATH_MODULE . '/tinyMceAutoSave/models' );

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oTinyMceAutoSave->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('tempCreated' => 'ASC') )
) );

$oSorting->setSortingDataDict( array(
	'tempId' => array(),
	'tempContent' => array(),
	'tempCreated' => array()
) );

$aTempData = $oTinyMceAutoSave->readByGroupKey( 'infoContent', '*' );

if( !empty($aTempData) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );

	$oOutputHtmlTable = new clOutputHtmlTable( $oTinyMceAutoSave->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'tempControls' => array(
			'title' => ''
		)
	) );

	$sEditUrl = $oRouter->getPath( 'adminInfoContentPageAdd' );

	foreach( $aTempData as $entry ) {
		$entry['tempContent'] = strip_tags($entry['tempContent']);

		if( mb_strlen($entry['tempContent']) > 50 ) {
			$entry['tempContent'] = mb_substr($entry['tempContent'], 0, 50) . '...';
		}

		$row = array(
			'tempId' => $entry['tempId'],
			'tempContent' => $entry['tempContent'],
			'tempCreated' => $entry['tempCreated'],
			'tempControls' => '
				<a href="' . $sEditUrl . '?tempId=' . $entry['tempId'] . '" class="ajax icon iconText iconOverlays">' . _( 'Show' ) . '</a>
				<a href="' . $oRouter->sPath . '?event=deleteTinyMceTemp&amp;deleteTinyMceTemp=' . $entry['tempId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>'
		);

		$oOutputHtmlTable->addBodyEntry( $row );
	}

	$sTable = $oOutputHtmlTable->render();
} else {
	$sTable = '<strong>' . _('There are no items to show') . '</strong>';
}
echo '
	<div class="tempTable view">
		<h1>' . _('Unsaved texts') . '</h1>
		' . $sTable . '
	</div>';
