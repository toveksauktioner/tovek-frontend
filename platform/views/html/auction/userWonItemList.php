<?php

require_once PATH_FUNCTION . '/fOutputHtml.php';

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );
$aDataDict = current( $oAuctionItemDao->getDataDict() );

$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );

// Clear sort
$oAuctionItemDao->aSorting = array();

$aItemData = array();
$aAuctionData = array();

clFactory::loadClassFile( 'clOutputHtmlAuction' );
$oOutputHtmlAuctionItems = new clOutputHtmlAuction( array(
	'listKey' => 'wonItems',
	'viewFile' => 'auction/userWonItemList.php',
	'title' => _( 'Won bids' ),
	'viewmode' => 'mixed',
	'sortType' => 'itemEndTime',
	'entries' => '10',
	'entriesSequence' => !empty($_GET['entriesSequence']) ? $_GET['entriesSequence'] : null,
	'listAll' => !empty($_GET['listAll']) ? $_GET['listAll'] : false,
	'nextAuctionButton' => false,
	'showEnded' => true
) );

if( !empty($_GET['sortBy']) && $_GET['sortBy'] == 'ended' ) {
	$oAuctionItemDao->aSorting = array(
		'itemEndTime' => 'DESC'
	);
} elseif( !empty($_GET['sortBy']) ) {
	// Sort
	$aSort = array();
	switch( $_GET['sortBy'] ) {
		case 'itemNo': default:
			$aSort['itemSortNo'] = 'ASC';
			break;
		case 'endTime':
			$aSort['itemEndTime'] = 'DESC';
			break;
		case 'alphabetically':
			$aSort['itemTitle'] = 'ASC';
			break;
	}
	$oAuctionItemDao->aSorting = $aSort;
} else {
	// Default sorting
	$oAuctionItemDao->aSorting = array(
		'itemEndTime' => 'DESC'
	);
}

// Frontend items
$aItemData = valueToKey( 'itemId', $oAuctionEngine->readAuctionItem(array(
	'winningUserId' => $_SESSION['userId'],
	'status' => 'ended',
)) );

// Backend items merged to front items
// $oBackEnd->oDao->sCriterias = null;
// $oBackEnd->setSource( 'entAuctionItem', 'itemId' );
// $oBackEnd->oDao->aSorting = array( 'itemEndTime' => 'DESC' );
// $oBackEnd->oDao->setCriterias( array(
// 	'winningUserId' => array(
// 		'fields' => 'itemWinningUserId',
// 		'value' => $_SESSION['userId']
// 	)
// ) );
// $aItemData += valueToKey( 'itemId', $oBackEnd->read('*') );
$oBackEnd->oDao->sCriterias = null;
$oBackEnd->oDao->aSorting = array();

// Reset
$oAuctionItemDao->sCriterias = null;
$oAuctionItemDao->aSorting = null;


// Auction data
if( !empty($aItemData) ) {
	foreach( $aItemData as $iKey => $aItem ) {
		$aItemData[ $iKey ]['routePath'] = '#';
	}

	// Auction data
	$aAuctionData = valueToKey( 'auctionId', $oBackEnd->readAuction( '*', arrayToSingle($aItemData, null, 'itemAuctionId') ) );
	foreach( $aAuctionData as $iAuctionId => $aAuction ) {
		$aPartData = current( $oBackEnd->readAuctionPart( '*', null, $iAuctionId ) );
		if( !empty($aPartData) ) $aAuctionData[ $iAuctionId ] += $aPartData;
	}
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
		<div class="view auction itemList userWonItemList">
			<h2>' . _( 'Vunna rop' ) . '</h2>
			', $oOutputHtmlAuctionItems->render(), '
		</div>';
}
