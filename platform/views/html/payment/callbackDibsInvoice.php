<?php

/*$sMessage = "Transact: " . $_POST['transact'] . " | OrderId: " . $_POST['orderid'] . " | MD5KEY2: " . $_POST['md5key2'];
mail("renfors@argonova.se", "DIBS Callback", $sMessage );*/

/*$aAdminIps = array(
	'kontoret' => '213.88.134.199'
);
$sIpAddress = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
if( in_array($sIpAddress, $aAdminIps) ) {
	$_POST = array(
		'transact' => '2586246535',
		'orderid' => 'IN-21715',
		'md5key2' => '6a46b5bf21d7f65b5355e422175b6dbb'
	);
	echo '<pre>';
	print_r( $_POST );
	echo '</pre>';
	$GLOBALS['debug'] = true;
}*/

if( !empty($_POST['transact']) && !empty($_POST['orderid']) && !empty($_POST['md5key2']) ) {
	$oPaymentDibs = clRegistry::get( 'clPaymentDibsHosted', PATH_MODULE . '/payment/models' );
	$oPaymentDibs->finalizeInvoiceOrder( $_POST['orderid'] );
}
