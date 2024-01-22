<?php

/**
 * $Id: formSignup.php 1419 2014-04-17 09:40:57Z alu $
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author: alu $
 * @version		Subversion: $Revision: 1419 $, $Date: 2014-04-17 11:40:57 +0200 (to, 17 apr 2014) $
 */

if( !empty($_SESSION['userId']) ) {
	$oRouter->redirect( $oRouter->getPath( 'userCustomerAccount' ) );
}

$sUserTermsUrl = '/'; // $oRouter->getPath( 'layoutKey' );
$iUserTermsInfoContentId = 00;

$aErr = array();

$oUser = clRegistry::get( 'clUser' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

// DataDict
$aFormDataDict = array(
	'infoCustomerGroup' => array(
		'type' => 'array',
		'values' => array(),
		'title' => _( 'Customer type' ),
		'infoGroup' => 'generic'
	),
	'userEmail' => array(
		'type' => 'string',
		'required' => true,
		'extraValidation' => array(
			'Email'
		),
		'title' => _( 'Email' ),
		'infoGroup' => 'account'
	),
	'orderPaymentSame' => array(
		'fieldAttributes' => array( 'class' => 'orderPaymentSame' ),
		'type' => 'boolean',
		'values' => array(
			'yes' => _( 'Yes' ),
			'no' => _( 'No' )
		),
		'title' => _( 'Same for payment information' ),
		'infoGroup' => 'payment'
	),
	'orderDeliverySame' => array(
		'fieldAttributes' => array( 'class' => 'orderDeliverySame' ),
		'type' => 'boolean',
		'values' => array(
			'yes' => _( 'Yes' ),
			'no' => _( 'No' )
		),
		'title' => _( 'Same for delivery information' ),
		'infoGroup' => 'delivery'
	)
) + $GLOBALS['UserInfoDataDict'] + array(
	'userPass' => array(
		'type' => 'string',
		'title' => _( 'Password' ), 
		'required' => true,
		'appearance' => 'secret',
		'min' => 4,
		'max' => 100,
		'infoGroup' => 'account'
	),
	'userPassConfirm' => array(
		'type' => 'string',
		'title' => _( 'Confirm password' ), 
		'required' => true,
		'appearance' => 'secret',
		'min' => 4,
		'max' => 100,
		'infoGroup' => 'account'
	)
);

/**
 * All customer groups
 */
$aCustomerGroups = valueToKey( 'groupId', $oCustomer->readCustomerGroup() );
$aCustomerGroupList = arrayToSingle( $aCustomerGroups, 'groupId', 'groupNameTextId' );

// Add groups to formDict
$aFormDataDict['infoCustomerGroup']['values'] = $aCustomerGroupList;

// Make dataDict of formDict
$aUserDataDict = array(
	'userDataDict' => $aFormDataDict + array(
		'username' => array(
			'type' => 'string'
		)
	)
);

/**
 * Handle registration
 */
if( isset($_POST['frmUserRegister']) ) {
	$oUserManager = clRegistry::get( 'clUserManager' );
	$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
	
	/**
	 * User terms check
	 */
	if( empty($_POST['userTerms']) || $_POST['userTerms'] == 'no' ) {
		$aErr[] = _( 'You must agree to the terms and conditions' );
	} else {
		$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
		$_POST['userTermsText'] = ( ($aText = $oInfoContent->read('contentTextId', $iUserTermsInfoContentId)) ? $aText[0]['contentTextId'] : null );
	}
	
	if( empty($_POST['infoName']) && !empty($_POST['infoFirstname']) && !empty($_POST['infoSurname']) ) {
		// Assemble composed name
		$_POST['infoName'] = $_POST['infoFirstname'] . ' ' . $_POST['infoSurname'];
	}
	
	if( empty($_POST['username']) && !empty($_POST['userEmail']) ) {
		// Make e-mail users username
		$_POST['username'] = $_POST['userEmail'];
	}
	
	/**
	 * Payment information
	 */
	if( isset($_POST['orderPaymentSame']) && $_POST['orderPaymentSame'] == 'yes' ) {
		foreach( $aUserDataDict['userDataDict'] as $sFieldKey => $aField ) {			
			if( array_key_exists($sFieldKey, $_POST) && (!empty($aField['infoGroup']) && $aField['infoGroup'] == 'payment') ) {
				// Assemble new key for comparison
				$sCompareKey = str_replace( 'Payment', '', $sFieldKey );
				
				if( array_key_exists($sCompareKey, $_POST) ) {
					$_POST[$sFieldKey] = $_POST[$sCompareKey];
				}
			}
		}
	}
	unset( $_POST['orderPaymentSame'] );
	
	/**
	 * Delivery information
	 */
	if( isset($_POST['orderDeliverySame']) && $_POST['orderDeliverySame'] == 'yes' ) {
		foreach( $aUserDataDict['userDataDict'] as $sFieldKey => $aField ) {
			if( array_key_exists($sFieldKey, $_POST) && (!empty($aField['infoGroup']) && $aField['infoGroup'] == 'delivery') ) {
				// Assemble new key for comparison
				$sCompareKey = str_replace( 'Delivery', 'Payment', $sFieldKey );
				
				if( array_key_exists($sCompareKey, $_POST) ) {
					$_POST[$sFieldKey] = $_POST[$sCompareKey];
				}
			}
		}
	}
	unset( $_POST['orderDeliverySame'] );
	
	/**
	 * Customer group specifics
	 */
	switch( $_POST['infoCustomerGroup'] ) {
		/**
		 * Private person
		 */
		case '1':
			$aUserDataDict['userDataDict']['infoFirstname']['required'] = true;
			$aUserDataDict['userDataDict']['infoSurname']['required'] = true;
			$aUserDataDict['userDataDict']['infoUserPin']['extraValidation'] = array( 'Pin' );
			break;
		
		/**
		 * Reseller
		 */
		case '2':
			$aUserDataDict['userDataDict']['infoUserPin']['extraValidation'] = array( 'CompanyPin' );
			break;
	}
	
	// Make sure that passwords is equal
	if( $_POST['userPass'] !== $_POST['userPassConfirm'] ) {
		$aErr[] = _( 'The passwords does not match' );
	}
	
	// Make sure user not already exists
	if( $oUserManager->isUser( array_intersect_key($_POST, array('username' => '', 'userEmail' => '')) ) ) {
		$aErr[] = _( 'This account already exists' );
	}
	
	if( empty($aErr) ) {
		// Data validation
		$_POST = array_intersect_key( $_POST, $aUserDataDict['userDataDict']  );	
		$aErr = clDataValidation::validate( $_POST, $aUserDataDict );
		if( !empty($aErr) ) {
			clErrorHandler::setValidationError( $aErr );
			$aErr = clErrorHandler::getValidationError( 'userDataDict' );
		}
	} else {
		clErrorHandler::setValidationError( $aErr );
	}
	
	if( empty($aErr) ) {
		/**
		 * Allow user to write to user table
		 */
		$oAcl = new clAcl();
		$oAcl->setAcl( array(
			'writeUser' => 'allow',
			'readUser' => 'allow',
			'writeCustomer' => 'allow',
			'readCustomer' => 'allow'
		) );		
		$oUserManager->setAcl( $oAcl );
		$oCustomer->setAcl( $oAcl );
		
		// User granted for usage by customer group settings
		$_POST['userGrantedUsage'] = $aCustomerGroups[ $_POST['infoCustomerGroup'] ]['groupAutoGrantedUsage'];
		
		// New user data object
		$oNewUser = new clUser();
		$oNewUser->setData( $_POST );
		
		if( $iUserId = $oUserManager->createUserWithInfo( $oNewUser ) ) {
			// Add new user to user groups
			$oUserManager->createUserToGroup( $iUserId, array('guest', 'user') );
			
			/**
			 * Create customer of user
			 */
			$iCustomerId = $oCustomer->create( array(
				'customerDescription' => sprintf( _( 'Registration made on the page at %s' ), date('Y-m-d H:i:s') ),
				'customerUserId' => $iUserId,
				'customerGroup' => $_POST['infoCustomerGroup']
			) );
			$aErr = clErrorHandler::getValidationError( 'createCustomer' );
			
			/**
			 * E-mail confirmation to the new user
			 */
			$oMailTemplate = new clTemplateHtml();
			$oMailTemplate->setTemplate( 'mail.php' );
			$oMailTemplate->setTitle( _( $GLOBALS['userRegister']['subject'] ) );
			$oMailTemplate->setContent( sprintf($GLOBALS['userRegister']['bodyHtml'], $_POST['username'], $_POST['userPass']) );
			$sMailHtmlOutput = $oMailTemplate->render();			
			$oMailHandler = clRegistry::get( 'clMailHandler' );	
			$oMailHandler->prepare( array(
				'title' => _( $GLOBALS['userRegister']['subject'] ),
				'content' => $sMailHtmlOutput,
				'to' => (array) $_POST['userEmail']
			) );				
			$oMailHandler->send();
			
			/**
			 * E-mail content to be sent to site email
			 */
			$sMailHtmlOutput = '
				<dl>
					<dt>' . _( 'Username' ) . '</dt>
					<dd>' . $_POST['username'] . '</dd>
					
					<dt>' . _( 'Name' ) . '</dt>
					<dd>' . $_POST['infoName'] . '</dd>
					
					<dt>' . _( 'Email' ) . '</dt>
					<dd>' . $_POST['userEmail'] . '</dd>
				</dl>';
			
			/**
			 * E-mail notification to the site email
			 */
			$oMailHandler = clRegistry::get( 'clMailHandler' );	
			$oMailHandler->prepare( array(
				'title' => _( 'New registration of customer - ' ) . SITE_TITLE,
				'content' => $sMailHtmlOutput,
				'replyTo' => SITE_MAIL_TO
			) );				
			$oMailHandler->send();
			
			if( $_POST['userGrantedUsage'] == 'yes' ) {
				// Login user
				$oUser->loginByEmail( $oNewUser->readData('userEmail'), $_POST['userPass'] );			
				$_SESSION['customer']['newCustomer'] = true;
			}
			
			$oRouter->redirect( '/' );
			// $oRouter->redirect( $oRouter->getPath( 'guestInfo-00000000000000000000000000000000' ) );
			
		} else {
			// Error while creating user
			$aErr = clErrorHandler::getValidationError( 'createUser' );
		}
	}
}

// Country list
$aCountries = arrayToSingle( $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName'
) ), 'countryId', 'countryName' );
if( !empty($aCountries) ) {
	foreach( $aCountries as $iCountryId => $sCountry ) $aCountries[ $iCountryId ] = _( $sCountry );	
}

