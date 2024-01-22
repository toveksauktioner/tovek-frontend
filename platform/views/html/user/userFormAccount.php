<?php
$aErr = array();

$oDataValidation = clRegistry::get( 'clDataValidation' );
// $oUser = clRegistry::get( 'clUser', PATH_MODULE . '/user/models' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oUserSettings = clRegistry::get( 'clUserSettings', PATH_MODULE . '/userSettings/models' );

$aAvailableSettings = valueToKey( 'settingsKey', $oUserSettings->readByUserGroup('user') );
$aUserSettings = arrayToSingle( $oUserSettings->readUserSetting($_SESSION['userId']), 'settingsKey', 'settingsValue' );

$aUserDataDict = $oUser->oDao->getDataDict();
$aCountriesData = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName',
	'countryIsoCode2'
) );

$aUserDataDict = $oUser->oDao->getDataDict();
$aCountriesData = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName'
) );

$aCountries = array();
foreach( $aCountriesData as $entry ) {
	$aCountries[ $entry['countryId'] ] = _( $entry['countryName'] );
}

$aFormDataDict = array(
	'userType' => array(
		'type' => 'array',
		'title' => _( 'Customer type' ),
		'values' => array(
			'privatePerson' => _( 'Private person' ),
			'company' => _( 'Company' )
		),
		'attributes' => array(
			'disabled' => true
		),
		'fieldAttributes' => array(
			'class' => 'disabled'
		)
	),
	'username' => array(
		'attributes' => array(
			'disabled' => true
		),
		'fieldAttributes' => array(
			'class' => 'disabled'
		)
	),
	'userPin' => array(
		'title' => ( 'Org nr' ),
		'attributes' => array(
			'disabled' => true
		),
		'fieldAttributes' => array(
			'class' => 'disabled'
		)
	),
	'infoName' => array(
		'title' => _( 'Company' ),
		'attributes' => array(
			'disabled' => true
		),
		'fieldAttributes' => array(
			'class' => 'disabled'
		)
	),
	'infoFirstname' => array(
		'required' => true,
		'attributes' => array(
			'disabled' => true
		),
		'fieldAttributes' => array(
			'class' => 'disabled'
		)
	),
	'infoSurname' => array(
		'required' => true,
		'attributes' => array(
			'disabled' => true
		),
		'fieldAttributes' => array(
			'class' => 'disabled'
		)
	),
	'infoVatNo' => array(
		'required' => true
	),
	'infoContactPerson' => array(
		'title' => _( 'Contact person' ),
		'required' => true
	),
	'infoAddress' => array(
		'title' => _( 'Address' )
	),
	'infoZipCode' => array(
		'title' => _( 'Zip code' ),
		'required' => true,
		'fieldAttributes' => array(
			'class' => 'number'
		)
	),
	'infoCity' => array(
		'title' => _( 'Postort' ),
		'required' => true
	),
	'infoCountry'  => array(
		'type' => 'array',
		'values' => $aCountries,
		'defaultValue' => 210, # 210 = Sweden
		'title' => _( 'Country' ),
		'required' => true
	),
	'userEmail' => array(
		'title' => _( 'E-post' ),
		'attributes' => array(
			'disabled' => true
		),
		'fieldAttributes' => array(
			'class' => 'disabled email'
		)
	),
	'userNewsletterSignup' => array(
		'title' => _( 'I want to receive reccuring newsletters' ),
		'type' => 'boolean',
		'values' => array(
			1 => ''
		),
		'fieldAttributes' => array(
			'class' => 'userNewsletterSignup checkbox'
		)
	),
	'infoPhone' => array(
		'required' => true,
		'title' => _( 'Phone' ),
		'fieldAttributes' => array(
			'class' => 'phone'
		)
	),
	'infoCellPhone' => array(
		'title' => _( 'Cell phone' ),
		'fieldAttributes' => array(
			'class' => 'phone'
		)
	),
	'frmUserEdit' => array(
		'type' => 'hidden',
		'value' => 1
	)
);

/**
 * Adding settings fields to dataDict
 */
