<?php

if( empty($_POST['logLabel']) || empty($_POST['logData']) ) return;

$oLogger = clRegistry::get( 'clLogger' );

$aLogData = $_POST + array(
  'logCreated' => date( 'Y-m-d H:i:s' )
);
$oLogger->create( $aLogData );
