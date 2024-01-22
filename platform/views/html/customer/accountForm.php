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

if( empty($_SESSION['userId']) ) {	
	$oRouter->redirect( $oRouter->getPath( 'guestCustomerSignup' ) );
}

$aErr = array();

$oUser = clRegistry::get( 'clUser' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

if( !empty($_SESSION['customer']['editData']) && $_SESSION['customer']['editData'] == 'successful' ) {
	unset( $_SESSION['customer']['editData'] );
	
	$oNotification = clRegistry::get( 'clNotificationHandler' );
	$oNotification->set( array(
		'dataSaved' => _( 'The data has been saved' )
	) );
}

// DataDict
$aFormDataDict = array(
	'infoCustomerGroup' => array(
		'type' => 'array',
		'values' => array(),
		'title' => _( 'Customer type' ),
		'infoGroup' => 'generic',
		'attributes' => array(
			'disabled' => 'disabled',
			'class' => 'disabled'
		)
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
		'title' => _( 'New password' ),
		'appearance' => 'secret',
		'min' => 4,
		'max' => 100,
		'infoGroup' => 'account'
	),
	'userPassConfirm' => array(
		'title' => _( 'Confirm new password' ),
		'appearance' => 'secret',
		'min' => 4,
		'max' => 100,
		'infoGroup' => 'account'
	)
);

/**
 * All readable fields
 */
$aReadFields = array();
foreach( $oCustomer->oDao->aDataDict as $sEntity => $aDataDict ) {
	if( $sEntity == 'entUser' ) continue;
	$aReadFields = array_merge( $aReadFields, array_keys($aDataDict) );
}
$aReadFields[] = 'userEmail';

/**
 * Customer data
 */
$aCustomerData = current( $oCustomer->readByUserId( $_SESSION['userId'], $aReadFields ) );

if( empty($aCustomerData) && empty($_SESSION['userId']) ) {
	$oRouter->redirect( $oRouter->getPath( 'guestCustomerSignup' ) );
	
} elseif( empty($aCustomerData) && !empty($_SESSION['userId']) ) {
	echo '
		<div class="view userCustomerAccount">
			<h1>' . _( 'My page' ) . '</h1>
			' . _( 'No customer account' ) . '
		</div>';
		
	return;
}

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
if( isset($_POST['frmEdit']) ) {
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
	switch( $aCustomerData['groupId'] ) {
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
	
	// Data validation
	$_POST = array_intersect_key( $_POST, $aUserDataDict['userDataDict']  );	
	$aErr += clDataValidation::validate( $_POST, $aUserDataDict );
	
	/**
	 * Data updates
	 */
	if( empty($aErr) ) {
		/**
		 * Change password
		 */
		if( !empty($_POST['userPass']) && !empty($_POST['userPassConfirm']) && ($_POST['userPass'] === $_POST['userPassConfirm']) ) {
			if( $oUser->updatePass($_POST['userPass']) ) {
				$oNotification->set( array('updateUserPass' => _( 'The password has changed' )) );					
			} else {
				$aErr += clErrorHandler::getValidationError( 'updateUserPass' );
			}
		} elseif( !empty($_POST['userPass']) && !empty($_POST['userPassConfirm']) && ($_POST['userPass'] != $_POST['userPassConfirm']) ) {
			$aErr[] = _( 'The passwords do not match' );
		}
			
		/**
		 * Change email
		 */
		if( !empty($_POST['userEmail']) && !empty($_POST['userPass']) ) {
			if( $oUser->updateEmail($_POST['userEmail'], $_POST['userPass']) ) {
				$oNotification->set( array('updateUserEmail' => _( 'The email has been changed' )) );
			} else {
				$aErr += clErrorHandler::getValidationError( 'updateUserEmail' );
			}
		}
			
		/**
		 * Unset user fields
		 */
		unset(
			$_POST['userPass'],
			$_POST['userPassConfirm'],
			$_POST['userEmail']
		);
		
		/**
		 * Update user data
		 */
		$oUser->updateData( $_POST );
		$aErr += clErrorHandler::getValidationError( 'updateUser' );
		
		if( empty($aErr) ) {
			$_SESSION['customer']['editData'] = 'successful';
			$oRouter->redirect( $oRouter->sPath );
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

switch( $aCustomerData['groupId'] ) {
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

$oOutputHtmlForm->init( $aUserDataDict, array(
	'attributes'	=> array(),
	'labelSuffix'	=> '',
	'errors'		=> array(),
	'data'			=> $aCustomerData,
	'errors' 		=> $aErr,
	'method'		=> 'post',
	'buttons'		=> array(
		'submit' => array(
			'content' => _( 'Save' ),
			'attributes' => array(
				'name' => 'frmEdit'
			)
		)		
	)
) );

$oOutputHtmlForm->setFormDataDict( $aFormDataDict );
$oOutputHtmlForm->setGroups( $aFormGroups );

echo '
	<div class="view userCustomerAccount">
		<h1>' . _( 'My page' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
	</div>';

$oTemplate->addBottom( array(
	'key' => 'signUpJs',
	'content' => '
		<script>
			$(document).ready( function() {
				//$("#infoCustomerGroup").bind( "change", function() {
				//	$(this).parents("form").submit();
				//} );
				
				/**
				 * PaymentSame switching
				 */
				//$(".fieldGroup.payment .orderPaymentSame input").prop("checked", true);				
				//if( $(".fieldGroup.payment .orderPaymentSame input").is(":checked") ) {
				//	$(".fieldGroup.payment .field:not(.orderPaymentSame)").hide();
				//}	
				$(".fieldGroup.payment .orderPaymentSame input").change( function() {
					if( $(this).is(":checked") ) {
						$(".fieldGroup.payment .field:not(.orderPaymentSame)").hide();
					} else {
						$(".fieldGroup.payment .field:not(.orderPaymentSame)").show();
					}
				} );
				
				/**
				 * DeliverySame switching
				 */
				//$(".fieldGroup.delivery .orderDeliverySame input").prop("checked", true);				
				//if( $(".fieldGroup.delivery .orderDeliverySame input").is(":checked") ) {
				//	$(".fieldGroup.delivery .field:not(.orderDeliverySame)").hide();
				//}	
				$(".fieldGroup.delivery .orderDeliverySame input").change( function() {
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