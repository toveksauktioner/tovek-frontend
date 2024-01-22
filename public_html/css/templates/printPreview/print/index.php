<?php
$iOffset = 3600 * 24 * 3;
header( 'Content-Type: text/css; charset=UTF-8' );
header( 'Cache-Control: must-revalidate' );
header( 'Expires: ' . gmdate('D, d M Y H:i:s', time() + $iOffset) . ' GMT' );

$aFiles = scanDir( '.' );
foreach( $aFiles as $sFile ) {
	$aExplodedByDot = explode(".", $sFile);
	if( end($aExplodedByDot) !== 'css' ) continue;
	readFile( $sFile );
	echo "\n";
}
