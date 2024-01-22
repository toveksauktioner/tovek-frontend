<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );

$oOutputHtmlAuction = clRegistry::get( 'clOutputHtmlAuction' );

$aSorting = array( 'itemEndTime' => 'ASC' );

// Filter
$bShowEndedChanged = false;
$bShowWonChanged = false;
if( isset($_GET['showEnded']) ) {
  if( empty($_GET['showEnded']) ) {
    $bForceFirstActivePage = true;
  }

  $bShowEnded = ( !empty($_GET['showEnded']) ? true : false );

  // Determine if the "show ended" checkbox is checked
  if( isset($_SESSION['browser']['auction']['userItemsList']['showEnded']) && ($_SESSION['browser']['auction']['userItemsList']['showEnded'] != $bShowEnded) ) {
    $bShowEndedChanged = true;
  }

  $_SESSION['browser']['auction']['userItemsList']['showEnded'] = $bShowEnded;
}
if( isset($_GET['show']) ) {
  if( !empty($_GET['show']) ) {
    if( !is_array($_GET['show']) ) $_GET['show'] = explode( ',', str_replace(' ', '', $_GET['show']) );
    $aShow = $_GET['show'];

  } else {
    $aShow = [];
  }

  // Determine if the "won" checkbox is changed
  if( in_array('won', $_SESSION['browser']['auction']['userItemsList']['show']) != in_array('won', $aShow) ) {
    $bShowWonChanged = true;
  }

  $_SESSION['browser']['auction']['userItemsList']['show'] = $aShow;
}
if( $bShowEndedChanged ) {
  // Won items cannot be shown if ended is not shown
  if( $_SESSION['browser']['auction']['userItemsList']['showEnded'] === false ) {
    if( in_array('won', $_SESSION['browser']['auction']['userItemsList']['show']) ) {
      foreach( $_SESSION['browser']['auction']['userItemsList']['show'] as $key => $value ) {
        if( $value == 'won' ) unset( $_SESSION['browser']['auction']['userItemsList']['show'][ $key ] );
      }
    }
  }
}
if( $bShowWonChanged ) {
  // Force "show ended" to show won
  if( in_array('won', $_SESSION['browser']['auction']['userItemsList']['show']) ) {
    $_SESSION['browser']['auction']['userItemsList']['showEnded'] = true;
  }
}
if( !isset($_SESSION['browser']['auction']['userItemsList']['show']) ) {
  // Default filter
  $_SESSION['browser']['auction']['userItemsList']['show'] = array_keys( AUCTION_USER_BID_STATUS );
}

// Standard listning parameters
$aStandardBrowserParams = [
  'showEnded' => true,
  'show' => array_keys( AUCTION_USER_BID_STATUS )
];
if( empty($_SESSION['browser']['auction']['userItemsList']) ) {
  $_SESSION['browser']['auction']['userItemsList'] = $aStandardBrowserParams;
} else {
  $_SESSION['browser']['auction']['userItemsList'] += $aStandardBrowserParams;
}

// Sort items in tag structure
$aTagItems = [];
$aTagItems['bids'] = arrayToSingle( $oAuctionEngine->readBidByUser($_SESSION['userId']), 'historyBidItemId', 'historyBidItemId' );
$aTagItems['favorites'] = ( !empty($oAuctionEngine->aUserFavoriteItems) ? $oAuctionEngine->aUserFavoriteItems : [] );
$aTagItems['won'] = arrayToSingle( $oAuctionEngine->readAuctionItem(array(
  'fields' => 'itemId',
  'winningUserId' => $_SESSION['userId'],
  'status' => array( 'active', 'ended' )
)), 'itemId', 'itemId' );

// Asseble total items to show based on selected prefs
$aItemIds = [];
if( !empty($_SESSION['browser']['auction']['userItemsList']['show']) ) {
  foreach( $_SESSION['browser']['auction']['userItemsList']['show'] as $sShow ) {
    $aItemIds += $aTagItems[ $sShow ];
  }
}

