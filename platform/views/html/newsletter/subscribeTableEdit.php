<?php

$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );

// Sort
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oNewsletterSubscriber->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('subscriberCreated' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'subscriberId' => array(),
	'subscriberName' => array(),
	'subscriberEmail' => array(),
	'subscriberStatus' => array(),
	'subscriberUnsubscribe' => array(),
	'subscriberCreated' => array()
) );

// Search form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oNewsletterSubscriber->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'searchForm' ),
	'data' => !empty($_GET['searchQuery']) ? $_GET : null,
	'buttons' => array(
		'submit' => _( 'Search' ),
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'searchQuery' => array(
		'title' => _( 'Search' )
	),
	'formSearch' => array(
		'type' => 'hidden',
		'value' => true
	)
), array_diff_key($_GET, array('searchQuery' => '', 'page' => '')) );
$sSearchForm = $oOutputHtmlForm->render();

// Search
if( !empty($_GET['searchQuery']) ) {
	$oNewsletterSubscriber->oDao->setCriterias( array(
		'searchCountry' => array(
			'type' => 'like',
			'value' => $_GET['searchQuery'],
			'fields' => array(
				'subscriberName',
				'subscriberEmail'
			)
		)
	) );
}

$aSubscriberData = $oNewsletterSubscriber->read( array(
	'subscriberId',
	'subscriberName',
	'subscriberEmail',
	'subscriberStatus',
	'subscriberUnsubscribe',
	'subscriberCreated'
) );

if( !empty($aSubscriberData) ) {		
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oNewsletterSubscriber->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'subscriberControls' => array(
			'title' => ''
		)
	) );

	$sEditUrl = $oRouter->getPath( 'adminNewsletterSubscriberFormAdd' );

	foreach( $aSubscriberData as $entry ) {
		$row = array(
			'subscriberId' => $entry['subscriberId'],
			'subscriberName' => '<a href="' . $sEditUrl . '?subscriberId=' . $entry['subscriberId'] . '" class="ajax">' . htmlspecialchars( $entry['subscriberName'] ) . '</a>',
			'subscriberEmail' => $entry['subscriberEmail'],
			'subscriberStatus' => '<span class="' . $entry['subscriberStatus'] . '">' . _( ucfirst($entry['subscriberStatus']) ) . '</span>',
			'subscriberUnsubscribe' => _( ucfirst($entry['subscriberUnsubscribe']) ),
			'subscriberCreated' => $entry['subscriberCreated'],
			'subscriberControls' => '
				<a href="' . $sEditUrl . '?subscriberId=' . $entry['subscriberId'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
				<a href="' . $oRouter->sPath . '?event=deleteNewsletterSubscriber&amp;deleteNewsletterSubscriber=' . $entry['subscriberId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		);
		
		$oOutputHtmlTable->addBodyEntry( $row );
	}

	$sOutput = $oOutputHtmlTable->render();
} else {
	$sOutput = '<strong>' . _('There are no items to show') . '</strong>';
}

echo '
	<div class="newsletterSubscriberTable view">
		<h1>' . _( 'Newsletter subscribers' ) . '</h1>
		' . $sSearchForm . '
		' . $sOutput . '
	</div>';
	
echo '
	<br /><br />
	<a href="' . $oRouter->sPath . '?event=importSubscribers&importSubscribers=users" class="icon iconText iconDbImport">' . _( 'Import subscribers' ) . '</a>';
