<?php

$oUserAgreement = clRegistry::get( 'clUserAgreement', PATH_MODULE . '/userAgreement/models' );

$iAgreementId = current( $oUserAgreement->readCurrent('agreementId') );

echo '
	<div class="agreementAcceptButton">
		<a href="?userAgreementAccept=' . $iAgreementId . '" class="icon iconText iconOk">Godkänn</a>
	</div>';
