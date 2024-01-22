<?php

/**
 * guestUserPassRetrieval
 * /glömt-lösenord
 * userPassRetrieval - formRetrieval.php

 * guestUserPassRetrievalActivation
 * /glömt-lösenord/aktivering
 * userPassRetrieval - formActivation.php
 *
 * http://front.tovek.se/glömt-lösenord/aktivering?key=7c022f216c54c9ea14fdf61ac227028f
 */

$aErr = array();

$oUserPassRetrieval = clRegistry::get( 'clUserPassRetrieval', PATH_MODULE . '/userPassRetrieval/models' );

/**
 * POST
 */
if( !empty($_POST['frmUserPassRetrieval']) ) {
	if( empty($_POST['userEmail']) ) {
		$aErr['userEmail'] = _( 'Your email is invalid' );
	}
	
	if( !clDataValidation::isEmail($_POST['userEmail']) ) {
		$aErr['userEmail'] = _( 'Your email is invalid' );
	} else {
		$oUserManager = clRegistry::get( 'clUserManager' );		
		$iUserId = $oUserManager->readByEmail( $_POST['userEmail'], 'userId' );
		if( empty($iUserId) ) {
			$aErr['userEmail'] = _( 'The user doesn´t exists' ); 
		}
	}
	
	if( empty($aErr) ) {
		$iUserId = current( current($iUserId) );
		
		/**
		 * Temp access
		 */
		$oAcl = new clAcl();
		$oAcl->setAcl( array(
			'createUserPassRetrieval' => 'allow'
		) + $oUser->oAcl->aAcl );
		$oUserPassRetrieval->setAcl( $oAcl );
		
		// New random pass
		$sNewPass = generateRandomPass( 8 );
		$sActivationKey = $oUserPassRetrieval->createByUser( $iUserId, $sNewPass, $_POST['userEmail'] );
		
		if( $sActivationKey !== false ) {
			$sUsername = current( current($oUserManager->read('username', $iUserId)) );
			
			$oMailHandler = clRegistry::get( 'clMailHandler' );
			$oMailHandler->prepare( array(
				'to' => $_POST['userEmail'],
				'title' => _( 'You have requested a new password' ),
				'content' => sprintf( $GLOBALS['userPassRetrieval']['txtMail'], $sUsername, (SITE_PROTOCOL == 'http' ? 'http' : 'https') . '://' . SITE_DOMAIN . $oRouter->getPath( 'guestUserPassRetrievalActivation' ) . '?key=' . $sActivationKey ),
				'replyTo' => SITE_MAIL_FROM
			) );
			
			if( $oMailHandler->send() ) {
				$oNotification->set( array( 'userPassRetrieval' => _( 'We have sent an email (if the address exists) with further instructions' ) ) );
			}
		} else {
			$aErr = clErrorHandler::getValidationError( 'createUserPassRetrieval' );
		}
	}
}

/**
 * Form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( array(
	'entUserPassRetrieval' => array(
		'userEmail' => array(
			'type' => 'string',
			'title' => _( 'Email' )
		),
		'frmUserPassRetrieval' => array(
			'type' => 'hidden',
			'value' => true
		)
	)
), array(
	'action' => '',
	'attributes' => array( 'class' => 'marginal' ),
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Forgot password' )
	)
) );

echo '
	<div class="view userPassRetrieval formRetrieval">
		<h1>' . _( 'Forgot password' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
	</div>';
