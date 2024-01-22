<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );

$oOutputHtmlAuction = clRegistry::get( 'clOutputHtmlAuction' );

/**
 * Read auction data
 */
$aReadFields = array(
	'auctionId',
	'auctionTitle',
	'auctionShortTitle',
	'auctionLocation',
	'partLocation',
	'partId',
	'partTitle',
	'partAuctionStart',
	'partStatus'
);
$aRouteRelation = current( $oRouter->readObjectByRoute() );
if( !empty($aRouteRelation) ) {
	// Read running auctions
	$aAuction = current( $oAuctionEngine->readAuction( array(
		'fields' => $aReadFields,
		'partId' => $aRouteRelation['objectId']
	) ) );
} elseif( !empty($_GET['partId']) ) {
	// Read running auctions
	$aAuction = current( $oAuctionEngine->readAuction( array(
		'fields' => $aReadFields,
		'partId' => $_GET['partId']
	) ) );
}

if( !empty($aAuction) ) {
	// Increase viewed count
	$oAuctionEngine->increaseViewedCount( 'Auction', $aAuction['auctionId'] );

	// Show ended?
	if( isset($_GET['showEnded']) ) {
		if( empty($_GET['showEnded']) ) {
			$bForceFirstActivePage = true;
		}
		$_SESSION['browser']['auction'][ $aAuction['partId'] ]['showEnded'] = ( !empty($_GET['showEnded']) ? true : false );
	}

	// Default sorting
	// if( !empty($_GET['sortBy']) ) {
	// 	if( empty($_SESSION['browser']['auction'][ $aAuction['partId'] ]['sortBy']) || ($_GET['sortBy'] != $_SESSION['browser']['auction'][ $aAuction['partId'] ]['sortBy']) ) {
	// 		$_GET['page'] = 1;
	// 	}
	// 	$_SESSION['browser']['auction'][ $aAuction['partId'] ]['sortBy'] = $_GET['sortBy'];

	// } else if( empty($_SESSION['browser']['auction'][ $aAuction['partId'] ]['sortBy']) ) {
	// 	$_SESSION['browser']['auction'][ $aAuction['partId'] ]['sortBy'] = 'no';
	// }

	// Sort and pagination type
	$sPaginationType = 'auction';
	// switch( $_SESSION['browser']['auction'][ $aAuction['partId'] ]['sortBy'] ) {
	// 	case 'title':
	// 		$aSorting = array( 'itemTitle' => 'ASC' );
	// 		break;
	// 	case 'highestBid':
	// 		$aSorting = array( 'itemWinningBidValue' => 'DESC' );
	// 		break;
	// 	case 'lowestBid':
	// 		$aSorting = array( 'itemWinningBidValue' => 'ASC' );
	// 		break;
	// 	case 'no':
	// 	default:
	// 		$aSorting = array( 'itemSortNo' => 'ASC' );
	// 		$sPaginationType = 'auction';
	// }

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
		'itemWinningBidValue',
		'itemEndTime',
		'itemMarketValue',
		'itemVatValue',
		'itemFeeValue',
		'itemStatus',
		'itemAuctionId',
		'itemPartId',
		'itemAddressId',
		// 'routePath'
	);

	// First count total
	$aAuctionItemTotal = arrayToSingle( $oAuctionEngine->readAuctionItem(array(
		'fields' => array(
			'itemSortNo',
			'itemEndTime'
		),
		'status' => array( 'active', 'ended' ),
		'auctionId' => $aAuction['auctionId'],
		'partId' => $aAuction['partId'],
		// 'sorting' => $aSorting
	)), 'itemSortNo', 'itemEndTime' );

	$iAuctionItemTotal = count( $aAuctionItemTotal );
	$iAuctionItemStartNo = key( $aAuctionItemTotal );
	$iAuctionItemEndNo = $iAuctionItemStartNo + $iAuctionItemTotal;
	if( !empty($aAuctionItemTotal) ) {
		$iCurrentTime = time();

		foreach( $aAuctionItemTotal as $iItemSortNo => $sItemEndTime ) {
			if( strtotime($sItemEndTime) > $iCurrentTime ) {
				$iAuctionItemFirstActiveSortNo = $iItemSortNo;
				break;
			}
		}
	}

	// Get a specific item no
	if( !empty($_GET['itemSortNo']) && ctype_digit($_GET['itemSortNo']) ) {
		$iItemSortNo = (int) $_GET['itemSortNo'];
		$oAuctionItemDao->setCriterias( array(
			'itemSortNo' => array(
				'type' => 'in',
				'fields' => 'itemSortNo',
				'value' => $iItemSortNo
			)
		) );

		$_SESSION['browser']['auction'][ $aAuction['partId'] ]['showEnded'] = true;
		$_SESSION['browser']['auction'][ $aAuction['partId'] ]['page'] = ceil( (($iItemSortNo - $iAuctionItemStartNo + 1) / AUCTION_ITEM_PAGINATION) );
	}

	// Pagination
	if( !empty($_GET['page']) ) {
		$iPage = $_GET['page'];
	} else if( !empty($_SESSION['browser']['auction'][ $aAuction['partId'] ]['page']) ){
		$iPage = $_SESSION['browser']['auction'][ $aAuction['partId'] ]['page'];
	}


	// Pagination type - different methods for limiting readout
	// AUCTION - reads predefined pages based on itemSortNo
	// NORMAL - normal pagination with pages based on the current filter
	if( $sPaginationType == 'auction' ) {
		// Auction reads predefined pages or sections
		if( !empty($iAuctionItemFirstActiveSortNo) ) {
			$iPages = ceil( $iAuctionItemTotal / AUCTION_ITEM_PAGINATION );
			for( $iCurrentPage=1; $iCurrentPage<=$iPages; $iCurrentPage++ ) {
				if( $iAuctionItemFirstActiveSortNo < ($iAuctionItemStartNo + ($iCurrentPage * AUCTION_ITEM_PAGINATION)) ) {
					$iFirstActivePage = $iCurrentPage;
					break;
				}
			}
		}

		// If no page is selected show the first section with active items
		if( empty($iPage) ) {
			if( !empty($iFirstActivePage) ) {
				$iPage = $iFirstActivePage;
			} else {
				$iPage = 1;
			}
		} else if( !empty($bForceFirstActivePage) && !empty($iFirstActivePage) && ($iPage < $iFirstActivePage) ) {
			$iPage = $iFirstActivePage;
		} else {
			if( $iPage > $iPages ) $iPage = $iPages;
		}

		// Show ended on pages not active
		if( !empty($iFirstActivePage) && ($iPage < $iFirstActivePage) ) {
			$_SESSION['browser']['auction'][ $aAuction['partId'] ]['showEnded'] = true;
		}

		// If all items are ended - showEnded is forced
		if( empty($iFirstActivePage) ) {
			$_SESSION['browser']['auction'][ $aAuction['partId'] ]['showEnded'] = true;
		}

		$_SESSION['browser']['auction'][ $aAuction['partId'] ]['page'] = $iPage;

		// Set criteria for getting current section (page)
		$iSectionStart = (($iPage - 1) * AUCTION_ITEM_PAGINATION) + $iAuctionItemStartNo;
		$iSectionEnd = $iSectionStart + AUCTION_ITEM_PAGINATION - 1;
		$oAuctionItemDao->setCriterias( array(
			'page' => array(
				'type' => 'between',
				'value' => $iSectionStart,
				'value2' => $iSectionEnd,
				'fields' => 'itemSortNo'
			)
		) );

	} else {
		// "Normal" readout with pagination based on the list
		$iOffset = ($iPage - 1) * AUCTION_ITEM_PAGINATION;
		$oAuctionItemDao->setEntries( AUCTION_ITEM_PAGINATION, $iOffset );
	}

	$aStatuses = array( 'active', 'ended' );
	// if( !empty($_SESSION['browser']['auction'][ $aAuction['partId'] ]['showEnded']) ) $aStatuses[] = 'ended';

	// Read this pagination page
	$aAuctionItems = valueToKey( 'itemId', $oAuctionEngine->readAuctionItem( array(
		'fields' => $aReadFields,
		'status' => $aStatuses,
		'auctionId' => $aAuction['auctionId'],
		'partId' => $aAuction['partId'],
		// 'sorting' => $aSorting
	) ) );

	if( !empty($aAuctionItems) ) {
		$aRoutes = arrayToSingle( $oRouter->readByObject(arrayToSingle($aAuctionItems, null, 'itemId'), 'AuctionItem', [
			'objectId',
			'routePath'
		], 1), 'objectId', 'routePath' );

		foreach( $aAuctionItems as $iItemId => $aItemData ) {
			if( !empty($aRoutes[$iItemId]) ) $aAuctionItems[ $iItemId ]['routePath'] = $aRoutes[$iItemId];
		}
	}
}

