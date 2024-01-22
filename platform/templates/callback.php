<?php

if( $GLOBALS['logCallbacks'] === true ) {
	/**
	 * Log callback data
	 */
	$aData = array(
		'post' => $_POST,
		'get' => $_GET
	);
	clFactory::loadClassFile( 'clLogger' );
	clLogger::log( $aData, 'callback.log' );
}

echo trim( $sContent );

?>
