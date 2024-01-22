<?php

$aErr = array();
$sNotification = '';

$aFormDataDict = array(
	'entContact' => array(
		'contactName' => array(
			'type' => 'string',
			'title' => _( 'Your name' ),
			'required' => true
		),
		'contactEmail' => array(
			'type' => 'string',
			'title' => _( 'Your email' ),
			'required' => true,
			'extraValidation' => array(
				'Email'
			)
		),
		'contactContent' => array(
			'type' => 'string',
			'appearance' => 'full',
			'title' => _( 'Message' ),
		),
		'contactTargetEmail' => array(
			'type' => 'string',
			'required' => true,
			'title' => _( 'Send to' ),
			'extraValidation' => array(
				'Email'
			)
		),		
		'frmContact' => array(
			'type' => 'hidden',
			'value' => true
		)
	)
);

if( !empty($_POST['frmContact']) ) {
	
	$aValidationErr = clDataValidation::validate( $_POST, $aFormDataDict );
	
	if( empty($aValidationErr) ) {
		if( empty($_POST['contactContent']) ) $_POST['contactContent'] = _( 'No message' );
	
	/*
		$oProductTemplate = clRegistry::get( 'clProductTemplate', PATH_MODULE . '/product/models/' );
		$sProduct = current($oProductTemplate->readByRoute( $oRouter->iCurrentRouteId, array('templateTitleTextId') ));
		$sProduct = $sProduct['templateTitleTextId'];

		$messageHtml  = '
<p>Hej! <br />

Jag fann en produkt jag tror du kan vara intresserad av: <strong>' . $sProduct . '</strong><br />
Klicka på denna länken för att få mer information: <a href="http://' . SITE_DOMAIN . htmlspecialchars($oRouter->sPath) . '">' . $sProduct . '</a></p>

<p>Övrigt Meddelande: ' . $_POST['contactContent'] . '</p>';

		$messageText  = '
Hej! 

Jag fann en produkt jag tror du kan vara intresserad av: ' . $sProduct . '
Kopiera in denna länken i din webbläsare för att få mer information: http://' . SITE_DOMAIN . htmlspecialchars($oRouter->sPath) . '

Övrigt Meddelande: ' . $_POST['contactContent'];
	*/
	
		$messageHtml  = '
<p>Hej! <br />

Här kommer ett tips från en kompis som tycker du skall besöka: <a href="http://www.' . SITE_DOMAIN . $oRouter->sPath . '">' . SITE_DOMAIN . $oRouter->sPath . '</a></p>

<p>Övrigt Meddelande: ' . $_POST['contactContent'] . '</p>';

		$messageText  = '
Hej! 

Här kommer ett tips från en kompis som tycker du skall besöka: http://www.' . SITE_DOMAIN . $oRouter->sPath . '

Övrigt Meddelande: ' . $_POST['contactContent'];
	
		$oMail = clRegistry::get( 'clMail' );
		$oMail->addTo( $_POST['contactTargetEmail'] )
			->setFrom( $_POST['contactEmail'], $_POST['contactName'] )
			->setSubject( sprintf(_('Recommendation of product from %s'), $_POST['contactName']) )			
			->setBodyText( strip_tags( $messageText ) )
			->setBodyHtml( $messageHtml );
		
		if($oMail->send()){
			// Notifications
			$sNotification .= '
			<ul class="notification highlight">
				<li class="notification">' . _( 'Message sent' ) . '</li>
			</ul>';
	
			foreach( $_POST as $key => $value ) {
				$_POST[$key] = null;
			}
		} else {
			$aErr[] = _( 'Could not send your message, please try again later' );
		}
		
	} else {
		clErrorHandler::setValidationError( $aValidationErr );
		$aErr = clErrorHandler::getValidationError( 'entContact' );
	}
	
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'action' => '',
	'attributes' => array( 'class' => 'marginal' ),
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => array(
			'content' => _( 'Submit' ),
			'attributes' => array(
				'class' => 'button'
			)
		)
	)
) );
echo '
	<div class="formTellAFriend" style="width:auto;">
		' . $sNotification . '
		<div class="templateShow col2_content">' . $oOutputHtmlForm->render() . '</div>
		<div class="borderbottom_col2_content"> </div>
	</div>';