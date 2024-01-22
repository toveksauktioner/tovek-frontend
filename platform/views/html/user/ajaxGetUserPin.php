<?php

/*** Check´s if the request is made by ajax ***/
if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
	$oRouter->redirect( '/' );
}

$oDataValidation = clRegistry::get( 'clDataValidation' );

$bConfirmed = false;

if( $_GET['infoCountry'] == 210 ) {
	// Checks for swedish registrars

	// Auto correct user pin 
	$bReplaced = $_GET['userPin'];
	$_GET['userPin'] = preg_replace( REGEX_USERNAME, '', $_GET['userPin'] );
	$_GET['userPin'] = ( (strlen($_GET['userPin']) == 12) ? substr($_GET['userPin'], 2) : $_GET['userPin'] );
	$bReplaced = ( $bReplaced != $_GET['userPin'] );

	if( $_GET['userType'] == 'privatePerson' ) {
		// Private persons need an unique and valid pin

		$oUserManager = clRegistry::get( 'clUserManager' );
		$oUserManager->oDao->setCriterias( array(
			'userType' => array(
				'type' => '=',
				'fields' => 'userType',
				'value' => $_GET['userType']
			),
			'userPin' => array(
				'type' => '=',
				'fields' => 'userPin',
				'value' => $_GET['userPin']
			)
		) );
		$aData = $oUserManager->read( array('userPin') );

		$bConfirmed = $oDataValidation::isPin( $_GET['userPin'] );

	} else {
		// Private persons need an valid pin
		$bConfirmed = $oDataValidation::isCompanyPin( $_GET['userPin'] );
	}

} else {
	// Non swedish registrars have no checks

	$bConfirmed = true;
}

	$bConfirmed = true;

$aResultData = [];

if( $bConfirmed === false ) {
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
		'corrected' => $_GET['userPin']
	];

} else {
	$aResultData = [
		'result' => 'success'
	];
}

echo json_encode( $aResultData );
die();
