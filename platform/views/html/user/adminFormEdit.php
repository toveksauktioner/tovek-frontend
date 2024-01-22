<?php

$aErr = array(
	'addUser' => '',
	'updateUserPass' => '',
	'updateUserEmail' => ''
);
$sFormChangePass = '';
$sFormChangeEmail = '';

require_once PATH_FUNCTION . '/fData.php';
$oUserManager = clRegistry::get( 'clUserManager' );

// Edit user
$iUserId = $_SESSION['userId'];
$sTitle = _( 'Edit account settings' );
$oEditUser = new clUser( $_SESSION['userId'] );

// Change password form
$aFormDataDict = array(
	'updateUserPass' => array(
		'userPass' => array(
			'title' => _( 'New password' ),
			'appearance' => 'secret'
		),
		'userPassConfirm' => array(
			'title' => _( 'Confirm password' ),
			'appearance' => 'secret'
		),
		'frmChangePass' => array(
			'type' => 'hidden',
			'value' => true
		)
	)
);

// Change password
if( !empty($_POST['frmChangePass']) ) {
	if( $_POST['userPass'] === $_POST['userPassConfirm'] ) {
		if( $oEditUser->updatePass($_POST['userPass']) ) {
			$oNotification->set( array('updateUserPass' => _('The password has changed')) );
			$_POST = array();
		} else {
			$aErr['updateUserPass'] = clErrorHandler::getValidationError( 'updateUserPass' );
		}
	} else {
		$aErr['updateUserPass'][] = _( 'The passwords do not match' );
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'attributes' => array( 'class' => 'marginal' ),
	'labelSuffix' => ':',
	'errors' => $aErr['updateUserPass'],
	'method' => 'post'
) );
$sFormChangePass = '
	<div class="userChangePass">
		<h2>' . _( 'Change password' ) . '</h2>
		' . $oOutputHtmlForm->render() . '
	</div>';


// Change email form
$aFormDataDict = array(
	'updateUserEmail' => array(
		'userEmail' => array(
			'title' => _( 'New email' ),
		),
		'userPass' => array(
			'title' => _( 'Password' ),
			'appearance' => 'secret'
		),
		'frmChangeEmail' => array(
			'type' => 'hidden',
			'value' => true
		)
	)
);

// Change email
if( !empty($_POST['frmChangeEmail']) ) {
	if( $oEditUser->updateEmail($_POST['userEmail'], $_POST['userPass']) ) {
		$oNotification->set( array('updateUserEmail' => _('The email has been changed')) );
		$_POST = array();
	} else {
		$aErr['updateUserEmail'] = clErrorHandler::getValidationError( 'updateUserEmail' );
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'attributes' => array( 'class' => 'marginal' ),
	'labelSuffix' => ':',
	'errors' => $aErr['updateUserEmail'],
	'method' => 'post'
) );
$sFormChangeEmail = '
	<div class="userChangeEmail">
		<h2>' . _( 'Change email' ) . '</h2>
		' . $oOutputHtmlForm->render() . '
	</div>';

// User edit form
$aFormDataDict = array(
	'username' => array(),
	'userEmail' => array(
		'appearance' => 'readonly'
	)
);

$aFormDataDict += array(
	'userEmail' => array(),
	'infoName' => array(),
	'infoAddress' => array(),
	'infoZipCode' => array(),
	'infoCity' => array(),
	'infoCountry' => array(),
	'infoPhone' => array(),
	'frmUserAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);

if( !empty($_POST['frmUserAdd']) ) {
	// Update
	if( !empty($_SESSION['userId']) ) {
		$oEditUser->oDao->updateUserData( $_SESSION['userId'], $_POST );
		$aErr['addUser'] = clErrorHandler::getValidationError( 'updateUser' );
	}
	if( empty($aErr['addUser']) ) $oNotification->set( array('addUser' => _('Account data has been changed')) );
}

$aUserData = $oEditUser->readData( array(
		'username',
		'userEmail',
		'infoName',
		'infoAddress',
		'infoZipCode',
		'infoCity',
		'infoCountry',
		'infoPhone'
) );

// User form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oUser->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'marginal' ),
	'data' => $aUserData,
	'errors' => $aErr['addUser'],
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );
$sUserForm = $oOutputHtmlForm->render();

echo '
	<div class="view user adminFormEdit">
		<h1>' . _( 'Your account' ) . '</h1>
		<section>
			<h2>' . $sTitle . '</h2>
			' . $sUserForm . '
		</section>
		<section>' . $sFormChangePass . '</section>
		<section>' . $sFormChangeEmail . '</section>

	</div>';
