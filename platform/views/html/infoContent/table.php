<?php

$aInfoBlocks = array();

$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
// Language support
$oInfoContent->oDao->setLang( $GLOBALS['langIdEdit'] );

clFactory::loadClassFile( 'clOutputHtmlSorting' );
// Sorting blocks
$oSorting = new clOutputHtmlSorting( $oInfoContent->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('contentKey' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'contentKey' => array(),
	'contentCreated' => array()
) );

// Sorting infoPages
$oSortingPages = new clOutputHtmlSorting( $oInfoContent->oDao, array(
	'currentSort' => ( isset($_GET['sortPages']) ? array($_GET['sortPages'] => (isset($_GET['sortPagesDirection']) ? $_GET['sortPagesDirection'] : 'ASC' )) : array('contentKey' => 'ASC') ),
	'getVariable' => 'sortPages'
) );
$oSortingPages->setSortingDataDict( array(
	'contentKey' => array(),
	'contentCreated' => array()
) );

$aInfoContents = $oInfoContent->read( array(
	'contentId',
	'contentKey',
	'contentViewId',
	'contentCreated'
) );

$sOutput = '';

if( !empty($aInfoContents) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oInfoContent->oDao->getDataDict(), array('attributes' => array('cellspacing' => '0')) );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'infoContentControls' => array(
			'title' => ''
		)
	) );

	$sEditUrl = $oRouter->getPath( 'adminInfoContentAdd' );

	// Read relation between views and layouts
	$oLayoutHtml = clRegistry::get( 'clLayoutHtml' );
	$aLayoutData = $oLayoutHtml->readByViewId( arrayToSingle($aInfoContents, null, 'contentViewId' ) );
	$aInfoContentToLayouts = arrayToSingle($aLayoutData, 'viewId', 'sectionLayoutKey' );

	$oLocale = clRegistry::get( 'clLocale' );
	$aLocaleIds = arrayToSingle( $oLocale->read(), null, 'localeId' );
	
	foreach( $aInfoContents as $entry ) {
		// Check if info content belongs to a layout
		if( array_key_exists( $entry['contentViewId'], $aInfoContentToLayouts ) ) {
			$aInfoBlocks[] = $entry;
			continue;
		}
		
		// Multiple languages
		$aInfoContentLocaleData = array();
		if( count($aLocaleIds) > 1 ) {			
			foreach( $aLocaleIds as $iLocaleId ) {
				$aInfoContentLocaleData[] = $oInfoContent->oDao->read( array(
					'fields' => '*',
					'criterias' => 'langId = ' . (int) $iLocaleId
				) );
			}
		}
		
		if( count($aInfoContentLocaleData) > 1 ) {
			$sDeleteMessage = _( 'Do you really want to delete this item? Please note that this page exists in multiple languages' );
		} else {
			$sDeleteMessage = _( 'Do you really want to delete this item?' );
		}
		
		$row = array(
			'contentKey' => '<a href="' . $sEditUrl . '?contentId=' . $entry['contentId'] . '">' . htmlspecialchars( $entry['contentKey'] ) . '</a>',
			'contentCreated' => $entry['contentCreated'],
			'infoContentControls' => '<a href="' . $sEditUrl . '?contentId=' . $entry['contentId'] . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a> <a href="?event=deleteInfoContent&amp;deleteInfoContent=' . $entry['contentId'] . '" class="icon iconText iconDelete linkConfirm" title="' . $sDeleteMessage . '">' . _( 'Delete' ) . '</a>'
		);

		$oOutputHtmlTable->addBodyEntry( $row );
	}

	$sOutput = '<section>' . $oOutputHtmlTable->render() . '</section>';
} else {
	$sOutput = '
		<section><strong>' . _('There are no items to show') . '</strong></section>';
}

/**
 * infoblocks that belong to infoPages
 */
if( array_key_exists( 'super', $_SESSION['user']['groups'] ) ) {
	
	$sOutput .= '
		<h2>' . _( 'Infoblocks in infopages' ) . '</h2>';
	
	if( !empty($aInfoBlocks) ) {
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oOutputHtmlTable = new clOutputHtmlTable( $oInfoContent->oDao->getDataDict(), array('attributes' => array('cellspacing' => '0')) );
		$oOutputHtmlTable->setTableDataDict( $oSortingPages->render() + array(
			'infoContentControls' => array(
				'title' => ''
			)
		) );

		$sEditUrl = $oRouter->getPath( 'adminInfoContentAdd' );

		foreach( $aInfoBlocks as $entry ) {
			// Multiple languages
			if( count($aLocaleIds) > 1 ) {
				$aInfoContentLocaleData = array();
				foreach( $aLocaleIds as $iLocaleId ) {
					$aInfoContentLocaleData[] = $oInfoContent->oDao->read( array(
						'fields' => '*',
						'criterias' => 'langId = ' . (int) $iLocaleId
					) );
				}
			}
			
			if( count($aInfoContentLocaleData) > 1 ) {
				$sDeleteMessage = _( 'Do you really want to delete this item? Please note that this page exists in multiple languages' );
			} else {
				$sDeleteMessage = _( 'Do you really want to delete this item?' );
			}
			
			$row = array(
				'contentKey' => '<a href="' . $sEditUrl . '?contentId=' . $entry['contentId'] . '">' . htmlspecialchars( $entry['contentKey'] ) . '</a>',
				'contentCreated' => $entry['contentCreated'],
				'infoContentControls' => '<a href="' . $sEditUrl . '?contentId=' . $entry['contentId'] . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a> <a href="?event=deleteInfoContent&amp;deleteInfoContent=' . $entry['contentId'] . '" class="icon iconText iconDelete linkConfirm" title="' . $sDeleteMessage . '">' . _( 'Delete' ) . '</a>'
			);

			$oOutputHtmlTable->addBodyEntry( $row );
		}

		$sOutput .= '<section>' . $oOutputHtmlTable->render() . '</section>';
	} else {
		$sOutput .= '
			<section><strong>' . _('There are no items to show') . '</strong></section>';
	}
}

echo '
	<div class="view infoContent table">
		<h1>' . _( 'Infoblocks' ) . '</h1>
		' . ($_SESSION['user']['groupKey'] == 'super' ? '
		<section class="tools">
			<div class="tool">
				<a href="' . $oRouter->getPath( 'adminInfoContentAdd' ) . '" class="icon iconText iconAdd">' . _( 'Create' ) . '</a>
			</div>
		</section>
		' : '')  . '
		' . $sOutput . '
	</div>';

$oInfoContent->oDao->setLang( $GLOBALS['langId'] );
