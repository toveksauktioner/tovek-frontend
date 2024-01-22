<?php

$aErr = array(
	'addUser' => '',
	'addCustomer' => '',
	'updateUserPass' => '',
	'updateUserEmail' => ''
);

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oDiscount = clRegistry::get( 'clDiscount', PATH_MODULE . '/discount/models' );

$aCustomerDataDict = $oCustomer->oDao->aDataDict['entCustomer'];

/**
 * Lists of data
 */
$aCountryList = arrayToSingle( $oContinent->aHelpers['oParentChildHelper']->readChildren( null ), 'countryId', 'countryName' );
$aUserGroupList = arrayToSingle( $oCustomer->oUser->readGroup(), 'groupKey', 'groupTitle' );
$aCustomerGroupList = arrayToSingle( $oCustomer->readCustomerGroup(), 'groupId', 'groupNameTextId' );

/**
 * Filter user group list based on users access level
 */
if( !$oUser->oAclGroups->isAllowed('superuser') ) {
	$aUserGroupList = array_intersect_key( $aUserGroupList, $oUser->oAclGroups->aAcl );
}

$iEditUserId = null;

if( !empty($_GET['customerId']) ) {
	/**
	 * Customer's user account ID
	 */
	$iEditUserId = (int) current(current( $oCustomer->readAll( 'customerUserId', $_GET['customerId'] ) ));
	
	if( !empty($iEditUserId) ) {		
		/**
		 * User access control
		 */
		$oEditUser = new clUser( $iEditUserId );
		if( !$oUser->oAclGroups->isAllowed('superuser') ) {
			$aDiffGroups = array_diff_key( $oEditUser->aGroups, $aUserGroupList );
			if( !empty($aDiffGroups) ) {
				throw new Exception( 'noUserAccess - ' . $iEditUserId );
			}
		}
		
		/**
		 * Login as user
		 */
		if( !empty($_GET['action']) && $_GET['action'] == 'loginAsUser' ) {			
			unset($oUser);
			session_unset(); // Remove ACL from previous user
			$oNewUserLogin = new clUser();
			$oNewUserLogin->loginInit( $iEditUserId );
			clRegistry::set( 'clUser', $oNewUserLogin );
			$oRouter->redirect( '/' );
		}
		
		/**
		 * Change password
		 */
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
		
		/**
		 * Change email
		 */
		if( !empty($_POST['frmChangeEmail']) ) {
			if( $oEditUser->updateEmail($_POST['userEmail'], $_POST['userPass']) ) {
				$oNotification->set( array('updateUserEmail' => _('The email has been changed')) );
				$_POST = array();
			} else {
				$aErr['updateUserEmail'] = clErrorHandler::getValidationError( 'updateUserEmail' );
			}
		}
	}
}

/**
 * Customer group change
 */
if( !empty($_POST['frmUpdateCustomerGroup']) && !empty($_POST['customerGroup']) ) {
	if( !empty($_POST['customerId']) ) {
		$oCustomer->updateGroupForCustomer( $_POST['customerId'], $_POST['customerGroup'] );
	} else {
		$iEditCustomerGroup = $_POST['customerGroup'];
	}
	$_POST = array();
}

/**
 * Send login credentials by e-mail
 */
