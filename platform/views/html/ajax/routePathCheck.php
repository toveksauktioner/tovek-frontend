<?php

if( !empty($_GET['routePath']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {

	$aData = $oRouter->oDao->readRouteByPath( $_GET['routePath'] );
	
	if( !empty($aData) ) {
		echo json_encode( $aData );		
	}

	die();
	
}