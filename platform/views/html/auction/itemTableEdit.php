<?php

if( empty($_GET['auctionId']) ) return;

if( !empty($_GET['sendWinnerMail']) ) {
	$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );

	$oAuctionItem->sendWinnerMail( $_GET['sendWinnerMail'], null, true );

	return;
}

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oItemDao = $oAuctionEngine->getDao( 'auctionItem' );

/**
 * Sorting
 */
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oItemDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('itemSortNo' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'itemSortNo' => array(),
	'itemTitle' => array(),
	'itemCreated' => array(),
	'itemStatus' => array()
) );

// Data
$aItems = $oAuctionEngine->readAuctionItem( array(
	'auctionId' => $_GET['auctionId'],
	'status' => '*'
) );

if( !empty($aItems) ) {
    // Table init
    clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oItemDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'controls' => array(
			'title' => ''
		)
	) );

    foreach( $aItems as $aItem ) {
        $aRow = array(
            'itemSortNo' => $aItem['itemSortNo'],
            'itemTitle' => $aItem['itemTitle'],
            'itemCreated' => substr( $aItem['itemCreated'], 0, 16 ),
            'itemStatus' => '<span class="' . $aItem['itemStatus'] . '">' . _( ucfirst($aItem['itemStatus']) ) . '</span>',
			'controls' => ''
        );
        $oOutputHtmlTable->addBodyEntry( $aRow );
    }

    $sOutput = $oOutputHtmlTable->render();

} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view auction itemTableEdit">
		<h1>' . _( 'Auction items' ) . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $oRouter->getPath( 'adminAuctions' ) . '" class="icon iconText iconGoBack">' . _( 'Go back' ) . '</a>
			</div>
		</section>
		<section>
			' . $sOutput . '
		</section>
	</div>';
