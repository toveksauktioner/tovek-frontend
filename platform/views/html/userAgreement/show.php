<?php


$oUserAgreement = clRegistry::get( 'clUserAgreement', PATH_MODULE . '/userAgreement/models' );

$oUserAgreement->oDao->setLang( 1 );
$aUserAgreementSwe = $oUserAgreement->readCurrent();

$oUserAgreement->oDao->setLang( 2 );
$aUserAgreementEng = $oUserAgreement->readCurrent();

echo '
	<div class="view userAgreement show">
		<div id="agreementLangSelect" class="tabs">
			<a href="#" id="agreementLangSwe" class="tab selected">Svenska</a>
			<a href="#" id="agreementLangEng" class="tab">English</a>
		</div>
		<div id="agreementSwe">
			<h1>' . $aUserAgreementSwe['agreementTitleTextId'] . '</h1>
			<em>' . substr( $aUserAgreementSwe['agreementCreated'], 0, 10 ) . '</em>
			' . $aUserAgreementSwe['agreementContentTextId'] . '
		</div>
		<div id="agreementEng" style="display: none;">
			<h1>' . $aUserAgreementEng['agreementTitleTextId'] . '</h1>
			<em>' . substr( $aUserAgreementEng['agreementCreated'], 0, 10 ) . '</em>
			' . $aUserAgreementEng['agreementContentTextId'] . '
		</div>
	</div>
	<script>
		$("#agreementLangSwe").click( function() {
			$( this ).addClass( "selected" );
			$("#agreementLangEng").removeClass( "selected" );
			$("#agreementSwe").show();
			$("#agreementEng").hide();
		} );
		$("#agreementLangEng").click( function() {
			$( this ).addClass( "selected" );
			$("#agreementLangSwe").removeClass( "selected" );
			$("#agreementEng").show();
			$("#agreementSwe").hide();
		} );
	</script>';
