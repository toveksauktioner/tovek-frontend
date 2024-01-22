<?php

$aErr = array();
$bLoggedIn = false;
$sLoginURL = $oRouter->getPath( 'userLogin' );

$oUser = clRegistry::get( 'clUser' );
$oLayout = clRegistry::get( 'clLayoutHtml' );

if( !empty($_POST['frmGuestUserLogin']) ) {
	$oUserManager = clRegistry::get( 'clUserManager' );
	$aEmailData = current( $oUserManager->readByEmailAndPassword( $_POST['username'], $_POST['userPass'], array('username','userEmail') ) );
	if( !empty($aEmailData) ) $_POST['username'] = $aEmailData['username'];

	if( empty($_POST['username']) || empty($_POST['userPass']) ) {
		$aErr[] = _( 'You must fill in both a username and password to login.' );

	} else {
		if( !$oUser->login( $_POST['username'], $_POST['userPass']) ) {
			$aErr[] = _( 'Your username or password is not valid.' );
			$_POST = array( 'frmLoggedIn' => false );

		} else {
			$_POST = array( 'frmLoggedIn' => true );
			$bLoggedIn = true;
		}
	}
}

// Handle return to
$aIgnoreReturnPaths = [
	$oRouter->getPath( 'guestFormLogin' ),
	$oRouter->getPath( 'ajaxGlobalCall' )
];
if( !empty($_GET['returnTo']) && !in_array($_GET['returnTo'], $aIgnoreReturnPaths) ) $_SESSION['returnTo'] = $_GET['returnTo'];

$sReloadPath = '/';
if( !empty($_SESSION['returnTo']) ) {
	if( !empty($_SESSION['returnTo']['acoKey']) ) {
		$sReloadPath = $oRouter->getPath( $_SESSION['returnTo']['acoKey'] );

	} else if( !is_array($_SESSION['returnTo']) ) {
		$sReloadPath = $_SESSION['returnTo'];
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oUser->oDao->getDataDict(), array(
	'action' => $oRouter->sPath,
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

// Form output
if( $bLoggedIn ) {
  $sFormOutput = '
    <div class="formMessageSent">
      <i class="far fa-check-circle"></i>
      <div class="title">' . _( 'Du är inloggad' ) . '</div>
    </div>';

	if( $oRouter->sPath == $sLoginURL ) {
		$sFormOutput .= '
			<script>
				$( function() {
					setTimeout( function() { location.href = "' . $sReloadPath . '"; }, 2000 );
				} );
			</script>';
	}

} else {
	$sFormOutput = $oOutputHtmlForm->render();
}


// Form handled  by ajax
if( !empty($_POST['frmGuestUserLogin']) ) {
  echo $sFormOutput;
  exit;
}

echo '
	<div class="view user guestFormLogin popupLogin">
		<h1>' . _( 'Login' ) . '</h1>
		' . $sFormOutput . '
		<div class="links">
			<a href="' . $oRouter->getPath( 'guestUserSignup' ) . '" class="ajax button small">' . _( 'Signup' ) . '</a>
			<a href="' . $oRouter->getPath( 'guestUserAccountRetrieval' ) . '" class="ajax button small">' . _( 'Forgotten logindata?' ) . '</a>
		</div>
	</div>
  <script>
    $( document ).on( "submit", ".view.user.guestFormLogin form", function(ev) {
      ev.preventDefault();

      $( this ).parent().load( "' . $sLoginURL . '", $(this).serializeArray(), function() {
				if( $(".view.user.guestFormLogin .formMessageSent").length > 0 ) {
						$("#popupLinkBox").delay( 2000 ).animate( {
						opacity: 0
					}, 200, function() {
						location.href = "' . $sReloadPath . '";
					} );
				}
			} );
    } );
  </script>';
