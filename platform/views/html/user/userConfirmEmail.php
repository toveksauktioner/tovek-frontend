<?php

if( !isset($_SESSION['emailConfirmation']) ) {
	$oRouter->redirect( $oRouter->getPath( 'guestCustomerSignup' ) );
	return;
}

/**
 * Re-send confirm email
 */
if( !empty($_GET['reSend']) && !empty($_SESSION['emailConfirmationEmail']) ) {
	// User data
	$oUserManager = clRegistry::get( 'clUserManager' );
	$aUser = current( $oUserManager->readByEmail( $_SESSION['emailConfirmationEmail'], '*' ) );
	
	if( !empty($aUser) ) {
		// Generate code
		$sCode = md5( $aUser['userId'] . $aUser['username'] . $aUser['userPass'] . USER_PASS_SALT );
		
		$sMailHtmlOutput = '
			<p>' . _( 'Please click link below to confirm your e-post address' ) . '.</p>
			<p>' . sprintf( '<a href="%s%s%s?email=%s&code=%s">' . _( 'Confirm %s by clicking here' ) . '</a>', (SITE_PROTOCOL != 'http' ? 'https://' : 'http://'), SITE_DOMAIN, $oRouter->getPath('guestUserConfirmEmail'), $aUser['userEmail'], $sCode, $aUser['userEmail'] ) . '</p>';
			
		$oMailHandler = clRegistry::get( 'clMailHandler' );
		$oMailHandler->prepare( array(
			'title' => _( 'Confirm your e-post address' ),
			'content' => $sMailHtmlOutput,
			'to' => $aUser['userEmail']
		) );
		$oMailHandler->send();
		
	} else {	
		// Could not find any user
		
	}
}

/**
 * Confirmation
 */
if( !empty($_GET['email']) && !empty($_GET['code']) ) {
	// User data
	$oUserManager = clRegistry::get( 'clUserManager' );
	$aUser = current( $oUserManager->readByEmail( $_GET['email'], '*' ) );
	if( !empty($aUser) ) {
		// Generate check code
		$sCode = md5( $aUser['userId'] . $aUser['username'] . $aUser['userPass'] . USER_PASS_SALT );
		
		if( $sCode === $_GET['code'] ) {
			/**
			 * Code match check code
			 */
			
			/**
			 * Allow user to write to user table
			 */
			$oAcl = new clAcl();
			$oAcl->setAcl( array(
				'writeUser' => 'allow',
				'readUser' => 'allow'
			) );
			$oUserManager->setAcl( $oAcl );
			
			$oUserManager->update( $aUser['userId'], array(
				'userEmailConfirmed' => 'yes'
			) );
			$aErr = clErrorHandler::getValidationError( 'updateUser' );
			
			if( empty($aErr) ) {
				unset( $_SESSION['emailConfirmationEmail'] );
				$_SESSION['emailConfirmation'] = true;
				
				$oUser = clRegistry::get( 'clUser' );
				$oUser->loginInit( $aUser['userId'] );
				$oRouter->redirect( $oRouter->getPath( 'userCustomerAccount' ) );
			}
			
		} else {
			/**
			 * Code did not match check code
			 */
			
		}
	}
}

$sOutput = '';

/**
 * Email still to be confirmed
 */
if( $_SESSION['emailConfirmation'] === false ) {
	$sOutput = '<p>' . _( 'An email has been sent to you for confirmation of your email address.' ) . '</p>';
	
	if( !empty($_SESSION['emailConfirmationEmail']) ) {
		$sOutput .= '
			<div class="reSend">
				<p>&nbsp;</p>
				<p><em>' . _( 'No email? Click here to re-send it' ) . ':
				<a href="?reSend=true">' . _( 'Re-send confirm email' ) . '</a></em></p>
			</div>';
	}
}	

/**
 * Email already confirmed
 */
if( $_SESSION['emailConfirmation'] === true ) {
	unset( $_SESSION['emailConfirmation'] );
	$oRouter->redirect( $oRouter->getPath( 'guestStartpage' ) );
	return;
}

echo '
	<div class="view user confirmEmail">
		<h1>' . _( 'Email confirmation page' ) . '</h1>
		' . $sOutput . '
	</div>';