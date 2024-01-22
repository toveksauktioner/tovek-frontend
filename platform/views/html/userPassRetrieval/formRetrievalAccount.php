<?php

// 32147
//if( !empty($_GET['Test']) ) {
//	$oCellsyntSms = clRegistry::get( 'clCellsyntSms', PATH_MODULE . '/cellsyntSms/models' );
//	$mResult = $oCellsyntSms->send( '', 'Test sms..' );
//	var_dump( $mResult ); die();
//}

$aErr = array();

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oNotification = clRegistry::get( 'clNotificationHandler' );

if( !isset($_SESSION['accountRetrieval']) ) {
	$_SESSION['accountRetrieval'] = array(
		'phone' => null,
		'email' =>  null,
		'status' => null,
		'code' => null
	);

	$GLOBALS['accountRetrieval'] = array(
		'code' => null,
		'codeStatus' => null
	);
} else {
	$GLOBALS['accountRetrieval'] = array(
		'code' => null,
		'codeStatus' => null
	);
}

/**
 * Post
 */
if( !empty($_POST) ) {
	/**
	 * Send code
	 */
	if( !empty($_POST['frmUserAccountRetrieval']) ) {
		// Validate reCAPTCHA
		$aErrRecaptcha = clDataValidation::validateRecaptcha( 'passRetrieval' );
		if( !empty($aErrRecaptcha) ) {
			$aErr[] = _( '"Jag är inte en robot" måste vara ikryssad' );
		}

		if( empty($aErr) ) {
			$oUserManager = clRegistry::get( 'clUserManager' );

			/**
			 * To email
			 */
			if( !empty($_POST['email']) ) {
				$aData = $oUserManager->readByEmail( $_POST['email'], '*' );

				if( count($aData) > 1 ) {				
					$bFoundUser = false;					
					if( empty($_POST['username']) ) {
						$aErr[] = _( 'You need to enter an username' );
					} else {						
						foreach( $aData as $aEntry ) {
							if( mb_strtolower($aEntry['username']) == mb_strtolower($_POST['username']) ) {								
								$aData = $aEntry;
								$bFoundUser = true;
								break;
							}
						}
					}
					if( $bFoundUser === false && !empty($_POST['username']) ) {
						$aErr[] = _( 'Unkown error' );
					}
				} else {
					$aData = current( $aData );
				}
				
				if( empty($aErr) && !empty($aData) ) {
					$iCode = hexdec( substr( sha1( $aData['infoCellPhone'] . $aData['userEmail'] . $aData['userPass'] . date( 'Y-m-d' ) . 'tovekSalt' ), 1, 4 ) );
					
					//$sUrl = 'https://' . SITE_DOMAIN . $oRouter->getPath( 'guestAccountRetrieval' ) . '?code=' . $sKey;

					$sSubject = _( 'Återställ konto hos tovek.se' );
					$sMessage = sprintf( _( 'Din kod är: %s' ), $iCode ) . "<br />
					\\ tovek.se";

					$oMail = clRegistry::get( 'clMail' );
					$oMail->setFrom( SITE_MAIL_FROM )
						  ->addTo( $aData['userEmail'] )
						  ->setReplyTo( SITE_MAIL_TO )
						  ->setSubject( $sSubject )
						  ->setBodyHtml( nl2br($sMessage) )
						  ->setBodyText( strip_tags($sMessage) );

					if( $oMail->send() ) {
						$_SESSION['accountRetrieval']['status'] = 'codeSent';
						$_SESSION['accountRetrieval']['email'] = $_POST['email'];
						$_SESSION['accountRetrieval']['username'] = $_POST['username'];
						$GLOBALS['accountRetrieval']['code'] = $iCode;

						$oNotification->set( array(
							'dataSaved' => sprintf( _( 'A code has been sent to: %s, please await it.' ), $_POST['email'] )
						) );
					}
				}
			/**
			 * To phone
			 */
			} elseif( !empty($_POST['phoneNumber']) ) {
				$aData = $oUserManager->readByPhone( $_POST['phoneNumber'], '*' );
				
				if( count($aData) > 1 ) {
					$bFoundUser = false;
					if( empty($_POST['username']) ) {
						$aErr[] = _( 'You need to enter an username' );
					} else {
						foreach( $aData as $aEntry ) {
							if( mb_strtolower($aEntry['username']) == mb_strtolower($_POST['username']) ) {
								$aData = $aEntry;
								$bFoundUser = true;
								break;
							}
						}
					}
					if( $bFoundUser === false && !empty($_POST['username']) ) {
						$aErr[] = _( 'Unkown error' );
					}
				} else {
					$aData = current( $aData );
				}

				if( empty($aErr) && !empty($aData) ) {
					$iCode = hexdec( substr( sha1( $aData['infoCellPhone'] . $aData['userEmail'] . $aData['userPass'] . date( 'Y-m-d' ) . 'tovekSalt' ), 1, 4 ) );
					//$sUrl = 'https://' . SITE_DOMAIN . $oRouter->getPath( 'guestAccountRetrieval' ) . '?code=' . $sKey;

					$sMessage = sprintf( _( 'Din kod är: %s' ), $iCode ) . "
	\\\ tovek.se";

					$oCellsyntSms = clRegistry::get( 'clCellsyntSms', PATH_MODULE . '/cellsyntSms/models' );
					$mResult = $oCellsyntSms->send( $_POST['phoneNumber'], $sMessage );
					if( $mResult != false ) {
						$_SESSION['accountRetrieval']['status'] = 'codeSent';
						$_SESSION['accountRetrieval']['phone'] = $_POST['phoneNumber'];
						$_SESSION['accountRetrieval']['username'] = $_POST['username'];
						$GLOBALS['accountRetrieval']['code'] = $iCode;

						$oNotification->set( array(
							'dataSaved' => sprintf( _( 'A code has been sent to: %s, please await it.' ), $_POST['phoneNumber'] )
						) );
					}
				}
			/**
			 * Post email code send
			 */
			} else {
				$aErr = _( 'You need to enter either an phone number or email' );
			}
		}
	}

	/**
	 * Code confirmation
	 */
	if( !empty($_POST['frmCodeConfirm']) ) {
		$oUserManager = clRegistry::get( 'clUserManager' );

		if( !empty($_SESSION['accountRetrieval']['phone']) ) {
			$aData = current( $oUserManager->oDao->read( array(
				'infoCellPhone' => $_SESSION['accountRetrieval']['phone'],
				'username' => !empty($_SESSION['accountRetrieval']['username']) ? $_SESSION['accountRetrieval']['username'] : null,
				'fields' => '*'
			) ) );
		} else {
			$aData = current( $oUserManager->oDao->read( array(
				'userEmail' => $_SESSION['accountRetrieval']['email'],
				'username' => !empty($_SESSION['accountRetrieval']['username']) ? $_SESSION['accountRetrieval']['username'] : null,
				'fields' => '*'
			) ) );
		}
		
		$iCode = hexdec( substr( sha1( $aData['infoCellPhone'] . $aData['userEmail'] . $aData['userPass'] . date( 'Y-m-d' ) . 'tovekSalt' ), 1, 4 ) );
		
		if( !empty($aData) && ((int) $_POST['stringCode'] == $iCode) ) {
			/**
			 * Valid code
			 */
			$GLOBALS['accountRetrieval'] = array(
				'code' => $iCode,
				'codeStatus' => 'valid'
			);

			$_SESSION['accountRetrieval']['status'] = 'resetPassword';
			$_SESSION['accountRetrieval']['code'] = md5( $iCode );

		} else {
			/**
			 * Invalid code
			 */
			$oNotification->set( array(
				'dataError' => _( 'Invalid code' )
			) );
		}
	}

	/**
	 * Post code confirmation
	 */
	if( !empty($_POST['frmResetPassword']) ) {
		$oUserManager = clRegistry::get( 'clUserManager' );

		if( !empty($_SESSION['accountRetrieval']['phone']) ) {
			$aData = current( $oUserManager->oDao->read( array(
				'infoCellPhone' => $_SESSION['accountRetrieval']['phone'],
				'username' => !empty($_SESSION['accountRetrieval']['username']) ? $_SESSION['accountRetrieval']['username'] : null,
				'fields' => '*'
			) ) );
		} else {
			$aData = current( $oUserManager->oDao->read( array(
				'userEmail' => $_SESSION['accountRetrieval']['email'],
				'username' => !empty($_SESSION['accountRetrieval']['username']) ? $_SESSION['accountRetrieval']['username'] : null,
				'fields' => '*'
			) ) );
		}

		if( !empty($aData) ) {
			// Assamble check code
			$iCode = hexdec( substr( sha1( $aData['infoCellPhone'] . $aData['userEmail'] . $aData['userPass'] . date( 'Y-m-d' ) . 'tovekSalt' ), 1, 4 ) );

			if( !empty($_SESSION['accountRetrieval']['code']) && $_SESSION['accountRetrieval']['code'] == md5($iCode) ) {
				/**
				 * Valid code
				 */
				if( !empty($_POST['password']) && $_POST['password'] == $_POST['password2'] ) {
					/**
					 * Update password
					 */
					$oUserManager->oDao->updateUserData( $aData['userId'], array(
						'userPass' => hashUserPass( $_POST['password'], $aData['userEmail'] )
					) );

					$oNotification->setSessionNotifications( array(
						'dataSaved' => _( 'Your password has been updated' )
					) );

					unset( $_SESSION['accountRetrieval'] );

					$oRouter->redirect( '/' );

				} else {
					$aErr[] = _( 'Password did not match' );
				}
			}
		} else {
			$aErr[] = _( 'Could not find the user' );
		}
	}
}

