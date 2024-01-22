<?php

/**
* $Id: index.php 1498 2015-01-21 09:21:00Z suarez $
* This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
*
* This file fetches files sent in through the get variable (array) include[], parses them and outputs them.
* Accepts files with extensions, without extensions and parent directories.
*
* @package		argoPlatform
* @category		Framework
* @link 		http://www.argonova.se
* @author		$Author: suarez $
* @version		Subversion: $Revision: 1498 $, $Date: 2015-01-21 10:21:00 +0100 (on, 21 jan 2015) $
*/

set_time_limit( 0 );
ini_set('display_startup_errors', true);
error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('date.timezone', 'Europe/Stockholm');

$aFiles	= array();
$sCurrentDirectory = dirname(__FILE__);
$sCurrentDirectoryLength = strlen($sCurrentDirectory);

require_once dirname( dirname( $sCurrentDirectory ) ) . '/platform/config/cfBase.php';
date_default_timezone_set( SITE_DEFAULT_TIMEZONE );

header( 'Content-Type: text/css; charset=UTF-8' );
header( 'Cache-Control: must-revalidate' );
header( 'Expires: ' . gmdate('D, d M Y H:i:s', time() + CACHE_CSS_TIME) . ' GMT' );

if( defined('COMPRESS_CSS') && COMPRESS_CSS === true && extension_loaded('zlib') && ini_get('zlib.output_compression') == 0 ) {
	ob_start('ob_gzhandler');
} else {
	ob_start();
}

/**
 * Try to use parser if enabled
 */
