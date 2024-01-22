<?php

$oPaymentPaysonCheckout = clRegistry::get( 'clPaymentPaysonCheckout', PATH_MODULE . '/payment/models' );

echo '
	<div class="view paysonCheckoutConfirmation">
		' . $oPaymentPaysonCheckout->getCheckoutConfirmation() . '
	</div>';
