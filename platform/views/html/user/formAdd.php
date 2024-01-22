<?php

/**
 * Check discount module has been removed
 */
if( !file_exists(PATH_MODULE . '/discount') ) {
	// Exclude discount functionality
	$bDiscount = false;
} else {
	// Include discount functionality
	$bDiscount = true;
}

$aErr = array(
	'addUser' => '',
	'updateUserPass' => '',
	'updateUserEmail' => ''
);
$sFormChangePass = '';
$sFormChangeEmail = '';

require_once PATH_FUNCTION . '/fData.php';
$oNotification = clRegistry::get( 'clNotificationHandler' );
$oUserManager = clRegistry::get( 'clUserManager' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
if( $bDiscount === true ) {
	$oDiscount = clRegistry::get( 'clDiscount', PATH_MODULE . '/discount/models' );
	$oDiscount->setParentType( 'User' );
}

$aUserDataDict = $oUserManager->oDao->getDataDict();

$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName'
) );
$aCountries = arrayToSingle( $aCountries, 'countryId', 'countryName' );

$aUserGroups = arrayToSingle( $oUserManager->readGroup(), 'groupKey', 'groupTitle' );

// Get user groups
if( !$oUser->oAclGroups->isAllowed('superuser') ) {
	$aUserGroups = array_intersect_key( $aUserGroups, $oUser->oAclGroups->aAcl );
}

// Edit user
if( !empty($_GET['userId']) ) {
	$iUserId = $_GET['userId'];
	$sTitle = _( 'Edit user' );
	$oEditUser = new clUser( $_GET['userId'] );

	if( !$oUser->oAclGroups->isAllowed('superuser') && $iUserId != $_SESSION['userId'] ) {
		$aDiffGroups = array_diff_key($oEditUser->aGroups, $aUserGroups);
		if( !empty($aDiffGroups) ) {
			$oNotification->setError( array(
				'dataError' => _( 'You do not have access to modify this user' )
			) );
		}
	}

	if( empty($oNotification->aErrors) ) {
		if( !empty($_GET['action']) && $_GET['action'] === 'loginAsUser' && !empty($_GET['loginUserId']) ) {
			unset($oUser);
			session_unset(); // Remove ACL from previous user
			$oNewUserLogin = new clUser();
			$oNewUserLogin->loginInit( $_GET['loginUserId'] );
			clRegistry::set( 'clUser', $oNewUserLogin );
			$oRouter->redirect( '/' );
		}

		$aUserData = $oEditUser->readData( array(
			'username',
			'userGrantedStatus',
			'userEmail',
			'infoName',
			'infoFirstname',
			'infoSurname',
			'userPin',
			'infoAddress',
			'infoZipCode',
			'infoCity',
			'infoCountry',
			'infoPhone',
			'infoContactPerson'
		) );
		reset( $oEditUser->aGroups );
		$aUserData['userGroup'] = array_keys( $oEditUser->aGroups );

		// Discount
		if( $bDiscount === true ) {
			$aUserDiscount = $oDiscount->readByParent( $_GET['userId'], 'discountValue' );
			$aUserData['userDiscount'] = !empty( $aUserDiscount ) ? current( current($aUserDiscount) ) : 0;
		}

		// Change password form
		$aFormDataDict = array(
			'updateUserPass' => array(
				'userPass' => array(
					'type' => 'string',
					'title' => _( 'New password' ),
					'appearance' => 'secret',
					'min' => $aUserDataDict['entUser']['userPass']['min'],
					'max' => $aUserDataDict['entUser']['userPass']['max']
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
			'jsValidation' => true,
			'errors' => $aErr['updateUserPass'],
			'method' => 'post'
		) );
		$sFormChangePass = '
			<div class="userLogin">
				<a href="' . $oRouter->sPath . '?action=loginAsUser&amp;loginUserId=' . $_GET['userId'] . '&amp;' . stripGetStr( array('action', 'loginUserId') ) . '" class="icon iconText iconLock linkConfirm ui-state-default ui-corner-all" title="' . _( 'Do you really want to login as this user' ) . '?">' . _( 'Login as user' ) . '</a>
			</div>
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
			'userGrantedStatus' => array(),
			'userEmail' => array(
				'appearance' => 'readonly'
			)
		);

		$aFormDataDict['userGroup'] = array(
			'type' => 'arraySet',
			'values' => $aUserGroups,
			'title' => _( 'Groups' ),
			'appearance' => 'full'
		);
	}

} else {
	$sTitle = _( 'Add user' );
	$aUserData = $_POST;
	$aDiffGroups = array();
	$aFormDataDict = array(
		'username' => array(),
		'userGrantedStatus' => array(),
		'userPass' => array(
			'type' => 'string',
			'title' => _( 'Password' ),
			'appearance' => 'secret',
			'min' => $aUserDataDict['entUser']['userPass']['min'],
			'max' => $aUserDataDict['entUser']['userPass']['max']
		),
		'userPassConfirm' => array(
			'type' => 'string',
			'title' => _( 'Confirm password' ),
			'appearance' => 'secret'
		)
	);

	$aFormDataDict['userGroup'] = array(
		'type' => 'arraySet',
		'values' => $aUserGroups,
		'title' => _( 'Groups' ),
		'appearance' => 'full'
	);

}

