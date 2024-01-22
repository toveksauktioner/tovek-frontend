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

if( !empty($_GET['frmContact']) && $_GET['frmContact'] == 'true' ) {

	$aReponse = array(
		'status' => '',
		'message' => '',
		'error' => ''
	);

	$_GET = array_intersect_key( $_GET, $aDataDict['entContact']  );
	$aErr = clDataValidation::validate( $_GET, $aDataDict );
	if( empty($aErr) ) {
		$oHtmlPurier = clRegistry::get( 'clHtmlPurifier', PATH_CORE . '/htmlpurifier/' );
		$oHtmlPurier->setConfig( array(
			'allowed' => 'em,strong'
		) );

		$sSubject = _( 'Contact form at' ) . ' ' . SITE_DOMAIN;

		$sBodyHtml = '';
		foreach( $_GET as $key => $value ) {
			if( $aDataDict['entContact'][$key]['type'] == 'hidden' ) continue; // Skip hidden fields

			$sBodyHtml .= '<strong>' . ( array_key_exists( 'title', $aDataDict['entContact'][$key] ) ? $aDataDict['entContact'][$key]['title'] : $key ) . ':</strong> ' . $oHtmlPurier->purify($value) . "\n";
		}

		$aMailParams = array(
			'title' => $sSubject,
			'content' => nl2br($sBodyHtml),
			'replyTo' => $_GET['contactEmail']
		);

		$oMailHandler = clRegistry::get( 'clMailHandler' );
		$oMailHandler->prepare( $aMailParams );

		// echo var_dump( $oMailHandler );
		// exit;

		if( $oMailHandler->send() ) {
			$aReponse['status'] = '200';
			$aReponse['message'] = html_entity_decode($aContactSettings['contactSubmitMessageTextId']);
		} else {
			$aReponse['status'] = '400';
			$aReponse['message'] = 'MailHandler->send() failed';
		}
		$_POST = array();
	} else {
		clErrorHandler::setValidationError( $aErr );
		$aErr = clErrorHandler::getValidationError( 'entContact' );
		$aReponse['error'] = $aErr;
	}
	echo json_encode( $aReponse );
	exit;
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => $oRouter->sPath,
	'errors' => $aErr,
	'attributes' => array(
		'class' => 'marginal',
		'id' => 'formContact',
	),
	'method' => 'get',
	'buttons' => array(
		'submit' => $aContactSettings['contactButtonTextId']
	),
	'labelSuffix' => ':',
	'labelRequiredSuffix' => '*',
	'recaptcha' => true
) );

echo '
	<div class="view formContact">
		<div id="response"></div>
		' . $oOutputHtmlForm->render() . '
	</div>';

$oTemplate->addBottom( array(
	'key' => 'ajaxPost',
	'content' => '
	<script>
	$("#formContact").submit( function(e) {
		e.preventDefault();
		var url = $("#formContact").attr("action");
		var method = $("#formContact").attr("method");
		$.ajax( {
			type: method,
			// url: url,
			url: "?ajax=true&view=contact/formContact.php",
			data: $("#formContact").serialize(),
			success: function(data) {
				console.log("Return: " + data );
				var obj = JSON.parse( data );
				
				$("#response").addClass( obj.status );
				$("#response").append( obj.message );
				$("#response").addClass("show");

			},
			fail: function() {}
		} );
	} );
	</script>'
) );