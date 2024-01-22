<?php

$iSuggestedFinancingValue = null;

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

/**
 * Remove auto bid
 */
if( !empty($_GET['removeAutoBid']) ) {
	$oAuctionEngine->removeAutoBid( $_GET['autoId'] );
}

/**
 * Post bid
 */
if( !empty($_POST['frmPlaceBid']) ) {
	// Current highest Bid
	$aPreHighestBid = current( $oAuctionEngine->readItemBidHistory( $_POST['bidItemId'] ) );

	// Bid type
	if( empty($_POST['bidType']) ) $_POST['bidType'] = 'auto';
	$oAuctionEngine->placeBid( $_POST );

	/**
	 * Check if the bidding resulted in another winner and if anyone should be notified
	 */

	// After highest Bid
	$aAfterHighestBid = current( $oAuctionEngine->readItemBidHistory( $_POST['bidItemId'] ) );
	if( !empty($aAfterHighestBid) ) {
		/**
		 * Send notification if outbidded user that wants to know *
		 */
		if( $aAfterHighestBid['historyBidUserId'] != $aPreHighestBid['historyBidUserId'] ) {
			$oUserSettings = clRegistry::get( 'clUserSettings', PATH_MODULE . '/userSettings/models' );
			$aSettingsData = current( $oUserSettings->read('*', 'USER_OUTBIDDED_NOTIFICATION') );
			$sOutbiddedSettings = $oUserSettings->readUserSetting( $aPreHighestBid['historyBidUserId'], 'USER_OUTBIDDED_NOTIFICATION' );

			if( $sOutbiddedSettings == 'yes' ) {
				$oUserNotification = clRegistry::get( 'clUserNotification', PATH_MODULE . '/userNotification/models' );
				$oUserNotification->create( array(
					'notificationTitle' => $aSettingsData['settingsTitle'],
					'notificationMessage' => $aSettingsData['settingsMessage'],
					'notificationUrl' => '/rop?itemId=' . $aPreHighestBid['historyBidItemId'],
					'notificationUserId' => $aPreHighestBid['historyBidUserId']
				), $aSettingsData['settingsOverrideDefconLevel'] );
			}
		}
	}
}

if( !empty($_GET['itemId']) ) {
  $aItem = current( $oAuctionEngine->readAuctionItem( array(
    'fields' => '*',
    'itemId' => $_GET['itemId'],
    'status' => '*'
  ) ) );

	if( empty($aItem) ) {
		// Read from backend
	 	$oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
		$oBackEnd->setSource( 'entAuctionItem', 'itemId' );
		$aItem = current( $oBackEnd->read('*', $_GET['itemId']) );
	}

} elseif( !empty($GLOBALS['viewParams']['auction']['bidFormAdd.php']['item']) ) {
    $aItem = $GLOBALS['viewParams']['auction']['bidFormAdd.php']['item'];

} else {
    return;
}

/**
 * Cancelled item
 */
if( $aItem['itemMinBid'] == 0 && $aItem['itemMarketValue'] == 0 ) {
	return;
}

/**
 * Current bid
 */
$aBids = $oAuctionEngine->readItemBidHistory( $aItem['itemId'] );
$aHighestBid = current( $aBids );
if( !empty($aHighestBid) ) {
	$aBidder = current( $oUser->oDao->read( array(
		'userId' => $aHighestBid['historyBidUserId'],
		'fields' => array( 'username', 'userId', 'infoName' )
	) ) );
	if( empty($aBidder['username']) ) $aBidder['username'] = 'Från skarpa!';

	$iCreated = substr( $aHighestBid['historyBidPlaced'], 0, strrpos( $aHighestBid['historyBidPlaced'], '.') );
	$sDate = strtolower( formatIntlDate('d MMM Y', $iCreated) );
	$sDate .= ' ' . date( 'H:i', $iCreated );

	$iCurrentBid = $aHighestBid['historyBidValue'];
	$sCurrentBid = '<span id="currentBid' . $aItem['itemId'] . '">' . calculatePrice( $iCurrentBid, array('profile' => 'human') ) . '</span>';
	$sCurrentBidUser = sprintf( '<span class="bidder">%s</span><span class="bidDate">%s</span>', $aBidder['username'], $sDate );
	$iCurrentBidId = $aHighestBid['historyBidUserId'];

} else if( !empty($aItem['itemWinningBidValue']) ) {
	$iCurrentBid = $aItem['itemWinningBidValue'];
	$sCurrentBid = '<span id="currentBid' . $aItem['itemId'] . '">' . calculatePrice( $iCurrentBid, array('profile' => 'human') ) . '</span>';
	$sCurrentBidUser = '<span class="bidder"><em>Vinnande bud</em></span>';
	$iCurrentBidId = null;

} else {
	$iCurrentBid = $aItem['itemMinBid'];
	$sCurrentBid = '<span id="currentBid' . $aItem['itemId'] . '">' . calculatePrice( $iCurrentBid, array('profile' => 'human') ) . '</span>';
	$sCurrentBidUser = '<span class="bidder"><em>utropspris</em></span>';
	$iCurrentBidId = null;
}