if( isset($_POST['frmSendLoginCredentials']) ) {
	/**
	 * Confirmation
	 */
	$sLink = '<strong><a href="' . $oRouter->sPath . '?customerId=' . $_GET['customerId'] . '&sendLoginCredentials=true">' . _( 'confirm' ) . '</a></strong>';
	$oNotification->set( array(
		'updateUserCredentials' => sprintf( _( 'Please %s resetting login credentials and sending out new once to customer\'s e-mail' ), $sLink )
	) );
	$_POST = array();
}
if( !empty($_GET['sendLoginCredentials']) && $_GET['sendLoginCredentials'] == 'true' ) {
	// Read user data
	$aUser = current( $oCustomer->read( array('userId', 'userEmail'), $_GET['customerId'] ) );
	
	if( !empty($aUser) ) {
		// Reset password
		$oUserManager = clRegistry::get( 'clUserManager' );
		$sNewPass = $oUserManager->updateRandomPass( $aUser['userId'], $aUser['userEmail'] );
		
		// Assamble message
		$sBodyHtml = '
			<h3>' . sprintf( _( 'New login credentials at %s' ), ' ' . SITE_DOMAIN ) . '</h3>
			<p>
				<strong>' . _( 'Email' ) . ':</strong> ' . $aUser['userEmail'] . '<br />
				<strong>' . _( 'Password' ) . ':</strong> ' . $sNewPass . '<br />
				<br />
				<strong>' . _( 'Kind regards' ) . '</strong><br />
				<em>' . SITE_DOMAIN . '</em>
			</p>';
		
		/**
		 * Send email
		 */
		$aMailParams = array(
			'title' => sprintf( _( 'New login credentials at %s' ), ' ' . SITE_DOMAIN ),
			'content' => $sBodyHtml,
			'to' => $aUser['userEmail']
		);		
		$oMailHandler = clRegistry::get( 'clMailHandler' );
		$oMailHandler->prepare( $aMailParams );
		if( $oMailHandler->send() ) {
			$oNotification->setSessionNotifications( array(
				'updateUserCredentials' => sprintf( _( 'An e-mail with new login credentials has been sent to customer\'s e-mail' ), $sLink )
			) );
			$oRouter->redirect( $oRouter->sPath . '?customerId=' . $_GET['customerId'] );
		}
	}
}

/**
 * Post data
 */
if( isset($_POST['frmUserAdd']) ) {	
	$oDiscount->setParentType( 'User' );

	// Compile infoName from first- and surname, or unset them if company
	if( !empty($_POST['customerGroup']) && $_POST['customerGroup'] == 1 ) {
		if( !empty($_POST['infoFirstname']) && !empty($_POST['infoFirstname']) ) {
			$_POST['infoName'] = $_POST['infoFirstname'] . ' ' . $_POST['infoSurname'];
		}
		// Re-add infoName so that it gets updated
		$aFormDataDict[ 'infoName' ] = array(
			'title' => _( 'Name' )
		);
	} else {
		unset($_POST['infoFirstname'], $_POST['infoSurname'] );
		// Remove requirements
		unset( $oCustomer->oUser->oDao->aDataDict['entUserInfo']['infoFirstname']['required'] );
		unset( $oCustomer->oUser->oDao->aDataDict['entUserInfo']['infoSurname']['required'] );
	}
	
	/**
	 * Customer data
	 */
	$aCustomerDataPost = array();
	foreach( $_POST as $sLabel => $sValue ) {
		if( array_key_exists($sLabel, array_merge( $aCustomerDataDict, array('customerGroup' => null) )) ) {
			$aCustomerDataPost[$sLabel] = $sValue;
		}
	}
	
	/**
	 * Update
	 */
	if( !empty($_GET['customerId']) && ctype_digit($_GET['customerId']) ) {
		// Data
		$oEditUser->oDao->updateUserData( $iEditUserId, $_POST );
		$aErr['addUser'] = clErrorHandler::getValidationError( 'updateUser' );
		
		if( empty($aErr['addUser']) ) {
			// User group
			$oEditUser->setGroup( array_combine($_POST['userGroup'], array_intersect_key($aUserGroupList, array_flip($_POST['userGroup']))) );
			$oEditUser->updateGroup();
			
			// Discount
			$oDiscount->upsertByParent( $iEditUserId, array(
				'discountValue' => $_POST['userDiscount']
			) );
			
			// Customer data
			$oCustomer->update( $_GET['customerId'], $aCustomerDataPost );
			$aErr['addCustomer'] = clErrorHandler::getValidationError( 'updateCustomer' );
			
			$iCustomerId = $_GET['customerId'];
		}

	/**
	 * Create
	 */
	} else {
		if( $_POST['userPass'] !== $_POST['userPassConfirm'] ) {
			$aErr['addUser'] = array( 'userPass' => _( 'The passwords do not match' ) );
		}
		
		if( empty($aErr['addUser']) ) {
			$oNewUser = new clUser();
			$oNewUser->setData( $_POST );
			
			$oAcl = new clAcl();
			$oAcl->setAcl( array('writeUser' => 'allow', 'readUser' => 'allow') );
			$oCustomer->oUser->setAcl( $oAcl );
			
			if( !$oCustomer->oUser->isUser(array_intersect_key($_POST, array('username' => '', 'userEmail' => ''))) ) {
				$iUserId = $oCustomer->oUser->createUserWithInfo( $oNewUser );
				
				if( !empty($iUserId) ) {
					$aCustomerDataPost['customerUserId'] = $iUserId;
					unset( $aCustomerDataPost['customerId'] );
					
					// User group
					$oCustomer->oUser->createUserToGroup( $iUserId, $_POST['userGroup'] );
					
					// Discount
					$oDiscount->createByParent( $iUserId, array(
						'discountValue' => $_POST['userDiscount']
					) );
					
					// Customer data					
					if( !empty($aCustomerDataPost['customerUserId']) ) {
						$iCustomerId = $oCustomer->create( $aCustomerDataPost );
						$aErr['addCustomer'] = clErrorHandler::getValidationError( 'createCustomer' );
					}
					
					$aUserData = array();
				} else {
					$aErr['addUser'] = clErrorHandler::getValidationError( 'createUser' );
				}
				
			} else {
				$aErr['addUser'] = array( 'username' => _( 'User already exists' ) );
			}
		}
	}

	if( $_POST['customerGroup'] == 'privatePerson' ) unset( $aFormDataDict[ 'infoName' ] );

	if( empty($aErr['addUser']) ) $oRouter->redirect( $oRouter->sPath . '?customerId=' . $iCustomerId );
}

