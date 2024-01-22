<?php

if( !empty($_POST['transact']) && !empty($_POST['orderid']) && !empty($_POST['md5key2']) ) {

	$oPaymentDibs = clRegistry::get( 'clPaymentDibsHosted', PATH_MODULE . '/payment/models' );
	$oPaymentDibs->finalizeOrder( $_POST['orderid'] );

}