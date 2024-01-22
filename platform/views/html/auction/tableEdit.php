<?php

if( !empty($_GET['bidTest']) ) {
	die();

	$aData = array(
		'historyBidId' => '99999',
		'historyBidType' => 'normal',
		'historyBidValue' => '6000',
		'historyBidItemId' => '427996',
		'historyBidUserId' => '101212',
		'historyBidAuctionId' => '2396',
		'historyBidPartId' => '2679',
		'historyBidPlaced' => '1572127315.5777',
		'historyCreated' => '2019-10-27 00:01:55'
	);

	$oAuctionBid = clRegistry::get( 'clAuctionBid', PATH_MODULE . '/auction/models' );
	$oAuctionBid->oDao->oDb->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$oAuctionBid->oDao->oDb->setAttribute( PDO::ATTR_TIMEOUT, 20 );

	try {
		// Array of column names
		$aDataKeys = array_keys( $aData );

		$sQuery = "
			INSERT INTO entAuctionBidHistory(" . implode(',', $aDataKeys) . ")
			SELECT :" . implode(',:', $aDataKeys) . "
			FROM (SELECT 1) entTemporaryTable
			WHERE NOT EXISTS(
				SELECT * FROM entAuctionBidHistory
				WHERE historyBidId = :historyBidId
				LIMIT 1
			) <=> NULL LOCK IN SHARE MODE";

		// Prepare transaction query
		$oAuctionBid->oDao->oDb->prepare( $sQuery );

		// Turns off autocommit mode and begin transaction
		$oAuctionBid->oDao->oDb->beginTransaction();

		$oAuctionBid->oDao->oDb->execute( array_combine(
			array_map(
				function($key) { return ":" . $key; },
				array_keys($aData)
			),
		$aData ) );

		// Fetch bid ID
		$iLastInsertId = (int) $oAuctionBid->oDao->oDb->lastId();

		// Finish transaction
		$oAuctionBid->oDao->oDb->commit();

		echo '<pre>';
		var_dump( $iLastInsertId );
		var_dump( $sQuery );
		die;

	} catch( PDOException $oError ) {
		// Bid could not be placed
		$oAuctionBid->oDao->oDb->rollBack();

		echo '<pre>';
		var_dump( $oError );
		die();
	}

	echo '<pre>';
	var_dump( $var );
	die;
}

if( !empty($_GET['historyUpdate']) ) {
	die();

	ini_set( 'memory_limit', '4096M' );
	set_time_limit( 0 );

	$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );

	$aItems = valueToKey( 'itemId', $oAuction->oDao->oDb->query( "SELECT itemId, itemSortNo, itemTitle, itemBidCount FROM entAuctionItem" ) );

	$aHistoryByItem = groupByValue( 'historyBidItemId', $oAuction->oDao->oDb->query( 'SELECT * FROM entAuctionBidHistory GROUP BY historyBidId' ) );

	if( !empty($aItems) && !empty($aHistoryByItem) ) {
		$iCount = 0;
		foreach( $aHistoryByItem as $iItemId => $aHistoryGroup ) {
			$iHistoryCount = count( $aHistoryGroup );

			if( $iHistoryCount != $aItems[ $iItemId ]['itemBidCount'] ) {
				//echo $iItemId . ': ej lika<br />';
				//continue;

				//echo '<pre>';
				//var_dump( $iItemId );
				//die;

				//$res = $oAuction->oDao->oDb->write( 'UPDATE entAuctionItem SET itemBidCount = "' . $iHistoryCount . '" WHERE itemId = "' . $iItemId . '"' );
				//$iCount++;

				//echo '<pre>';
				//var_dump( $iItemId );
				//var_dump( $res );
				//die;
			}
			//echo $iItemId . ': lika<br />';

			//if( $iCount > 300 ) {
			//	echo '<pre>Done! ';
			//	var_dump( $iCount );
			//	die;
			//}
		}
		echo 'All done!';
		die();
	}
}

if( !empty($_GET['itemDup']) ) {
	die();

	$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );

	$aItems = valueToKey( 'itemId', $oAuction->oDao->oDb->query( "SELECT itemId, itemSortNo, itemTitle, itemBidCount FROM entAuctionItem WHERE itemEndTime > '2019-10-29 00:00:00' AND itemEndTime < '2019-10-29 23:59:00' ORDER BY itemSortNo ASC" ) );

	if( !empty($aItems) ) {
		foreach( $aItems as $iItemId => $aItem ) {
			$aHistory = $oAuction->oDao->oDb->query( 'SELECT * FROM entAuctionBidHistory WHERE historyBidItemId = "' . $iItemId . '" GROUP BY historyBidId' );
			$iHistoryCount = count( $aHistory );

			$res = $oAuction->oDao->oDb->write( 'UPDATE entAuctionItem SET itemBidCount = "' . $iHistoryCount . '" WHERE itemId = "' . $iItemId . '"' );
		}
	}
}

