<?php

// Callback from Wasa Kredit API

clFactory::loadClassFile( 'clLogger' );
$sLogFile = 'financing-wasakreditCallback.log';

$sLogType = 'ALL';  // ERR | NONE | ALL

$iResponseCode = http_response_code();
$aResponseBody = json_decode( file_get_contents('php://input'), true );

if( $iResponseCode == 200 ) {
  // Handle successful ping back

  if( $sLogType == 'ALL' ) {
    clLogger::log( '------------------------------------', $sLogFile );
    clLogger::log( 'Response Code: ' . $iResponseCode, $sLogFile );
    clLogger::log( 'Response Body: ' . print_r($aResponseBody, true), $sLogFile );
  }

  // Nothing more to do than redirect to current page
  if( empty($_SESSION['userId']) ) {
    $aPathInfo = $oRouter->getPath( 'guestUserSignup' );

  } else if( !empty($_SESSION['browser']['history']) ) {
    $aPathInfo = end( $_SESSION['browser']['history'] );
    $sPath = $aPathInfo['url'] . ( !empty($aPathInfo['query']) ? '?' . $aPathInfo['query'] : '' );

  } else {
    $sPath = '/';
  }

  $oRouter->redirect( $sPath );
  exit;

} else {
  // Log errors

  if( $sLogType == 'ERR' ) {
    clLogger::log( '------------------------------------', $sLogFile );
    clLogger::log( 'Response Code: ' . $iResponseCode, $sLogFile );
    clLogger::log( 'Response Body: ' . print_r($aResponseBody, true), $sLogFile );
  }
}
