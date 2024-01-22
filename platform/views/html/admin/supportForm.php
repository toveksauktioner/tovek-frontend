<?php

$aErr = array();
$sErrorMessages = '';

require_once PATH_FUNCTION . '/fData.php';

$aFormDataDict = array(
	'entSupport' => array(
		'supportName' => array(
			'type' => 'string',
			'title' => _( 'Name' )
		),
		'supportEmail' => array(
			'type' => 'string',
			'title' => _( 'E-Mail' ),
			'extraValidation' => array(
				'email'
			)
		),
		'supportPhone' => array(
			'type' => 'string',
			'title' => _( 'Phone' ),
		),
		'supportSubject' => array(
			'type' => 'string',
			'title' => _( 'Subject' )
		),
		'supportMessage' => array(
			'type' => 'string',
			'title' => _( 'Message' ),
			'appearance' => 'full',
			'required' => true
		),
		'supportErrorMessage' => array(
			'type' => 'string',
			'title' => _( 'Error message' ),
			'appearance' => 'full'
		),
		'supportRemoteAddr' => array(
			'type' => 'hidden',
			'value' => getRemoteAddr()
		),
		'supportWebBrowser' => array(
			'type' => 'hidden',
			'value' => $_SERVER['HTTP_USER_AGENT']
		),
		'supportSiteDomain' => array(
			'type' => 'hidden',
			'value' => SITE_DOMAIN
		),
		'supportRoutePath' => array(
			'type' => 'hidden',
			'value' => $oRouter->sPath
		),
		'frmSupport' => array(
			'type' => 'hidden',
			'value' => 1
		)
	)
);

if( !empty($_POST['frmSupport']) ) {
	$_POST = array_intersect_key($_POST, $aFormDataDict['entSupport']);
	$aValidationErr = clDataValidation::validate( $_POST, $aFormDataDict );

	if( empty($aValidationErr) ) {
		$message  =
			_( 'Name' )				. ': ' . $_POST['supportName'] . '<br />' .
			_( 'E-Mail' )			. ': ' . $_POST['supportEmail'] . '<br />' .
			_( 'Phone' )			. ': ' . $_POST['supportPhone'] . '<br />' .
			_( 'Subject' )			. ': ' . $_POST['supportSubject'] . '<br />' .
			_( 'Message' )			. ': ' . $_POST['supportMessage'] . '<br />' .
			_( 'Error message' )	. ': ' . $_POST['supportErrorMessage'] . '<br />' .
			'<p>Misc data:</p> ' 	.
			_( 'Site domain' )		. ': ' . $_POST['supportSiteDomain'] . '<br />' .
			_( 'Route path' )		. ': ' . $_POST['supportRoutePath'] . '<br />' .
			_( 'Browser/OS' )		. ': ' . $_POST['supportWebBrowser'] . '<br />' .
			_( 'IP' )				. ': ' . $_POST['supportRemoteAddr'] . '<br />' . '
			';

		$oMail = clRegistry::get( 'clMail' );
		$oMail->addTo( 'support@argonova.se' )
			  ->setSubject( _( 'Support' ) . ': ' . SITE_DOMAIN )
			  ->setBodyHtml( $message );

		if( $oMail->send() ){
			$_POST = null;
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'entSupport' => _( 'The form was sent!' )
			) );
		}
	} else {
		clErrorHandler::setValidationError( $aValidationErr );
		$aErr = clErrorHandler::getValidationError( 'entSupport' );
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'action' => $oRouter->getPath('adminSupportForm'),
	'attributes' => array( 'class' => 'marginal support' ),
	'errors' => $aErr,
	'data' => $_POST,
	'labelSuffix' => '',
	'method' => 'post',
	'jsValidation' => true,
	'buttons' => array(
		'submit' => _( 'Send' )
	)
) );

echo '
<div class="view formSupport">
	<section>
		<h2>Support</h2>
		' . $oOutputHtmlForm->render() . '
	</section>
</div>';