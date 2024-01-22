<?php

//die();

/**
 * * 3,4,5 * * * /usr/bin/php -f /var/www/platform/cronjob/cjAutoArchiceItems.php >/dev/null
 * ('crontab -e' on server to edit)
 */

try {
    // Bootstrap platform
	require_once( dirname(dirname(__FILE__)) . '/core/bootstrap.php' );
	$_SERVER['REQUEST_URI'] = ''; // Cronjob fix for router

	ini_set( 'error_reporting', E_ALL );
	ini_set( 'display_errors', true );
	ini_set( 'display_startup_errors', true );
	ini_set( 'memory_limit', '1G' );
	set_time_limit( 0 );

	$iNumberOfItemsLimit = 100;
	$aCheckTables = array(
		'entAuctionItem',
		'entAuctionBid',
		'entAuctionBidHistory',
		'entAuctionAutoBid'
	);

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

		file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAutoArchiveItemsErrors.log', date('Y-m-d H:i:s') . ' ' . $sError . "\n", FILE_APPEND );
		return true;
	}
	set_error_handler( 'cronjobErrorHandler' );
	function setException( $oException ) {
		echo sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAutoArchiveItemsErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
		return true;
	}
	set_exception_handler( 'setException' );

	// Error (not exception) variable
	$aError = array();

	// Database object
	$oDbFrontend= clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );

	// Logger
	clFactory::loadClassFile( 'clLogger' );
	//clLogger::log( '- Cronjob started @ ' . date( 'Y-m-d H:i:s' ) . ' -', 'cjAutoArchiveItems.log' );

	// Start a timer
	$fStartTime = microtime(true);

	// 0. Check prerequisites
	// Diff all tables that have archive version. If all ok proceed - else send mail to markus@tovek.se
	$aNotSame = array();
	foreach( $aCheckTables as $sTable ) {
		$sLiveTableSyntax = json_encode( $oDbFrontend->query("DESC " . $sTable . ";") );
		$sArchiveTableSyntax = json_encode( $oDbFrontend->query("DESC " . $sTable . "Archive;") );

		if( $sLiveTableSyntax != $sArchiveTableSyntax ) {
			$aNotSame[] = $sTable . ' != ' . $sTable . 'Archive';
		}
	}
	if( !empty($aNotSame) ) {
		mail( 'markus@tovek.se', 'Archive function failed', implode(' ', $aNotSame) );
		die();
	}

	// 1. Read old items
	$sOldItemsQuery = "SELECT * FROM entAuctionItem WHERE itemEndTime < DATE_SUB(NOW(), INTERVAL 1 MONTH) LIMIT " . $iNumberOfItemsLimit . ";";
	$aOldItems = $oDbFrontend->query( $sOldItemsQuery );
// echo count($aOldItems) . ' items';
	// 2. Step through old items and read bid and other data
	// When things have been archived - delete from live tables
	if( !empty($aOldItems) ) {
		foreach( $aOldItems as $aOldItem ) {
			// a. Copy item data to archive table  - delete on success
			$sArchiveItemQuery = "INSERT IGNORE INTO entAuctionItemArchive SELECT * FROM entAuctionItem WHERE itemId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . ";";
			$oDbFrontend->query( $sArchiveItemQuery );
			$sDeleteArchived = "DELETE FROM entAuctionItem WHERE itemId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . " AND itemId IN(SELECT itemId FROM entAuctionItemArchive) AND itemEndTime < DATE_SUB(NOW(), INTERVAL 1 MONTH);";
		  $oDbFrontend->query( $sDeleteArchived );
			// echo "\n" . $sDeleteArchived;
			// echo "\n" . $sArchiveItemQuery;

			// b. Copy bid data - delete on success
			$sArchiveBidQuery = "INSERT IGNORE INTO entAuctionBidArchive SELECT * FROM entAuctionBid WHERE bidItemId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . ";";
			$oDbFrontend->query( $sArchiveBidQuery );
			$sDeleteArchived = "DELETE FROM entAuctionBid WHERE bidItemId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . " AND bidId IN(SELECT bidId FROM entAuctionBidArchive);";
		  $oDbFrontend->query( $sDeleteArchived );
			// echo "\n" . $sDeleteArchived;
			// echo "\n" . $sArchiveBidQuery;

			// c. Copy bid history data - delete on success
			$sArchiveHistoryBidQuery = "INSERT IGNORE INTO entAuctionBidHistoryArchive SELECT * FROM entAuctionBidHistory WHERE historyBidItemId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . ";";
			$oDbFrontend->query( $sArchiveHistoryBidQuery );
			$sDeleteArchived = "DELETE FROM entAuctionBidHistory WHERE historyBidItemId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . " AND historyId IN(SELECT historyId FROM entAuctionBidHistoryArchive);";
		  $oDbFrontend->query( $sDeleteArchived );
			// echo "\n" . $sDeleteArchived;
			// echo "\n" . $sArchiveHistoryBidQuery;

			// d. Copy auto bid data - delete on success
			$sArchiveAutoBidQuery = "INSERT IGNORE INTO entAuctionAutoBidArchive SELECT * FROM entAuctionAutoBid WHERE autoItemId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . ";";
			$oDbFrontend->query( $sArchiveAutoBidQuery );
			$sDeleteArchived = "DELETE FROM entAuctionAutoBid WHERE autoItemId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . " AND autoId IN(SELECT autoId FROM entAuctionAutoBidArchive);";
		  $oDbFrontend->query( $sDeleteArchived );
			// echo "\n" . $sDeleteArchived;
			// echo "\n" . $sArchiveAutoBidQuery;

			// e. Delete route and routeToObject
			$sDeleteRouteDataQuery = "DELETE t1, t2 FROM entRouteToObject t1 LEFT JOIN entRoute t2 ON t2.routeId=t1.routeId WHERE t1.objectType = 'AuctionItem' AND t1.objectId = " . $oDbFrontend->escapeStr( $aOldItem['itemId'] ) . ";";
			$oDbFrontend->query( $sDeleteRouteDataQuery );
			// echo "\n" . $sDeleteRouteDataQuery;
		}
	}

	// Log
	// clLogger::log( '------------------------------------------------------------', 'cjAutoArchiveItems.log' );
	// clLogger::log( 'Moved <' . count($aOldItems) . '> auction items with bids.', 'cjAutoArchiveItems.log' );
	// clLogger::log( 'Cronjob finished @ ' . number_format( microtime(true) - $fStartTime, 4 ) . 's.', 'cjAutoArchiveItems.log' );
	// clLogger::logRotate( 'cjAutoArchiveItems.log', '8M' );

} catch( Throwable $oThrowable ) {
    // Exception logging
	file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAutoArchiveItemsErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oThrowable->getMessage(), $oThrowable->getLine(), $oThrowable->getFile(), $oThrowable->getCode() ), FILE_APPEND );

} catch( Exception $oException ) {
	// Exception logging
	file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAutoArchiveItemsErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );

}
