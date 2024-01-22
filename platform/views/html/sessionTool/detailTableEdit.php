<?php

$oGeoIP2 = clRegistry::get( 'clGeoIP2', PATH_CORE . '/geoIp2' );
$oSessionTool = clRegistry::get( 'clSessionTool', PATH_MODULE . '/sessionTool/models' );

// Sort
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oSessionTool->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'DESC' )) : array('sessionTimestamp' => 'DESC') )
) );
$oSorting->setSortingDataDict( current( $oSessionTool->oDao->getDataDict() ) );

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oSessionTool->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 100
) );

/**
 * Search
 */
if( !empty($_GET['frmSearch']) ) {
	// Search for sessions
	$oSessionTool->oDao->setCriterias( array(
		'searchSession' => array(
			'fields' => array_keys( current( $oSessionTool->oDao->getDataDict() ) ),
			'value' => $_GET['searchQuery'],
			'type' => 'like'
		)		
	) );
}

// Data
$aSessions = $oSessionTool->read();

// Render pagination
$sPagination = $oPagination->render();

if( !empty($aSessions) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oSessionTool->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( current( $oSessionTool->oDao->getDataDict() ) + array(
		'sessionControls' => array(
			'title' => ''
		)
	) );
	
	foreach( $aSessions as $iKey => $aSession ) {
		$aClass = array( 'row' );
		
		if( $aSession['sessionId'] == session_id() ) {
			$aClass[] = 'active';
		}
		
		$oOutputHtmlTable->addBodyEntry( array(
			'sessionId' => $aSession['sessionId'],
			'sessionLastIp' => long2ip( $aSession['sessionLastIp'] ),
			'sessionLastIpGeo' => long2ip($aSession['sessionLastIp']) != '0.0.0.0' ? implode( ', ', $oGeoIP2->getInformation( long2ip($aSession['sessionLastIp']) ) ) : '',
			'sessionUserAgent' => $aSession['sessionUserAgent'],
			'sessionData' => substr( $aSession['sessionData'], 0, 70 ) . ' [...]',
			'sessionUserId' => $aSession['sessionUserId'],
			'sessionTimestamp' => date( 'Y-m-d H:i:s', $aSession['sessionTimestamp'] ),
			'sessionControls' => '
				<a href="#rowKey' . $iKey . '" class="icon iconInfo iconText toggleShow">' . _( 'Toggle data' ) . '</a>
			'
		), array(
			'class' => implode( ' ', $aClass )
		) );
		
		$oOutputHtmlTable->addBodyEntry( array(
			'sessionId' => array(
				'value' => '<pre style="font-size: 1.1em;">' . var_export( $oSessionTool->unserialize_php( $aSession['sessionData'] ), true ) . '</pre>',
				'attributes' => array(
					'colspan' => 7
				)
			)
		), array(
			'id' => 'rowKey' . $iKey,
			'class' => 'dataOutputRow'
		) );
	}
	
	$sOutput = $oOutputHtmlTable->render();
	
} else {
	$sOutput = '<strong>' . _( 'There are no sessions to show' ) . '</strong>';
}

/**
 * Search form 
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( array(
	'stringSearch' => array(
		'searchQuery' => array(
			'title' => _( 'Keywords' ),
			'type' => 'string'
		),
		'frmSearch' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
), array(
	'action' => $oRouter->sPath,
	'attributes' => array(
		'class' => 'inline'
	),
	'includeQueryStr' => false,
	'buttons' => array(
		'submit' => _( 'Search' )
	)
) );
$sSearchForm = $oOutputHtmlForm->render();

echo '
	<div class="view sessionTool tableEdit">
		<h1>' . _( 'Sessions' ) . '</h1>
		<section class="tools">
			<div class="tool">
				' . $sSearchForm . '
			</div>
		</section>
		<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">
			<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath('adminSessionList') . '">Sessioner</a>
				</li>
				<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active">
					<a href="' . $oRouter->getPath('adminSessionDetail') . '">Detaljerat</a>
				</li>
			</ul>
			<div class="view sessionTool ui-tabs-panel ui-widget-content ui-corner-bottom ui-helper-clearfix">			
				' . $sOutput . '
				' . $sPagination . '
			</div>
		</div>
	</div>';
	
$oTemplate->addStyle( array(
	'key' => 'customViewStylesheet',
	'content' => '
		.tools { margin-bottom: 1em; }
		
		.dataTable tr.active { background: #daffd3; font-weight: 700; }
		.dataOutputRow { background: #f0f0f0; }
		.dataOutputRow:hover {  background: #f0f0f0;  }
		.dataOutputRow td pre { height: 25em; overflow-y: scroll; }
	'
) );