/**
 * Form groups
 */
$aFormGroups = array(
	'generic' => array(
		'title' => _( 'Generic information' ),
		'fields' => array(
			'infoCustomerGroup'
		)
	),
	'payment' => array(
		'title' => _( 'Payment address' ),
		'fields' => array()
	),
	'delivery' => array(
		'title' => _( 'Delivery address' ),
		'fields' => array(
			'orderDeliverySame'
		)
	),
	'account' => array(
		'title' => _( 'Account details' ),
		'fields' => array()
	)
);

if( !empty($_POST['infoCustomerGroup']) ) {
	// Do nothing here at the moment..
	
} elseif( !empty($_SESSION['infoCustomerGroup']) ) {
	$_POST['infoCustomerGroup'] = $_SESSION['infoCustomerGroup'];
	
} else {
	$_POST['infoCustomerGroup']	= key( $aCustomerGroups );
	
}

switch( $_POST['infoCustomerGroup'] ) {
	/**
	 * Private person
	 */
	case '1':
		unset(
			$aFormDataDict['infoVatNo'],
			$aFormDataDict['infoContactPerson'],
			$aFormDataDict['infoName']
		);
		$aFormDataDict['infoUserPin']['title'] = _( 'SSN/PIN' );
		$aFormDataDict['infoUserPin']['extraValidation'] = array( 'Pin' );
		$aFormDataDict['infoFirstname']['required'] = true;
		$aFormDataDict['infoSurname']['required'] = true;		
		break;
	
	/**
	 * Reseller
	 */
	case '2': 
		unset(
			$aFormDataDict['infoFirstname'],
			$aFormDataDict['infoSurname']
		);
		//$aFormDataDict['infoUserPin']['title'] = _( 'Company ID' );
		$aFormDataDict['infoUserPin']['extraValidation'] = array( 'CompanyPin' );
		break;
}

