<?php

$aErr = array();

$oUser = clRegistry::get( 'clUser' );
$oLayout = clRegistry::get( 'clLayoutHtml' );

if( !empty($_POST['frmGuestUserLogin']) ) {
	$oUserManager = clRegistry::get( 'clUserManager' );
	$aEmailData = current( $oUserManager->readByEmailAndPassword( $_POST['username'], $_POST['userPass'], array('username','userEmail') ) );
	if( !empty($aEmailData) ) $_POST['username'] = $aEmailData['username'];

	if( empty($_POST['username']) || empty($_POST['userPass']) ) {
		if( !empty($_GET['ajax']) && $_GET['ajax'] == 'true' ) {
			echo json_encode( array(
				'result' => 'fail',
				'message' => _( 'You must fill in both a username and password to login.' )
			) );
			return;
		}
		$aErr[] = _( 'You must fill in both a username and password to login.' );
	} else {
		if( !$oUser->login( $_POST['username'], $_POST['userPass']) ) {
			if( !empty($_GET['ajax']) && $_GET['ajax'] == 'true' ) {
				echo json_encode( array(
					'result' => 'fail',
					'message' => _( 'Your username or password is not valid.' )
				) );
				return;
			}
			$aErr[] = _( 'Your username or password is not valid.' );
			$_POST = array( 'frmLoggedIn' => false );
		} else {
			if( !empty($_GET['ajax']) && $_GET['ajax'] == 'true' ) {
				echo json_encode( array(
					'result' => 'success',
					'message' => ''
				) );
				return;
			}
			$_POST = array( 'frmLoggedIn' => true );
		}
	}
}

if( !empty($_SESSION['userId']) )	{
	if( !empty($_SESSION['returnto']['acoKey']) && ($sPath = $oRouter->getPath($acoKey = $_SESSION['returnto']['acoKey'])) )	{
		unset( $_SESSION['returnto'] );
		if( $oLayout->isAllowed($acoKey) ) $oRouter->redirect( $sPath );
	}

	if( !empty($_POST['frmLoggedIn']) && $_POST['frmLoggedIn'] === true ) {
		if( array_key_exists('super', $_SESSION['user']['groups']) )	$oRouter->redirect( '/admin' );
		if( array_key_exists('admin', $_SESSION['user']['groups']) )	$oRouter->redirect( '/admin' );
		if( array_key_exists('user', $_SESSION['user']['groups']) )		$oRouter->redirect( $oRouter->sPath );
	}
}

if( !empty($aErr) ) {
	// TODO
	// Find a good way to determine if the template allready has been rendered
	//$oTemplate->addBottom( array(
	//	'key' => 'jsWrongLogin',
	//	'content' => '
	//	<script>
	//		alert("' . implode('\\n', $aErr) . '");
	//	</script>'
	//) );

	echo '
	<script>
		alert("' . implode('\\n', $aErr) . '");
	</script>';

	$aErr = array();
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oUser->oDao->getDataDict(), array(
	'action' => '',
	'errors' => $aErr,
	'attributes' => array(
		'class' => 'newForm'
	),
	'placeholders' => false,
	'labelSuffix' => '',
	'method' => 'post',
	'buttons' => array(
		'submit' => array(
			'content' => _( 'Login' ),
			'attributes' => array(
				'class' => 'submit'
			)
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'username' => array(),
	'userPass' => array(
		'appearance' => 'secret',
		'fieldAttributes' => array(
			'class' => 'password'
		)
	),
	'frmGuestUserLogin' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

echo '
	<div class="view user popupLogin">
		<h1>' . _( 'Login' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
		<div class="links">
			<a href="' . $oRouter->getPath( 'guestUserSignup' ) . '" class="ajax button small">' . _( 'Signup' ) . '</a>
			<a href="' . $oRouter->getPath( 'guestUserAccountRetrieval' ) . '" class="ajax button small">' . _( 'Forgotten logindata?' ) . '</a>
		</div>
	</div>';
