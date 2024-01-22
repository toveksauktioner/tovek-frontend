<?php

$aErr = array();

$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );
$oNotification = clRegistry::get( 'clNotificationHandler' );

if( !empty($_POST['frmUnsubscribe']) ) {
	$oAcl = new clAcl();
	$oAcl->setAcl( array(
		'readNewsletterSubscriber' => 'allow',
		'writeNewsletterSubscriber' => 'allow'
	) );
	$oNewsletterSubscriber->setAcl( $oAcl );

	if( !empty($_POST['unsubscribeConfirmation']) && $_POST['unsubscribeConfirmation'] == 'yes' ) {
		$aData = current( $oNewsletterSubscriber->readByEmail( $_POST['subscriberEmailUnsub'], '*' ) );

		if( !empty($aData) && $aData['subscriberUnsubscribe'] == 'no' ) {
			if( !empty($aData['subscriberEmail']) && !empty($aData['subscriberUnsubscribe']) && !empty($aData['subscriberCreated']) ) {
				$sCheckSum = md5( $aData['subscriberEmail'] . $aData['subscriberUnsubscribe'] . $aData['subscriberCreated'] );

				$sUrl = (SITE_DEFAULT_PROTOCOL == 'http' ? 'http' : 'https') . '://www.' . SITE_DOMAIN . $oRouter->getPath( 'guestNewsletterUnsubscriberConfirm' ) . '?subscriber=' . $sCheckSum . '|' . $aData['subscriberEmail'];

				$sMailContent = '
					<h1>' . _( 'This is a mail from' ) . ' ' . SITE_TITLE . '</h1>
					<p>' . _( 'Someone, hopefully you. Have told us that you whant to unsubscribe from our newsletter' ) . '.<br />
					' . _( 'To confirm this, click the link below. If It not was you, just ignore this mail' ) . '.</p><br />
					<p><a href="' . $sUrl . '">' . _( 'By clicking here you confirm your unsubscription for newsletters from us' ) . '</a></p><br />
					<p>' . _( 'If you have problems clicking the link abow, the address is:' ) . '<br />
					<em>' . $sUrl . '</em></p>';

				$oMailTemplate = new clTemplateHtml();
				$oMailTemplate->setTemplate( 'mailDefault.php' );
				$oMailTemplate->setTitle( _( 'Request on unsubscribing from our newsletter' ) );
				$oMailTemplate->setContent( $sMailContent );
				$sMailBodyHtml = $oMailTemplate->render();

				$oMail = clRegistry::get( 'clMail' );
				$oMail->setFrom( SITE_MAIL_FROM )
					  ->addTo( $_POST['subscriberEmailUnsub'] )
					  ->setReplyTo( SITE_MAIL_FROM )
					  ->setSubject( _( 'Request on unsubscribing from our newsletter' ) )
					  ->setBodyHtml( $sMailBodyHtml )
					  ->setBodyText( strip_tags($sMailBodyHtml) );

				if( $oMail->send() ) {
					$oNotification->add( sprintf( _('We have send a confirmation link to %s, use that link to confirm your unsubscription'), $_POST['subscriberEmailUnsub'] ) );
				}

			} else {
				$oNotification->addError( _( 'There was a problem with unscribing the e-mail you provided' ) );
			}
		} elseif( !empty($aData) && $aData['subscriberUnsubscribe'] == 'yes' ) {
			$oNotification->addError( _( 'The e-mail address you provided is already unsubscribed' ) );
		} else {
			$oNotification->addError( _( 'Unable to find the e-mail address you provided' ) );
		}
	} else {
		$oNotification->addError( _( 'You must confirm your unsubscription' ) );
	}
}

// Datadict
$aFormDataDict = array(
	'newsletterSubscribe' => array(
		'subscriberEmailUnsub' => array(
			'type' => 'string',
			'title' => _( 'E-mail' ),
			'attributes' => array(
				'placeholder' => _( 'E-mail' )
			),
			'required' => true
		),
		'unsubscribeConfirmation' => array(
			'type' => 'boolean',
			'values' => array(
				'yes' => _( 'Yes' ),
				'no' => _( 'No' )
			),
			'title' => _( 'Yes, I wish to unsubscribe' ),
			'suffixContent' => _( 'Yes, I wish to unsubscribe' ),
			'required' => true
		)
	)
);

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Send' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'subscriberEmailUnsub' => array(
		'extraValidation' => array(
			'Email'
		)
	),
	'unsubscribeConfirmation' => array(
		'fieldAttributes' => array(
			'id' => 'confirmationCheck'
		)
	),
	'frmUnsubscribe' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Output
echo '
	<div class="view guestUnsubscribeForm">
		<h1>' . _( 'Unsubscribe from newsletter' ) . '</h1>
		<p>' . _( 'You wish to terminate your subscription to our newsletter.' ) . '</p>
		' . $oOutputHtmlForm->render() . '
	</div>';
