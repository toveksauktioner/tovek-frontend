<?php

$aErr = array();

$oUser = clRegistry::get( 'clUser' );
$oLayout = clRegistry::get( 'clLayoutHtml' );

if( !empty($_POST['frmCustomerUserLogin']) ) {
	if( empty($_POST['username']) || empty($_POST['userPass']) ) {
		$aErr[] = _( 'You must fill in both a username and password to login.' );
	} else {
		if( !$oUser->login( $_POST['username'], $_POST['userPass']) ) {
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
	if( array_key_exists('user', $_SESSION['user']['groups']) )		$oRouter->redirect( '/user/panel' );
}

if( !empty($aErr) ) {
	$oTemplate->addBottom( array(
		'key' => 'jsWrongLogin',
		'content' => '
		<script>
			alert("' . implode('\\n', $aErr) . '");
		</script>'
	) );
	$aErr = array();
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oUser->oDao->getDataDict(), array(
	'action' => '',
	'errors' => $aErr,
	'attributes' => array(
		'class' => 'vertical'
	),
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => array(
			'content' => _( 'Login' )
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'username' => array(),
	'userPass' => array(
		'appearance' => 'secret'
	),
	'frmCustomerUserLogin' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

echo '
	<div class="customerLoginForm view">
		<h1>' . _( 'Login (already customer)' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
		<a href="' . $oRouter->getPath( 'guestUserPassRetrieval' ) . '" class="ajax">' . _( 'Forgotten logindata?' ) . '</a>
	</div>';
