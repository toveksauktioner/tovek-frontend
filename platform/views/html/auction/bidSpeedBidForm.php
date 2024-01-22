<?php

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

/**
 * Post bid
 */
if( !empty($_POST['frmPlaceBid']) ) {
	// Bid type
	$_POST['bidType'] = !empty($_POST['freeBidSelect']) ? 'normal' : 'auto';
	$oAuctionEngine->placeBid( $_POST );
}

if( !empty($_GET['itemId']) ) {
    $aItem = current( $oAuctionEngine->readAuctionItem( array(
        'fields' => '*',
        'itemId' => $_GET['itemId'],
        'status' => '*'
    ) ) );

} elseif( !empty($GLOBALS['viewParams']['auction']['bidSpeedBidForm.php']['item']) ) {
    $aItem = $GLOBALS['viewParams']['auction']['bidSpeedBidForm.php']['item'];

} else {
    return;
}

/**
 * Next bid
 */
$aHighestBid = current( $oAuctionEngine->readItemBidHistory( $aItem['itemId'] ) );
if( !empty($aHighestBid) && !empty($_SESSION['userId']) && $aHighestBid['historyBidUserId'] == $_SESSION['userId'] ) {
	$aBreakpoint = array();
	foreach( AUCTION_BID_TARIFF as $iKey => $aTariff ) {
		if( $aHighestBid['historyBidValue'] > $aTariff['break'] ) $aBreakpoint = $aTariff;
	}
	$iNextBid = ( $aHighestBid['historyBidValue'] + $aBreakpoint['min'] );
	$sBidButton = '<span id="nextBid' . $aItem['itemId'] . '">' . _( "You're leading" ) . '</span>';

} elseif( !empty($aHighestBid) ) {
	$aBreakpoint = array();
	foreach( AUCTION_BID_TARIFF as $iKey => $aTariff ) {
		if( $aHighestBid['historyBidValue'] > $aTariff['break'] ) $aBreakpoint = $aTariff;
	}
	$iNextBid = ( $aHighestBid['historyBidValue'] + $aBreakpoint['min'] );
	$sBidButton = '<span id="nextBid' . $aItem['itemId'] . '">' . _( 'Bidding' ) . ' ' . $iNextBid . ':-</span>';

} else {
	$aBreakpoint = array();
	foreach( AUCTION_BID_TARIFF as $iKey => $aTariff ) {
		if( $aItem['itemMinBid'] > $aTariff['break'] ) $aBreakpoint = $aTariff;
	}
	$iNextBid = ( $aItem['itemMinBid'] + $aBreakpoint['min'] );
	$sBidButton = '<span id="nextBid' . $aItem['itemId'] . '">' . _( 'Bidding' ) . ' ' . $iNextBid . ':-</span>';

}

if( !empty($_SESSION['userId']) && time() < strtotime($aItem['itemEndTime']) ) {
	$sBidForm = '
		<form class="bidForm" method="post">
			<div class="field intervalBid">
				<p class="buttons">
					<button name="submitPost" type="submit" class="submit' . ((!empty($aHighestBid) && $aHighestBid['historyBidUserId'] == $_SESSION['userId']) ? ' leading' : '') . '" data-item-id="' . $aItem['itemId'] . '">' . $sBidButton . '</button>
				</p>
				<label for="bidValueInterval">' . _( 'My bid' ) . '</label>
				<div class="slider" id="slider' . $aItem['itemId'] . '" data-item-id="' . $aItem['itemId'] . '" data-next-bid="' . $iNextBid . '">
					<div class="custom-handle ui-slider-handle"></div>
				</div>
				<input value="none" id="bidValueInterval" name="bidValueInterval" type="hidden" class="hidden" />
			</div>
			<div class="field freeBid">
				<div class="container top">
					<label for="freeBidSelect">' . _( 'I do not want to set maximum bid' ) . '</label>
					<input type="checkbox" id="freeBidSelect" name="freeBidSelect" class="checkbox" />
					<div class="container bottom">
						<label for="bidValue">Belopp:</label>
						<input title="Lägg ett bud" maxlength="255" id="bidValue" name="bidValue" type="text" class="text" value="' . $iNextBid . '" data-item-id="' . $aItem['itemId'] . '" />
					</div>
				</div>
			</div>
			<div class="hidden">
				<input value="' . $aItem['itemAuctionId'] . '" id="bidAuctionId" name="bidAuctionId" type="hidden" class="hidden" />
				<input value="' . $aItem['itemPartId'] . '" id="bidPartId" name="bidPartId" type="hidden" class="hidden" />
				<input value="' . $aItem['itemId'] . '" id="bidItemId" name="bidItemId" type="hidden" class="hidden" />
				<input value="' . $aItem['itemMinBid'] . '" id="itemMinBid" name="itemMinBid" type="hidden" class="hidden" />
				<input value="' . $aItem['itemEndTime'] . '" id="itemEndTime" name="itemEndTime" type="hidden" class="hidden" />
				<input value="' . $_SESSION['userId'] . '" id="bidUserId" name="bidUserId" type="hidden" class="hidden" />
				<input value="none" id="previousSubmit" name="previousSubmit" type="hidden" class="hidden" />
				<input value="1" id="frmPlaceBid" name="frmPlaceBid" type="hidden" class="hidden" />
			</div>
		</form>';

} elseif( !empty($_SESSION['userId']) && time() > strtotime($aItem['itemEndTime']) ) {
	$sBidForm = '<p class="message endedMessage">' . _( 'Time has ended!' ) . '</p>';

} else {
	$sBidForm = '<p class="message"><a href="/logga-in" class="loginLink button info">' . _( 'Login to place a bid' ) . '</a></p>';
	$oLayout = clRegistry::get( 'clLayoutHtml' );
	$sBidForm .= $oLayout->renderView( 'user/popupLogin.php' );

}

echo '
    <div class="view auction bidSpeedForm" data-item-id="' . $aItem['itemId'] . '">
        ' . $sBidForm . '
    </div>';