/**
 * Dynamic changes to dataDict
 */
foreach( $aFormDataDict as $sFieldKey => $aEntry ) {
	if( empty($aEntry['infoGroup']) ) {
		// System field
		unset( $aFormDataDict[$sFieldKey] );
		continue;
	}
		
	if( strpos(strtolower($sFieldKey), 'country') && $aEntry['type'] == 'integer' ) {
		// Country selection field
		$aFormDataDict[$sFieldKey]['type'] = 'array';
		$aFormDataDict[$sFieldKey]['values'] = array( '' => _( 'Select country' ) ) + $aCountries;
		
		if( empty($_POST[$sFieldKey]) ) {
			$_POST[$sFieldKey] = $GLOBALS['defaultCountryId'];
		}
	}
	
	if( empty($aFormGroups[$aEntry['infoGroup']]) ) {
		continue;
	
	} else {
		// Add field to group
		$aFormGroups[$aEntry['infoGroup']]['fields'][] = $sFieldKey;
	}
}

/**
 * Make sure the password fields aren´t assigned a value
 */
foreach( array('userPass', 'userPassConfirm') as $key ) {
	unset( $_POST[$key] );
}

/**
 * User terms
 */
$aFormDataDict['userTerms'] = array(
	'type' => 'boolean',
	'values' => array(
		'yes' => _( 'Yes' ),
		'no' => _( 'No' )
	),
	'title' => _( 'Yes, I agree to the' ) . ' <a href="' . $sUserTermsUrl . '" target="_blank">' . _( 'terms of use' ) . '</a>'
);
$aFormGroups['userAgreement'] = array(
	'title' => _( 'Confirm User Terms' ),
	'fields' => array( 'userTerms' )
);

