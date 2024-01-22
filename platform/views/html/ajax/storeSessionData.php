<?php

// Certain session variables can be altered via ajax
// sessionVariables is an json decoded array

if( empty($_GET['key']) || empty($_GET['value']) ) return;

$aAllowedKeys = array(
  'auctionListView',
  'auctionSelectedItem',
  'showDev'
);

if( !empty($_GET['referrer']) ) {
  $aUrlParts = explode( '/', $_GET['referrer'] );
  $iPartId = end( $aUrlParts );
  $_SESSION['browser']['auction'][ $iPartId ][ $_GET['key'] ] = $_GET['value'];

} else {
  if( in_array($_GET['key'], $aAllowedKeys) ) {
    $_SESSION['browser'][ $_GET['key'] ] = $_GET['value'];
  }
}
