<?php

$oDataValidation = clRegistry::get( 'clDataValidation' );

// Filter the email address beforehand
$_GET['email'] = filter_var( $_GET['email'], FILTER_SANITIZE_EMAIL );

$aResult = array(
  'filteredEmail' => $_GET['email'],
  'result' => (bool) $oDataValidation->isEmail( $_GET['email'] )
);

if( !$aResult['result'] ) {
  $aResult['reason'] = _( 'Email' ) . ' ' . _( 'is not valid' );
}

echo json_encode( $aResult );
