<?php

/*** Check´s if the request is made by ajax ***/
if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) return;

/*** Check if item ID is given ***/	
if( empty($_GET['itemId']) ) return;
    
/**
 * Output item time
 */

$aItemIds = explode( ',', $_GET['itemId'] );

$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

// Data
$aItemData = $oAuctionEngine->readAuctionItem( array(
    'fields' => array('itemId','itemEndTime'),
    'itemId' => $aItemIds,
    'status' => '*'
) );
$aData = arrayToSingle( $aItemData, 'itemId', 'itemEndTime' );
// (old) $aData = arrayToSingle( $oAuctionEngine->readAuctionItem( array('itemId','itemEndTime'), null ), 'itemId', 'itemEndTime' );

if( $_GET['type'] == 'clock' && !empty($aData) ) {
    foreach( $aData as $key => $value ) {
        $aData[$key] = (strtotime( $value ) - strtotime( date('Y-m-d H:i:s') )) . '000';
    }			
}

if( $_GET['type'] == 'date' && !empty($aData) ) {
    foreach( $aData as $key => $value ) {
        $aData[$key] = convertTime( $value, $key );
    }		
}

echo json_encode( $aData );