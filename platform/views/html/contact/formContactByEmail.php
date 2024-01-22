<?php

$oContact = clRegistry::get( 'clContact', PATH_MODULE . '/contact/models' );
$aContactSettings = current( $oContact->read( '*', 1 ) ); // Read settings for contact form 1

$aErr = array();

$aDataDict = array(
	'entContactByEmail' => array(
		'contactName' => array(
			'title' => _( 'Name' ),
			'type' => 'string',
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'name'
			)
		),
		'contactEmail' => array(
			'title' => _( 'Your e-mail' ),
			'type' => 'string',
			'min' => 6,
			'max' => 320,
			'extraValidation' => array(
				'email'
			),
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'email'
			)
		),
		'entContactByEmail' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
);

// Post
if( !empty($_POST['entContactByEmail']) && $_POST['entContactByEmail'] == 'true' ) {
	// reCaptcha validation
	//$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	//$bValidate = $oOutputHtmlForm->validateReCaptcha( $_POST['g-recaptcha-response'] );
	//if( $bValidate == false ) {
	//	$aErr[ _( 'Validate' ) ] = _( 'Failed' );
	//}
	
	$_POST = array_intersect_key( $_POST, $aDataDict['entContactByEmail']  );
	$aErr = clDataValidation::validate( $_POST, $aDataDict );
	
	if( empty($aErr) ) {
		$oHtmlPurier = clRegistry::get( 'clHtmlPurifier', PATH_CORE . '/htmlpurifier/' );
		$oHtmlPurier->setConfig( array(
			'allowed' => 'em,strong'
		) );

		$sSubject = _( 'Contact form at' ) . ' ' . SITE_DOMAIN;

		$sBodyHtml = '';
		foreach( $_POST as $key => $value ) {
			if( $aDataDict['entContactByEmail'][$key]['type'] == 'hidden' ) continue; // Skip hidden fields

			$sBodyHtml .= '<strong>' . ( array_key_exists( 'title', $aDataDict['entContactByEmail'][$key] ) ? $aDataDict['entContactByEmail'][$key]['title'] : $key ) . ':</strong> ' . $oHtmlPurier->purify($value) . "\n";
		}

		$oMailHandler = clRegistry::get( 'clMailHandler' );
		$oMailHandler->prepare( array(
			'title' => $sSubject,
			'content' => nl2br( $sBodyHtml ),
			'replyTo' => $_POST['contactEmail']
		) );

		if( $oMailHandler->send() ) {
			$_GET['formContactByEmail'] = 'submitted'; // For Google analytics tracking etc.
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
		$aErr = clErrorHandler::getValidationError( 'entContactByEmail' );
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => $oRouter->sPath . '?' . http_build_query( array( 'submitted' => 'formContactByEmail' ) + $_GET ) . '#formContactByEmail' , // On submit, return to current path with a get variables and scroll back to this form
	'errors' => $aErr,
	'attributes' => array(
		'class' => 'marginal',
		'id' => 'formContactByEmail',
	),
	'method' => 'post',
	'buttons' => array(
		'submit' => $aContactSettings['contactButtonTextId']
	),
	'labelSuffix' => ':',
	'labelRequiredSuffix' => '*'
) );

echo '
	<div class="view contact formContactByEmail">
		' . $oOutputHtmlForm->render() . '
	</div>';
