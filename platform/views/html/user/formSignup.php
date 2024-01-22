<?php

if( !empty($GLOBALS['registrationBlocked']) && ($GLOBALS['registrationBlocked'] === true) ) {
	echo $GLOBALS['registrationBlockedMessage'];
	return;
}

if( !empty($_SESSION['userId']) ) $oRouter->redirect( $oRouter->getPath( 'userAccount' ) );

$aErr = array(
	'createUser' => array()
);

$oUser = clRegistry::get( 'clUser' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
$oCreditRating = clRegistry::get( 'clCreditRatingCreditsafe', PATH_MODULE . '/creditRating/models' );
$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );
$oEmailQueue = clRegistry::get( 'clEmailQueue', PATH_MODULE . '/email/models' );
$oSystemText = clRegistry::get( 'clSystemText', PATH_MODULE . '/systemText/models' );

$aUserDataDict = $oUser->oDao->getDataDict();
$aCountriesData = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName',
	'countryIsoCode2'
) );

$aCountries = array();
$aCountriesIsoCode = array();
foreach( $aCountriesData as $entry ) {
	$aCountries[ $entry['countryId'] ] = _( $entry['countryName'] );
	$aCountriesIsoCode[ $entry['countryId'] ] = $entry['countryIsoCode2'];
}

// Handle customer type
if( empty($_SESSION['userType']) ) $_SESSION['userType'] = USER_TYPE_DEFAULT_VALUE;
if( !empty($_POST['userType']) ) $_SESSION['userType'] = $_POST['userType'];
$sCustomerType = $_SESSION['userType'];

$iDefaultCountryId = ( !empty($_POST['infoCountry']) ? $_POST['infoCountry'] : 210 ); # 210 = Sweden
$sDefaultVatNo = $aCountriesIsoCode[$iDefaultCountryId];