$bFormNotSent = ( empty($_POST) ? true : false );
foreach( $aAvailableSettings as $aSettingData ) {
	$aSplittedValues = explode( ',', $aSettingData['settingsValues'] );
	$aValues = array();
	foreach( $aSplittedValues as $sSplittedValue ) {
		list( $sKey, $sValue ) = explode( '|', $sSplittedValue );
		$aValues[$sKey] = $sValue;
	}

	switch( $aSettingData['settingsType'] ) {
		case 'select':
			$aNotificationDataDict[ $aSettingData['settingsKey'] ] = array(
				'type' => 'array',
				'values' => $aValues
			);
			break;

		case 'radio':
			$aNotificationDataDict[ $aSettingData['settingsKey'] ] = array(
				'type' => 'array',
				'appearance' => 'full',
				'values' => $aValues
			);
			break;

		case 'checkbox':
			$aNotificationDataDict[ $aSettingData['settingsKey'] ] = array(
				'type' => 'boolean',
				'fieldAttributes' => array(
					'class' => 'userNewsletterSignup checkbox'
				),
				'values' => $aValues
			);
			break;

		case 'text':
			$aNotificationDataDict[ $aSettingData['settingsKey'] ] = array(
				'type' => 'string',
				'defaultValue' => $aSettingData['settingsValues']
			);
		default:
			break;
	}

	$aNotificationDataDict[ $aSettingData['settingsKey'] ]['title'] = _( $aSettingData['settingsTitle'] );

	if( $bFormNotSent ) {
		if( !empty($aUserSettings[ $aSettingData['settingsKey'] ]) ) {
			$_POST[ $aSettingData['settingsKey'] ] = $aUserSettings[ $aSettingData['settingsKey'] ];
		} else {
			unset( $_POST[ $aSettingData['settingsKey'] ] );
		}
	} else if( empty($_POST[ $aSettingData['settingsKey'] ]) ) {
		unset( $_POST[ $aSettingData['settingsKey'] ] );
	}
}

if( !empty($_SESSION['userId']) ) {
	$aReadFields = $aFormDataDict;
	unset( $aReadFields['userNewsletterSignup'] );
	unset( $aReadFields['frmUserEdit'] );
	$aUserData = current( $oUser->oDao->read( array(
		'fields' => array_keys($aReadFields),
		'userId' => $_SESSION['userId']
	) ) );

	unset( $_POST['userType'] );
	unset( $_POST['username'] );
	unset( $_POST['infoName'] );
	unset( $_POST['infoFirstname'] );
	unset( $_POST['infoSurname'] );
	unset( $_POST['userPin'] );
	unset( $_POST['userEmail'] );
}

// Newsletter setting
$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );
if( !empty($aUserData['userEmail']) ) {
	$aNewsletterData = current( $oNewsletterSubscriber->readByEmail($aUserData['userEmail'], array(
		'subscriberId',
		'subscriberStatus',
		'subscriberUnsubscribe'
	)) );

	if( !empty($aNewsletterData) ) {
		$iNewsletterSubscriberId = $aNewsletterData['subscriberId'];

		if( ($aNewsletterData['subscriberStatus'] == 'active') && ($aNewsletterData['subscriberUnsubscribe'] == 'no') ) {
			$aUserData['userNewsletterSignup'] = 'yes';
		}
	}
}

if( $aUserData['userType'] == 'privatePerson' ) {
	// Change titles
	$aFormDataDict['userPin']['title'] = _( 'SSN/PIN' );
	$aFormDataDict['userPin']['suffixContent'] = ' ' . _( 'YYMMDDXXXX' );

	// Unset company specific inputs
	unset( $aFormDataDict['infoContactPerson'], $_POST['infoContactPerson'], $aFormDataDict['infoName'], $_POST['infoName'] );
} elseif( $aUserData['userType'] == 'company' ) {
	// Unset privatePerson specific inputs
	unset( $aFormDataDict['infoFirstname'], $aFormDataDict['infoSurname'], $_POST['infoFirstname'], $_POST['infoSurname'] );
}

// Vat-no only for foreign countries
if( ($aUserData['userType'] == 'company') && ($aUserData['infoCountry'] != 210) ) {
	unset( $aFormDataDict['userPin'] );
} else {
	unset( $aFormDataDict['infoVatNo'] );
}

