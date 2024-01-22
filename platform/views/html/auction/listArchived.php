<?php

$oAuctionBackend = clRegistry::get( 'clAuctionBackend', PATH_MODULE . '/auctionTransfer/models' );
$oAuctionDao = $oAuctionBackend->oDao;


/**
 * Sorting
 */
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oAuctionDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('partAuctionStart' => 'DESC') )
) );

$oSorting->setSortingDataDict( array(
	'auctionId' => array(
		'title' => ''
	),
	'auctionTitle' => array(
		'title' => _( 'Auction' )
	),
	'partTitle' => array(
		//'title' => '<img alt="" src="/images/templates/tovekClassic/icon-info-list.png">&nbsp;' . _( 'Title' )
		'title' => _( 'Title' )
	),
	'auctionLocation' => array(
		'title' => _( 'City' )
	),
	'partAuctionStart' => array(
		//'title' => '<img alt="" src="/images/templates/tovekClassic/icon-endtime-list.png">&nbsp;' . _( 'Took place' )
		'title' => _( 'Took place' )
	)
) );


$oAuctionDao->setCriterias( array(
	'archiveStatus' => array(
		'type' => '=',
		'value' => 'active',
		'fields' => array(
			'auctionArchiveStatus'
		)
	)
) );
$aAuctionData = $oAuctionBackend->readAuction( array(
	'fields' => 'partAuctionStart',
	'auctionStatus' => 'active',
	'partStatus' => 'ended'
) );

$aDates = array();
if( !empty($aAuctionData) ) {
    foreach( $aAuctionData as $entry ) {
        $iYear = (int) substr( $entry['partAuctionStart'], 0, 4 );
        $iMonth = (int) substr( $entry['partAuctionStart'], 5, 2 );
        $aDates[$iYear][$iMonth] = $iMonth;
    }
}

$sSelectDateOutput = '
	<div class="tabs scroll narrow years">';
foreach( $aDates as $iYear => $aMonths ) {
	if( !empty($iYear) ) {
		$sSelectDateOutput .= '
			<a href="' . $oRouter->sPath . '?y=' . $iYear . '" class="tab ' . ( (!empty($_GET['y']) && ($_GET['y'] == $iYear)) ? 'selected' : '' ) . '">' . $iYear . '</a>';
	}
}
$sSelectDateOutput .= '
	</div>';

if( !empty($_GET['y']) ) {
	$sSelectDateOutput .= '
		<div class="tabs scroll narrow smaller months">';
	for( $i=1; $i<=12; $i++ ) {
		$oDateObj = DateTime::createFromFormat( '!m', $i );
		$sMonth = $oDateObj->format( 'F' );
		$sMonth = formatIntlDate( "LLL", strtotime( $_GET['y'] . '-' . $i) );

		if( isset($aDates[$_GET['y']][$i]) ) {
			$sSelectDateOutput .= '
				<a href="' . $oRouter->sPath . '?y=' . $_GET['y'] . '&m=' . $i . '" class="tab ' . ( (!empty($_GET['m']) && ($_GET['m'] == $i)) ? 'selected' : '' ) . '">' . $sMonth . '</a>';
		} else {
			$sSelectDateOutput .= '
				<span class="tab">' . $sMonth . '</span>';
		}
	}
	$sSelectDateOutput .= '
		</div>';
}

// Limit to year (and month)
if( !empty($_GET['y']) ) {
	$sSearchValue = $_GET['y'];

	if( !empty($_GET['m'])) {
		$sSearchValue .= '-' . ( ($_GET['m'] < 10) ? '0' : '' ) . $_GET['m'];
	}

	$aCriterias = array(
		'dateSearch' => array(
			'type' => 'like',
			'value' => $sSearchValue,
			'fields' => array(
				'partAuctionStart'
			)
		)
	);
	$oAuctionDao->setCriterias( $aCriterias );
}

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oAuctionDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 10,
	'stringSingular' => _( 'Auction' ),
	'stringPlural' => _( 'Auctions' )
) );

/**
 * Get data
 */
$aReadFields = array(
	'auctionId',
	'auctionTitle',
	'auctionLocation',
	'partId',
	'partTitle',
	'partAuctionTitle',
	'partAuctionStart',
	'partStatus',
	'routePath'
);

$aAuctionData = $oAuctionBackend->readAuction( array(
	'fields' => $aReadFields,
	'auctionStatus' => 'active',
	'partStatus' => 'ended'
) );

$sOutput = '';

if( !empty($aAuctionData) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oAuctionDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() );

	$iCount = 1;

	foreach( $aAuctionData as $aAuction ) {
		$sClass = '';

		// Replace auction title with alternative if set
		if( !empty($aAuction['partAuctionTitle']) ) {
			$aAuction['auctionTitle'] = $aAuction['partAuctionTitle'];
		}

		if( $iCount % 2 == 0 ) $sClass = 'odd';

		$row = array(
			'auctionId' => '<small>' . $aAuction['auctionId'] . '</small>',
			'auctionTitle' => '<a href="' . $oRouter->getPath( 'guestAuctionItemsArchived' ) . '?auctionId=' . $aAuction['auctionId'] . '&partId=' . $aAuction['partId'] . '&sortBy=ended">' . $aAuction['auctionTitle'] . '</a>',
			'partTitle' => $aAuction['partTitle'],
			'auctionLocation' => $aAuction['auctionLocation'],
			'partAuctionStart' => formatIntlDate( "d MMM", strtotime($aAuction['partAuctionStart']) )
		);
		$oOutputHtmlTable->addBodyEntry( $row, array( 'class' => $sClass ) );

		++$iCount;
	}

	$sOutput = $oOutputHtmlTable->render( array( 'class' => 'auctionList' ) ) . $oPagination->render();

} else {
	$sOutput = '<strong>' . _('There are no items to show') . '</strong>';
}

echo '
	<div class="view auction listArchivedAuctions">
		<h1>' . _( 'Auction archive' ) . '</h1>
		' . $sSelectDateOutput . '
		' . $sOutput . '
	</div>';
