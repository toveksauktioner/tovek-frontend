<?php

$oPaymentPayer = clRegistry::get( 'clPaymentPayer', PATH_MODULE . '/payment/models' );
 
if( $oPaymentPayer->validateIpAddress() ) {
    if( $oPaymentPayer->validateCallback() ) {
        die("TRUE");
    }
}
die("FALSE");