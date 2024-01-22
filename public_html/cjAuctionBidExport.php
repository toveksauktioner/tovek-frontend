<?php

if( empty($_SERVER['HTTP_X_FORWARDED_FOR']) || $_SERVER['HTTP_X_FORWARDED_FOR'] != "213.88.134.199" ) {
	header( "HTTP/1.1 404 Not Found" );
	exit;
}
die();

/**
 * 15 * * * * /usr/bin/php -f /var/www/platform/cronjob/cjAuctionItemEnd.php >/dev/null
 * ('crontab -e' on server to edit)
 */

try {
    // Bootstrap platform
	require_once( dirname(dirname(__FILE__)) . '/platform/core/bootstrap.php' );
	$_SERVER['REQUEST_URI'] = ''; // Cronjob fix for router

	ini_set( 'error_reporting', E_ALL );
	ini_set( 'display_errors', true );
	ini_set( 'display_startup_errors', true );
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
        
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionBidExportErrors.log', date('Y-m-d H:i:s') . ' ' . $sError . "\n", FILE_APPEND );
		return true;
	}
	set_error_handler( 'cronjobErrorHandler' );
	function setException( $oException ) {
		echo sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionBidExportErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
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

	// Logger
	clFactory::loadClassFile( 'clLogger' );
	//clLogger::log( '- Cronjob started @ ' . date( 'Y-m-d H:i:s' ) . ' -', 'cjAuctionBidExport.log' );

	// Start a timer
	$fStartTime = microtime(true);

	/**
     * Read all ended auction parts
     */
    $sAuctionPartQuery = "SELECT * FROM entAuctionPart WHERE partStatus = 'ended'";
	$aAuctionParts = $oDbFront->query( $sAuctionPartQuery );
	
	foreach( $aAuctionParts as $aPart ) {
        // Read all items in this part
        $aBackendItems = $oDbBackend->query( 'SELECT * FROM entAuctionItem WHERE itemPartId = "' . $aPart['partId'] . '"' );
        
        // Read all bids for this parts auction in backend
        $aItemIds = arrayToSingle( $aBackendItems, null, 'itemId' );
        $aBackendBids = $oDbBackend->query( 'SELECT * FROM entAuctionBid WHERE bidItemId IN (' . implode( ', ', array_map('intval', $aItemIds) ) . ')' );
        
        // Read all history bids from frontend
        $aFrontendBids = $oDbFront->query( 'SELECT * FROM entAuctionBidHistory WHERE historyBidItemId IN (' . implode( ', ', array_map('intval', $aItemIds) ) . ') GROUP BY historyBidId' );
        
        if( count( $aBackendBids ) == count( $aFrontendBids ) ) {
            continue; // All bids exported
        }
        
        $aBidToInsert = array();
        foreach( $aFrontendBids as $aHistoryBid ) {
            // Create transaction ID
			$sBidTransactionId = md5( $aHistoryBid['historyBidPlaced'] . $aHistoryBid['historyBidId'] . $aHistoryBid['historyBidUserId'] );
            
            // Assamble all transaction IDs from backend
            $aAllTransactionIds = arrayToSingle( $aBackendBids, null, 'bidTransactionId' );
            
            if( !in_array($sBidTransactionId, $aAllTransactionIds) ) {
                // Export bid:
                
                
            } else {
                /**
                 * Found current bid in backend data
                 */                
            }
            
            echo '<pre>';
            var_dump( $aHistoryBid );
            var_dump( $aBackendBids );
            die;
        }
        
        
        echo '<pre>ok: ';
        var_dump( count( $aBackendItems ) );
        var_dump( count( $aBackendBids ) );
        var_dump( count( $aFrontendBids ) );
        die;
    }
	
	// Log
	//clLogger::log( 'Updated <' . $iUpdatedItemCount . '> auction items.', 'cjAuctionBidExport.log' );
	//clLogger::log( 'Cronjob finished @ ' . number_format( microtime(true) - $fStartTime, 4 ) . 's.', 'cjAuctionBidExport.log' );
	//clLogger::logRotate( 'cjAuctionBidExport.log', '8M' );
    
} catch( Throwable $oThrowable ) {
    // Exception logging
	echo '<pre>';
	var_dump( $oThrowable );
	die;
	//file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionBidExportErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
    
} catch( Exception $oException ) {
	// Exception logging
	echo '<pre>';
	var_dump( $oException );
	die;
	//file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionBidExportErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );

}