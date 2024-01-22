<?php

/**
 *	Cronjob
 *	- Don't forget to add as cronjob:
 *	/usr/local/bin/php -f /home/account-name/domains/domain-name/platform/cronjob/cjExample.php >/dev/null 2>&1
 *	/usr/bin/php7.0 -f /home/httpd/account-name/domain-name/platform/cronjob/cjExample.php >/dev/null 2>&1
 *	|* / 10| * * * *
 */

ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', true );
ini_set( 'memory_limit', '1G' );
set_time_limit( 0 );
 
// Config
define( 'CRONJOB_NAME', 'AuctionTransfer' );
define( 'CRONJOB_DEBUG', true );
define( 'CRONJOB_LOGGING', true );
define( 'CRONJOB_LOG_FILE_SIZE', '8M' );

// Logging files
define( 'CRONJOB_LOG_PATH', dirname(dirname(__FILE__)) . '/logs' );
define( 'CRONJOB_LOGGER_FILE', 'cj' . CRONJOB_NAME . '_msg.log' );
define( 'CRONJOB_ERROR_LOG_FILE', CRONJOB_LOG_PATH . '/cj' . CRONJOB_NAME . '_error.log' );
define( 'CRONJOB_EXCEPRION_LOG_FILE', CRONJOB_LOG_PATH . '/cj' . CRONJOB_NAME . '_exception.log' );
 
try {	
	// Bootstrap platform
	require_once( dirname(dirname(__FILE__)) . '/core/bootstrap.php' );
	
	// Cronjob fix for router
	$_SERVER['REQUEST_URI'] = ''; 
	
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
		if( CRONJOB_DEBUG ) {
			echo $sError;
		}
		if( CRONJOB_LOGGING ) {
			file_put_contents( CRONJOB_ERROR_LOG_FILE, date('Y-m-d H:i:s') . ' ' . $sError . "\n", FILE_APPEND );
		}
		return true;
	}
	
	/**
	 * Cronjob exception handling
	 */
	function setException( $oException ) {
		if( CRONJOB_DEBUG ) {
			echo sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
		}
		if( CRONJOB_LOGGING ) {
			file_put_contents( CRONJOB_EXCEPRION_LOG_FILE, date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
		}
		return true;
	}
	
	// Set error & exception handlers
	set_error_handler( 'cronjobErrorHandler' );	
	set_exception_handler( 'setException' );

	// Logger
	clFactory::loadClassFile( 'clLogger' );
	
	if( CRONJOB_LOGGING ) {	
		clLogger::log( 'Cronjob start...', CRONJOB_LOGGER_FILE );
	}
	
	// Cronjob timer
	$fStartTime = microtime( true );
	
	// Modules of use
	$oConfig = clRegistry::get( 'clConfig' );
    $oAuctionTransfer = clRegistry::get( 'clAuctionTransfer', PATH_MODULE . '/auctionTransfer/models' );
	
	/**
	 * Set temporary access
	 */
	clFactory::loadClassFile( 'clAcl' );
	$oAcl = new clAcl();
	$oAcl->setAcl( array(
		'readConfig' => 'allow',
        'readAuctionTransfer' => 'allow',
        'writeAuctionTransfer' => 'allow'
	));
	$oConfig->setAcl( $oAcl );
    $oAuctionTransfer->setAcl( $oAcl );
	
	// Dependency files
	require_once( PATH_FUNCTION . '/fData.php' );
	
	# # # Do stuff below # # #
	
	// Data
	
	# # # End of doing stuff # # #
	
	if( CRONJOB_LOGGING ) {
		clLogger::log( 'Cronjob done.', CRONJOB_LOGGER_FILE );
		clLogger::log( '- - -', CRONJOB_LOGGER_FILE );
	}
	
	clLogger::logRotate( CRONJOB_LOGGER_FILE, CRONJOB_LOG_FILE_SIZE );
	clLogger::logRotate( CRONJOB_ERROR_LOG_FILE, CRONJOB_LOG_FILE_SIZE );
	clLogger::logRotate( CRONJOB_EXCEPRION_LOG_FILE, CRONJOB_LOG_FILE_SIZE );
	
} catch( Exception $oException ) {
	if( CRONJOB_LOGGING ) {
		file_put_contents( CRONJOB_EXCEPRION_LOG_FILE, date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
	}
}
	
	
	
	
	
	
	
	
	
	