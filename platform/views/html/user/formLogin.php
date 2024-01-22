<?php

$aErr = array();

$oUser = clRegistry::get( 'clUser' );
$oLayout = clRegistry::get( 'clLayoutHtml' );

/**
 *
 * Admin type
 * 
 */
if( !isset($_SESSION['adminType']) || $_SESSION['adminType'] != 'new' ) {
	$oRouter->redirect( 'https://backend.tovek.se/admin/login' );
}

/**
 * Login
 */
if( !empty($_POST['frmUserLogin']) ) {
	if( empty($_POST['username']) || empty($_POST['userPass']) )	{
		$aErr[] = _( 'You must fill in both a username and password to login.' );
	} else {
		if( !$oUser->login( $_POST['username'], $_POST['userPass']) )	{
			$aErr[] = _( 'Your username or password is not valid.' );
		}
	}
}

if( !empty($_SESSION['userId']) )	{
	if( !empty($_SESSION['returnto']['acoKey']) && ($sPath = $oRouter->getPath($acoKey = $_SESSION['returnto']['acoKey'])) )	{
		unset( $_SESSION['returnto'] );
		if( $oLayout->isAllowed($acoKey) ) $oRouter->redirect( $sPath );
	}

	if( array_key_exists('super', $_SESSION['user']['groups']) )	$oRouter->redirect( '/admin' );
	if( array_key_exists('admin', $_SESSION['user']['groups']) )	$oRouter->redirect( '/admin' );
	if( array_key_exists('user', $_SESSION['user']['groups']) )		$oRouter->redirect( '/' );
}

if( !empty($_SESSION['returnto']['sentTwice']) && !$_SESSION['returnto']['sentTwice'] )	{
	$sRoutePath = $oRouter->getPath( $_SESSION['returnto']['acoKey'] );
	if( !empty($sRoutePath) && $sRoutePath != '/admin' ) {
		$sError = '<ul class="notification"><li class="error">' . sprintf( _( 'You don\'t have access to %s' ), $_SESSION['returnto']['acoKey'] ) . '</ul>';
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oUser->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array(
		'class' => 'marginal'
	),
	'errors' => $aErr,
	'labelRequiredSuffix' => '',
	'method' => 'post',
	'buttons' => array(
		'submit' => array(
			'content' => _( 'Login' )
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'username' => array(
		'title'	=> _( 'Username' )
	),
	'userPass' => array(
		'appearance' => 'secret',
	),
	'frmUserLogin' => array(
		'type' => 'hidden',
		'value' => true
	)
) );
// Autofocus loginbox
$oTemplate->addBottom( array(
	'key' => 'jsFocusUsername',
	'content' => '
	<script>
		document.getElementById("username").focus();
	</script>'
) );

echo '
	<div class="view user formLogin">
		' . ( isset( $sError ) ? $sError : '' ) . '
		<img src="/images/templates/aroma/logo.png" alt="" />
		<section>
			<h1>' . _( 'Log in' ) . '</h1>
			' . $oOutputHtmlForm->render() . '
		</section>
	</div>';
