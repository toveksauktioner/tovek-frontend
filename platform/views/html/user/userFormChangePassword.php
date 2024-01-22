<?php

$aErr = array();
$bFormSent = false;

$oDataValidation = clRegistry::get( 'clDataValidation' );
$oUser = clRegistry::get( 'clUser', PATH_MODULE . '/user/models' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

$aUserDataDict = $oUser->oDao->getDataDict();

$aFormDataDict = array(
	'userNewPass' => array(
		'title' => _( 'New password' ),
		'appearance' => 'secret',
		'min' => 4,
		'max' => 100,
    'fieldAttributes' => array(
      'class' => 'password'
    )
	),
	'userNewPassConfirm' => array(
		'title' => _( 'Confirm new password' ),
		'appearance' => 'secret',
		'min' => 4,
		'max' => 100,
    'fieldAttributes' => array(
      'class' => 'password'
    )
	),
	'frmUserChangePass' => array(
		'type' => 'hidden',
		'value' => 1
	)
);


// Handle registration
if( isset($_POST['frmUserChangePass']) ) {

	// Make sure that passwords are equal
	if( $_POST['userNewPass'] !== $_POST['userNewPassConfirm'] ) $aErr['userNewPassConfirm'] = _( 'The passwords don´t match' );

  // Passwords cannot be empty
	if( empty($aErr) ) {
  	if( empty($_POST['userNewPass']) ) $aErr['userNewPass'] = _( 'Lösenorden får inte vara tomma' );
  	if( empty($_POST['userNewPassConfirm']) ) $aErr['userNewPassConfirm'] = _( 'Lösenorden får inte vara tomma' );
  }

	if( empty($aErr) ) {
		if( $oUser->updatePass($_POST['userNewPass']) ) {
			$oNotification->set( array('updateUserPass' => _('Your password has changed.')) );
		}
	}

	$bFormSent = true;
}

// Make sure the password fields aren´t assigned a value
unset(
  $_POST['userNewPass'],
  $_POST['userNewPassConfirm'],
  $_POST['frmUserChangePass']
);


$oOutputHtmlForm->init( $aUserDataDict, array(
	'attributes'	=> array(
		'class' => 'newForm',
		'id' => 'userChangePass'
	),
	'labelSuffix'	=> '',
	'data'			=> $_POST,
	'errors' 		=> $aErr,
	'method'		=> 'post',
  'action'    => '?ajax=1&view=user/userFormChangePassword.php',
	'buttons'		=> array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );
$oOutputHtmlForm->setGroups( array(
	'one' => array(
		'title' => ' ',
		'fields' => array_keys($aFormDataDict)
	)
) );

// Form output
if( $bFormSent && empty($aErr) ) {
  $sFormOutput = '
    <div class="formMessageSent">
      <i class="far fa-check-circle"></i>
      <div class="title">' . _( 'Lösenordet har ändrats.' ) . '</div>
    </div>';

} else {
	$sFormOutput = $oOutputHtmlForm->render();
}

echo '
	<div class="view userSignup">
		<h2>' . _( 'Byt lösenord' ) . '</h2>
		' . $sFormOutput . '
	</div>
  <script>
    $( document ).on( "submit", "#userChangePass", function(ev) {
      ev.preventDefault();

      $( this ).parent().load( "?ajax=1&view=user/userFormChangePassword.php", $(this).serializeArray(), function() {
				$("#popupLinkBox").delay( 2000 ).animate( {
					opacity: 0
				}, 200, function() {
					$("#popupLinkBox").hide().css( "opacity", "initial" );
				} );
			} );
    } );
  </script>';
