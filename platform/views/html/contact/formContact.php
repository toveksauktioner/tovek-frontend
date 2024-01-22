<?php

$oContact = clRegistry::get( 'clContact', PATH_MODULE . '/contact/models' );
$aContactSettings = current( $oContact->read( '*', 1 ) ); // Read settings for contact form 1

$aErr = array();

$aDataDict = array(
	'entContact' => array(
		'contactName' => array(
			'title' => _( 'Name' ),
			'type' => 'string',
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'name'
			)
		),
		'contactPhone' => array(
			'title' => _( 'Phone' ),
			'type' => 'string',
			'fieldAttributes' => array(
				'class' => 'phone'
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
		'contactMessage' => array(
			'title' => _( 'Message' ),
			'type' => 'string',
			'appearance' => 'full',
			'attributes' => array(
				'rows' => 3,
				'cols' => 20
			),
			'required' => true,
			'fieldAttributes' => array(
				'class' => 'message'
			)
		),
		//'contactFile' => array( # contactFile[1] (multi)
		//	'title' => _( 'File' ),
		//	'type' => 'upload',
		//	'fieldAttributes' => array(
		//		'class' => 'uploadFile'
		//	)
		//),
		'frmContact' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
);

// Post
if( !empty($_POST['frmContact']) && $_POST['frmContact'] == 'true' ) {
	// reCaptcha validation
	//$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	//$bValidate = $oOutputHtmlForm->validateReCaptcha( $_POST['g-recaptcha-response'] );
	//if( $bValidate == false ) {
	//	$aErr[ _( 'Validate' ) ] = _( 'Failed' );
	//}
	
	$_POST = array_intersect_key( $_POST, $aDataDict['entContact']  );
	$aErr = clDataValidation::validate( $_POST, $aDataDict );
	if( empty($aErr) ) {
		$oHtmlPurier = clRegistry::get( 'clHtmlPurifier', PATH_CORE . '/htmlpurifier/' );
		$oHtmlPurier->setConfig( array(
			'allowed' => 'em,strong'
		) );

		$sSubject = _( 'Contact form at' ) . ' ' . SITE_DOMAIN;

		$sBodyHtml = '';
		foreach( $_POST as $key => $value ) {
			if( $aDataDict['entContact'][$key]['type'] == 'hidden' ) continue; // Skip hidden fields

			$sBodyHtml .= '<strong>' . ( array_key_exists( 'title', $aDataDict['entContact'][$key] ) ? $aDataDict['entContact'][$key]['title'] : $key ) . ':</strong> ' . $oHtmlPurier->purify($value) . "\n";
		}

		$aMailParams = array(
			'title' => $sSubject,
			'content' => nl2br( $sBodyHtml ),
			'replyTo' => $_POST['contactEmail']
		);

		// Single file
		//if( !empty($_FILES['contactFile']['tmp_name']) && $_FILES['contactFile']['error'] == UPLOAD_ERR_OK ) {
		//	$aMailParams['attachments'] = array( array(
		//		'name' => $_FILES['contactFile']['name'],
		//		'path' => $_FILES['contactFile']['tmp_name'],
		//		'content' => file_get_contents( $_FILES['contactFile']['tmp_name'] )
		//	) );
		//}
		// Multi files
		//if( !empty($_FILES['contactFile']) ) {
		//	$aMailParams['attachments'] = array();
		//	foreach( $_FILES['contactFile']['error'] as $iKey => $iError ) {
		//		if( $iError == UPLOAD_ERR_OK ) {
		//			$aMailParams['attachments'][] = array(
		//				'name' => $_FILES['contactFile']['name'][ $iKey ],
		//				'path' => $_FILES['contactFile']['tmp_name'][ $iKey ],
		//				'content' => file_get_contents( $_FILES['contactFile']['tmp_name'][ $iKey ] ),
		//				'type' => $_FILES['contactFile']['type'][ $iKey ]
		//			);
		//		}
		//	}
		//}

		$oMailHandler = clRegistry::get( 'clMailHandler' );
		$oMailHandler->prepare( $aMailParams );

		if( $oMailHandler->send() ) {
			$oTemplate->addBottom( array(
				'key' => 'jsAlertContactFormSent',
				'content' => '
				<script>
					alert("' . $aContactSettings['contactSubmitMessageTextId'] . '");
				</script>'
			) );

			//$oNotification->set( array( 'dataSaved' => _('Your message was sent!') ) );
		}

		$_POST = array();
	} else {
		clErrorHandler::setValidationError( $aErr );
		$aErr = clErrorHandler::getValidationError( 'entContact' );
		//$oTemplate->addBottom( array(
		//	'key' => 'scrollToError',
		//	'content' => '
		//	<script>
		//		$(document).ready( function() {
		//			$("html, body").animate({
		//				scrollTop: $("form .result.error").offset().top
		//			}, 1000);
		//		} );
		//	</script>'
		//) );
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => $oRouter->sPath . '?' . http_build_query( array( 'submitted' => 'formContact' ) + $_GET ) . '#formContact' , // On submit, return to current path with a get variables and scroll back to this form
	'errors' => $aErr,
	'attributes' => array(
		'class' => 'marginal',
		'id' => 'formContact',
	),
	'method' => 'post',
	'buttons' => array(
		'submit' => $aContactSettings['contactButtonTextId']
	),
	'labelSuffix' => ':',
	'labelRequiredSuffix' => '*',
	'recaptcha' => true
) );

echo '
	<div class="view formContact">
		' . $oOutputHtmlForm->render() . '
	</div>';

// For label in input
//$oTemplate->addScript( array(
//	'key' => 'jsDefaultValue',
//	'src' => '/js/jquery.defaultValue.js'
//) );
//$oTemplate->addBottom( array(
//	'key' => 'jsDefaultValueScript',
//	'content' => '
//	<script>
//		$(".formContact form .field label").each( function() {
//			if( $( ".formContact form  #" + $(this).attr("for") ).val() == "" ) {
//				$( ".formContact form  #" + $(this).attr("for") ).defaultvalue( $(this).text() );
//			}
//		} );
//		$(".formContact form").submit( function() {
//			$(".formContact form .field label").each( function() {
//				if( $(this).text() == $( ".formContact form #" + $(this).attr("for") ).val() ) {
//					$( ".formContact form  #" + $(this).attr("for") ).val("");
//				}
//			} );
//		} );
//	</script>'
//) );

// Word counter for text fields
//$oTemplate->addScript( array(
//	'key' => 'jsCounter',
//	'src' => '/js/jquery.counter-2.1.js'
//) );
//$oTemplate->addBottom( array(
//	'key' => 'counter',
//	'content' => '
//	<script>
//		$("#contactMessage").counter({
//			type: "char",
//			goal: 100,
//			count: "up",
//			msg : "' . _( 'characters' ) . ' (max 100)",
//		});
//	</script>'
//) );