/**
 * Current user auto bid
 */
$sAutoBid = '';
if( !empty($_SESSION['userId']) ) {
	$aAutoBid = current( $oAuctionEngine->readAuctionAutoBid( array(
		'userId' => $_SESSION['userId'],
		'itemId' => $aItem['itemId']
	) ) );
	if( !empty($aAutoBid) ) {
		$sAutoBid = '
			<div class="currentAutoBid">
				<span class="label">' . _( 'Ditt maxbud' ) . ':</span> <span class="value">' . calculatePrice( $aAutoBid['autoMaxBid'], array('profile' => 'human') )  . '</span>
				<a href="?ajax=true&view=auction/bidFormAdd.php&removeAutoBid=' . $aAutoBid['autoUserId'] . '&autoId=' . $aAutoBid['autoId'] . '&itemId=' .  $aItem['itemId'] . '" data-item-id="' .  $aItem['itemId'] . '" class="removeAutoBid button small cancel"><i class="fas fa-trash"></i></a>
			</div>';

		// Set financing value
		$iSuggestedFinancingValue = $aAutoBid['autoMaxBid'];
	}
}

// If the user don't have autobid - present help button and normal bid button
$sNormalBid = '';

if( empty($sAutoBid) ) {
	$sNormalBid .= '
		<p class="buttons normalBid">
			<button type="submit" name="submitPost" class="submitBid small fullWidth">' . _( 'Bud' ) . '</button>
		</p>';
} else {
	$sNormalBid .= $sAutoBid;
}

// Help
$sHelp = '';

// Find next valid bid
$iNextValidBid = $iCurrentBid;
$aCurrentStep = array();
foreach( AUCTION_BID_TARIFF as $iKey => $aStep ) {
	if( $iNextValidBid >= $aStep['break'] ) {
		$aCurrentStep = $aStep;
	}
}
if( !empty($aCurrentStep) && !empty($aHighestBid) ) $iNextValidBid += $aCurrentStep['min'];

// Set financing value default
if( empty($iSuggestedFinancingValue) ) $iSuggestedFinancingValue = $iNextValidBid;