if( CSS_PARSER_ENABLE === true ) {
	try {
		require_once CSS_PARSER_HANDLER;
		$oParser = new clCssParserHandler( $sCurrentDirectory );

	} catch( Throwable $oThrowable ) {
		if( $GLOBALS['debug'] === true ) {
			echo printf( _( 'Throwable: "%s" on line %s in file %s at code %s' ), $oThrowable->getMessage(), $oThrowable->getLine(), $oThrowable->getFile(), $oThrowable->getCode() );
			exit;
		}
		echo printf( _( 'Exception: "%s"' ), $oThrowable->getMessage() );
		exit;

	} catch( Exception $oException ) {
		if( $GLOBALS['debug'] === true ) {
			echo printf( _( 'Exception: "%s" on line %s in file %s at code %s' ), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
			exit;
		}
		echo printf( _( 'Exception: "%s"' ), $oException->getMessage() );
		exit;

	}
}

/**
 * Check for files
 */
try {
	// Use specific files
	if( isset( $_GET['include'] ) ) {
		$aIncludePaths = explode( ';', $_GET['include'] );

		foreach( $aIncludePaths as &$sIncludePath ) {
			$sIncludePath = trim( $sIncludePath, '/' );

			if( $sIncludePath[0] == '.') {
				throw new Exception( 'Path began with dot' );
			}

			if( !is_dir($sIncludePath) ) {
				$sExtension	= '.' . pathinfo( $sIncludePath, PATHINFO_EXTENSION );

				if( $sExtension == '.' && is_file($sCurrentDirectory . '/' . $sIncludePath . '.scss') ) {
					$sIncludePath .= '.scss';
				} elseif( $sExtension == '.' && is_file($sCurrentDirectory . '/' . $sIncludePath . '.css') ) {
					$sIncludePath .= '.css';
				} else {
					// No file found, skip this one
					continue;
				}

				$aFiles[] =  $sIncludePath;
				continue;
			}

			if( strpos($sIncludePath, '.') !== false ) {
				throw new Exception( 'Directory path contained dot' );
			}

			$aFiles += scandir( $sCurrentDirectory . '/' . $sIncludePath );

			foreach( $aFiles as $iKey => $sFile ) {
				$aFiles[ $iKey ] = $sIncludePath . '/' . $sFile;
			}
		}
	} else {
		$aFiles = scandir( $sCurrentDirectory );
	}

} catch( Exception $e ) {
	throw new Exception( 'Invalid path: ' . $e->getMessage() );
}

if( isset($oParser) ) {
	/**
	 * Parse all files to one single buffer output
	 */
	
	foreach( $aFiles as $iKey => $sFile ) {
		if( $sFile[0] == '.' || is_dir( $sFile ) ) unset( $aFiles[ $iKey ] );
	}

	try {
		$sBuffer = $oParser->output( $aFiles );

	} catch( Throwable $oThrowable ) {
		// Print exceptions on debug
		if( $GLOBALS['debug'] === true ) {
			// Not added to the buffer because it is stripped of comments at compression
			echo "\n\n/* Exception: " . $oThrowable->getMessage() . " */\n\n";
			// Output exception as a "status message" on top of the page
			echo "\n" . 'body:before { background: rgba(255,0,0,.75); border-bottom: 1px #fff solid; box-shadow: 0 0 .5em .25em rgba(0,0,0,.25); padding: 1em; position: fixed; top: 0; left: 0; right: 0; z-index: 10000; content: "CSS exception: ' . str_replace('"', "", $oThrowable->getMessage() ) . '"; }' . "\n";
		}

	} catch( Exception $oException ) {
		// Print exceptions on debug
		if( $GLOBALS['debug'] === true ) {
			// Not added to the buffer because it is stripped of comments at compression
			echo "\n\n/* Exception: " . $oException->getMessage() . " */\n\n";
			// Output exception as a "status message" on top of the page
			echo "\n" . 'body:before { background: rgba(255,0,0,.75); border-bottom: 1px #fff solid; box-shadow: 0 0 .5em .25em rgba(0,0,0,.25); padding: 1em; position: fixed; top: 0; left: 0; right: 0; z-index: 10000; content: "CSS exception: ' . str_replace('"', "", $oException->getMessage() ) . '"; }' . "\n";
		}

	}

	/**
	 * Our own compress buffer
	 */
	if( defined('COMPRESS_CSS') && COMPRESS_CSS === true && $sBuffer ) {
		$sBuffer = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $sBuffer );
		$sBuffer = str_replace( array("\r\n", "\r", "\n", "\t"), "", $sBuffer );
		$sBuffer = str_replace( array("; ", " {"), array(";", "{"), $sBuffer );
		$sBuffer = str_replace( array( "     ", "    ", "   ", "  " ), array(" "), $sBuffer ); // Remove "all" double spaces
	}

	echo $sBuffer;

} else {
	/**
	 * Output classic, file by file
	 */

	foreach( $aFiles as &$sFile ) {
		if( $sFile[0] == '.' || is_dir( $sFile ) ) {
			continue;
		}

		$sBuffer = null;
		$sExtension = pathinfo( $sFile, PATHINFO_EXTENSION );
		$sFile = $sCurrentDirectory . '/' . $sFile;

		try {
			if( $sExtension ) {
				if( $sExtension !== 'css' ) {
					throw new Exception( 'Invalid file extension (' . $sExtension . ') in ' . $sFile );
				}
			} else {
				$sFile .= '.css';
			}

			$sRealpath = realpath( $sFile );

			if( !$sRealpath ) {
				throw new Exception( 'File not found: ' . $sFile );
			}

			// May not be outside of current directory
			if( strrpos($sRealpath, $sCurrentDirectory, -strlen( $sRealpath ) ) === FALSE ) {
				throw new Exception( 'Access denied: ' . $sFile );
			}

			$sBuffer = file_get_contents( $sFile );

		} catch( Throwable $oThrowable ) {
			// Print exceptions on debug
			if( $GLOBALS['debug'] === true ) {
				// Not added to the buffer because it is stripped of comments at compression
				echo "\n\n/* Exception: " . $oThrowable->getMessage() . " */\n\n";
				// Output exception as a "status message" on top of the page
				echo "\n" . 'body:before { background: rgba(255,0,0,.75); border-bottom: 1px #fff solid; box-shadow: 0 0 .5em .25em rgba(0,0,0,.25); padding: 1em; position: fixed; top: 0; left: 0; right: 0; z-index: 10000; content: "CSS exception: ' . str_replace('"', "", $oThrowable->getMessage() ) . '"; }' . "\n";
			}

		} catch( Exception $oException ) {
			// Print exceptions on debug
			if( $GLOBALS['debug'] === true ) {
				// Not added to the buffer because it is stripped of comments at compression
				echo "\n\n/* Exception: " . $oException->getMessage() . " */\n\n";
				// Output exception as a "status message" on top of the page
				echo "\n" . 'body:before { background: rgba(255,0,0,.75); border-bottom: 1px #fff solid; box-shadow: 0 0 .5em .25em rgba(0,0,0,.25); padding: 1em; position: fixed; top: 0; left: 0; right: 0; z-index: 10000; content: "CSS exception: ' . str_replace('"', "", $oException->getMessage() ) . '"; }' . "\n";
			}
		}

		/**
		 * Our own compress buffer
	 	 */
		if( defined('COMPRESS_CSS') && COMPRESS_CSS === true && $sBuffer ) {
			$sBuffer = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $sBuffer );
			$sBuffer = str_replace( array("\r\n", "\r", "\n", "\t"), "", $sBuffer );
			$sBuffer = str_replace( array("; ", " {"), array(";", "{"), $sBuffer );
			$sBuffer = str_replace( array( "     ", "    ", "   ", "  " ), array(" "), $sBuffer ); // Remove "all" double spaces
		}

		echo $sBuffer;
	}
}

/**
 * If any modification dates was found
 */
if( !empty($aFileModificationTimes) ) {
	// Try to make browser update if needed
	header( 'ETag: ' . md5( ob_get_contents() ) );
	header( 'Last-Modified: ' . date("D, j M Y H:i:s T", max( $aFileModificationTimes )) );
}
