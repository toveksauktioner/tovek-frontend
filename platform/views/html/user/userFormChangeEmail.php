<?php

$aErr = array();

$oDataValidation = clRegistry::get( 'clDataValidation' );
$oUser = clRegistry::get( 'clUser', PATH_MODULE . '/user/models' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

$aUserDataDict = $oUser->oDao->getDataDict();

$aFormDataDict = [
	'entEmailChangeForm' => [
		'userEmail' => [
			'title' => _( 'E-post' ),
	    'fieldAttributes' => [
	      'class' => 'email'
	    ]
		],
		'userPass' => [
			'title' => _( 'Lösenord' ),
			'appearance' => 'secret',
			'min' => 4,
			'max' => 100,
	    'fieldAttributes' => [
	      'class' => 'password'
	    ]
		],
		'frmUserChangeEmail' => [
			'type' => 'hidden',
			'value' => 1
		]
	]
];


// Handle registration
if( isset($_POST['frmUserChangeEmail']) ) {
  $aErr = $oDataValidation->validate( $_POST, $aFormDataDict );

  // Email validation
  if( !$oDataValidation->isEmail($_POST['userEmail']) ) $aErr['userEmail'] = _( 'Felaktig e-postadress' );

  // Check password
  $sCurrentPass = $oUser->readData( 'userPass', $_SESSION['userId'] );
  $sFormPass = hashUserPass( $_POST['userPass'], $oUser->aData['userEmail'] );

  if( $sCurrentPass != $sFormPass ) {
    $aErr['userPass'] = _( 'Fel lösenord' );
    unset( $_POST['userPass'] );
  }

	if( empty($aErr) ) {
		if( $oUser->updateEmail( $_POST['userEmail'], $_POST['userPass'] ) ) {
			$oNotification->set( array('updateUserEmail' => _('E-postadress har ändrats')) );
		} else {
			$aErr = clErrorHandler::getValidationError( 'updateUserEmail' );
		}
	}
}

// Make sure the password fields aren´t assigned a value
unset( $_POST['userPass'] );

if( !empty($_SESSION['userId']) ) {
  $aUserData = current( $oUser->oDao->read( array(
    'fields' => 'userEmail',
    'userId' => $_SESSION['userId']
  ) ) );
  $_POST += $aUserData;
}

$oOutputHtmlForm->init( $aUserDataDict, array(
	'attributes'	=> array(
		'class' => 'newForm',
		'id' => 'userChangeEmail'
	),
	'labelSuffix'	=> '',
	'data'			=> $_POST,
	'errors' 		=> $aErr,
	'method'		=> 'post',
  'action'    => '?ajax=1&view=user/userFormChangeEmail.php',
	'buttons'		=> array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict['entEmailChangeForm'] );
$oOutputHtmlForm->setGroups( array(
	'one' => array(
		'title' => ' ',
		'fields' => array_keys($aFormDataDict['entEmailChangeForm'])
	)
) );

// Form output
if( !empty($_POST['frmUserChangeEmail']) && empty($aErr) ) {
  $sFormOutput = '
    <div class="formMessageSent">
      <i class="far fa-check-circle"></i>
      <div class="title">' . _( 'E-postadressen har ändrats.' ) . '</div>
    </div>';

} else {
	$sFormOutput = $oOutputHtmlForm->render();
}


// Form handled  by ajax
if( !empty($_POST['frmUserChangeEmail']) ) {
  echo $sFormOutput;
  exit;
}

echo '
	<div class="view userSignup">
		<h2>' . _( 'Ändra e-post' ) . '</h2>
		' . $sFormOutput . '
	</div>
  <script>
    $( document ).on( "submit", "#userChangeEmail", function(ev) {
      ev.preventDefault();

      $( this ).parent().load( "?ajax=1&view=user/userFormChangeEmail.php", $(this).serializeArray(), function() {
				$("#popupLinkBox").delay( 2000 ).animate( {
					opacity: 0
				}, 200, function() {
					// $("#popupLinkBox").hide().css( "opacity", "initial" );
					location.reload();
				} );
			} );
    } );
  </script>';