if( !empty($aItemIds) ) {
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
		'routePath'
	);

	// First count total
	$aAuctionItemTotal = arrayToSingle( $oAuctionEngine->readAuctionItem(array(
		'fields' => array(
			'itemSortNo',
			'itemEndTime'
		),
    'itemId' => $aItemIds,
		'status' => array( 'active', 'ended' ),
		'sorting' => $aSorting,
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

		$_SESSION['browser']['auction']['userItemsList']['showEnded'] = true;
		$_SESSION['browser']['auction']['userItemsList']['page'] = ceil( (($iItemSortNo - $iAuctionItemStartNo) / AUCTION_ITEM_PAGINATION) );
	}

	// Pagination
	if( !empty($_GET['page']) ) {
		$iPage = $_GET['page'];
	} else if( !empty($_SESSION['browser']['auction']['userItemsList']['page']) ){
		$iPage = $_SESSION['browser']['auction']['userItemsList']['page'];
	} else {
    $iPage = 1;
  }

	// "Normal" readout with pagination based on the list
	$iOffset = ($iPage - 1) * AUCTION_ITEM_PAGINATION;
	$oAuctionItemDao->setEntries( AUCTION_ITEM_PAGINATION, $iOffset );

	$aStatuses = array( 'active', 'ended' );
	// if( !empty($_SESSION['browser']['auction']['userItemsList']['showEnded']) ) $aStatuses[] = 'ended';
	// Read this pagination page
	$aAuctionItems = valueToKey( 'itemId', $oAuctionEngine->readAuctionItem( array(
		'fields' => $aReadFields,
    'itemId' => $aItemIds,
		'status' => $aStatuses,
		'sorting' => $aSorting
	) ) );
}

// Reset criterias
$oAuctionItemDao->setCriterias();

// Create filter tools
$aFilterTools = [
  'show' => [
    'title' => '<i class="fas fa-filter"></i>',
    'options' => []
  ]
];
foreach( AUCTION_USER_BID_STATUS as $key => $value ) {
  $aFilterTools['show']['options'][] = '
    <input type="checkbox" name="show" id="show-' . $key . '" value="' . $key . '"' . ( in_array($key, $_SESSION['browser']['auction']['userItemsList']['show']) ? ' checked="checked"' : '' ) . '><label for="show-' . $key . '">' . $value . '</label>';
}

// Create tag structure
$aTags = [
  'values' => [
    'outbidded' => '<i class="far fa-frown"></i> ' . _( 'Överbjuden' ),
    'outbiddedEnded' => '<i class="far fa-frown"></i> ' . _( 'Överbjuden' ),
    'leader' => '<i class="far fa-smile"></i> ' . _( 'Högsta bud' ),
    'winner' => '<i class="far fa-smile-beam"></i> ' . _( 'Vinnare' )
  ],
  'data' => [
    'outbidded' => [],
    'leader' => [],
    'winner' => $aTagItems['won']
  ]
];

// // Get highest bid for items bidded on
// $aHighestBidUser = [];
// if( !empty($aTagItems['bids']) ) {
//   $aBiddedItemsBid = $oAuctionEngine->readHistory_in_AuctionBid( [
//    'itemId' => $aTagItems['bids'],
//    'fields' => [
//      'historyBidItemId',
//      'historyBidUserId'
//    ]
//   ] );
//   foreach( $aBiddedItemsBid as $aBid ) {
//     if( !isset($aHighestBidUser[ $aBid['historyBidItemId'] ]) ) {
//       $aHighestBidUser[ $aBid['historyBidItemId'] ] = $aBid['historyBidUserId'];
//     }
//   }
// }
//
// // Get current winner from bids and determine if winner or loser
// foreach( $aTagItems['bids'] as $aBidItemId ) {
//   $bEnded = ( !empty($aAuctionItems[ $aBidItemId ]) && (strtotime($aAuctionItems[ $aBidItemId ]['itemStatus']) == 'ended') );
//
//   if( in_array($aBidItemId, $aTagItems['won']) ) {
//     // Do nothing
//
//   } else if( !$bEnded && !empty($aHighestBidUser[ $aBidItemId ]) && ($aHighestBidUser[ $aBidItemId ] == $_SESSION['userId']) ) {
//     $aTags['data']['leader'][] = $aBidItemId;
//
//   } else {
//     if( $bEnded ) {
//       $aTags['data']['outbiddedEnded'][] = $aBidItemId;
//     } else {
//       $aTags['data']['outbidded'][] = $aBidItemId;
//     }
//   }
// }

/**
 * Assamble item list
 */
$oOutputHtmlAuctionItems = new clOutputHtmlAuction( array(
	'listKey' => 'userItemsList',
	'viewFile' => 'auction/userItemsList.php',
	'title' => _( 'Bud & Favoriter' ),
	'sortType' => ( !empty($_SESSION['browser']['auction']['userItemsList']['sortBy']) ? $_SESSION['browser']['auction']['userItemsList']['sortBy'] : 'no' ),
	'entriesSequence' => !empty($iPage) ? $iPage : 1,
	'entriesTotal' => ( !empty($iAuctionItemTotal) ? $iAuctionItemTotal : 0 ),
  'additionalFilterTools' => $aFilterTools,
  'tags' => $aTags,
	'paginationType' => 'normal',
	'paginationFistActivePage' => ( !empty($iFirstActivePage) ? $iFirstActivePage : false ),
	'showEnded' => true //( !empty($_SESSION['browser']['auction']['userItemsList']['showEnded']) ? true : false )
) );

if( !empty($aAuctionItems) ) $oOutputHtmlAuctionItems->addItemData( $aAuctionItems );
$sOutput = $oOutputHtmlAuctionItems->render();

echo '
    <div class="view auction itemList userItemsList">
			<a name="listTop"></a>
      ' . $sOutput . '
    </div>';
