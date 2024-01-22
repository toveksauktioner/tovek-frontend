<?php

$oContact = clRegistry::get( 'clContact', PATH_MODULE . '/contact/models' );
$aContactSettings = current( $oContact->read( '*', 1 ) ); // Read settings for contact form 1

$aErr = array();

$aDataDict = array(
	'entContactByPhone' => array(
		'contactPhone' => array(
			'title' => _( 'Phone' ),
			'type' => 'string',
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'phone'
			)
		),
		'entContactByPhone' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
);

// Post
if( !empty($_POST['entContactByPhone']) && $_POST['entContactByPhone'] == 'true' ) {
	// reCaptcha validation
	//$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	//$bValidate = $oOutputHtmlForm->validateReCaptcha( $_POST['g-recaptcha-response'] );
	//if( $bValidate == false ) {
	//	$aErr[ _( 'Validate' ) ] = _( 'Failed' );
	//}
	
	$_POST = array_intersect_key( $_POST, $aDataDict['entContactByPhone']  );
	$aErr = clDataValidation::validate( $_POST, $aDataDict );
	
	if( empty($aErr) ) {
		$oHtmlPurier = clRegistry::get( 'clHtmlPurifier', PATH_CORE . '/htmlpurifier/' );
		$oHtmlPurier->setConfig( array(
			'allowed' => 'em,strong'
		) );

		$sSubject = _( 'Contact form at' ) . ' ' . SITE_DOMAIN;

		$sBodyHtml = '';
		foreach( $_POST as $key => $value ) {
			if( $aDataDict['entContactByPhone'][$key]['type'] == 'hidden' ) continue; // Skip hidden fields

			$sBodyHtml .= '<strong>' . ( array_key_exists( 'title', $aDataDict['entContactByPhone'][$key] ) ? $aDataDict['entContactByPhone'][$key]['title'] : $key ) . ':</strong> ' . $oHtmlPurier->purify($value) . "\n";
		}

		$oMailHandler = clRegistry::get( 'clMailHandler' );
		$oMailHandler->prepare( array(
			'title' => $sSubject,
			'content' => nl2br( $sBodyHtml )
		) );

		if( $oMailHandler->send() ) {
			$oTemplate->addBottom( array(
				'key' => 'jsAlertContactFormSent',
				'content' => '
				<script>
					alert("' . $aContactSettings['contactSubmitMessageTextId'] . '");
				</script>'
			) );
		}

		$_POST = array();
	} else {
		clErrorHandler::setValidationError( $aErr );
		$aErr = clErrorHandler::getValidationError( 'entContactByPhone' );
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => $oRouter->sPath . '?' . http_build_query( array( 'submitted' => 'formContactByPhone' ) + $_GET ) . '#formContactByPhone' , // On submit, return to current path with a get variables and scroll back to this form
	'errors' => $aErr,
	'attributes' => array(
		'class' => 'marginal',
		'id' => 'formContactByPhone',
	),
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Call me' )
	),
	'labelSuffix' => ':',
	'labelRequiredSuffix' => '*'
) );

echo '
	<div class="view contact formContactByPhone">
		<h2>' . _( 'Do you want to be called?' ) . '</h2>
		<p>' . _( 'Enter your phone number and we\'ll call you' ) . '</p>
		' . $oOutputHtmlForm->render() . '
	</div>';
