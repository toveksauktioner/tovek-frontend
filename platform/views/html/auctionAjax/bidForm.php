<?php

/*** Check´s if the request is made by ajax ***/
if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
	return;
}

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

/**
 * Post bid
 */
if( !empty($_POST['frmPlaceBid']) ) {
	// Current highest Bid
	$aPreHighestBid = current( $oAuctionEngine->readItemBidHistory( $_POST['bidItemId'] ) );

	// Bid type
	// $_POST['bidType'] = isset($_POST['submitPost']) ? 'normal' : 'auto';
	if( empty($_POST['bidType']) ) $_POST['bidType'] = 'auto';
	$aResult = $oAuctionEngine->placeBid( $_POST );

    if( $aResult['result'] == 'error' ) {
        // Error
        echo json_encode( $aResult );
        return;
    } else {
				/**
				 * Check if the bidding resulted in another winner and if anyone should be notified
				 */

				// After highest Bid
				$aAfterHighestBid = current( $oAuctionEngine->readItemBidHistory( $_POST['bidItemId'] ) );
				if( !empty($aAfterHighestBid) && !empty($aPreHighestBid) ) {
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

        // Success
        echo json_encode( $aResult );
        return;
    }
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
