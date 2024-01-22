<?php

if( empty($_GET['amount']) ) return;

$oFinancing = clRegistry::get( 'clFinancingWasaKredit', PATH_MODULE . '/financing/models' );

echo $oFinancing->apiGetMonthlyCostWidget( $_GET['amount'] );
