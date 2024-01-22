<?php

require_once PATH_FUNCTION . '/fUser.php';

$aErr = array();
$sCookieLabel = str_replace( '.', '_', SITE_DOMAIN ) . '-cookies-hide-information';

if( !empty($_POST['frmCookieAcceptAdd']) ) {
	clFactory::loadClassFile( 'clLogger' );
	clLogger::log( getUserIp(), 'cookieAccepted.log' );

	if( empty($_COOKIE[$sCookieLabel]) ) {
		setcookie( $sCookieLabel, 'yes', time() + (86400 * 30), '/' );
	}

	$oRouter->redirect( $oRouter->sPath );
}

if( !isset($_COOKIE[$sCookieLabel]) ) {
	$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
	$sText = ( ($aInfo = $oInfoContent->read('contentTextId', 28)) ? $aInfo[0]['contentTextId'] : null );

	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( array(
		'entCookie' => array(
			'frmCookieAcceptAdd' => array(
				'type' => 'hidden',
				'value' => true
			)
		)
	), array(
		'errors' => $aErr,
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'I understand' )
		)
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'frmCookieAcceptAdd' => array()
	) );

	echo '
		<div id="cookieNotification">
			<div class="container">
				<div class="message">
					' . $sText . '
				</div>
				' . $oOutputHtmlForm->render() . '
			</div>
		</div>';
}
