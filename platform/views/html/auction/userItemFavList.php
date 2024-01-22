<?php 

require_once PATH_FUNCTION . '/fOutputHtml.php';

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );
$aDataDict = current( $oAuctionItemDao->getDataDict() );

// Clear sort
$oAuctionItemDao->aSorting = array();

$aItemData = array();
$aAuctionData = array();

// The init of the clOutputHtmlAuction has to be done before the check of favorite items because it is done in that init
clFactory::loadClassFile( 'clOutputHtmlAuction' );
$oOutputHtmlAuctionItems = new clOutputHtmlAuction( array(
	'listKey' => 'favItems',
	'viewFile' => 'auction/userItemFavList.php',
	'title' => _( 'Favorites' ),
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

if( !empty($oAuctionEngine->aUserFavoriteItems) ) {
	// Item data
	$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );
	$oAuctionItemDao->aSorting = array( 'itemEndTime' => 'ASC' );
	$aItemData = $oAuctionEngine->readAuctionItem( array(
		'fields' => '*',
		'itemId' => $oAuctionEngine->aUserFavoriteItems
	) );

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
			'auctionStatus' => '*',
			'partStatus' => '*'
		) ) );
	}
}

$oOutputHtmlAuctionItems->addAuctionData( $aAuctionData );
$oOutputHtmlAuctionItems->addItemData( $aItemData );

/*** Check´s if the request is made by ajax ***/
if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
	// Ajax
	echo $oOutputHtmlAuctionItems->renderListContent();
} else {
	$sOutput = $oOutputHtmlAuctionItems->render();
}

// Normal
echo '
	<div class="view auction itemList userItemFavList">
		<h2>' . _( 'Favoriter' ) . '</h2>
		' . $oOutputHtmlAuctionItems->render() . '
	</div>';
