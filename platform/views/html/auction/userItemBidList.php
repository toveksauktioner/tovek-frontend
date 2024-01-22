<?php 

require_once PATH_FUNCTION . '/fOutputHtml.php';

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );
$aDataDict = current( $oAuctionItemDao->getDataDict() );

// Clear sort
$oAuctionItemDao->aSorting = array();

$aItemData = array();
$aAuctionData = array();

// Item by user bid items
$aReadFields = array(
	'itemId'
);
$aUserBidData = $oAuctionEngine->readBidByUser( $_SESSION['userId'], $aReadFields );
$aUserBidItemIds = arrayToSingle( $aUserBidData, null, 'historyBidItemId' );

clFactory::loadClassFile( 'clOutputHtmlAuction' );
$oOutputHtmlAuctionItems = new clOutputHtmlAuction( array(
	'listKey' => 'userBidItems',
	'viewFile' => 'auction/userItemBidList.php',
	'title' => _( 'Bud & Subscriptions' ),
	'viewmode' => 'mixed',
	'sortType' => 'itemEndTime',
	'entries' => '5',
	'entriesSequence' => !empty($_GET['entriesSequence']) ? $_GET['entriesSequence'] : null,
	'listAll' => !empty($_GET['listAll']) ? $_GET['listAll'] : false,
	'nextAuctionButton' => false
) );

if( !empty($_GET['sortBy']) && $_GET['sortBy'] == 'ended' ) {
	$oAuctionItemDao->aSorting = array(
		'itemSortNo' => 'ASC'
	);
} elseif( !empty($_GET['sortBy']) ) {
	// Sort
	$aSort = array();
	switch( $_GET['sortBy'] ) {
		case 'itemNo': default:
			$aSort['itemSortNo'] = 'ASC';
			break;
		case 'endTime':
			$aSort['itemEndTime'] = 'ASC';
			break;
		case 'alphabetically':
			$aSort['itemTitle'] = 'ASC';
			break;
	}
	$oAuctionItemDao->aSorting = $aSort;
} else {
	// Default sorting
	$oAuctionItemDao->aSorting = array(
		'itemEndTime' => 'ASC'
	);
}

if( !empty($aUserBidItemIds) ) {
	// Item data
	if( !empty($_GET['sortBy']) && $_GET['sortBy'] == 'ended' ) {
		// Data
		$aItemData = $oAuctionEngine->readAuctionItem( array(
			'fields' => '*',
			'itemId' => $aUserBidItemIds,
			'status' => 'ended'
		) );

	} else {
		// Data
		$aItemData = $oAuctionEngine->readAuctionItem( array(
			'fields' => '*',
			'itemId' => $aUserBidItemIds
		) );
	}
}

// Reset
$oAuctionItemDao->sCriterias = null;
$oAuctionItemDao->aSorting = null;

if( !empty($aItemData) ) {
	$aReadFields = array(
		'auctionId',
		'auctionType',
		'auctionTitle',
		'auctionSummary',
		'auctionDescription',
		'auctionLocation',
		'auctionArchiveStatus',
		'auctionCreated',
		'partId',
		'partTitle',
		'partDescription',
		'partPreBidding',
		'partAuctionStart',
		'partStatus',
		'partHaltedTime',
		'partCreated',
		'partAuctionId'
	);
	// Auction data
	$aAuctionData = valueToKey( 'auctionId', $oAuctionEngine->readAuction( array(
		'fields' => $aReadFields,
		'auctionId' => arrayToSingle($aItemData, null, 'itemAuctionId'),
		'auctionStatus' => 'active',
		'partStatus' => '*'
	) ) );
}

$oOutputHtmlAuctionItems->addAuctionData( $aAuctionData );
$oOutputHtmlAuctionItems->addItemData( $aItemData );

/*** Check´s if the request is made by ajax ***/
if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
	// Ajax
	echo $oOutputHtmlAuctionItems->renderListContent();
} else {
	// Normal
	echo '
		<div class="view auction itemList userItemBidList">
			<h2>' . _( 'Bud' ) . '</h2>
			', $oOutputHtmlAuctionItems->render(), '
		</div>';
}
