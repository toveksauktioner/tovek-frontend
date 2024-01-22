<?php

/***
  Collected file for accessing item parts for updating lists and show page
  Item ID and what to fetch must be specified
   * itemID => numerical
   * parts => json encoded array with parts to fetch:
      * userBidStatus => is the user outbidded/leader/winner of this object?
      * bidList => TO DO
      * bidListAll => TO DO
      * highestBid => TO DO
      * currentBid => TO DO
*/

/*** Check´s if the request is made by ajax ***/
if( empty($_GET['ajax']) ) {
	return;
}

require_once PATH_FUNCTION . '/fMoney.php';

$aReturn = [
  'result' => null,
  'html' => [],
  'error' => '',
  // 'indata' => $_GET
];

/*** Item Id must be specified */
if( empty($_GET['itemId']) || !ctype_digit(str_replace(',', '', $_GET['itemId'])) ) {
  $aReturn['result'] = 'failure';
  $aReturn['error'] = 'No valid item ID(s) specified';

} else {
  $aItemIds = explode( ',', $_GET['itemId'] );
}

if( empty($aReturn['error']) ) {
  $oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

  // Fetch the item data
  $aItemData = valueToKey( 'itemId', $oAuctionEngine->readAuctionItem([
    'itemId' => $aItemIds,
    'status' => '*',
    'fields' => [
      'itemId',
      'itemEndTime',
      'itemBidCount',
      'itemMinBid'
    ]
  ]) );

  // Fetch bid data
  $aItemBids = $oAuctionEngine->readHistory_in_AuctionBid( [
   'itemId' => $aItemIds
  ] );

  // Fetch bidder data
  $aBidderUserIds = arrayToSingle( $aItemBids, null, 'historyBidUserId' );
  if( !empty($aBidderUserIds) ) {
    $aBidders = arrayToSingle( $oUser->oDao->read([
      'userId' => $aBidderUserIds,
      'fields' => [ 'userId', 'username' ]
    ]), 'userId', 'username' );
  }

  $aItemBids = groupByValue( 'historyBidItemId', $aItemBids );

  $aBidHistoryList = [];
  foreach( $aItemData as $iItemId => &$aItem ) {
    $aItem['timestamp'] = strtotime( $aItem['itemEndTime'] );
    $aItem['bidHistoryHtml'] = '
      <ul>
        <li>
          <div>&nbsp;</div>
          <div class="user">' . _( 'Inga bud' ) . '</div>
          <div class="value">&nbsp;</div>
          <div class="time">&nbsp;</div>
        </li>
      </ul>';

    if( !empty($aItemBids[ $iItemId ]) ) {
      // $aItem['bidData'] = $aItemBids[ $iItemId ];

      $bFirst = true;
      $aBidHistoryList = [];
      $sPreviousTime = 0;
      foreach( $aItemBids[ $iItemId ] as $aBidData ) {
        $iUserId = $aBidData['historyBidUserId'];
        $sUsername = ( !empty($aBidders[$iUserId]) ? $aBidders[$iUserId] : '' );
        $aClass = array();

        if( $bFirst ) {
          $aItem['currentBid'] = calculatePrice( $aBidData['historyBidValue'], array('profile' => 'human') );
          $aItem['currentBidUser'] = $sUsername;
          $aItem['currentBidUserId'] = $iUserId;
          $aClass[] = 'first';
          if( $aItem['timestamp'] < time() )  $aClass[] = 'bidEnded';
          $bFirst = false;
        }

        // Bid history HTML
        if( strpos($aBidData['historyBidPlaced'], '-') == false ) {
          $iCreated = substr( $aBidData['historyBidPlaced'], 0, strrpos( $aBidData['historyBidPlaced'], '.') );
          $sDate = date( 'Y-m-d', $iCreated );
          $sTime = date( 'H:i:s', $iCreated );
        } else {
          $sDate = date( 'Y-m-d', strtotime($aBidData['historyBidPlaced']) );
          $sTime = date( 'H:i:s', strtotime($aBidData['historyBidPlaced']) );
        }

        // Bid class
        if( !empty($aBidData['historyBidType']) ) $aClass[] = $aBidData['historyBidType'];
        if( $sPreviousTime == $aBidData['historyCreated'] ) $aClass[] = 'autoCreated';

        // Bid history HTML
        $aBidHistoryList[] = '
          <li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '>
            <div class="label">&nbsp;</div>
            <div class="user">' . $sUsername . '</div>
            <div class="value">' . calculatePrice( $aBidData['historyBidValue'], array('profile' => 'human') ) . '</div>
            <div class="time"><datetime><date>' . $sDate . '</date><time>' . $sTime . '</time></datetime></div>
          </li>';

          $sPreviousTime = $aBidData['historyCreated'];
      } 

      if( !empty($aBidHistoryList) ) {
        $aItem['bidHistoryHtml'] = '<ul>' . implode( '', $aBidHistoryList ) . '</ul>';
      }

    } else {
      $aItem['itemMinBid'] = calculatePrice( $aItem['itemMinBid'], array('profile' => 'human') );
    }

  }

  if( !empty($aItemData) ) {
    $aReturn['data'] = $aItemData;
    $aReturn['result'] = 'success';
  }

} else {
  $aReturn['result'] = 'failure';
  $aReturn['error'] = 'No item found with ID:' . $_GET['itemId'];
}

echo json_encode( $aReturn );
