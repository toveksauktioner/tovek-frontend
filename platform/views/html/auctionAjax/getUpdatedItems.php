<?php

// Goal for this view is to return all data needed for auction lists and items
//
// Gets all items updated and then returns the ones that are new according to
// provided date and time
//
// Data is returned in JSON format
//
// Input is date and time (to return anything altered after that time)
// Optional input is itemId which will only return changes for that item

$iCurrentTime = time();
if( empty($_GET['after']) ) $_GET['after'] = $iCurrentTime - 600;                // Default is changes made one minute ago
$iItemId = ( (!empty($_GET['itemId']) && ctype_digit($_GET['itemId'])) ? $_GET['itemId'] : null );
$aReturnData = [
  'currentTime' => $iCurrentTime,
  'items' => []
];

require_once PATH_FUNCTION . '/fMoney.php';
$oDbFront = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );
$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );

// Get
$sQuery = "
  SELECT historyId, MAX(historyBidValue) AS historyBidValue, historyBidItemId
  FROM entAuctionBidHistory
  WHERE historyCreated >= " . $oDbFront->escapeStr( date('Y-m-d H:i:s', $_GET['after']) ) . "
    " . ( !empty($iItemId) ? "AND historyBidItemId = " . $oDbFront->escapeStr($iItemId) : "" ) . "
  GROUP BY historyBidItemId
  ORDER BY historyBidId DESC
";
$aBidHistory = $oDbFront->query( $sQuery );

if( !empty($aBidHistory) ) {

  $aItemEndTime = arrayToSingle( $oAuctionItem->read([
    'itemId',
    'itemEndTime'
  ], arrayToSingle($aBidHistory, null, 'historyBidItemId')), 'itemId', 'itemEndTime' );

  foreach( $aBidHistory as $aBidData ) {
    $aReturnData['items'][ $aBidData['historyBidItemId'] ] = [
      'highestBid' => $aBidData['historyBidValue'],
      'timeLeft' => ( strtotime($aItemEndTime[ $aBidData['historyBidItemId'] ]) - $iCurrentTime )
    ];
  }

}

echo json_encode( $aReturnData );
echo "Time: " . ( time() - $iCurrentTime ) . 's';
