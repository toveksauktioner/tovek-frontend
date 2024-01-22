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
        
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionEndErrors.log', date('Y-m-d H:i:s') . ' ' . $sError . "\n", FILE_APPEND );
		return true;
	}
	set_error_handler( 'cronjobErrorHandler' );
	function setException( $oException ) {
		echo sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionEndErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
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
	//clLogger::log( '- Cronjob started @ ' . date( 'Y-m-d H:i:s' ) . ' -', 'cjAuctionEnd.log' );

	// Start a timer
	$fStartTime = microtime(true);

	/**
     * Read all active auction parts
     */
    $sAuctionPartQuery = "SELECT * FROM entAuctionPart WHERE partStatus = 'running' AND partAuctionStart LIKE '%" . date('Y-m-d') . "%'";
	$aAuctionParts = $oDbFront->query( $sAuctionPartQuery );
	
	if( !empty($aAuctionParts) ) {
		foreach( $aAuctionParts as $aPart ) {
			/**
			 * Read items
			 */
			$sItemQuery = '
				SELECT *
				FROM entAuctionItem
				WHERE itemPartId = "' . $aPart['partId'] . '"
				AND itemEndTime > NOW()
				AND itemStatus = "active"				
			';			
			$aItems = $oDbFront->query( $sItemQuery );
			
			if( empty($aItems) ) {
				/**
				 * Time to end auction
				 */
				//echo 'Ending auction: ' . $aPart['partAuctionId'] . '<br />';
				$sAuctionQuery = 'UPDATE entAuction SET auctionStatus = "inactive" WHERE auctionId = "' . $aPart['partAuctionId'] . '"';
				$oDbFront->query( $sAuctionQuery );
				
				$sAuctionQuery = 'UPDATE entAuctionPart SET partStatus = "ended" WHERE partId = "' . $aPart['partId'] . '"';
				$oDbFront->query( $sAuctionQuery );
			}
		}
	}
	
	// Log
	//clLogger::log( 'Updated <' . $iUpdatedItemCount . '> auction items.', 'cjAuctionEnd.log' );
	//clLogger::log( 'Cronjob finished @ ' . number_format( microtime(true) - $fStartTime, 4 ) . 's.', 'cjAuctionEnd.log' );
	//clLogger::logRotate( 'cjAuctionEnd.log', '8M' );
    
} catch( Throwable $oThrowable ) {
    // Exception logging
	echo '<pre>';
	var_dump( $oThrowable );
	die;
	//file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionEndErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
    
} catch( Exception $oException ) {
	// Exception logging
	echo '<pre>';
	var_dump( $oException );
	die;
	//file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionEndErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );

}