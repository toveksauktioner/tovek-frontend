<?php

/**
 *
 * Admin type
 * 
 */
if( !isset($_SESSION['adminType']) ) {
    $_SESSION['adminType'] = 'new';
}

$sGroupKey = array_key_exists( 'super', $oUser->aGroups ) ? 'super' : ( array_key_exists('admin', $oUser->aGroups) ? 'admin' : 'user' );

if( in_array($sGroupKey, array('super','admin')) ) {
    $oRouter->redirect( 'https://tovek.se/admin' );
} else {
    $oRouter->redirect( 'https://tovek.se/admin/login' );
}

exit;