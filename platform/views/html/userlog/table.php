<?php

$oUserLog = clRegistry::get( 'clUserLog', PATH_MODULE . '/userlog/models' );
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oUserLog->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('userlogCreated' => 'ASC') )
) );

$oSorting->setSortingDataDict( array(
	'username' => array(),
	'userlogParentType' => array(),
	'userlogParentId' => array(),
	'userlogEvent' => array(),
	'userlogCreated' => array()
) );

clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oUserLog->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 50
) );

if( !empty($_GET['formSearch']) ) {
	$aSearchCriterias = array();

	// Text search
	if( !empty( $_GET['searchQuery']) ) {
		$aSearchCriterias += array (
			'freeText' => array(
				'type' => 'like',
				'value' => $_GET['searchQuery'],
				'fields' => array(
					'username',
					'userlogParentType',
					'userlogParentId',
				)
			)
		);
	}

	if( !empty($_GET['event']) ) {
		$aSearchCriterias += array(
			'eventType' => array(
				'type' => 'like',
				'value' => $_GET['event'],
				'fields' => array(
					'userlogEvent',
				)
			)
		);
	}

	$oUserLog->oDao->setCriterias( $aSearchCriterias );
}

$aLogEntries = $oUserLog->oDao->readData( array(
	'fields' => '*',
	'userlogId' => '*'
) );

$sOutput = "";

// Log search form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oUserLog->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'searchForm' ),
	'data' => $_GET,
	'buttons' => array(
		'submit' => _( 'Search' ),
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'searchQuery' => array(
		'title' => _( 'Search' )
	),
	'event' => array(
		'type' => 'array',
		'title' => _( 'Event-type' ),
		'values' => array(
			'' => _( 'All' ),
			'create' => _( 'Create' ),
			'delete' => _( 'Delete' ),
			'update' => _( 'Update' ),
			'upsert' => _( 'Upsert' )
		)
	),
	'formSearch' => array(
		'type' => 'hidden',
		'value' => true
	)
), array_diff_key($_GET, array('searchQuery' => '', 'event' => '', 'page' => '')) );
$sSearchForm = $oOutputHtmlForm->render();

if( !empty($aLogEntries) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oUserLog->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() );

	foreach( $aLogEntries as $aEntry ) {
		$oOutputHtmlTable->addBodyEntry( $aEntry );
	}
	$sOutput = $oOutputHtmlTable->render();
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view log tableEdit">
		<h1>' . _( 'Logs' ) . '</h1>
		<section class="tools">
			' . $sSearchForm . '
		</section>
		<section>
			' . $sOutput . '
			' , $oPagination->render() . '
		</section>
	</div>';