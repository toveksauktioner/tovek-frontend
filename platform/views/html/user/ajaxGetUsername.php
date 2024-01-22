<?php

/*** Check´s if the request is made by ajax ***/
if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
	$oRouter->redirect( '/' );
}

// Auto correct username 
$bReplaced = $_GET['username'];
$_GET['username'] = preg_replace( REGEX_USERNAME, '', $_GET['username'] );
$bReplaced = ( $bReplaced != $_GET['username'] );


$oUserManager = clRegistry::get( 'clUserManager' );
$oUserManager->oDao->setCriterias( array(
	'username' => array(
		'type' => '=',
		'fields' => 'username',
		'value' => $_GET['username']
	)
) );
$aData = $oUserManager->read( array('username') );

$bDenied = false;
foreach( $GLOBALS['denyUsernamesIncluding'] as $sDeniedValue ) {
	if( stristr($_GET['username'], $sDeniedValue) ) {
		$bDenied = true;
	}
}

if( preg_match(REGEX_USERNAME, $_GET['username']) ) {
	$bDenied = true;
}

$aResultData = [];

if( $bDenied === true ) {
	$aResultData = [
		'result' => 'failure',
		'reason' => 'denied'
	];

} else if( !empty($aData) ) {
	$aResultData = [
		'result' => 'failure',
		'reason' => 'exists'
	];

} else if( $bReplaced) {
	$aResultData = [
		'result' => 'replaced',
		'reason' => 'nonvalidchars',
		'corrected' => $_GET['username']
	];

} else {
	$aResultData = [
		'result' => 'success'
	];
}

echo json_encode( $aResultData );
die();