if( empty($_POST['orderPaymentSame']) ) {
	$_POST['orderPaymentSame'] = 'yes';
}
if( empty($_POST['orderDeliverySame']) ) {
	$_POST['orderDeliverySame'] = 'yes';
}

$oOutputHtmlForm->init( $aUserDataDict, array(
	'attributes'	=> array(),
	'labelSuffix'	=> '',
	'errors'		=> array(),
	'data'			=> $_POST,
	'errors' 		=> $aErr,
	'method'		=> 'post',
	'buttons'		=> array(
		'submit' => array(
			'content' => _( 'Register' ),
			'attributes' => array(
				'name' => 'frmUserRegister'
			)
		),
		'button' => array(
			'content' => _( 'Cancel' ),
			'attributes' => array(
				'onclick' => 'registerCustomer();'
			)
		)		
	),
	'extraElementWrapper' => true
) );

$oOutputHtmlForm->setFormDataDict( $aFormDataDict );
$oOutputHtmlForm->setGroups( $aFormGroups );

echo '
	<div class="view customer signup">
		<h1>' . _( 'Signup' ) . '</h1>
		<div class="information">
			<p>
				' . _( 'Required fields are marked with' ) . ' *
			</p>
			<p>
				' . _( 'Already a customer?' ) . ' <a href="' . $oRouter->getPath( 'userLogin' ) . '" onclick="javascript:registerCustomer();">' . _( 'Click here to login' ) . '.</a>
			</p>
		</div>
		' . $oOutputHtmlForm->render() . '
	</div>';

$oTemplate->addBottom( array(
	'key' => 'signUpJs',
	'content' => '
		<script>
			$(document).ready( function() {
				$("#infoCustomerGroup").bind( "change", function() {
					$(this).parents("form").submit();
				} );
				
				/**
				 * PaymentSame switching
				 */	
				$(document).delegate( ".fieldGroup.payment .orderPaymentSame input", "change", function() {
					if( $(this).is(":checked") ) {
						$(".fieldGroup.payment .field:not(.orderPaymentSame)").hide();
					} else {
						$(".fieldGroup.payment .field:not(.orderPaymentSame)").show();
					}
				} );
				
				/**
				 * DeliverySame switching
				 */
				$(document).delegate( ".fieldGroup.delivery .orderDeliverySame input", "change", function() {
					if( $(this).is(":checked") ) {
						$(".fieldGroup.delivery .field:not(.orderDeliverySame)").hide();
					} else {
						$(".fieldGroup.delivery .field:not(.orderDeliverySame)").show();
					}
				} );
			} );
		</script>
	'
) );