/**
 * Assemble output
 */
switch( $_SESSION['accountRetrieval']['status'] ) {
	case 'resetPassword':
		if( $GLOBALS['accountRetrieval']['codeStatus'] == 'valid' ) {
			/**
			 * Password form
			 */			
			$oOutputHtmlForm->init( array(
				'entResetPassword' => array(
					'password' => array(
						//'type' => 'secret',
						'title' => _( 'Password' ),
						'appearance' => 'secret'
					),
					'password2' => array(
						//'type' => 'secret',
						'title' => _( 'Repeat' ),
						'appearance' => 'secret'
					),
					'frmResetPassword' => array(
						'type' => 'hidden',
						'value' => true
					)
				)
			), array(
				'method' => 'post',
				'action' => $oRouter->sPath,
				'attributes' => array( 'class' => 'marginal' ),
				'extraWrappers'	=> true,
				'placeholders' => true,
				'errors' => $aErr,
				'data' => $_POST,
				'labelSuffix' => ':',
				//'recaptcha' => true,
				'buttons' => array( 'submit' => _( 'Send' ) )
			) );
			$sTitle = _( 'Välj ett nytt lösenord' );
			$sOutput = $oOutputHtmlForm->render();

		} else {
			/**
			 * Re-enter code from
			 */
			$oOutputHtmlForm->init( array(
				'entUserPassRetrieval' => array(
					'stringCode' => array(
						'type' => 'string',
						'title' => _( 'Code' )
					),
					'frmCodeConfirm' => array(
						'type' => 'hidden',
						'value' => true
					)
				)
			), array(
				'method' => 'post',
				'action' => $oRouter->sPath,
				'extraWrappers'	=> true,
				'placeholders' => true,
				'errors' => $aErr,
				'data' => $_POST,
				'labelSuffix' => ':',
				//'recaptcha' => true,
				'buttons' => array( 'submit' => _( 'Send' ) )
			) );
			$sTitle = _( 'Ange den kod som skickats till dig' );
			$sOutput = $oOutputHtmlForm->render();
		}
		break;

	case 'codeSent':
		/**
		 * Code from
		 */
		$oOutputHtmlForm->init( array(
			'entUserPassRetrieval' => array(
				'stringCode' => array(
					'type' => 'string',
					'title' => _( 'Code' )
				),
				'frmCodeConfirm' => array(
					'type' => 'hidden',
					'value' => true
				)
			)
		), array(
			'method' => 'post',
			'action' => $oRouter->sPath,
			'extraWrappers'	=> true,
			'placeholders' => true,
			'errors' => $aErr,
			'data' => $_POST,
			'labelSuffix' => ':',
			//'recaptcha' => true,
			'buttons' => array( 'submit' => _( 'Send' ) )
		) );
		$sTitle = _( 'Ange den kod som skickats till dig' );
		$sOutput = $oOutputHtmlForm->render();
		break;

	default:
		/**
		 * Phone or email form
		 */
		$oOutputHtmlForm->init( array(
			'entUserPassRetrieval' => array(
				'email' => array(
					'type' => 'string',
					'title' => _( 'E-post' )
				),
				'phoneNumber' => array(
					'type' => 'string',
					'title' => _( 'eller Telefonnummer' ),
					'suffixContent' => '<hr />',
					'fieldAttributes' => array(
						'class' => 'fieldPhoneNumber'
					)
				),
				'username' => array(
					'type' => 'string',
					'title' => _( 'Username' ),
					'suffixContent' => '(' . _( 'If you have multiple account' ) . ')',
					'fieldAttributes' => array(
						'class' => 'fieldUsername'
					)
				),
				'frmUserAccountRetrieval' => array(
					'type' => 'hidden',
					'value' => true
				)
			)
		), array(
			'method' => 'post',
			'action' => $oRouter->sPath,
			'attributes' => array( 'class' => 'marginal' ),
			'extraWrappers'	=> true,
			'placeholders' => true,
			'errors' => $aErr,
			'data' => $_POST,
			'labelSuffix' => ':',
			'recaptcha' => true,
			'buttons' => array( 'submit' => _( 'Send' ) )
		) );
		$sTitle = _( 'Återställ ditt lösenord via email eller sms' );
		$sOutput = $oOutputHtmlForm->render();
		break;
}

/**
 * Output
 */
echo '
	<div class="view user accountRetrieval smsRetrieval">
		<h1>' . $sTitle . '</h1>
		' . $sOutput . '
	</div>';

$oTemplate->addStyle( array(
	'key' => 'viewStyle',
	'content' => '
		.view.user.accountRetrieval.smsRetrieval form .fieldPhoneNumber .suffixContent {
			display: block;
			padding: 1.1em 0 .1em 0;
		}
		.view.user.accountRetrieval.smsRetrieval form .fieldPhoneNumber .suffixContent hr {
			color: #ccc;
			background: #ccc;
		}
		.view.user.accountRetrieval.smsRetrieval form .fieldUsername .suffixContent {
			display: block;
			color: #ccc;
			font-size: .8em;
			font-style: italic;
		}
	'
) );