if( !empty($_GET['bidDup']) ) {
	die();

	$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );

	$aHistoryByBid = groupByValue( 'historyBidId', $oAuction->oDao->oDb->query( '
		SELECT historyId, historyBidValue, historyBidItemId, historyBidAuctionId, entAuctionBidHistory.historyBidId
		FROM entAuctionBidHistory
		INNER JOIN(
		SELECT historyBidId
		FROM entAuctionBidHistory
		GROUP BY historyBidId
		HAVING COUNT(historyId) >1
		)temp ON entAuctionBidHistory.historyBidId= temp.historyBidId;' ) );

	$aItems = array();
	foreach( $aHistoryByBid as $iBidId => $aHistoryBids ) {
		$aFirst = current( $aHistoryBids );
		if( !isset($aItems[ $aFirst['historyBidItemId'] ]) ) {
			$aItems[ $aFirst['historyBidItemId'] ] = array();
		}
		$aItems[ $aFirst['historyBidItemId'] ][ $iBidId ] = $aHistoryBids;
	}

	foreach( $aItems as $iItemId => $aGroupBids ) {
		$aHistory = $oAuction->oDao->oDb->query( 'SELECT * FROM entAuctionBidHistory WHERE historyBidItemId = "' . $iItemId . '" GROUP BY historyBidId' );
		$iHistoryCount = count( $aHistory );

		$res = $oAuction->oDao->oDb->write( 'UPDATE entAuctionItem SET itemBidCount = "' . $iHistoryCount . '" WHERE itemId = "' . $iItemId . '"' );

		//$iBidCount = 0;
		//
		//foreach( $aGroupBids as $iBidId => $aHistoryBids ) {
		//	foreach( $aHistoryBids as $iKey => $aHistory ) {
		//		if( $iKey == 0 ) continue;
		//
		//		$oAuction->oDao->oDb->write( 'DELETE FROM entAuctionBidHistory WHERE historyId = "' . $aHistory['historyId'] . '"' );
		//		$iBidCount++;
		//	}
		//}
		//
		//$oAuction->oDao->oDb->write( '
		//	UPDATE entAuctionItem
		//	SET itemBidCount = itemBidCount - ' . $iBidCount . '
		//	WHERE itemId = "' . $iItemId . '"' );
		//
		//echo '<pre>';
		//var_dump( $aItems );
		//die;
	}
}

$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );

/**
 * Sorting
 */
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oAuction->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('itemSortNo' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'auctionId' => array(),
	'auctionType' => array(),
	'auctionInternalName' => array(),
	'auctionTitle' => array(),
	'auctionStatus' => array()
) );

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oAuction->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 30
) );

// Data
$aAuctions = $oAuction->readAll();

// Pagination
$sPagination = $oPagination->render();

if( !empty($aAuctions) ) {
    // Table init
    clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oAuction->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'controls' => array(
			'title' => ''
		)
	) );

    foreach( $aAuctions as $aAuction ) {
        $aRow = array(
            'auctionId' => $aAuction['auctionId'],
            'auctionType' => _( ucfirst($aAuction['auctionType']) ),
            'auctionInternalName' => $aAuction['auctionInternalName'],
            'auctionTitle' => $aAuction['auctionTitle'],
			'auctionStatus' => '<span class="' . $aAuction['auctionStatus'] . '">' . _( ucfirst($aAuction['auctionStatus']) ) . '</span>',
            'controls' => '
                <a href="' . $oRouter->getPath( 'adminAuctionItems' ) . '?auctionId=' . $aAuction['auctionId'] . '" class="icon iconText iconList">' . _( 'Item list' ) . '</a>
				&nbsp;|&nbsp;
				<a href="' . $oRouter->getPath( 'adminAuctionBids' ) . '?auctionId=' . $aAuction['auctionId'] . '&partId=' . $aAuction['partId'] . '" class="icon iconText iconList">' . _( 'Budlista' ) . '</a>'
        );
        $oOutputHtmlTable->addBodyEntry( $aRow );
    }

    $sOutput = $oOutputHtmlTable->render();

} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view auction tableEdit">
		<h1>' . _( 'Auctions' ) . '</h1>
        <section class="tools">
			<div class="tool">
				<a href="' . $oRouter->getPath( 'adminAuctionTransfers' ) . '" class="icon iconText iconDbImport">' . _( 'Transfer' ) . '</a>
			</div>
			<div class="tool">
				<a href="' . $oRouter->getPath( 'adminAuctionTransferRouteCheck' ) . '" class="icon iconText iconDbImport">' . _( 'Route path check' ) . '</a>
			</div>
		</section>
		<section>
			' . $sPagination . '
			' . $sOutput . '
			' . $sPagination . '
		</section>
	</div>';