$aFormDataDict = array(
	'infoCountry'  => array(
		'type' => 'array',
		'values' => $aCountries,
		'defaultValue' => $iDefaultCountryId,
		'title' => _( 'Country' ),
		'required' => true
	),
	'userType' => array(
		'type' => 'array',
		'values' => array(
			'privatePerson' => _( 'Private person' ),
			'company' => _( 'Company' )
		),
		'suffixContent' => '<input type="submit" id="updateCustomerType" />',
		'defaultValue' => $sCustomerType,
		'title' => _( 'Customer type' )
	),
	'username' => array(
		'title' => _( 'Username' )
	),
	'infoName' => array(
		'title' => _( 'Company' )
	),
	'userPass' => array(
		'title' => _( 'Password' ),
		'required' => true,
		'appearance' => 'secret',
		'min' => 4,
		'max' => 100,
		'fieldAttributes' => array(
			'class' => 'password'
		)
	),
	'userPassConfirm' => array(
		'title' => _( 'Confirm password' ),
		'required' => true,
		'appearance' => 'secret',
		'min' => 4,
		'max' => 100,
		'fieldAttributes' => array(
			'class' => 'password'
		)
	),
	'infoFirstname' => array(
		'required' => true
	),
	'infoSurname' => array(
		'required' => true
	),
	'userEmail' => array(
		'title' => _( 'E-post' ),
		'required' => true,
		'fieldAttributes' => array(
			'class' => 'email'
		)
	),
	'userPin' => array(
		'title' => ( 'Org nr' )
	),
	'infoVatNo' => array(
		'defaultValue' => $sDefaultVatNo,
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
	'userNewsletterSignup' => array(
		'title' => _( 'I want to receive newsletters' ),
		'type' => 'boolean',
		'fieldAttributes' => array(
			'class' => 'userNewsletterSignup checkbox'
		),
		'values' => array(
			1 => '1'
		)
	),
	'infoCellPhone' => array(
		'title' => _( 'Cell phone' ),
		'required' => true,
		'fieldAttributes' => array(
			'class' => 'phone'
		)
	),
	'infoPhone' => array(
		'title' => _( 'Alternativ telefon' ),
		'fieldAttributes' => array(
			'class' => 'phone'
		)
	)
);

$aEmailFields = array(
	'userType',
	'username',
	'userPin',
	'userEmail',
	'infoName',
	'infoAddress',
	'infoZipCode',
	'infoCity',
	'infoCountry',
	'infoPhone'
);

if( !empty($_POST) ) {
	/**
	 * Development logging stuff
	 * Save all tries to see where things get wrong
	 */
	$oLogger = clRegistry::get( 'clLogger' );
	$oLogger->log( var_export($_POST, true), 'userFormSignup.log' );
}

// Special checks for swedish registrations
if( isset($_POST['infoCountry']) && ($_POST['infoCountry'] == 210) && isset($_POST['frmUserRegister']) ) {

	// UserPin is required
	if( !empty($_POST['userPin']) ) {

		// Make the userPin field in correct format and strip invalid chars
		$_POST['userPin'] = preg_replace( '/\D/', '', $_POST['userPin'] );

		if( strlen($_POST['userPin']) == 12 ) {
			$_POST['userPin'] = substr( $_POST['userPin'], 2 );
		}
		if( (strlen($_POST['userPin']) != 10) ) {
			$aErr['createUser']['userPin'] = _( 'SSN/PIN' ) . ' ' . _( 'is not valid' );
		}

		if( $_POST['userType'] == 'privatePerson' ) {
			$oUserManager = clRegistry::get( 'clUserManager' );
			$aData = $oUserManager->isUser( array(
				'userType' => $_POST['userType'],
				'userPin' => $_POST['userPin']
			), 'AND' );
			if( !empty($aData) ) {
				$aErr['createUser']['userPin'] = _( 'SSN/PIN' ) . ' ' . _( 'finns redan registrerat' );
			}
		}
	} else {
		$aErr['createUser']['userPin'] = _( 'SSN/PIN' ) . ' ' . _( 'is not valid' );
	}
}

if( $sCustomerType == 'privatePerson' ) {
	// Change titles
	$aFormDataDict['userPin']['title'] = _( 'SSN/PIN' );

	// Unset company specific inputs
	unset( $aFormDataDict['infoContactPerson'], $_POST['infoContactPerson'], $aFormDataDict['infoName'], $aFormDataDict['infoVatNo'] );

	// Special critera for swedish registrations
	if( empty($_POST['infoCountry']) || ($_POST['infoCountry'] == 210) ) {
		unset( $aFormDataDict['infoVatNo'] );
		$aFormDataDict['userPin']['suffixContent'] = ' ' . _( 'Entered in the format' ) . ' ' . _( 'YYMMDDXXXX' );
	}
} elseif( $sCustomerType == 'company' ) {
	// Unset privatePerson specific inputs
	unset( $aFormDataDict['infoFirstname'], $aFormDataDict['infoSurname'] );

	// Hide VAT-no for Swedish companies - hide the pin field for other
	if( empty($_POST['infoCountry']) || ($_POST['infoCountry'] == 210) ) {
		unset( $aFormDataDict['infoVatNo'] );
	} else {
		unset( $aFormDataDict['userPin'] );
	}
}

// Email must be set and have correct syntax
if( (empty($_POST['userEmail']) || !clDataValidation::isEmail($_POST['userEmail'])) && isset($_POST['frmUserRegister']) ) {
	$aErr['createUser']['userEmail'] = _( 'Email' ) . ' ' . _( 'is not valid' );
}

// Set the initial digits of the VAT no
if( empty($_POST['infoVatNo']) ) {
	$_POST['infoVatNo'] = $sDefaultVatNo;
} else {
	// Force beginning of VAT to country ISO - Greece is anomoly so dont use this

	// $sFirstThree = substr( trim($_POST['infoVatNo']), 0, 3 );
	//
	// preg_match( "/[A-Za-z][A-Za-z][[\\s]/", $sFirstThree, $aMatches);
	// if( !empty($aMatches) ) {
	// 	$sWithoutFirstThree = substr( trim($_POST['infoVatNo']), 3 );
	// } else {
	// 	$sWithoutFirstThree = trim($_POST['infoVatNo']);
	// }
	//
	// $_POST['infoVatNo'] = $sDefaultVatNo . $sWithoutFirstThree;
}

// Handle registration
if( isset($_POST['frmUserRegister']) ) {
	try {
		require_once PATH_FUNCTION . '/fUser.php';

		// Validate reCAPTCHA
		//$aErrRecaptcha = clDataValidation::validateRecaptcha( 'passRetrieval' );
		//if( !empty($aErrRecaptcha) ) {
		//	$aErr['createUser']['g-recaptcha'] = _( '"Jag är inte en robot" måste vara ikryssad' );
		//}

		// Blacklist check
		$oUserBlacklist = clRegistry::get( 'clUserBlacklist', PATH_MODULE . '/userBlacklist/models' );
		if( !empty($GLOBALS['registrationCheck']) ) {
			$aBlacklistCriterias = array();
			foreach( $GLOBALS['registrationCheck'] as $sUserField => $sBlacklistField ) {
				if( !empty($_POST[$sUserField]) ) {
					$aBlacklistCriterias[] = $sBlacklistField . ' = ' . $oUserBlacklist->oDao->oDb->escapeStr( $_POST[$sUserField] );
				}
			}
			$aBlacklistData = $oUserBlacklist->oDao->readData( array(
				'criterias' => implode( ' OR ', $aBlacklistCriterias )
			) );
			if( !empty($aBlacklistData) ) {
				$aErr['createUser'][] = _( 'Registreringen godkändes inte. Ring oss på 0346-487 70 så hjälper vi dig.' );
			}
		}

		// Check that the username does not contain invalid phrases
		foreach( $GLOBALS['denyUsernamesIncluding'] as $sDeniedValue ) {
			if( stristr($_POST['username'], $sDeniedValue) ) {
				$aErr['createUser']['username'] = 'Användarnamnet är inte godkänt';
				break;
			}
		}

		// Check that the username does not contain invalid characters
		if( preg_match(REGEX_USERNAME, $_POST['username']) ) {
			$aErr['createUser']['username'] = 'Användarnamnet innehåller ogiltiga tecken. Bara bokstäver, siffror, - och _ är giltiga.';
		}

		// Allow user to write to user table
		$oAcl = new clAcl();
		$oAcl->setAcl( array('writeUser' => 'allow', 'readUser' => 'allow') );
		$oUserManager = clRegistry::get( 'clUserManager' );
		$oUserManager->setAcl( $oAcl );

		// Compile infoName from first- and surname, or unset them if company
		if( !empty($_POST['userType']) && $_POST['userType'] == 'privatePerson' ) {
			if( !empty($_POST['infoFirstname']) && !empty($_POST['infoFirstname']) ) {
				$_POST['infoName'] = $_POST['infoFirstname'] . ' ' . $_POST['infoSurname'];
			}
		} else {
			unset($_POST['infoFirstname'], $_POST['infoSurname'] );
			$oUserManager->oDao->aDataDict['entUserInfo']['infoFirstname']['required'] = false;
			$oUserManager->oDao->aDataDict['entUserInfo']['infoSurname']['required'] = false;
		}

		// If it is an non-swedish registrar userPin has no requirements
		if( $_POST['infoCountry'] != 210 ) {
			unset( $oUserManager->oDao->aDataDict['entUser']['userPin']['max'] );
			unset( $oUserManager->oDao->aDataDict['entUser']['userPin']['min'] );
			unset( $oUserManager->oDao->aDataDict['entUser']['userPin']['required'] );
		}

		/*** Make sure that passwords is equal **/
		if( $_POST['userPass'] !== $_POST['userPassConfirm'] ) $aErr['createUser']['userPassConfirm'] = _( 'The passwords don´t match' );

		if( ($_POST['userType'] == 'privatePerson') && !empty($_POST['userPin']) ) {
			list( $iYear, $iMonth, $iDay ) = str_split( $_POST['userPin'], 2 );
			if( $iYear > date('y') ) {
				$iYear += 1900;
			} else {
				$iYear += 2000;
			}

			if( checkdate($iMonth, $iDay, $iYear) ) {
				$oBirthDate = new DateTime( $iYear . '-' . $iMonth . '-' . $iDay  );
				$oToday = new DateTime( '00:00:00' );

				$oDiff = $oToday->diff( $oBirthDate );
				if( $oDiff->y < 18 ) $aErr['createUser'][] = _( 'Du måste vara minst 18 år gammal' );
			}
		}
		
		/*** Make sure agreement is accepted **/
		if( empty($_POST['agreement']) ) $aErr['createUser']['agreement'] = _( 'Du måste godkänna registreringsavtalet' );

		/**
		 * Handle and remove newsletter field before validation
		 */
		$aNewsletterPostData = array();
		if( !empty($_POST['userNewsletterSignup']) ) {
			if( !empty($_POST['userEmail']) ) {
				$aNewsletterSubscriber = current( $oNewsletterSubscriber->readByEmail($_POST['userEmail'], 'subscriberId') );
				if( !empty($aNewsletterSubscriber) ) {
				 $aNewsletterPostData['subscriberId'] = $aNewsletterSubscriber['subscriberId'];
			 }
			}
			$aNewsletterPostData = array(
				'subscriberStatus' => 'active',
				'subscriberUnsubscribe' => 'no'
			);

			if( !isset($aNewsletterPostData['subscriberId']) ) {
				// Create
				$aNewsletterPostData += array(
					'subscriberEmail' => $_POST['userEmail'],
					'subscriberCreated' => date( 'Y-m-d H:i:s' )
				);
			}
		}
		unset( $_POST['userNewsletterSignup'] );


		if( empty($aErr['createUser']) ) {
			if( empty($_SESSION['userId']) ) {
				/* CREDIT RATING
				 * Get credit rating info to confirm the data submitted
				*/
				if( $_POST['infoCountry'] == 210 ) {
					// Credit rating is only applied on swedish registrars

					$bPassedCreditCheck = false;

					if( $_POST['userType'] == 'privatePerson' ) {
						// Private person checkup

						$aData = array(
							'SearchNumber' 	=> $_POST['userPin'],
							'Block_Name' 		=> 'TOVEK_P_BASIC'
						);
						$bPassedCreditCheck = $oCreditRating->getDataBySecure( $aData );

						$aDataToValidate = array(
							'NewDataSet' => array(
								'GETDATA_RESPONSE' => array(
									'FIRST_NAME' => array(
										'type' => 'IN',
										'value' => $_POST['infoFirstname'],
										'onFail' => 'deny'
									),
									'LAST_NAME' => array(
										'type' => 'IN',
										'value' => $_POST['infoSurname'],
										'onFail' => 'deny'
									),
									'ZIPCODE' => array(
										'type' => 'EQUALS',
										'value' => $_POST['infoZipCode'],
										'onFail' => 'warning'
									),
									'TOWN' => array(
										'type' => 'EQUALS',
										'value' => $_POST['infoCity'],
										'onFail' => 'warning'
									)
								)
							)
						);
					} else {

						if( substr($_POST['userPin'], 0, 3) == '556' ) {
							// Joint-stock companies checkup

							$aData = array(
								'SearchNumber' 	=> $_POST['userPin'],
								'Templates' 		=> 'TOVEKC2'
							);
							$bPassedCreditCheck = $oCreditRating->casCompanyService( $aData );

							$aDataToValidate = array(
								'CASCOMPANYSERVICERESULT' => array(
									'STATUS' => array(
										'type' => 'EQUALS',
										'value' => 1,
										'onFail' => 'deny'
									)
								)
							);
						} else {
							// Non joint-stock companies checkup

							$aData = array(
								'SearchNumber' 	=> $_POST['userPin'],
								'Block_Name' 		=> 'TOVEK_C_BASIC'
							);

							$bPassedCreditCheck = $oCreditRating->getDataBySecure( $aData );

							$aDataToValidate = array(
								'NewDataSet' => array(
									'GETDATA_RESPONSE' => array(
										'NAME' => array(
											'type' => 'IN',
											'value' => $_POST['infoName'],
											'onFail' => 'deny'
										),
										'ZIPCODE' => array(
											'type' => 'EQUALS',
											'value' => $_POST['infoZipCode'],
											'onFail' => 'warning'
										),
										'TOWN' => array(
											'type' => 'EQUALS',
											'value' => $_POST['infoCity'],
											'onFail' => 'warning'
										),
										'COMPANY_STATUS' => array(
											'type' => 'EQUALS',
											'value' => 'Aktivt',
											'onFail' => 'deny'
										)
									)
								)
							);
						}
					}

					$_POST += array(
						'infoCreditChecked' => date( 'Y-m-d H:i:s' ),
						'infoCreditRatingId'			=> $oCreditRating->iRatingId
					);

					if( $bPassedCreditCheck === true ) {
						$mPassedCreditCheck = $oCreditRating->validateData( $aDataToValidate );

						if( $mPassedCreditCheck === true ) {
							$_POST += array(
								'infoCreditCheckedResult' => 'approved',
								'userGrantedStatus' 				=> 'active',
								'infoApproved' 						=> 'yes'
							);
						} else if( $mPassedCreditCheck === false ) {
							$_POST += array(
								'infoCreditCheckedResult' => 'denied',
								'userGrantedStatus' 				=> 'inactive',
								'infoApproved' 						=> 'no'
							);
						} else if( is_array($mPassedCreditCheck) ) {
							$sCreditCheckResult = 'denied';
							if( isset($mPassedCreditCheck['deny']) && $mPassedCreditCheck['deny'] ) {
								$sCreditCheckResult = 'denied';
							} else if( isset($mPassedCreditCheck['warning']) && $mPassedCreditCheck['warning'] ) {
								$sCreditCheckResult = 'warning';
							}

							$_POST += array(
								'infoCreditCheckedResult' => $sCreditCheckResult,
								'userGrantedStatus' 				=> 'inactive',
								'infoApproved' 						=> 'no'
							);
						}
					} else {
						// If credit check failed or denied the user it is set to inactive
						$_POST += array(
							'infoCreditCheckedResult' => 'denied',
							'userGrantedStatus' 				=> 'inactive',
								'infoApproved' 					=> 'no'
						);
					}
				} else {
					// Foreign registrars is inactive by default. Admin needs to activate

					$_POST += array(
						'userGrantedStatus' 	=> 'inactive',
						'infoApproved' 			=> 'no'
					);
				}

				// Now create the user
				$_POST['userCreated'] = date( 'Y-m-d H:i:s' );
				$oNewUser = new clUser();
				$oNewUser->setData( $_POST );

				if( $oUserManager->isUser(array_intersect_key($_POST, array('username' => ''))) ) {
					$aErr['createUser']['username'] = _( 'Användarnamnet är upptaget. Välj ett annat.' );

				} else if( $oUserManager->isUser(array_intersect_key($_POST, array('userPin' => '', 'userType' => '', 'userEmail' => '')), 'AND') ) {
					$aErr['createUser']['username'] = _( 'Kontot finns redan. Har du glömt lösenord? <a href="/glömt-lösenord">Klicka här för att återställa lösenordet</a>' );

				} else {
					if( $iUserId = $oUserManager->createUserWithInfo( $oNewUser ) ) {
						$oUserManager->createUserToGroup( $iUserId, array('guest', 'user') );

						$oMailHandler = clRegistry::get( 'clMailHandler' );

						if( $_POST['userGrantedStatus'] == 'active' ) {
							$oUser->loginByEmail( $oNewUser->readData('userEmail'), $_POST['userPass'] );

							/**
							 * Send mail to customer
							 */
							$sMailContent = sprintf( $GLOBALS['userRegister']['bodyHtml'], $_POST['username'], $_POST['userPass'] );
							$oMailHandler->prepare( array(
								'from' => 'Toveks auktioner <' . SITE_MAIL_FROM . '>',
								'to' => $_POST['userEmail'],
								'title' => _( $GLOBALS['userRegister']['subject'] ),
								'content' => $sMailContent
							) );
							$oMailHandler->send();

						} else {
							$oNotification->set( array('createUser' => _('Ditt konto behöver godkännas av administratör.')) );
						}

						$sMailHtmlOutput = '';
						foreach( $aEmailFields as $entry ) {
							if( !empty($_POST[$entry]) ) {
								if( !empty($GLOBALS['UserInfoDataDict'][$entry]['type']) ) {
									switch( $GLOBALS['UserInfoDataDict'][$entry]['type'] ) {
										case 'array':
										case 'arraySet':
											$sMailHtmlOutput .= ( isset($aFormDataDict[$entry]['title']) ? $aFormDataDict[$entry]['title'] : $entry ) . ': ' . $aFormDataDict[$entry]['values'][ $_POST[$entry] ] . '<br />';
											break;

										default:
											$sMailHtmlOutput .= ( isset($aFormDataDict[$entry]['title']) ? $aFormDataDict[$entry]['title'] : $entry ) . ': ' . $_POST[$entry] . '<br />';
											break;
									}
								} else {
									$sMailHtmlOutput .= ( isset($aFormDataDict[$entry]['title']) ? $aFormDataDict[$entry]['title'] : $entry ) . ': ' . $_POST[$entry] . '<br />';
								}
							}
						}

						/**
						 * CreateUpdate newsletter subscriber if selected
						 */
						if( !empty($aNewsletterPostData) ) {
							if( isset($aNewsletterPostData['subscriberId']) ) {
								// Update
								$oNewsletterSubscriber->update( $aNewsletterPostData['subscriberId'], $aNewsletterPostData );
							} else {
								// Create
								$aNewsletterPostData['subscriberId'] = $oNewsletterSubscriber->create( $aNewsletterPostData );
							}
						}

						/**
						 * Send mail to administrator
						 */
						/*$oMailHandler->send( array(
							'from' => 'Toveks auktioner <' . SITE_MAIL_FROM . '>',
							'to' => SITE_MAIL_TO,
							'title' => _( 'New registration of customer - ' ) . SITE_TITLE,
							'content' => $sMailHtmlOutput
						) );*/

						if( $_POST['userGrantedStatus'] == 'active' ) {
							$_SESSION['newCustomer'] = true;
							$oRouter->redirect( $oRouter->getPath( 'userNewAccountStopover' ) );

						} else {
							// Send mail to non swedish registrants with info on how to complete the registration
							if( $_POST['infoCountry'] != 210 ) {
								$iCustomerNo = current( current($oUserManager->read(['userCustomerNo'], $iUserId)) );

								$aMailText = current( $oSystemText->read(null, 'USER_FOREIGN_REGISTRATION') );
								$sMailText = $oSystemText->replaceParams( $aMailText, [
									'customerNo' => $iCustomerNo
								] );

								$oEmailQueue->create( [
									'queueService' => 'sendgrid',
									'queueTo' => json_encode( [
										'email' => $_POST['userEmail']
									] ),
									'queueFrom' => json_encode( [
										'name' => 'Toveks Auktioner',
										'email' => 'info@tovek.se'
									] ),
									'queueTemplate' => 'frontend',
									'queueTemplateData' => json_encode( [
										'subject' => 'Ditt konto på tovek.se / Your account at tovek.se',
										'title_0' => 'tovek.se',
										'title_1' => 'Komplettering / Additional info',
										'title_2' => '',
										'content' => $sMailText
									] + SENDGRID_TEMPLATE_DEFAULT_DATA )
								] );
							}

							echo '
								<script>
									alert( "Er registrering kommer behandlas inom 2 timmar under kontorstid. Vid eventuella bekymmer hör av er till oss på 0346-48770 så hjälper vi er." );
									window.location.href = "' . $oRouter->getPath( 'userNewAccountStopover' ) . '";
								</script>';
						}
					} else {
						$aErr['createUser'] += clErrorHandler::getValidationError( 'createUser' );
					}
				}
				/*else {
					if( !empty($_POST['userPass']) && $oUser->updatePass($_POST['userPass']) ) {
						$oNotification->set( array('updateUserPass' => _('Your password has changed.')) );
					}

					// Try to update data and if it fails add error message
					if( $oUser->updateData( array_intersect_key($_POST, $aFormDataDict) ) ) {
						$oNotification->set( array('updateUserData' => _('Your account information has been updated.')) );
					} else {
						$aErr += clErrorHandler::getValidationError( 'updateUser' );
					}

				}
				*/
			}
		} else {
			unset( $_POST['agreement'], $_POST['userNewsletterSignup'] );
		}

	} catch( Throwable $oThrowable ) {
		if( $GLOBALS['debug'] == true ) {
			echo '<pre>';
			var_dump( $oThrowable );
			die();
		}

	} catch( Exception $oException ) {
		if( $GLOBALS['debug'] == true ) {
			echo '<pre>';
			var_dump( $oThrowable );
			die();
		}

	}
}

// Form defaults
if( !isset($_POST['frmUserRegister']) ) {
	if( empty($_POST['infoCountry']) ) $_POST['infoCountry'] = 210;
	$_POST['userNewsletterSignup'] = 1;
}

if( empty($_POST['userEmail']) && !empty($_GET['email']) ) {
	$_POST['userEmail'] = $_GET['email'];
}

$oOutputHtmlForm->init( $aUserDataDict, array(
	'action' => $oRouter->sPath,
	'attributes'	=> array(
		'class' => 'newForm framed columns',
		'id' => 'userRegisterForm'
	),
	'labelSuffix' => '',
	'labelRequiredSuffix' => '*',
	'extraWrappers'	=> true,
	'errors'		=> array(),
	'data'			=> $_POST,
	'errors' 		=> $aErr['createUser'],
	'method'		=> 'post',
	'recaptcha'		=> false,
	'placeholders'	=> false,
	'buttons'		=> array(
		'submit' => array(
			'content' => _( 'Send registration' ),
			'attributes' => array(
				'name' => 'frmUserRegister'
			)
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

$oOutputHtmlForm->setGroups( array(
	'basics' => array(
		'title' => _( 'Grunduppgifter' ),
		'fields' => array(
			'infoCountry' ,
			'userType',
			'userEmail',
			'userPin',
			'infoVatNo',
		)
	),
	'loginInfo' => array(
		'title' => _( 'Inloggningsuppgifter' ),
		'prefixContent' => _( 'Användarnamnet visas i budhistoriken när du lagt bud' ),
		'fields' => array(
			'username',
			'userPass',
			'userPassConfirm'
		)
	),
	'info' => array(
		'title' => _( 'Info' ),
		'fields' => array(
			'infoName',
			'infoFirstname',
			'infoSurname',
			'infoPhone',
			'infoCellPhone'
		)
	),
	'address' => array(
		'title' => _( 'Adress' ),
		'fields' => array(
			'infoName',
			'infoAddress',
			'infoZipCode',
			'infoCity',
			'userNewsletterSignup'
		)
	)
) );

$oTemplate->addBottom( array(
	'key' => 'autoSubmitOnCustomerTypeChange',
	'content' => '
	<script>
		function addError( selector, reason ) {
			var suffixObj = $( selector ).siblings(" .suffixContent");

			if( suffixObj.length == 0 ) {
				$( selector ).parent().removeClass( "approved" ).addClass( "error" );
				$( selector ).after( "<span class=\"suffixContent\">" + reason + "</span>" );

			} else {
				$( selector ).parent().removeClass( "approved" ).addClass( "error" );
				suffixObj.html( reason );
			}
		}

		function addCorrection( selector, reason, correction ) {
			var suffixObj = $( selector ).siblings(" .suffixContent");

			$( selector ).parent().removeClass( "approved" ).addClass( "warning" );
			$( selector ).val( correction );

			if( suffixObj.length == 0 ) {
				$( selector ).after( "<span class=\"suffixContent\">" + reason + "</span>" );

			} else {
				suffixObj.html( reason );
			}
		}

		function addApproved( selector, reason ) {
			var suffixObj = $( selector ).siblings(" .suffixContent");

			if( reason == "" ) reason = "<i class=\"fas fa-check\"></i>";

			if( suffixObj.length == 0 ) {
				$( selector ).parent().removeClass( "error errorField" ).addClass( "approved" );
				$( selector ).after( "<span class=\"suffixContent\" id=\"userCheck\">" + reason + "</span>" );

			} else {
				$( selector ).parent().removeClass( "error errorField" ).addClass( "approved" );
				suffixObj.html( reason );
			}
		}

		$(document).ready(function() {
			$("#updateCustomerType").hide();
			$("select#userType, select#infoCountry").bind( "change", function() {
				$( this ).parents("form").submit();
			} );
		} );

		$(document).on( "keyup", "#userPassConfirm", function() {
			if( $(this).val() == $("#userPass").val() ) {
				addApproved( "#userPassConfirm", "" );
			} else {
				addError( "#userPassConfirm", "Lösenorden matchar inte" );
			}
		} );

		$(document).on( "keyup", "#userPass", function() {
			if( $("#userPassConfirm").val() != "" ) {
				if( $(this).val() == $("#userPassConfirm").val() ) {
					addApproved( "#userPassConfirm", "" );
				} else {
					addError( "#userPassConfirm", "Lösenorden matchar inte" );
				}
			}
		} );

		$(document).on( "focusout", "#userPassConfirm", function() {
			if( $(this).val() != $(".view.user.signup #userPass").val() ) {
				addError( "#userPassConfirm", "Lösenorden matchar inte" );
			}
		} );

		$(document).on( "focusout", ".view.user.signup #username", function() {
			var time = Date.now();

			$.ajax( {
				url: ajaxGlobalUrl + "?view=user/ajaxGetUsername.php&ajax=true&username=" + escape($(this).val()) + "",
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html",
				time: time
			} ).fail( function() {
				// Failed

			} ).done( function( data, textStatus, jqXHR ) {
				data = JSON.parse( data );

				if( data.result == "failure" ) {
					var reason =  "Användarnamnet är inte godkänt";		// denied
					if( data.reason == "exists" ) {
						reason = "Användarnamnet finns redan registrerat";
					}
					addError( "#username", reason );

				} else if( data.result == "replaced" ) {
					addCorrection( "#username", "Användarnamnet har korrigerats", data.corrected );

				} else {
					addApproved( "#username", "" );
				}
			} );
		} );

		$(document).on( "focusout", "#userPin", function() {
			var time = Date.now();
			var userType = $("#userType").val();
			var infoCountry = $("#infoCountry").val();

			$.ajax( {
				url: ajaxGlobalUrl + "?view=user/ajaxGetUserPin.php&ajax=true&userPin=" + $(this).val() + "&userType=" + userType + "&infoCountry=" + infoCountry + "",
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html",
				time: time
			} ).fail( function() {
				// Failed

			} ).done( function( data, textStatus, jqXHR ) {
				data = JSON.parse( data );

				console.log(data);

				if( data.result == "failure" ) {
					var reason =  "Personnumret är inte godkänt";		// denied
					if( data.reason == "exists" ) {
						reason = "Personnumret finns redan registrerat";
					}

					addError( "#userPin", reason );

				} else if( data.result == "replaced" ) {
					addCorrection( "#userPin", "Numret har korrigerats", data.corrected );

				} else {
					addApproved( "#userPin", "" );
				}
			} );
		} );

		$(document).on( "focusout", "#userEmail", function() {
			var time = Date.now();

			$.ajax( {
				url: ajaxGlobalUrl + "?view=user/ajaxCheckEmail.php&ajax=true&email=" + $(this).val() + "",
				type: "GET",
				data: "noCss=true",
				async: true,
				dataType: "html",
				time: time
			} ).fail( function() {
				// Failed

			} ).done( function( data, textStatus, jqXHR ) {
				var obj = JSON.parse( data );
				console.log(obj);
				if( obj.result ) {
					addApproved( "#userEmail", "" );
					$("#userEmail").val( obj.filteredEmail );
				} else {
					addError( "#userEmail", obj.reason );
				}
			} );
		} );
	</script>
	'
) );

$sRegistrationAgreement = $oOutputHtmlForm->createCheckboxSet( 'agreement', array(
	'yes' => _( 'Yes, I have read the registration agreement and approves it.' )
) );

$sRegistrationAgreementField = '
	<a href="/registrering/avtal?ajax=true" class="popupLink customerTerms">' . _( 'Läs vårt registreringsavtal' ) . '*</a>
	' . $oOutputHtmlForm->createField( null, '', $sRegistrationAgreement, array(
	'attributes' => array(
		'class' => 'checkboxSet'
	)
) );

echo '
	<div class="view user signup">
		' .	$oOutputHtmlForm->renderForm(
				$oOutputHtmlForm->renderErrors() .
				'<a href="/hjälp-med-registreringen" class="popupLink button help ajax columnSpanFull" data-size="full">Hjälp vid registrering</a>' .
				$oOutputHtmlForm->renderGroups() .
				clOutputHtmlForm::createFieldset( null, $sRegistrationAgreementField, array(
					'id' => 'registrationAgreement',
					'class' => 'columnSpanFull'
				) ) .
				$oOutputHtmlForm->renderButtons() .
				'<div class="formBottom columnSpanFull">* ' . _( 'Vänligen fyll i alla obligatoriska fält, kontrollera att din e-post är korrekt och godkänn registreringsavtalet.' ) . '</div>'
			) . '
	</div>';