/**
 * Edit customer
 */
if( !empty($_GET['customerId']) ) {
	$sTitle = _( 'Edit customer' );
	
	if( !empty($iEditUserId) ) {
		/**
		 * User discount
		 */		
		$oDiscount->setParentType( 'User' );
		$aUserDiscount = $oDiscount->readByParent( $iEditUserId, 'discountValue' );
		$aUserData['userDiscount'] = !empty( $aUserDiscount ) ? current( current($aUserDiscount) ) : 0;
	}
	
	/**
	 * All readable fields
	 */
	$aReadFields = array();
	foreach( $oCustomer->oDao->aDataDict as $aDataDict ) {
		$aReadFields = array_merge( $aReadFields, array_keys($aDataDict) );
	}

	// Data
	$aCustomerData = current( $oCustomer->readAll( $aReadFields, $_GET['customerId'] ) );

	// Customer group
	if( !empty($iEditCustomerGroup) ) {
		$aCustomerData['customerGroup'] = $iEditCustomerGroup;
	} else {
		$aCustomerData['customerGroup'] = $aCustomerData['groupId'];	
	}
	
	// Add user group data
	reset( $oEditUser->aGroups );
	$aCustomerData['userGroup'] = array_keys( $oEditUser->aGroups );
	
	/**
	 * Change password form
	 */
	$oOutputHtmlForm->init( array(
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
	), array(
		'attributes' => array( 'class' => 'marginal' ),
		'labelSuffix' => ':',
		'errors' => $aErr['updateUserPass'],
		'method' => 'post'
	) );
	$oOutputHtmlForm->setGroups( array(
		'changePassword' => array(
			'title' => _( 'Change password' ),
			'fields' => array(
				'userPass',
				'userPassConfirm'
			)
		)
	) );
	$sFormChangePass = '
		<section class="userChangePass">
			<h2>' . _( 'Change password' ) . '</h2>
			' . $oOutputHtmlForm->render() . '
		</section>';


	/**
	 * Change email form
	 */	
	$oOutputHtmlForm->init( array(
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
	), array(
		'attributes' => array( 'class' => 'marginal' ),
		'labelSuffix' => ':',
		'errors' => $aErr['updateUserEmail'],
		'method' => 'post'
	) );
	$oOutputHtmlForm->setGroups( array(
		'changeEmail' => array(
			'title' => _( 'Change email' ),
			'fields' => array(
				'userEmail',
				'userPass'
			)
		)
	) );
	$sFormChangeEmail = '
		<section class="userChangeEmail">
			<h2>' . _( 'Change email' ) . '</h2>
			' . $oOutputHtmlForm->render() . '
		</section>';

	/**
	 * Customer edit form data dict
	 */
	$aFormDataDict = array(
		'username' => array(),
		'userGrantedUsage' => array(),
		'userEmail' => array(
			'appearance' => 'readonly'
		),
		'userGroup' => array(
			'type' => 'arraySet',
			'values' => $aUserGroupList,
			'title' => _( 'Groups' ),
			'appearance' => 'full'
		)
	);
	
	/**
	 * Login as user
	 */
	$sLoginAsUser = '
		<div class="userLogin">
			<a href="' . $oRouter->sPath . '?action=loginAsUser&amp;loginUserId=' . $iEditUserId . '&amp;' . stripGetStr( array('action', 'loginUserId') ) . '" class="linkConfirm icon iconText iconUser" title="' . _( 'Do you really want to login as this user' ) . '?">
				' . sprintf( _( 'Login as %s' ), $aCustomerData['username'] ) . '
			</a>
		</div>';
	
/**
 * New customer
 */
} else {
	$sTitle = _( 'Add customer' );
	
	// Data	
	$aCustomerData = $_POST;
	
	/**
	 * Customer edit form data dict
	 */
	$aFormDataDict = array(
		'username' => array(),
		'userGrantedUsage' => array(),
		'userPass' => array(
			'type' => 'string',
			'title' => _( 'Password' ),
			'appearance' => 'secret'
		),
		'userPassConfirm' => array(
			'type' => 'string',
			'title' => _( 'Confirm password' ),
			'appearance' => 'secret'
		),
		'userGroup' => array(
			'type' => 'arraySet',
			'values' => $aUserGroupList,
			'title' => _( 'Groups' ),
			'appearance' => 'full'
		)
	);
}

