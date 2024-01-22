<?php

// Auction short url redirect
// Redirect to appropriate route


$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

if( !empty($_GET['a']) ) {
  if( stristr($_GET['a'], '-') ) {
    list( $iAuctionId, $iPartId ) = explode( '-', $_GET['a'] );
  } else {
    $iAuctionId = $_GET['a'];
    $iPartId = null;
  }

  $aAuctionData = current( $oAuctionEngine->readAuction(array(
    'fields' => array(
      'auctionId',
      'partId',
      'routePath'
     ),
     'auctionId' => $iAuctionId,
     'partId' => $iPartId,
     'auctionStatus' => '*',
     'partStatus' => '*'
   )) );

  if( !empty($aAuctionData['routePath']) ) {
    header( "Location: " . $aAuctionData['routePath'] );
  } else {
    header( "Location: /" );
  }
}
