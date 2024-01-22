<?php

if( !isset($_SESSION['userId']) ) {
	return;
}

$oGeoIP2 = clRegistry::get( 'clGeoIP2', PATH_CORE . '/geoIp2' );
$oSessionTool = clRegistry::get( 'clSessionTool', PATH_MODULE . '/sessionTool/models' );

// Sort
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oSessionTool->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'DESC' )) : array('sessionTimestamp' => 'DESC') )
) );
$oSorting->setSortingDataDict( array(
	'sessionId' => array(),
	'sessionLastIp' => array(),
	'sessionLastIpGeo' => array(),
	'sessionUserAgent' => array()
) );

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
$aSessions = $oSessionTool->readByUser( $_SESSION['userId'] );

// Render pagination
$sPagination = $oPagination->render();

if( !empty($aSessions) ) {
	$aTableDict = array(
		'entTable' => array(
			
		)
	);
	
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oSessionTool->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
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
			'sessionLastIpGeo' => $aSession['sessionLastIpGeo'],
			'sessionUserAgent' => $aSession['sessionUserAgent']
		), array(
			'class' => implode( ' ', $aClass )
		) );
	}
	
	$sOutput = $oOutputHtmlTable->render();
	
} else {
	$sOutput = '<strong>' . _( 'There are no sessions to show' ) . '</strong>';
}

echo '
	<div class="view sessionTool tableEdit">
		<h1>' . _( 'My sessions' ) . '</h1>
		<section>
			' . $sOutput . '
			' . $sPagination . '
		</section>
	</div>';
	
$oTemplate->addStyle( array(
	'key' => 'customViewStylesheet',
	'content' => '
		.dataTable tr.active { background: #daffd3; font-weight: 700; }
	
		.dataOutputRow { background: #f0f0f0; }
		.dataOutputRow:hover {  background: #f0f0f0;  }
		.dataOutputRow td pre { height: 25em; overflow-y: scroll; }
	'
) );
