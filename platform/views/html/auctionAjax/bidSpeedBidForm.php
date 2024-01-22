<?php

//$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
//
//$aItem = current( $oAuctionEngine->readAuctionItem( array(
//    'itemId' => $_GET['itemId'],
//    'status' => '*',
//    'fields' => '*'
//) ) );
//
///**
// * Bid value
// */
//$fBidValue = !empty($aItem['bidValue']) ? $aItem['bidValue'] : $aItem['itemMinBid'];		
//$sCurrentBid = '';
//$sCurrentBidTime = !empty($aItem['bidCreated']) ? $aItem['bidCreated'] : ''; // 'Lagt 30 jan 2019';
//if( !empty($aItem['auctionType']) && $aItem['auctionType'] != 'live' ) {
//    $sCurrentBid = '<span class="itemCurrentBid itemCurrentBid' . $aItem['itemId'] . '" data-item-id="' . $aItem['itemId'] . '">' . (empty($fBidValue) || $fBidValue == '0' ? _( 'Expires' ) : '') . '</span>';
//    if( !empty($fBidValue) || $fBidValue != '0' ) {
//        $sCurrentBidTime = '<span class="itemCurrentBidTime' . $aItem['itemId'] . '"></span>';
//    }			
//}
//$sNextBidValue = '';
//
///**
// * Handling of shown bid price
// */
//if( !empty($aItem['auctionTitle']) ) {
//    $sAuctionTitle = $aItem['auctionTitle'];
//} else {
//    $sAuctionTitle = '';
//}
//
//// Registration plate is not included in title after 30 days
//$sRegNo = '';
//if( !empty($aItem['itemRegistrationPlate']) && ($iTimeSinceEnded < 2592000) ) {
//    $sRegNo = ' [' . $aItem['itemRegistrationPlate'] . ']';
//}
//
///**
// * Bid form
// */			
//$sBidHistory = '';
//if( !empty($_SESSION['userId']) && time() < strtotime($aItem['itemEndTime']) ) {
//    $sBidForm = '
//        <form class="bidForm" method="post">
//            <div class="field intervalBid">
//                <p class="buttons">
//                    <button name="submitPost" type="submit" class="submit">' . _( 'Bidding' ) . ' <span id="nextBid' . $aItem['itemId'] . '">' . $sNextBidValue . '</span></button>
//                </p>
//                <label for="bidValueInterval">' . _( 'My bid' ) . '</label>							
//                <div class="slider">
//                    <div class="custom-handle ui-slider-handle"></div>
//                </div>
//                <input value="none" id="bidValueInterval" name="bidValueInterval" type="hidden" class="hidden" />
//            </div>
//            <div class="field freeBid">
//                <label for="freeBidSelect">' . _( 'I do not want to set maximum bid' ) . '</label> 
//                <input type="checkbox" id="freeBidSelect" name="freeBidSelect" class="checkbox" />
//                <div class="container">
//                    <label for="bidValue">Lägg ett bud:</label> 
//                    <input title="Lägg ett bud" maxlength="255" id="bidValue" name="bidValue" type="text" class="text" />
//                </div>
//            </div>
//            <div class="hidden">
//                <input value="' . $aItem['itemAuctionId'] . '" id="bidAuctionId" name="bidAuctionId" type="hidden" class="hidden">
//                <input value="' . $aItem['itemPartId'] . '" id="bidPartId" name="bidPartId" type="hidden" class="hidden">
//                <input value="' . $aItem['itemId'] . '" id="bidItemId" name="bidItemId" type="hidden" class="hidden">
//                <input value="' . $aItem['itemMinBid'] . '" id="itemMinBid" name="itemMinBid" type="hidden" class="hidden">
//                <input value="' . $aItem['itemEndTime'] . '" id="itemEndTime" name="itemEndTime" type="hidden" class="hidden">
//                <input value="' . $_SESSION['userId'] . '" id="bidUserId" name="bidUserId" type="hidden" class="hidden">
//                <input value="none" id="previousSubmit" name="previousSubmit" type="hidden" class="hidden">
//                <input value="1" id="frmPlaceBid" name="frmPlaceBid" type="hidden" class="hidden">
//            </div>
//        </form>';
//        
//    $oLayout = clRegistry::get( 'clLayoutHtml' );
//    $GLOBALS['viewParams']['auction']['bidList.php']['itemId'] = $aItem['itemId'];
//    $sBidHistory = $oLayout->renderView( 'auction/bidList.php' );
//    
//} elseif( !empty($_SESSION['userId']) && time() > strtotime($aItem['itemEndTime']) ) {				
//    $sBidForm = '<div class="endedMessage">' . _( 'Time has ended!' ) . '</div>';
//    $oLayout = clRegistry::get( 'clLayoutHtml' );
//    $GLOBALS['viewParams']['auction']['bidList.php']['itemId'] = $aItem['itemId'];
//    $sBidHistory = $oLayout->renderView( 'auction/bidList.php' );
//    
//} else {
//    $sBidForm = '<p class="message"><a href="/logga-in" class="popupLoginLink button info">' . _( 'Login to place a bid' ) . '</a></p>';
//    $oLayout = clRegistry::get( 'clLayoutHtml' );
//    $sBidForm .= $oLayout->renderView( 'user/popupLogin.php' );
//}
//
//echo '
//    <a href="#" class="close">X</a>
//    <p class="item">' . _( 'Call' ) . ' ' . $aItem['itemSortNo'] . ' ' . $aItem['itemTitle'] . $sRegNo . '</p>
//    <p class="bid">' . _( 'Bid' ) . ' ' . $sCurrentBid . '</p>
//	<p class="time">' . $sCurrentBidTime . '</p>
//    ' . $sBidForm . '';