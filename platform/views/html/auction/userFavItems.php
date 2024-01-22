<?php

if( !empty($_GET['ajax']) && empty($_GET['userId']) ) return;

$oAuctionItem = clRegistry::get( 'clAuctionItem', PATH_MODULE . '/auction/models' );

// Favorites toggle 
if( !empty($_GET['frmSetFav']) && !empty($_GET['itemId']) ) {
	$bStatus = ( empty($_GET['selected']) );	// Invert the current status
	$oAuctionItem->updateFavoriteItem( $_GET['itemId'], $_GET['userId'], $bStatus );
	echo json_encode( [
		'status' => ( $bStatus ? 'selected' : 'unselected' )
	] );
}


// Get user favorites 
if( !empty($_GET['frmGetFav']) ) {
	$aFavorites = arrayToSingle( $oAuctionItem->readFavoritesByUser($_GET['userId']), null, 'itemId' );
	echo json_encode( [ 
		'favorites' => $aFavorites
	] );
}