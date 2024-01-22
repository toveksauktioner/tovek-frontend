<?php

// This view is added to layouts that should be logged in history for back buttons and such
// Store this page view in session

if( empty($_SESSION['browser']['history']) ) $_SESSION['browser']['history'] = array();

$aLastHistory = end( $_SESSION['browser']['history'] );

$sThisHistory = array(
  'url' => $oRouter->sPath,
  'query' => http_build_query( $_GET )
);

if( empty($aLastHistory) || !empty(array_diff($aLastHistory, $sThisHistory)) ) {
  $_SESSION['browser']['history'][] = $sThisHistory;
}
