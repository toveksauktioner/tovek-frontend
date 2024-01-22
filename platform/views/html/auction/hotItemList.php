<?php
return;
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

$oOutputHtmlAuction = clRegistry::get( 'clOutputHtmlAuction' );

// Default sorting
$aSorting = array(
	'itemEndTime' => 'ASC'
);
if( !empty($_GET['sortBy']) && $_GET['sortBy'] == 'ended' ) {
	$aSorting = array(
		'itemEndTime' => 'DESC'
	);
} elseif( !empty($_GET['sortBy']) ) {
	// Sort
	$aSort = array();
	switch( $_GET['sortBy'] ) {
		case 'endTime': default:
			$aSorting = array( 'itemEndTime' => 'ASC' );
			break;
		case 'alphabetically':
			$aSorting = array( 'itemTitle' => 'ASC' );
			break;
	}
}

/**
 * Auction item data
 */
$aReadFields = array(
    'itemId',
    'itemSortNo',
    'itemTitle',
    'itemSummary',
    'itemDescription',
    'itemInformation',
    'itemMinBid',
    'itemBidCount',
    'itemEndTime',
		'itemFeeValue',
		'itemVatValue',
    'itemMarketValue',
    'itemStatus',
    'itemAuctionId',
    'itemPartId',
		'itemAddressId',
		'routePath'
);
if( !empty($_GET['sortBy']) && $_GET['sortBy'] == 'ended' ) {
	$aAuctionItems = $oAuctionEngine->readAuctionItem( array(
		'fields' => $aReadFields,
		'status' => 'ended',
		// 'hot' => 'yes',
		'sorting' => $aSorting,
		'entries' => 12
	) );

} else {
	$aAuctionItems = $oAuctionEngine->readAuctionItem( array(
		'fields' => $aReadFields,
		'status' => 'active',
		// 'hot' => 'yes',
		'sorting' => $aSorting,
		'entries' => 12
	) );

}

if( !empty($_GET) ) {
	/**
	 * Reset end time
	 */
	//if( !empty($_GET['resetEndTime']) && !empty($_GET['time']) ) {
	//	$aAuctionItems = $oAuctionEngine->readAuctionItem( array(
	//		'fields' => array('itemId','auctionId','partId'),
	//		'status' => '*',
	//		//'auctionId' => array(28, 1787, 1824)
	//		'itemId' => arrayToSingle( $aAuctionItems, null, 'itemId' )
	//	) );
	//	if( !empty($aAuctionItems) ) {
	//		foreach( arrayToSingle($aAuctionItems, 'partId', 'partId') as $iPartId ) {
	//			$oAuctionEngine->updateEndTimeByAuctionPart_in_AuctionItem( $iPartId, date( 'Y-m-d H:i:s', time() + $_GET['time'] ), 1 );
	//			$oAuctionEngine->updateStatusByAuctionPart_in_AuctionItem( $iPartId, 'active' );
	//		}
	//
	//		$oNotification->setSessionNotifications( array(
	//			'dataSaved' => sprintf( _( 'Auction starts at: %s' ), date( 'Y-m-d H:i:s', time() + $_GET['time'] ) )
	//		) );
	//	}
	//
	//	$oRouter->redirect( $oRouter->sPath );
	//}

	/**
	 * Import data
	 */
	//if( !empty($_GET['importData']) ) {
	//	$oAuctionTransfer = clRegistry::get( 'clAuctionTransfer', PATH_MODULE . '/auctionTransfer/models' );
	//	$oAuctionTransfer->import();
	//	$oNotification->setSessionNotifications( array(
	//		'dataSaved' => _( 'Data har blivit synkad' )
	//	) );
	//	$oRouter->redirect( $oRouter->sPath );
	//}

	/**
	 * Import data
	 */
	//if( !empty($_GET['cleanData']) ) {
	//	$oAuctionTransfer = clRegistry::get( 'clAuctionTransfer', PATH_MODULE . '/auctionTransfer/models' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionTransfer' );
	//
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuction' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionPart' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionItem' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionAddress' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionItemToItem' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionItemToUser' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionSearch' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionTag' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionTagToItem' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionToUser' );
	//
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionBid' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionBidHistory' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionAutoBid' );
	//
	//	$oNotification->setSessionNotifications( array(
	//		'dataSaved' => _( 'All bud data har rensats bort' )
	//	) );
	//	$oRouter->redirect( $oRouter->sPath );
	//}

	/**
	 * Import data
	 */
	//if( !empty($_GET['cleanBidData']) ) {
	//	$oAuctionTransfer = clRegistry::get( 'clAuctionTransfer', PATH_MODULE . '/auctionTransfer/models' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionBid' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionBidHistory' );
	//	$oAuctionTransfer->oDao->oDb->write( 'TRUNCATE TABLE entAuctionAutoBid' );
	//	$oAuctionTransfer->oDao->oDb->write( 'UPDATE entAuctionItem SET itemBidCount = "0"' );
	//
	//	$oNotification->setSessionNotifications( array(
	//		'dataSaved' => _( 'All bud data har rensats bort' )
	//	) );
	//	$oRouter->redirect( $oRouter->sPath );
	//}
}

