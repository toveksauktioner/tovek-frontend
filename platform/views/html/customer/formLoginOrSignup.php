<?php

$oLayout = clRegistry::get( 'clLayoutHtml' );

$aErr = array();

$oUser = clRegistry::get( 'clUser' );
$oLayout = clRegistry::get( 'clLayoutHtml' );

if( !empty($_POST['frmUserLogin']) ) {
	if( empty($_POST['username']) || empty($_POST['userPass']) )	{
		$aErr[] = _( 'You must fill in both a username and password to login.' );
	} else{
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

	if( array_key_exists('user', $_SESSION['user']['groups']) )		$oRouter->redirect( $oRouter->getPath('guestProductCart') );
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
	'frmUserLogin' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

$sLoginForm = '
	<div class="view customerFormLogin">
		<h1>' . _( 'Login (already customer)' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
		<a href="' . $oRouter->getPath( 'guestUserPassRetrieval' ) . '" class="ajax">' . _( 'Forgotten logindata?' ) . '</a>
	</div>';

echo '
	<div class="view formLoginOrSignup">
		<div class="col colTwo colFirst">
			' . $sLoginForm . '
		</div>
		<div class="col colTwo colLast">
			<div class="shopMethod">
				<p>
					<a href="javascript:registerCustomer();">' . _( 'Register here' ) . '</a><br />
					' . _( 'Register as a customer to facilitate the next purchase' ) . '
				</p>
				<p>
					<strong>' . _( 'Or' ) . '</strong>
				</p>
				<p>
					<a href="' . $oRouter->getPath( 'guestProductCart' ) . '?shopAsGuest=true">' . _( 'Buy as guest' ) . '</a><br />
					' . _( 'Shop without registering' ) . '
				</p>
			</div>
		</div>		
	</div>
	<div class="formSignup">		
		' . $oLayout->renderView( 'user/formSignup.php' ) . '
	</div>';
	
$oTemplate->addBottom( array(
	'key' => 'jsToggleRegisterOrLogin',
	'content' => '
	<script>
		function registerCustomer() {
			$(".formLoginOrSignup").toggle();
			$(".formSignup").toggle();
		}
	</script>'
) );