/**
 * Customer edit form data dict
 */
$aFormDataDict += array(
	'userEmail' => array(),
	# User related information
	'customerGroup' => array(
		'type' => 'array',
		'suffixContent' => '<input type="submit" id="updateCustomerType" />',
		'defaultValue' => !empty($aCustomerData['groupId']) ? $aCustomerData['groupId'] : null,
		'title' => _( 'Customer type' ),
		'values' => $aCustomerGroupList
	),
	'customerNumber' => array(),
	'infoContactPerson' => array(),
	'infoName' => array(),
	'infoFirstname' => array(),
	'infoSurname' => array(),
	'infoUserPin' => array(),
	'infoVatNo' => array(),
	'infoBoxAddress' => array(),
	'infoAddress' => array(),
	'infoZipCode' => array(),
	'infoCity' => array(),
	'infoPhone' => array(),
	'infoCountry'  => array(
		'type' => 'array',
		'values' => $aCountryList,
		'defaultValue' => 210, # 210 = Sweden
		'title' => _( 'Country' ),
		'required' => true
	),
	# Delivery adress
	'infoDeliveryCountry' => array(
		'type' => 'array',
		'values' => $aCountryList,
		'defaultValue' => 210, # 210 = Sweden
		'title' => _( 'Delivery country' ),
		'required' => true
	),
	'infoDeliveryName' => array(),
	'infoDeliveryFirstname' => array(),
	'infoDeliverySurname' => array(),
	'infoDeliveryPhone' => array(),
	'infoDeliveryAddress' => array(),
	'infoDeliveryAddress2' => array(),
	'infoDeliveryZipCode' => array(),
	'infoDeliveryCity' => array(),	
	# Payment adress
	'infoPaymentCountry' => array(
		'type' => 'array',
		'values' => $aCountryList,
		'defaultValue' => 210, # 210 = Sweden
		'title' => _( 'Payment country' ),
		'required' => true
	),
	'infoPaymentName' => array(),
	'infoPaymentFirstname' => array(),
	'infoPaymentSurname' => array(),
	'infoPaymentPhone' => array(),
	'infoPaymentAddress' => array(),
	'infoPaymentBoxAddress' => array(),
	'infoPaymentZipCode' => array(),
	'infoPaymentCity' => array(),	
	# User discount
	'userDiscount' => array(
		'title' => _( 'Discount' ) . ' (%)'
	),
	# Misc
	'customerId' => array(
		'type' => 'hidden'
	)	
);

