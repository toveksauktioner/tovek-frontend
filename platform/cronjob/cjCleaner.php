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
ini_set( 'display_startup_errors', true );
ini_set( 'memory_limit', '1G' );
set_time_limit( 0 );

// Config
define( 'CRONJOB_NAME', 'Cleaner' );
define( 'CRONJOB_DEBUG', false );

/**
 *
 * Killing time limit
 *
 */
define( 'KILLING_TIME_LIMIT', '30' );
define( 'KILLING_QUERY_LIMIT', 800 );

// Logging files
define( 'CRONJOB_LOGGING', false ); # No logging in this cron
define( 'CRONJOB_LOG_FILE_SIZE', '8M' );
define( 'CRONJOB_LOG_PATH', dirname(dirname(__FILE__)) . '/logs' );
define( 'CRONJOB_LOGGER_FILE', 'cj' . CRONJOB_NAME . '_msg.log' );
define( 'CRONJOB_ERROR_LOG_FILE', CRONJOB_LOG_PATH . '/cj' . CRONJOB_NAME . '_error.log' );
define( 'CRONJOB_EXCEPRION_LOG_FILE', CRONJOB_LOG_PATH . '/cj' . CRONJOB_NAME . '_exception.log' );

try {
    // Bootstrap
    require_once( dirname(dirname(__FILE__)) . '/core/bootstrap.php' );

	// Cronjob fix for router
	$_SERVER['REQUEST_URI'] = '';

    // Logger
    clFactory::loadClassFile( 'clLogger' );
	//$sMsg = "Cronjob start";
	//file_put_contents( PATH_LOG . '/' . CRONJOB_LOGGER_FILE, date('Y-m-d H:i:s') . "\t" . $sMsg . "\r\n", FILE_APPEND );

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

	if( CRONJOB_LOGGING === true ) {
		clLogger::log( 'Cronjob start...', CRONJOB_LOGGER_FILE );
	}

	// Cronjob timer
	$fStartTime = microtime( true );

    // Dependency files
	require_once( PATH_FUNCTION . '/fData.php' );

	// Database
	$oDb = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );

	/**
	 * Killing list query
	 */
	$sKillingQuery = sprintf( "SELECT GROUP_CONCAT('kill ',id SEPARATOR '; ') AS kill_list FROM information_schema.processlist WHERE command = 'Sleep' AND time > %s LIMIT %s", KILLING_TIME_LIMIT, KILLING_QUERY_LIMIT );

    // Get kill list
    $aResult = $oDb->query( $sKillingQuery );
    $sKillList = current(current( $aResult ));

    if( CRONJOB_LOGGING === true ) clLogger::log( sprintf( 'Kill list (master): %s', $sKillList ), CRONJOB_LOGGER_FILE );
	if( !empty($sKillList) ) {
		$iEntryCount = substr_count( $sKillList, ';' ) + 1;
		if( CRONJOB_LOGGING === true ) clLogger::log( sprintf( 'Entry count: %s', $iEntryCount ), CRONJOB_LOGGER_FILE );
		// Kill process
		$oDb->query( $sKillList );
	}

	if( method_exists($oDb, 'querySecondaryDb') ) {
		// Get kill list
		$sKillList = current(current( $oDb->querySecondaryDb( $sKillingQuery ) ));
		if( CRONJOB_LOGGING === true ) clLogger::log( sprintf( 'Kill list (slav): %s', $sKillList ), CRONJOB_LOGGER_FILE );
		if( !empty($sKillList) ) {
			$iEntryCount = substr_count( $sKillList, ';' ) + 1;
			if( CRONJOB_LOGGING === true ) clLogger::log( sprintf( 'Entry count: %s', $iEntryCount ), CRONJOB_LOGGER_FILE );
			// Kill process
			$oDb->querySecondaryDb( $sKillList );
		}
	}

	// Logging
	if( CRONJOB_LOGGING === true ) {
        clLogger::log( 'Cronjob done in ' . number_format( microtime(true) - $fStartTime, 2 ) . 's.', CRONJOB_LOGGER_FILE );
		clLogger::log( '- - -', CRONJOB_LOGGER_FILE );

        // Log rotate
        clLogger::logRotate( CRONJOB_LOGGER_FILE, CRONJOB_LOG_FILE_SIZE );
        clLogger::logRotate( CRONJOB_ERROR_LOG_FILE, CRONJOB_LOG_FILE_SIZE );
        clLogger::logRotate( CRONJOB_EXCEPRION_LOG_FILE, CRONJOB_LOG_FILE_SIZE );
	}

} catch( Throwable $oThrowable ) {
    // Manual logging
    if( CRONJOB_LOGGING ) file_put_contents( CRONJOB_EXCEPRION_LOG_FILE, date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );

} catch( Exception $oException ) {
	// Manual logging
    if( CRONJOB_LOGGING ) file_put_contents( CRONJOB_EXCEPRION_LOG_FILE, date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );

}
