<?php

$oPaymentKlarnaCheckout = clRegistry::get( 'clPaymentKlarnaCheckout', PATH_MODULE . '/payment/models' );

echo '
	<div class="view klarnaCheckoutConfirmation">
		' . $oPaymentKlarnaCheckout->getCheckoutConfirmation() . '
	</div>';