/**
 * Private person specifics
 */
if( !empty($aCustomerData['customerGroup']) && $aCustomerData['customerGroup'] == '1' ) {
	// Change titles
	$aFormDataDict['infoUserPin']['title'] = _( 'SSN/PIN' );
	$aFormDataDict['infoUserPin']['suffixContent'] = ' ' . _( 'YYMMDDXXXX' );

	// Unset company specific inputs
	unset(
		$aFormDataDict['infoContactPerson'],
		$_POST['infoContactPerson'],
		$aFormDataDict['infoName'],
		$aFormDataDict['infoPaymentName'],
		$aFormDataDict['infoDeliveryName'],
		$aFormDataDict['infoVatNo']
	);

/**
 * Company specifics
 */	
} elseif( !empty($aCustomerData['customerGroup']) && $aCustomerData['customerGroup'] == '2' ) {
	// Unset privatePerson specific inputs
	unset(
		$aFormDataDict['infoFirstname'],
		$aFormDataDict['infoSurname'],
		$aFormDataDict['infoDeliveryFirstname'],
		$aFormDataDict['infoDeliverySurname'],
		$aFormDataDict['infoPaymentFirstname'],
		$aFormDataDict['infoPaymentSurname']
	);
}

/**
 * User form
 */
$oOutputHtmlForm->init( $oCustomer->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'marginal' ),
	'data' => $aCustomerData,
	'errors' => $aErr['addUser'],
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => array(
			'content' => _( 'Save' ),
			'attributes' => array(
				'name' => 'frmUserAdd',
				'type' => 'submit',
				'class' => 'submitButton'
			)
		),
		'sendLoginCredentials' => array(
			'content' => _( 'Reset and/or send e-mail with login credentials' ),
			'attributes' => array(
				'name' => 'frmSendLoginCredentials',
				'type' => 'submit',
				'class' => 'sendLoginCredentials'
			)
		),
		'copyToPayment' => array(
			'content' => _( 'Copy to payment address' ),
			'attributes' => array(
				'name' => 'copyToPayment',
				'type' => 'submit',
				'class' => 'copyToPayment'
			)
		),
		'copyToDelivery' => array(
			'content' => _( 'Copy to delivery address' ),
			'attributes' => array(
				'name' => 'copyToDelivery',
				'type' => 'submit',
				'class' => 'copyToDelivery'
			)
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

/**
 * Form groups
 */
$oOutputHtmlForm->setGroups( array(
	'userData' => array(
		'title' => _( 'User data' ),
		'fields' => array(
			'userEmail',
			'username',
			'userGrantedUsage',
			'userPass',
			'userPassConfirm',
			'userGroup',
			'userDiscount'
		)
	),
	'information' => array(
		'title' => _( 'Information' ),
		'fields' => array(
			'userEmail',
			'customerNumber',
			'username',
			'userPass',
			'userPassConfirm',
			'userGroup',
			# User related information
			'customerGroup',
			'infoContactPerson',
			'infoName',
			'infoFirstname',
			'infoSurname',
			'infoUserPin',
			'infoVatNo',
			'infoBoxAddress',
			'infoAddress',
			'infoZipCode',
			'infoCity',
			'infoPhone',
			'infoCellPhone',
			'infoCountry',
			# User discount
			'userDiscount'
		)
	),
	'deliveryAddress' => array(
		'title' => _( 'Delivery address' ),
		'fields' => array(
			'infoDeliveryCountry',
			'infoDeliveryName',
			'infoDeliveryFirstname',
			'infoDeliverySurname',
			'infoDeliveryPhone',
			'infoDeliveryAddress',
			'infoDeliveryAddress2',
			'infoDeliveryZipCode',
			'infoDeliveryCity'			
		)
	),	
	'paymentAddress' => array(
		'title' => _( 'Payment address' ),
		'fields' => array(
			'infoPaymentCountry',
			'infoPaymentName',
			'infoPaymentFirstname',
			'infoPaymentSurname',
			'infoPaymentPhone',
			'infoPaymentAddress',
			'infoPaymentBoxAddress',
			'infoPaymentZipCode',
			'infoPaymentCity'			
		)
	)
) );

/**
 * Create form
 */
$sUserForm = $oOutputHtmlForm->renderErrors();
$sUserForm .= !empty($sLoginAsUser) ? $sLoginAsUser : '';
$sUserForm .= $oOutputHtmlForm->renderButtons( array('submit', 'sendLoginCredentials') );
$sUserForm .= $oOutputHtmlForm->renderGroups( 'userData' );
$sUserForm .= $oOutputHtmlForm->renderGroups( 'information' );
$sUserForm .= '<br style="clear: both" />';
$sUserForm .= $oOutputHtmlForm->renderButtons( array('copyToPayment', 'copyToDelivery') );
$sUserForm .= $oOutputHtmlForm->renderGroups( 'paymentAddress' );
$sUserForm .= $oOutputHtmlForm->renderGroups( 'deliveryAddress' );
$sUserForm .= $oOutputHtmlForm->renderFields();
$sUserForm = $oOutputHtmlForm->createForm( 'post', '', $sUserForm, array( 'class' => 'marginal' ) );

echo '
	<div class="view customerAdd">
		<h1>' . $sTitle . '</h1>
		' . $sUserForm . '
		<hr />
		' . (!empty($sFormChangePass) ? $sFormChangePass : '') . '
		' . (!empty($sFormChangeEmail) ? $sFormChangeEmail : '') . '
		<br class="clear" />
	</div>';

$oTemplate->addBottom( array(
	'key' => '',
	'content' => '
		<script>
			/**
			 * autoSubmitOnCustomerTypeChange
			 */
			$(document).ready( function() {
				$( "#updateCustomerType" ).hide();
				$( "select#customerGroup" ).bind( "change", function() {
					$(".view.customerAdd form").append( \'<input type="hidden" name="frmUpdateCustomerGroup" value="1" />\' );
					$( this ).parents("form").submit();
				} );
			} );
			
			/**
			 * Copy address to delivery address
			 */
			$(".copyToDelivery").click( function( event ) {
				event.preventDefault();
				$("#infoDeliveryFirstname").val( $("#infoFirstname").val() );
				$("#infoDeliverySurname").val( $("#infoSurname").val() );
				$("#infoDeliveryPhone").val( $("#infoPhone").val() );
				$("#infoDeliveryAddress").val( $("#infoAddress").val() );
				$("#infoDeliveryZipCode").val( $("#infoZipCode").val() );
				$("#infoDeliveryCity").val( $("#infoCity").val() );
				$("#infoDeliveryCountry").val( $("#infoCountry").val() );
				
				return false;
			} );
			
			/**
			 * Copy address to payment address
			 */
			$(".copyToPayment").click( function( event ) {
				event.preventDefault();
				$("#infoPaymentFirstname").val( $("#infoFirstname").val() );
				$("#infoPaymentSurname").val( $("#infoSurname").val() );
				$("#infoPaymentPhone").val( $("#infoPhone").val() );
				$("#infoPaymentAddress").val( $("#infoAddress").val() );
				$("#infoPaymentZipCode").val( $("#infoZipCode").val() );
				$("#infoPaymentCity").val( $("#infoCity").val() );
				$("#infoPaymentCountry").val( $("#infoCountry").val() );
				
				return false;
			} );
		</script>'
) );
