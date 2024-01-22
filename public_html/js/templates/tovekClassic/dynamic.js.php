<?php

require_once realpath( dirname(dirname(dirname(dirname(dirname(__FILE__))))) ) . '/platform/core/bootstrap.php';
require_once PATH_MODULE . '/auction/config/cfAuction.php';
 
/**
 * IP handling
 */
$aIpAllowed = array(
	'213.88.134.199'
);
$sClient = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

$sOutput = '';

/**
 * Debug
 */
if( in_array($sClient, $aIpAllowed) ) {
	$sOutput .= '
		function dynamicCheck( sInput ) {
			console.log( sInput );
		}';
		
} else {
	$sOutput .= '
		function dynamicCheck( sInput ) {}';
}

/**
 * Auction timmer check
 */
//$sOutput .= '
//	var aBidTimmerCheck = null;';
//	
//if( !empty($GLOBALS['additionalBidControl']) ) {
//	$sOutput .= '
//		aBidTimmerCheck = [' . implode(',', $GLOBALS['additionalBidControl']) . '];';
//}
//
//$sOutput .= '
//	function bidTimmerCheck( iTime ) {
//		if( aBidTimmerCheck == null ) return false;
//		
//		var iSeconds = (iTime / 1000);
//		
//		var iCheck = aBidTimmerCheck.indexOf( Math.floor(iSeconds) );
//		if( iCheck >= 0 ) return true;
//		else return false;
//	}';

// Compress
$sOutput = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $sOutput );
$sOutput = str_replace( array("\r\n", "\n"), " ", $sOutput );
$sOutput = str_replace( array("\t"), "", $sOutput );

echo $sOutput;