$aFormDataDict += array(
	'userEmail' => array(),
	'infoName' => array(),
	'infoContactPerson' => array(
		'title' => _( 'Contact person' ),
		'required' => true
	),
	'infoFirstname' => array(),
	'infoSurname' => array(),
	'userPin' => array(),
	'infoAddress' => array(),
	'infoZipCode' => array(),
	'infoCity' => array(),
	'infoCountry' => array(
		'type' => 'hidden',
		'value' => '210'
	),
	'infoPhone' => array()
);

if( $bDiscount === true ) {
	$aFormDataDict += array(
		'userDiscount' => array(
			'title' => _( 'Discount' ) . ' (%)'
		)
	);
}

if( isset($_POST['frmUserAdd']) ) {
	// Update
	if( !empty($_GET['userId']) && ctype_digit($_GET['userId']) ) {
		$oEditUser->oDao->updateUserData( $_GET['userId'], $_POST );
		$aErr['addUser'] = clErrorHandler::getValidationError( 'updateUser' );
		if( empty($aErr['addUser']) ) {
			$oEditUser->setGroup( array_combine($_POST['userGroup'], array_intersect_key($aUserGroups, array_flip($_POST['userGroup']))) );
			$oEditUser->updateGroup();
			if( $bDiscount === true ) {
				$oDiscount->upsertByParent( $_GET['userId'], array(
					'discountValue' => $_POST['userDiscount']
				) );
			}
			$iUserId = $_GET['userId'];
		}

	// Create
	} else {
		if( $_POST['userPass'] !== $_POST['userPassConfirm'] ) $aErr['addUser'][] = _( 'The passwords do not match' );

		if( empty($aErr['addUser']) ) {
			$oNewUser = new clUser();
			$oNewUser->setData( $_POST );

			$oAcl = new clAcl();
			$oAcl->setAcl( array('writeUser' => 'allow', 'readUser' => 'allow') );
			$oUserManager->setAcl( $oAcl );

			if( !$oUserManager->isUser(array_intersect_key($_POST, array('username' => '', 'userEmail' => ''))) ) {
				$iUserId = $oUserManager->createUserWithInfo( $oNewUser );

				if( !empty($iUserId) ) {
					$oUserManager->createUserToGroup( $iUserId, $_POST['userGroup'] );
					if( $bDiscount === true ) {
						$oDiscount->createByParent( $iUserId, array(
							'discountValue' => $_POST['userDiscount']
						) );
					}
					$aUserData = array();
				} else {
					$aErr['addUser'][] = clErrorHandler::getValidationError( 'createUser' );
				}

			} else {
				$aErr['addUser'][] = _( 'User already exists' );
			}
		}
	}

	if( empty($aErr['addUser']) ) $oRouter->redirect( $oRouter->sPath . '?userId=' . $iUserId );
}

// User form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oUser->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'marginal' ),
	'data' => $aUserData,
	'jsValidation' => true,
	'errors' => $aErr['addUser'],
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => array(
			'content' => _( 'Save' ),
			'attributes' => array(
				'name' => 'frmUserAdd'
			)
		),
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );
$sUserForm = $oOutputHtmlForm->render();

echo '
	<div class="view user formAdd">
		<h1>' . $sTitle . '</h1>
		<section>' . $sUserForm . '</section>
		' . (!empty($sFormChangePass) ? '
			<section>' . $sFormChangePass . '</section>
		' : '') . '
		' . (!empty($sFormChangeEmail) ? '
			<section>' . $sFormChangeEmail . '</section>
		' : '') . '
	</div>';