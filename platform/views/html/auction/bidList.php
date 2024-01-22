<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

$bCancelled = false;
if( !empty($GLOBALS['viewParams']['auction']['bidList.php']['item']) ) {
    $aItem = $GLOBALS['viewParams']['auction']['bidList.php']['item'];
    if( $aItem['itemMinBid'] == 0 && $aItem['itemMarketValue'] == 0 ) {
        $bCancelled = true;
    }

} elseif( !empty($_GET['itemId']) ) {
    $aItem = current( $oAuctionEngine->readAuctionItem( array(
        'itemId' => $_GET['itemId'],
        'status' => '*',
        'fields' => '*'
    ) ) );
    if( $aItem['itemMinBid'] == 0 && $aItem['itemMarketValue'] == 0 ) {
        $bCancelled = true;
    }

    if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == "213.88.134.199" ) {
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
    }

} else {
    return;

}

// Data
$aBidHistory = $oAuctionEngine->readItemBidHistory( $aItem['itemId'], 3 );

//if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == "213.88.134.199" ) {
//    /**
//     * Old bid data?
//     */
//    if( empty($aBidHistory) ) {
//        $oBackEnd2 = clFactory::create( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
//        $oBackEnd2->setSource( 'entAuctionBid', 'bidId' );
//        $oBackEnd2->oDao->aSorting = array( 'bidValue' => 'DESC' );
//        $oBackEnd2->oDao->setCriterias( array(
//            'bidItemId' => array(
//                'fields' => 'bidItemId',
//                'value' => $aItem['itemId']
//            ),
//            'bidStatus' => array(
//                'fields' => 'bidStatus',
//                'value' => 'successful'
//            )
//        ) );
//        $aBidData = $oBackEnd2->read( '*' );
//        $oBackEnd2->oDao->sCriterias = null;
//        $oBackEnd2->oDao->aSorting = array();
//
//        $aBidHistory = array();
//        foreach( $aBidData as $aBid ) {
//            $aBidHistory[] = array(
//                'historyBidValue' => $aBid['bidValue'],
//                'historyBidUserId' => $aBid['bidUserId'],
//                'historyBidPlaced' => $aBid['bidCreated'],
//                'historyBidType' => $aBid['bidType']
//            );
//        }
//    }
//}

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

        $sIconColor = '#' . str_pad( dechex($aBid['historyBidUserId']), 6, 'f', STR_PAD_LEFT );

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

echo '
    <div class="view auction bidList" data-item-id="' . $aItem['itemId'] . '">
        <h3>' . _( 'Bid history' ) . '</h3>
        ' . $sOutput . '
        ' . ($bCancelled == false ? '
        <p><a href="' . (!empty($aItem['routePath']) && $aItem['routePath'] != '#' ? $aItem['routePath'] : '/klassiskt/rop?itemId=' . $aItem['itemId']) . '">' . _( '+ Visa resten av budhistoriken' ) . '</a></p>
        ' : '') . '
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

            ' . ($bCancelled == false ? '
            <dt>' . _( 'Inropsavgift' ) . ':</dt>
            <dd>' . $sCallingFee . ' <span style="font-size: 13px;">exkl. moms</span></dd>
            ' : '') . '

            <dt>' . _( 'Ends' ) . ':</dt>
            <dd>' . convertTime( $aItem['itemEndTime'], $aItem['itemId'], 'show' ) . '</dd>

            <dt>' . _( 'Location' ) . ':</dt>
            <dd>' . $aItem['itemLocation'] . '</dd>
        </dl>
    </div>';