// Reset criterias
$oAuctionItemDao->setCriterias();

if( !empty($aAuction) ) {
	/**
	 * Assamble item list
	 */
	$oOutputHtmlAuctionItems = new clOutputHtmlAuction( array(
		'listKey' => 'listItems',
		'viewFile' => 'auction/itemList.php',
		'title' => _( 'Item list' ),
		// 'sortType' => ( !empty($_SESSION['browser']['auction'][ $aAuction['partId'] ]['sortBy']) ? $_SESSION['browser']['auction'][ $aAuction['partId'] ]['sortBy'] : 'no' ),
		'entriesSequence' => !empty($iPage) ? $iPage : 1,
		'entriesTotal' => $iAuctionItemTotal,
		'paginationType' => ( !empty($sPaginationType) ? $sPaginationType : 'normal' ),
		'paginationStartNo' => $iAuctionItemStartNo,
		'paginationFistActivePage' => ( !empty($iFirstActivePage) ? $iFirstActivePage : false ),
		'listAll' => !empty($_GET['listAll']) ? $_GET['listAll'] : false,
		'searchForm' => array( 'itemSortNo' ),
		'showEnded' => false //( !empty($_SESSION['browser']['auction'][ $aAuction['partId'] ]['showEnded']) ? true : false )
	) );
	$oOutputHtmlAuctionItems->addAuctionData( $aAuction );

		if( !empty($aAuctionItems) ) {
			$oOutputHtmlAuctionItems->addItemData( $aAuctionItems );
		}

	$sOutput = $oOutputHtmlAuctionItems->render();
} else {
	$sOutput = '<p><span>Ingen auktion</span></p>';
}

echo '
    <div class="view auction itemList" data-part-id="' . $aAuction['partId'] . '">
			<a name="listTop"></a>
			' . $sOutput . '
    </div>';
