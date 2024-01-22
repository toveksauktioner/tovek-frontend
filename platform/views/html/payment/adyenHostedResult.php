<?php

// adyenHostedResult

if( empty($_REQUEST) ) {
    $oRouter->redirect( $oRouter->getPath( 'guestCheckout' ) );
}

$oPaymentAdyen = clRegistry::get( 'clPaymentAdyenHosted', PATH_MODULE . '/payment/models' );
if( $oPaymentAdyen->finalizeOrder( $_REQUEST ) ) {
    $oRouter->redirect( $oRouter->getPath( 'userOrderReceipt' ) );
}

$oRouter->redirect( $oRouter->getPath( 'guestCheckout' ) );