$sBidForm = '';
if( !empty($_SESSION['userId']) && (time() < strtotime($aItem['itemEndTime'])) ) {
	$sBidForm = '
		<form class="bidForm newForm oneLiner bottomFixed" method="post">
			<div class="notification"></div>
			<div class="field noicon">
				<label for="bidValue">' . _( 'Ditt nästa bud' ) . ':</label>
				<input title="' . _( 'Ditt bud' ) . '" maxlength="255" id="bidValue" name="bidValue" type="number" class="number" data-item-id="' . $aItem['itemId'] . '" placeholder="" pattern="[0-9]+" value="' /*. $iNextValidBid*/ . '" />
			</div>
			<p class="buttons autoBid">
				<button type="submit" name="submitMaxBid" class="submitMaxBid narrow fullWidth">' . _( 'Maxbud' ) . '</button>
			</p>
			<p class="bidHelpButton">
				<a href="' . $oRouter->getPath( 'guestHelp' ) . '?c=1&t=64" class="popupLink" data-size="full">' . _( 'Hur fungerar budgivning?' ) . '</a>
			</p>
			' . $sNormalBid . '
			<div class="hidden">
				<input value="' . $aItem['itemAuctionId'] . '" id="bidAuctionId" name="bidAuctionId" type="hidden" class="hidden" />
				<input value="' . $aItem['itemPartId'] . '" id="bidPartId" name="bidPartId" type="hidden" class="hidden" />
				<input value="' . $aItem['itemId'] . '" id="bidItemId" name="bidItemId" type="hidden" class="hidden" />
				<input value="' . $aItem['itemMinBid'] . '" id="itemMinBid" name="itemMinBid" type="hidden" class="hidden" />
				<input value="' . $aItem['itemEndTime'] . '" id="itemEndTime" name="itemEndTime" type="hidden" class="hidden" />
				<input value="' . $_COOKIE['userId'] . '" id="bidUserId" name="bidUserId" type="hidden" class="hidden" />
				<input value="auto" id="bidType" name="bidType" type="hidden" class="hidden" />
				<input value="none" id="previousSubmit" name="previousSubmit" type="hidden" class="hidden" />
				<input value="1" id="frmPlaceBid" name="frmPlaceBid" type="hidden" class="hidden" />
			</div>
		</form>';

} elseif( (time() < strtotime($aItem['itemEndTime'])) ) {
	$sBidForm = '<p class="loginButton"><a href="/logga-in?returnTo=' . $oRouter->sPath . '" class="loginLink button info popupLink">' . _( 'Logga in för att lägga bud' ) . '</a></p>';
}

$aViewClass = array();
if( !empty($_SESSION['userId']) && empty($_GET['onlyCurrentBidInfo']) ) {

	if( $_SESSION['userId'] == $iCurrentBidId ) {
		$aViewClass[] = 'isBidder';
		$aViewClass[] = 'isWinner';

	} else {
		foreach( $aBids as $aBid ) {
			if( $aBid['historyBidUserId'] == $_SESSION['userId'] ) {
				$aViewClass[] = 'isBidder';
				break;
			}
		}
	}

}

// Current bid
// if( time() < strtotime($aItem['itemEndTime']) ) {
	$sCurrentBid = sprintf( '<span class="label">' . _( 'Current bid' ) . '</span> <span>%s</span>', $sCurrentBid . '' );

// } else {
// 	list( $sEndDate, $sEndTime ) = explode( ' ', $aItem['itemEndTime'] );
// 	$sCurrentBid = sprintf( '<span class="label">' . _( 'Avslutat rop' ) . '</span> <span>%s</span>', $sCurrentBid . '' );
// 	$sCurrentBidUser = '<datetime><date>' . $sEndDate . '</date><time>' . $sEndTime . '</time></datetime>';
// 	$aViewClass[] = 'isEnded';
// }


// Transfer value to other views
$GLOBALS['viewParams']['financing']['wasakreditUserItemNotice.php']['item'] = $aItem;
$GLOBALS['viewParams']['financing']['wasakreditUserItemNotice.php']['suggestedFinancingValue'] = $iSuggestedFinancingValue;

// Fix for only getting the current bid part of the form
// This also makes the script ignore the userId (anonymize) on line 201 above
if( !empty($_GET['onlyCurrentBidInfo']) ) {
	echo json_encode( [
		'currentBid' => $sCurrentBid,
		'currentBidUser' => $sCurrentBidUser,
		'currentBidUserId' => $iCurrentBidId
	] );
	return;
}

echo '
    <div class="view auction bidFormAdd ' . ( !empty($aViewClass) ? implode(' ', $aViewClass) : '' ) . '" data-item-id="' . $aItem['itemId'] . '">
			<h2><small>' . _( 'Rop' ) . ' ' . $aItem['itemSortNo'] . '</small> ' . $aItem['itemTitle'] . '</h2>
			<div class="currentBid">' . $sCurrentBid . '</div>
			<div class="currentBidUser" id="currentBidUser' . $aItem['itemId'] . '">
				' . $sCurrentBidUser . '
			</div>
		' . $sBidForm . '
    </div>';