// Handle registration
if( isset($_POST['frmUserEdit']) ) {
	unset( $_POST['frmUserEdit'] );

	foreach( $aAvailableSettings as $sSettingsKey => $aSettingsData ) {
		// Save the settings fields
		if( !empty($_POST[$sSettingsKey]) ) {
			if( is_array($_POST[$sSettingsKey]) ) $_POST[$sSettingsKey] = current( $_POST[$sSettingsKey] );

			if( isset($aUserSettings[$sSettingsKey]) ) {
				$oUserSettings->updateUserSetting( $_SESSION['userId'], $sSettingsKey, $_POST[$sSettingsKey] );
			} else {
				$oUserSettings->createUserSetting( $_SESSION['userId'], $sSettingsKey, $_POST[$sSettingsKey] );
			}
		} else {
			$oUserSettings->deleteUserSetting( $_SESSION['userId'], $sSettingsKey );
			unset($_POST[$sSettingsKey]);
		}

		// Re-read the settings
		$aUserSettings = arrayToSingle( $oUserSettings->readUserSetting($_SESSION['userId']), 'settingsKey', 'settingsValue' );
	}

	require_once PATH_FUNCTION . '/fUser.php';

	// Update newsletter subscription
	if( !empty($_POST['userNewsletterSignup']) ) {
		$aNewsletterPostData = array(
			'subscriberStatus' => 'active',
			'subscriberUnsubscribe' => 'no'
		);

		if( isset($iNewsletterSubscriberId) ) {
			// Update
			$oNewsletterSubscriber->update( $iNewsletterSubscriberId, $aNewsletterPostData );
		} else {
			// Create
			$aNewsletterPostData += array(
				'subscriberEmail' => $aUserData['userEmail'],
				'subscriberCreated' => date( 'Y-m-d H:i:s' )
			);
			$iSubscriberId = $oNewsletterSubscriber->create( $aNewsletterPostData );
		}

		$aUserData['userNewsletterSignup'] = 'yes';

	} else {
		$aNewsletterPostData = array(
			'subscriberStatus' => 'inactive',
			'subscriberUnsubscribe' => 'yes'
		);

		if( isset($iNewsletterSubscriberId) ) {
			// Update
			$oNewsletterSubscriber->update( $iNewsletterSubscriberId, $aNewsletterPostData );
		}

		unset( $aUserData['userNewsletterSignup'] );
	}
	unset( $_POST['userNewsletterSignup'] );

	// Compile infoName from first- and surname, or unset them if company
	if( $aUserData['userType'] == 'privatePerson' ) {
		if( !empty($_POST['infoFirstname']) && !empty($_POST['infoFirstname']) ) {
			$_POST['infoName'] = $_POST['infoFirstname'] . ' ' . $_POST['infoSurname'];
		}
		// Re-add infoName so that it gets updated
		$aFormDataDict[ 'infoName' ] = array(
			'title' => _( 'Name' )
		);
	} else {
		unset($_POST['infoFirstname'], $_POST['infoSurname'] );
	}

	if( empty($aErr) ) {

		// Try to update data and if it fails add error message
		if( $oUser->updateData( array_intersect_key($_POST, $aFormDataDict) ) ) {
			$oNotification->set( array('updateUserData' => _('Your account information has been updated.')) );
		} else {
			$aErr += clErrorHandler::getValidationError( 'updateUser' );
		}

	}

	if( $aUserData['userType'] == 'privatePerson' ) unset( $aFormDataDict[ 'infoName' ] );
}

// Prefill inputs with user data
if( !empty($aUserData) ) $_POST += $aUserData;

$oOutputHtmlForm->init( $aUserDataDict, array(
	'attributes'	=> array(
		'class' => 'newForm framed columns',
		'id' => 'userRegisterForm'
	),
	'labelSuffix'	=> '',
	'errors'		=> array(),
	'data'			=> $_POST,
	'errors' 		=> $aErr,
	'method'		=> 'post',
	'buttons'		=> array(
		'submit' => _( 'Spara ändringar' )
	)
) );
$oOutputHtmlForm->setFormDataDict( $aNotificationDataDict + $aFormDataDict );
$oOutputHtmlForm->setGroups( array(
	'basic' => array(
		'title' => _( 'Grunduppgifter' ),
		'fields' => array(
			'userType',
			'username',
			'userPin',
			'infoName',
			'infoFirstname',
			'infoSurname',
			'infoContactPerson',
			'infoVatNo'
		)
	),
	'address' => array(
		'title' => _( 'Adress' ),
		'fields' => array(
			'infoAddress',
			'infoZipCode',
			'infoCity',
			'infoCountry'
		)
	),
	'contact' => array(
		'title' => _( 'Kontaktuppgifter' ),
		'fields' => array(
			'userEmail',
			'infoPhone',
			'infoCellPhone'
		)
	),
	'notifications' => array(
		'title' => _( 'Notifieringar' ),
		'fields' => array_merge( array_keys($aNotificationDataDict), array('userNewsletterSignup') )
	),
	'hidden' => array(
		'title' => '',
		'fields' => array(
			'frmUserEdit'
		)
	),
) );

echo '
	<div class="view userSignup">
		<div class="information">
			' . ( ($aText = $oInfoContent->read('contentTextId', 39)) ? $aText[0]['contentTextId'] : null ) . '
		</div>
		' .	$oOutputHtmlForm->renderForm(
				$oOutputHtmlForm->renderErrors() .
				'<a href="' . $oRouter->getPath( 'guestHelp' ) . '?t=68&c=5" class="popupLink button help ajax columnSpanFull" data-size="full">Hjälp med mitt konto</a>' .
				$oOutputHtmlForm->renderGroups() .
				$oOutputHtmlForm->renderButtons()
		) . '
	</div>';

$oTemplate->addScript( array(
	'key' => 'popup',
	'src' => '/js/templates/tovekCommon/popup.js'
) );
$oTemplate->addScript( array(
	'key' => 'form',
	'src' => '/js/templates/tovek/form.js'
) );
