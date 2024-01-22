<?php

// Loads all css-files in same directory, or optional files via $_GET['fonts']

require_once dirname( dirname(dirname(dirname(__FILE__))) ) . '/platform/config/cfBase.php';
date_default_timezone_set( SITE_DEFAULT_TIMEZONE );

header( 'Content-Type: text/css; charset=UTF-8' );
header( 'Cache-Control: must-revalidate' );
header( 'Expires: ' . gmdate('D, d M Y H:i:s', time() + CACHE_CSS_TIME) . ' GMT' );

if( defined('COMPRESS_CSS') && COMPRESS_CSS === true && extension_loaded('zlib') && ini_get('zlib.output_compression') == 0 ) {
	ob_start('ob_gzhandler');
} else {
	ob_start();
}

if( !empty($_GET['fonts']) ) {
	$_GET['fonts'] = array_map( 'strtolower', (array) $_GET['fonts'] );
	$aFiles = scandir( dirname(__FILE__) );
	foreach( $aFiles as $key => $sFile ) {
		$sFile = strtolower($sFile);
		if( !in_array( mb_substr($sFile, 0, strrpos( $sFile, '.css' )) , $_GET['fonts'] ) ) unset( $aFiles[$key] );
	}
	
} else {
	$aFiles = scandir( dirname(__FILE__) );
}

if( !empty($aFiles) ) {
	$aFileModificationTimes = array();
	foreach( $aFiles as $sFile ) {
		if( strrchr($sFile, '.') !== '.css' ) continue;
		$aFileModificationTimes[$sFile]  = filemtime( $sFile );
		if( defined('COMPRESS_CSS') && COMPRESS_CSS === true ) {
			$sBuffer = file_get_contents( $sFile );
			$sBuffer = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $sBuffer );
			$sBuffer = str_replace( array("\r\n", "\r", "\n", "\t"), "", $sBuffer );
			$sBuffer = str_replace( array("; ", " {"), array(";", "{"), $sBuffer );
			$sBuffer = str_replace( array( "     ", "    ", "   ", "  " ), array(" "), $sBuffer ); // Remove "all" double spaces		
			echo $sBuffer;
		} else {
			readfile( $sFile );
			echo "\n";
		}
	}
	
	// Try to make browser update if needed
	header( 'ETag: ' . md5( ob_get_contents() ) );
	header( 'Last-Modified: ' . date("D, j M Y H:i:s T", max($aFileModificationTimes)) );
}