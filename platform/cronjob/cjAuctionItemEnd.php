<?php

 // die();
//file_put_contents( dirname(dirname(__FILE__)) . '/logs/endAuctionItemSent.log', date('Y-m-d H:i:s') . ' Testar', FILE_APPEND );

/**
 * 15 * * * * /usr/bin/php -f /var/www/platform/cronjob/cjAuctionItemEnd.php >/dev/null
 * ('crontab -e' on server to edit)
 */

try {
    // Bootstrap platform
	require_once( dirname(dirname(__FILE__)) . '/core/bootstrap.php' );
	$_SERVER['REQUEST_URI'] = ''; // Cronjob fix for router

	ini_set( 'error_reporting', E_ALL );
	ini_set( 'display_errors', true );
	ini_set( 'memory_limit', '1G' );
	set_time_limit( 0 );

	/**
     * Cronjob error handling
     */
	function cronjobErrorHandler( $iLevel, $sMsg, $sFilename = '', $iLineNr = '' ) {
		switch ( $iLevel ) {
			case E_USER_ERROR:
				$sError = sprintf( _('Fatal Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			case E_USER_WARNING:
				$sError = sprintf( _('Warning: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			case E_USER_NOTICE:
				$sError = sprintf( _('Notice: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			default:
				$sError = sprintf( _('Unknown Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
		}
		echo $sError;

		file_put_contents( dirname(dirname(__FILE__)) . '/logs/endAuctionItemErrors.log', date('Y-m-d H:i:s') . ' ' . $sError . "\n", FILE_APPEND );
		return true;
	}
	set_error_handler( 'cronjobErrorHandler' );
	function setException( $oException ) {
		echo sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/endAuctionItemErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
		return true;
	}
	set_exception_handler( 'setException' );

	// Error (not exception) variable
	$aError = array();

	// Dependency files
	require_once( PATH_FUNCTION . '/fData.php' );
    require_once( PATH_MODULE . '/auction/config/cfAuction.php' );

	// Database object
	$oDbFront = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );
    $oDbBackend = clRegistry::get( 'clDbPdoSecondary' );

	// Modules used (here and in dependency files)
	$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );
	$oAuction = clRegistry::get( 'clAuction', PATH_MODULE . '/auction/models' );
	$oEmailQueue = clRegistry::get( 'clEmailQueue', PATH_MODULE . '/email/models' );
	$oSystemText = clRegistry::get( 'clSystemText', PATH_MODULE . '/systemText/models' );

  // Set temporary access
  clFactory::loadClassFile( 'clAcl' );
  $oAcl = new clAcl();
  $oAcl->setAcl( array(
  	'readAuctionItem' => 'allow',
  	'writeAuctionItem' => 'allow',
  	'readAuction' => 'allow',
    'readSystemText' => 'allow',
    'writeEmailQueue' => 'allow',
  ));
  $oAuctionItem->setAcl( $oAcl );
  $oAuction->setAcl( $oAcl );
  $oEmailQueue->setAcl( $oAcl );
  $oSystemText->setAcl( $oAcl );

	// Logger
	clFactory::loadClassFile( 'clLogger' );
	//clLogger::log( '- Cronjob started @ ' . date( 'Y-m-d H:i:s' ) . ' -', 'endAuctionItem.log' );

	// Start a timer
	$fStartTime = microtime(true);

	// Counters
	$iUpdatedItemCount = 0;

	/**
     * Get ended auction items, that still is active
     */
	$sItemQuery = "
		SELECT itemId
		FROM entAuctionItem
		LEFT JOIN entAuctionPart ON entAuctionItem.itemAuctionId = entAuctionPart.partAuctionId
		WHERE itemEndTime < '" . date( 'Y-m-d H:i:s', strtotime('- 15 seconds') ) . "'
		AND itemEndTime > '" . date('Y-m-d') . " 00:00:00'
		AND itemStatus = 'active'
		AND partStatus = 'running'
		GROUP BY entAuctionItem.itemId
	";
	$aAuctionItems = $oDbFront->query( $sItemQuery );

	if( !empty($aAuctionItems) ) {

        /**
         * Handling each item, which ended in time but still has status 'active'
         */
		foreach( $aAuctionItems as $aItem ) {

			// Get winning bid
			$aAuctionItemBid = $oDbFront->query( '
				SELECT *
				FROM entAuctionBidHistory
				WHERE historyBidItemId = "' . $aItem['itemId'] . '"
				ORDER BY historyBidValue DESC
				LIMIT 1
			' );

			if( !empty($aAuctionItemBid) ) {
                // Winning bid
				$aAuctionItemBid = current( $aAuctionItemBid );
				$aUser = current( $oDbBackend->query( '
					SELECT userId,userEmail
					FROM entUser
					WHERE userId = "' . $aAuctionItemBid['historyBidUserId'] . '"
				' ) );

			} else {
				// No winning bid
                $aAuctionItemBid = 0;
				$aUser = array();

			}

			if( !empty($aAuctionItemBid) ) {

				// Update item with winning data
				$sQuery = '
					UPDATE entAuctionItem
					SET itemStatus = "ended",
						itemWinningBidId = "' . $aAuctionItemBid['historyBidId'] . '",
						itemWinningBidValue = "' . $aAuctionItemBid['historyBidValue'] . '",
						itemWinningUserId = "' . $aAuctionItemBid['historyBidUserId'] . '"
					WHERE itemId = "' . $aItem['itemId'] . '"
				';
				$mResult = $oDbFront->write( $sQuery );

				// Send winner mail
	 			if( !empty($mResult) && !empty($aUser['userEmail']) ) {
	 				$oAuctionItem->sendWinnerMail( $aItem['itemId'], [
						'email' => $aUser['userEmail']
					] );
				}

			} else {
				// End item without winning bid
		 		$sQuery = '
					 UPDATE entAuctionItem
					 SET itemStatus = "ended"
					 WHERE itemId = "' . $aItem['itemId'] . '"
				';
				$oDbFront->write( $sQuery );
			}

			++$iUpdatedItemCount;
		}

	} else {
		// clLogger::log( 'No items found', 'endAuctionItem.log' );

	}

	// Log
	// clLogger::log( 'Updated <' . $iUpdatedItemCount . '> auction items.', 'endAuctionItem.log' );
	// clLogger::log( 'Cronjob finished @ ' . number_format( microtime(true) - $fStartTime, 4 ) . 's.', 'endAuctionItem.log' );
	// clLogger::logRotate( 'endAuctionItem.log', '8M' );

} catch( Throwable $oThrowable ) {
    // Exception logging
	setException( $oThrowable );

} catch( Exception $oException ) {
	// Exception logging
	setException( $oException );

}