$sListTitle = _( 'Kommande rop' );

if( !empty($aAuctionItems) ) {
	/**
	 * Auction data
	 */
	$aAuction = valueToKey( 'auctionId', $oAuctionEngine->readAuction( array( 'auctionId' => arrayToSingle($aAuctionItems, 'itemAuctionId', 'itemAuctionId') ) ) );

} else {

	if( !empty($_GET['sortBy']) && $_GET['sortBy'] == 'ended' ) {
		$sListTitle = _( 'Avslutade rop' );

		$aAuctionItems = $oAuctionEngine->readAuctionItem( array(
			'fields' => $aReadFields,
			'status' => 'ended',
			'entries' => 12,
			'sorting' => $aSorting
		) );
	} else {
		$aAuctionItems = $oAuctionEngine->readAuctionItem( array(
			'fields' => $aReadFields,
			'status' => 'active',
			'entries' => 12,
			'sorting' => $aSorting
		) );
	}

	$aAuction = array();
	if( !empty($aAuctionItems) ) {
		/**
		 * Auction data
		 */
		$aAuction = valueToKey( 'auctionId', $oAuctionEngine->readAuction( array( 'auctionId' => arrayToSingle($aAuctionItems, 'auctionId', 'auctionId') ) ) );
	}

}

/**
 * Assamble item list
 */
$oOutputHtmlAuctionItems = new clOutputHtmlAuction( array(
	'listKey' => 'listItems',
	'viewFile' => 'auction/hotItemList.php',
	'title' => $sListTitle,
	'viewmode' => 'mixed',
	'sortType' => !empty($_GET['sortBy']) ? $_GET['sortBy'] : 'endTime',
	'entries' => '20',
	'entriesSequence' => '20',
	'listAll' => !empty($_GET['listAll']) ? $_GET['listAll'] : false
) );
$oOutputHtmlAuctionItems->addAuctionData( $aAuction );
$oOutputHtmlAuctionItems->addItemData( $aAuctionItems );

$sOutput = $oOutputHtmlAuctionItems->render();

//echo '
//	<div style="background: #f7dada; border: 3px solid #f9f9f9; padding: .9em 1.2em; line-height: 1.5em; font-size: .7em; opacity: .6; margin-bottom: 4em; box-shadow: 0 0 .2em #c82727;">
//		<h2>OBS! Från och med nu fungerar bara "skarpa" användare!</h2>
//		<p>(alltså samma användare / uppgifter som på tovek.se)</p>
//		<hr />
//		<h2>TESTVERKTYG</h2>
//		<hr />
//		<p><a href="?resetEndTime=true&time=30">' . _( 'Reset end time' ) . '</a> <em>(+30sec, +1min/item)</em></p>
//		<p><a href="?resetEndTime=true&time=300">' . _( 'Reset end time' ) . '</a> <em>(+5min, +1min/item)</em></p>
//		<p><a href="?resetEndTime=true&time=3600">' . _( 'Reset end time' ) . '</a> <em>(+60min, +1min/item)</em></p>
//		<p><a href="?resetEndTime=true&time=7200">' . _( 'Reset end time' ) . '</a> <em>(+2tim, +1min/item)</em></p>
//		<p><a href="?importData=true">' . _( 'Importera data' ) . '</a> <em>(manuell uppdatering)</em></p>
//		<p><a href="?cleanBidData=true">' . _( 'Nollställ alla bud' ) . '</a> <em>(Ta bort alla bud)</em></p>
//		<p><a href="?cleanData=true">' . _( 'Rensa data' ) . '</a> <em>(Ta bort allt)</em></p>
//		<p>&nbsp;</p>
//		<p><em>(Ditt sessions ID är: ' . session_id() . ')</em></p>
//		' . (!empty($_SESSION['userId']) ? '
//		<p><em>(Du är inloggad och användar ID är: ' . $_SESSION['userId'] . ')</em></p>
//		' : '') . '
//	</div>';
echo '
		<a name="listTop"></a>
    <div class="view auction itemList hotItemList">
        ' . $sOutput . '
    </div>';
