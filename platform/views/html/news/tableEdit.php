<?php

$oNews = clRegistry::get( 'clNews', PATH_MODULE . '/news/models' );
	$oNews->oDao->setLang( $GLOBALS['langIdEdit'] );

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oNews->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('newsCreated' => 'ASC') )
) );
$aNewsDataDict = $oNews->oDao->getDataDict( 'entNews' );

$oSorting->setSortingDataDict( array(
	'newsTitleTextId' => array(),
	'newsPublishStart' => array(),
	'newsPublishEnd' => array(),
	'newsCreated' => array(),
	'newsStatus' => array()
) );

$aNews = $oNews->aHelpers['oJournalHelper']->readAll( array(
	'newsId',
	'newsTitleTextId',
	'newsPublishStart',
	'newsPublishEnd',
	'newsCreated',
	'newsStatus'
) );

$sEditUrl = $oRouter->getPath( 'adminNewsAdd' );

$sOutput = '';

if( !empty($aNews) ) {
	$aNews = $oNews->read( array(
		'newsId',
		'newsTitleTextId',
		'newsPublishStart',
		'newsPublishEnd',
		'newsCreated',
		'newsStatus'
	), arrayToSingle($aNews, null, 'newsId')  );

	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oNews->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'newsControls' => array(
			'title' => ''
		)
	) );
	
	foreach( $aNews as $entry ) {
		if( $entry['newsPublishStart'] === '0000-00-00 00:00:00' ) $entry['newsPublishStart'] = '-';
		if( $entry['newsPublishEnd'] === '0000-00-00 00:00:00' ) $entry['newsPublishEnd'] = '-';

		if( !empty($entry['newsTitleTextId']) ) {
			$row = array(
				'newsTitleTextId' => '<a href="' . $sEditUrl . '?newsId=' . $entry['newsId'] . '" class="ajax">' . htmlspecialchars( $entry['newsTitleTextId'] ) . '</a>',
				'newsPublishStart' => substr( $entry['newsPublishStart'], 0, 10 ),
				'newsPublishEnd' => substr( $entry['newsPublishEnd'], 0, 10 ),
				'newsCreated' => substr( $entry['newsCreated'], 0, 16 ),
				'newsStatus' => '<span class="' . $entry['newsStatus'] . '">' . $aNewsDataDict['entNews']['newsStatus']['values'][ $entry['newsStatus'] ] . '</span>',
				'newsControls' => '
					<a href="' . $sEditUrl . '?newsId=' . $entry['newsId'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
					<a href="' . $oRouter->sPath . '?event=deleteJournal&amp;deleteJournal=' . $entry['newsId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			);
			$oOutputHtmlTable->addBodyEntry( $row );
		} else {
			$row = array(
				'newsTitleTextId' => '<a href="' . $sEditUrl . '?newsId=' . $entry['newsId'] . '" class="ajax">' . _( 'Not translated' ) . '</a>',
				'newsPublishStart' => substr( $entry['newsPublishStart'], 0, 10 ),
				'newsPublishEnd' => substr( $entry['newsPublishEnd'], 0, 10 ),
				'newsCreated' => substr( $entry['newsCreated'], 0, 16 ),
				'newsStatus' => '<span class="' . $entry['newsStatus'] . '">' . $aNewsDataDict['entNews']['newsStatus']['values'][ $entry['newsStatus'] ] . '</span>',
				'newsControls' => '
					<a href="' . $sEditUrl . '?newsId=' . $entry['newsId'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
					<a href="' . $oRouter->sPath . '?event=deleteJournal&amp;deleteJournal=' . $entry['newsId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			);
			$oOutputHtmlTable->addBodyEntry( $row, array( 'class' => 'notTranslated' ) );
		}
	}

	$sOutput = $oOutputHtmlTable->render();
	
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view news tableEdit">
		<h1>' . _( 'News' ) . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $sEditUrl. '" class="icon iconText iconAdd">' . _( 'Write new news' ) . '</a>
			</div>
		</section>
		<section>
			' . $sOutput . '
		</section>
	</div>';

$oNews->oDao->setLang( $GLOBALS['langId'] );