<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

if( !empty($GLOBALS['viewParams']['auction']['bidListAll.php']['item']) ) {
    $aItem = $GLOBALS['viewParams']['auction']['bidListAll.php']['item'];

} elseif( !empty($_GET['itemId']) ) {
    $aItem = current( $oAuctionEngine->readAuctionItem( array(
        'itemId' => $_GET['itemId'],
        'status' => '*',
        'fields' => '*'
    ) ) );

    /**
     * Old item?
     */
    $bOldItem = false;
    if( empty($aItem) ) {
        $oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
        $oBackEnd->setSource( 'entAuctionItem', 'itemId' );
        $aItem = current( $oBackEnd->read( '*', $_GET['itemId'] ) );
        $oBackEnd->oDao->sCriterias = null;
        $aItem['auctionType'] = 'net';

        $bOldItem = true;
    }

} else {
    return;

}

// Data
$aBidHistory = $oAuctionEngine->readItemBidHistory( $aItem['itemId'] );

/**
 * Old bid data?
 */
// if( empty($aBidHistory) ) {
//     $oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
//     $oBackEnd->setSource( 'entAuctionBid', 'bidId' );
//     $oBackEnd->oDao->aSorting = array( 'bidValue' => 'DESC' );
//     $oBackEnd->oDao->setCriterias( array(
//         'bidItemId' => array(
//             'fields' => 'bidItemId',
//             'value' => $aItem['itemId']
//         ),
//         'bidStatus' => array(
//             'fields' => 'bidStatus',
//             'value' => 'successful'
//         )
//     ) );
//     $aBidData = $oBackEnd->read( '*' );
//     $oBackEnd->oDao->sCriterias = null;
//     $oBackEnd->oDao->aSorting = array();
//
//     $aBidHistory = array();
//     foreach( $aBidData as $aBid ) {
//         $aBidHistory[] = array(
//             'historyBidValue' => $aBid['bidValue'],
//             'historyBidUserId' => $aBid['bidUserId'],
//             'historyBidPlaced' => $aBid['bidCreated'],
//             'historyBidType' => $aBid['bidType']
//         );
//     }
// }

if( !empty($aBidHistory) ) {
    $aList = array();

    $iFirstKey = key( $aBidHistory );
    $iEndTime = strtotime( $aItem['itemEndTime'] );

    $aPrevBid = null;
    foreach( $aBidHistory as $iKey => $aBid ) {

        $aUsername = current( $oUser->oDao->read( array('userId' => $aBid['historyBidUserId'], 'fields' => 'username') ) );
        if( empty($aUsername['username']) ) $aUsername['username'] = 'Från skarpa!';

        // Rewrite date
        if( strpos($aBid['historyBidPlaced'], '-') == false ) {
            $iCreated = substr( $aBid['historyBidPlaced'], 0, strrpos( $aBid['historyBidPlaced'], '.') );
            $sDate = date( 'Y-m-d', $iCreated );
            $sTime = date( 'H:i:s', $iCreated );
        } else {
            $sDate = date( 'Y-m-d', strtotime($aBid['historyBidPlaced']) );
            $sTime = date( 'H:i:s', strtotime($aBid['historyBidPlaced']) );
        }

        $aClass = array();
        if( ($iKey == $iFirstKey) && ($iEndTime >= time()) ) $aClass[] = 'winner';
        // if( ($iKey == $iFirstKey) && ($iEndTime < time()) ) $aClass[] = 'ended';
        if( !empty($aBid['historyBidType']) ) $aClass[] = $aBid['historyBidType'];

        if( !empty($aPrevBid) && ($aPrevBid['historyCreated'] == $aBid['historyCreated']) ) $aClass[] = "autoCreated";

        $aList[] = '
            <li' . (!empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' ) . '>
              <div class="label">&nbsp;</div>
              <div class="user">' . $aUsername['username'] . '</div>
              <div class="value">' . calculatePrice( $aBid['historyBidValue'] ) . ' kr</div>
              <div class="time"><datetime><date>' . $sDate . '</date><time>' . $sTime . '</time></datetime></div>
            </li>';

        $aPrevBid = $aBid;
    }

    $sOutput = '<ul>' . implode( '', $aList ) . '</ul>';

} else {
    $sOutput = '<ul><li class="noBids">' . _( 'No bids' ) . '</li></ul>';

}

// Winning bid
$sWinningBid = '';

// Calling fee
$sCallingFee = '';
if( !empty($aItem['itemFeeValue']) ) {
    if( $aItem['itemFeeType'] == 'sek' ) {
        $sCallingFee = calculatePrice( $aItem['itemFeeValue'], array(
            'format' => array(
                'money' => true
            )
        ) );
    }
    if( $aItem['itemFeeType'] == 'percent' ) {
        $sCallingFee = $aItem['itemFeeValue'] . ' ' . $aItem['itemFeeType'];
    }
}


$sLocation = '';
if( !empty($aItem['itemAddressId']) ) {
  
  // Google maps address
  $aAddress = current( $oAuctionEngine->readAuctionAddress_in_Auction(array(
    'addressAddress',
    'addressTitle',
    'addressHidden'
  ), $aItem['itemAddressId']) );

  if( !empty($aAddress) ) {

    if( $aAddress['addressHidden'] != 'yes' ) {
      // Google maps link
      $sMapUrl = 'https://www.google.se/maps/place/';
      if( !empty(trim($aAddress['addressAddress'])) ) {
        $sMapUrl .= preg_replace( '/\s+/', '+', $aAddress['addressAddress'] );
      } elseif( !empty(trim($aAddress['addressTitle'])) ) {
        $sMapUrl .= preg_replace( '/\s+/', '+', $aAddress['addressTitle'] );
      }

      $sLocation = '
        <a href="' . $sMapUrl . '" class="itemMap" data-item-id="' . $aItem['itemId'] . '" target="_blank"><i class="fas fa-map-marker-alt">&nbsp;</i><span class="long">' . $aAddress['addressTitle'] . '</span></a>';

    } else {
      $sLocation = '
        <i class="fas fa-map-marker-alt">&nbsp;</i><span class="long">' . $aAddress['addressTitle'] . '</span>';

    }

  }

}

echo '
      <div class="itemEndTime">' . convertTime( $aItem['itemEndTime'], $aItem['itemId'], 'show' ) . '</div>
      <div class="view auction bidListAll" data-item-id="' . $aItem['itemId'] . '">
        <dl class="marginal">
            ' . $sWinningBid . '
            <dt>' . _( 'Start price' ) . ':</dt>
            <dd>' . calculatePrice( $aItem['itemMinBid'], array(
                'format' => array(
                    'money' => true
                )
            ) ) . '</dd>

            <dt>' . _( 'VAT' ) . ':</dt>
            <dd>' . $aItem['itemVatValue'] . '% <span style="font-size: 13px;">moms tillkommer</span></dd>
            <dt>' . _( 'Inropsavgift' ) . ':</dt>
            <dd>' . $sCallingFee . ' <span style="font-size: 13px;">exkl. moms</span></dd>
            <dt>' . _( 'Location' ) . ':</dt>
            <dd>' . $sLocation . '</dd>
        </dl>
        <h3>' . _( 'Bid history' ) . '</h3>
        ' . $sOutput . '
      </div>';
