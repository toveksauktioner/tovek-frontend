<?php

/* * * *
 * Filename: formAdd.php
 * Created: 27/05/2014 by Renfors
 * Reference:
 * Description: View file for create / edit submitter.
 * * * */

$aErr = array();
$sSubmitterUsername = '';

require_once( PATH_FUNCTION . '/fOutputHtml.php' );
require_once( PATH_FUNCTION . '/fFileSystem.php' );

$oNotification = clRegistry::get( 'clNotificationHandler' );
$oSubmitter = clRegistry::get( 'clSubmitter', PATH_MODULE . '/submitter/models' );
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oUserManager = clRegistry::get( 'clUserManager' );

$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryIsoCode2',
	'countryName'
) );
$aCountries = arrayToSingle( $aCountries, 'countryIsoCode2', 'countryName' );

// File module
$oFile = clRegistry::get( 'clFile', PATH_MODULE . '/file/models' );
$oUpload = clRegistry::get( 'clUpload' );

if( isset($_SESSION['notifications']) ) {
	if( !empty($_SESSION['notifications']) ) {
		foreach( $_SESSION['notifications'] as $sType => $sString ) {
			$oNotification->set( array(
				$sType => $sString
			) );
		}
	}
	unset( $_SESSION['notifications'] );
}

// Create submitter from user
if( !empty($_GET['userId']) ) {
	$aUserData = current( $oUserManager->read(array(
		'userId',
		'userEmail',
		'userPin',
		'userType',
		'infoVatNo',
		'infoName',
		'infoFirstname',
		'infoSurname',
		'infoAddress',
		'infoZipCode',
		'infoCity',
		'infoCountryCode',
		'infoPhone',
		'infoCellPhone'
	), $_GET['userId']) );

	$_POST += array(
		'submitterStatus' => 'active',
		'submitterPin' => $aUserData['userPin'],
		'submitterVatNo' => $aUserData['infoVatNo'],
		'submitterType' => $aUserData['userType'],
		'submitterCompanyName' => ( !empty($aUserData['infoName']) ? $aUserData['infoName'] : $aUserData['infoFirstname'] . ' ' . $aUserData['infoSurname'] ),
		'submitterFirstname' => ( ($aUserData['userType'] == 'company') ? $aUserData['infoFirstname'] : '' ),
		'submitterSurname' => ( ($aUserData['userType'] == 'company') ? $aUserData['infoSurname'] : '' ),
		'submitterAddress' => $aUserData['infoAddress'],
		'submitterZipCode' => $aUserData['infoZipCode'],
		'submitterCity' => $aUserData['infoCity'],
		'submitterCountryCode' => $aUserData['infoCountryCode'],
		'submitterPhone' => $aUserData['infoPhone'],
		'submitterCellPhone' => $aUserData['infoCellPhone'],
		'submitterEmail' => $aUserData['userEmail'],
		'submitterUserId' => $aUserData['userId'],
		'frmSubmitterAdd' => '1'
	);
}

if( !empty($_POST['frmSubmitterAdd']) ) {
	// Account selection backwards compatibility
	if( !empty($_POST['submitterPaymentToType']) ) {
		switch( $_POST['submitterPaymentToType'] ) {
			case 'pg':
				$_POST['submitterPaymentToAccount'] = ( !empty($_POST['submitterPaymentPg']) ? $_POST['submitterPaymentPg'] : '' );
				break;

			case 'bg':
				$_POST['submitterPaymentToAccount'] = ( !empty($_POST['submitterPaymentBg']) ? $_POST['submitterPaymentBg'] : '' );
				break;

			case 'account':
				$_POST['submitterPaymentToAccount'] = ( !empty($_POST['submitterPaymentBankClearingNo']) ? $_POST['submitterPaymentBankClearingNo'] : '' ) . '-' . ( !empty($_POST['submitterPaymentBankAccountNo']) ? $_POST['submitterPaymentBankAccountNo'] : '' );
				break;

			default:
				$_POST['submitterPaymentToAccount'] = '';
		}
	}

	// Update
	if( !empty($_GET['submitterId']) && ctype_digit($_GET['submitterId']) ) {
		$oSubmitter->update( $_GET['submitterId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateSubmitter' );
		if( empty($aErr) ) {
			$iSubmitterId = $_GET['submitterId'];
		}
	// Create
	} else {
		$iSubmitterId = $oSubmitter->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createSubmitter' );
		if( empty($aErr) ) {}
	}

	// Files
	if( empty($aErr) && !empty($_FILES['submitterAgreementFile']['tmp_name'][0]) ) {
		if( !is_dir(PATH_CUSTOM_FILE . '/' . $oSubmitter->sModuleName) ) {
      if( !mkdir(PATH_CUSTOM_FILE . '/' . $oSubmitter->sModuleName, 0777) ) throw new Exception( sprintf(_('Could not create directory %s'), PATH_CUSTOM_FILE . '/' . $oSubmitter->sModuleName) );
    }

		$aNewFileNames = array();
		$aAllowedMime = array();

		foreach( $_FILES['submitterAgreementFile']['error'] as $key => $iErr ) {
			if( $iErr == UPLOAD_ERR_OK ) {
				if( empty($aErr) ) {
					$sFilename = cleanFilename( $_FILES['submitterAgreementFile']['name'][$key] );
					if( $iFileId = $oFile->create(array(
						'filename' => $sFilename,
						'fileParentType' => $oSubmitter->sModuleName,
						'fileParentId' => $_GET['submitterId']
					)) ) {
						$aNewFileNames[$key] = $iFileId . '_' . $sFilename;
					} else {
						$aErr = clErrorHandler::getValidationError( 'createFile' );
					}
				}
			}
		}

		if( empty($aErr) ) {
			// Upload
			$oUpload->setParams( array(
				'destination' => PATH_CUSTOM_FILE . '/' . $oSubmitter->sModuleName . '/',
				'allowedMime' => $aAllowedMime,
				'key' => 'submitterAgreementFile',
				'newFileName' => $aNewFileNames
			) );
			$aErr = $oUpload->upload();
		}
	}

	if( empty($aErr) && empty($_GET['submitterId']) ) {
		$_SESSION['notifications'] = $oNotification->aNotifications;
		$oRouter->redirect( $oRouter->sPath . '?submitterId=' . $iSubmitterId );
	}
}

// Edit
if( !empty($_GET['submitterId']) && ctype_digit($_GET['submitterId']) ) {
	// Submitter data
	$aSubmitterData = current( $oSubmitter->read( array(
		'submitterCustomId',
		'submitterStatus',
		'submitterPin',
		'submitterVatNo',
		'submitterType',
		'submitterCompanyName',
		'submitterFirstname',
		'submitterSurname',
		'submitterAddress',
		'submitterZipCode',
		'submitterCity',
		'submitterCountryCode',
		'submitterPhone',
		'submitterCellPhone',
		'submitterEmail',
		'submitterCommissionFee',
		'submitterMarketingFee',
		'submitterRecallFee',
		'submitterPaymentDays',
		'submitterPaymentToType',
		'submitterPaymentPg',
		'submitterPaymentBg',
		'submitterPaymentBank',
		'submitterPaymentBankClearingNo',
		'submitterPaymentBankAccountNo',
		'submitterSubmissionType',
		'submitterCreated',
		'submitterUserId',
	), $_GET['submitterId'] ) );

	// Users
	$aUserInfo = $oUserManager->read( array('username'), $aSubmitterData['submitterUserId'] );
	if( !empty($aUserInfo) ) $sSubmitterUsername = current( current($aUserInfo) );

	$sTitle = _( 'Edit submitter' );
	$sButton = _( 'Save' );
	$sMode = 'edit';

	// Fortnox control
	// Check if submitter is sent to Fortnox
	$aFortnoxData = $oSubmitter->readFortnoxSubmitter( $_GET['submitterId'] );
	if( empty($aFortnoxData) ) {
		$sFortnoxControl = '<a href="?submitterId=' . $_GET['submitterId'] . '&event=createFortnoxSubmitter&createFortnoxSubmitter=' . $_GET['submitterId'] . '" class="fortnoxBtn">' . _( 'Skicka till Fortnox' ) . '</a>';
	} else {
		$sFortnoxControl = '<a href="?submitterId=' . $_GET['submitterId'] . '&event=updateFortnoxSubmitter&updateFortnoxSubmitter=' . $_GET['submitterId'] . '" class="fortnoxBtn">' . _( 'Uppdatera Fortnox' ) . '</a>';
	}
} else {
	// New
	$aSubmitterData = $_POST;

	$aAuctionData = array();

	$sTitle = _( 'Create submitter' );
	$sButton = _( 'Create' );
	$sMode = 'add';
	$sFortnoxControl = '';
}

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oSubmitter->oDao->getDataDict(), array(
	'attributes' => array( 'class' => 'marginal' ),
	'data' => $aSubmitterData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => $sButton
	)
) );

$sSubmitterAgreements = '';
if( !empty($_GET['submitterId']) ) {
	$aSubmitterAgreements = $oFile->readByParent( $_GET['submitterId'], $oSubmitter->sModuleName );
	if( !empty($aSubmitterAgreements) ) {
		foreach( $aSubmitterAgreements as $aSubmitterAgreement ) {
			$sFilename = '/files/custom/' . $oSubmitter->sModuleName . '/' . $aSubmitterAgreement['fileId'] . '_' . $aSubmitterAgreement['filename'];
			$sSubmitterAgreements .= '
				<li><a href="' . $sFilename . '" target="_blank">' . $aSubmitterAgreement['filename'] . '</a></li>';
		}
	}
}

$aFormDataDict = array(
	'submitterCustomId' => array(),
	'submitterStatus' => array(),
	'submitterType' => array(),
	'submitterPin' => array(),
	'submitterVatNo' => array(),
	'submitterCompanyName' => array(),
	'submitterFirstname' => array(),
	'submitterSurname' => array(),
	'submitterAddress' => array(),
	'submitterZipCode' => array(),
	'submitterCity' => array(),
	'submitterCountryCode'  => array(
		'type' => 'array',
		'values' => $aCountries,
		'defaultValue' => 'SE',
		'title' => _( 'Country' ),
		'required' => true
	),
	'submitterPhone' => array(),
	'submitterCellPhone' => array(),
	'submitterEmail' => array(),
	'submitterCommissionFee' => array(),
	'submitterMarketingFee' => array(),
	'submitterRecallFee' => array(),
	'submitterPaymentDays' => array(),
	'submitterPaymentToType' => array(),
	'submitterPaymentPg' => array(
		'attributes' => array(
			'disabled' => true
		)
	),
	'submitterPaymentBg' => array(
		'attributes' => array(
			'disabled' => true
		),
		'suffixContent' => '<bankgiro>' . ( !empty($aSubmitterData['submitterPaymentBg']) ? $aSubmitterData['submitterPaymentBg'] : '' ) . '</bankgiro>'
	),
	'submitterPaymentBank' => array(
		'attributes' => array(
			'disabled' => true
		)
	),
	'submitterPaymentBankClearingNo' => array(
		'attributes' => array(
			'disabled' => true
		)
	),
	'submitterPaymentBankAccountNo' => array(
		'attributes' => array(
			'disabled' => true
		)
	),
	'submitterSubmissionType' => array(),
	'submitterAgreementFile[]' => array(
		'type' => 'upload',
		'attributes' => array(
			'id' => 'submitterAgreementFileUploader',
			'accept' => 'pdf',
			'multiple' => 'multiple'
		),
		'title' => _( 'Avtal' ),
		'suffixContent' => ( !empty($sSubmitterAgreements) ? '<ul>' . $sSubmitterAgreements . '</ul>' : '' )
	),
	'submitterUserId' => array(
		'type' => 'hidden'
	),
	'frmSubmitterAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);

if( isset($aSubmitterData['submitterType']) && ($aSubmitterData['submitterType'] == 'privatePerson') ) {
	$aFormDataDict['submitterFirstname']['attributes'] = array( 'disabled' => 'disabled' );
	$aFormDataDict['submitterSurname']['attributes'] = array( 'disabled' => 'disabled' );
	$aFormDataDict['submitterVatNo']['attributes'] = array( 'disabled' => 'disabled' );
}

$oOutputHtmlForm->setFormDataDict( $aFormDataDict );
$oOutputHtmlForm->setGroups( array(
	'submitterBaseInfo' => array(
		'title' => _( 'Base info' ),
		'fields' => array(
			'submitterCustomId',
			'submitterType',
			'submitterSubmissionType',
			'submitterCompanyName',
			'submitterPin',
			'submitterVatNo',
			'submitterFirstname',
			'submitterSurname',
			'submitterAddress',
			'submitterZipCode',
			'submitterCity',
			'submitterCountryCode',
			'submitterPhone',
			'submitterCellPhone',
			'submitterEmail',
			'submitterStatus'
		)
	),
	'submitterBillingInfo' => array(
		'title' => _( 'Billing info' ),
		'fields' => array(
			'submitterCommissionFee',
			'submitterMarketingFee',
			'submitterRecallFee',
			'submitterPaymentDays',
			'submitterPaymentToType',
			'submitterPaymentPg',
			'submitterPaymentBg',
			'submitterPaymentBank',
			'submitterPaymentBankClearingNo',
			'submitterPaymentBankAccountNo'
		)
	),
	'submitterAgreement' => array(
		'title' => _( 'Avtal' ),
		'fields' => array(
			'submitterAgreementFile[]'
		)
	)
) );

if( $sMode == 'add' ) {
	$sUserButtons = '
		<button type="button" id="createFromUser">' . _( 'Create from user' ) . '</button>
		<button type="button" id="createWithoutUser">' . _( 'Create without user' ) . '</button>';
} else {
	$sUserButtons = '
		<button type="button" id="connectUser">' . _( 'Select user' ) . '</button>';
}

echo '
	<div class="view submitterFormAdd">
		<h1>' . $sTitle . '</h1>
		<div class="connectUserControl connectControl">
			<span id="submitterUsername">' . ( !empty($sSubmitterUsername) ? _( 'Connected to' ) . ': ' . $sSubmitterUsername : '' ) . '</span>
			' . $sUserButtons . '
		</div>
		<div class="formContainer ' . $sMode . '">
			' . $oOutputHtmlForm->render() . '
		</div>
		' . $sFortnoxControl . '
		<div id="userSearchPopup">
			<div class="wrapper">
			</div>
		</div>
	</div>';


$oTemplate = clRegistry::get( 'clTemplateHtml' );
$oTemplate->addBottom( array(
	'key' => 'jsAlterForm',
	'content' => '
		<script>
			function enableAccountFields() {
				switch( $("#submitterPaymentToType").val() ) {
					case "pg":
						$("#submitterPaymentPg").prop( "disabled", false );
						$("#submitterPaymentBg").prop( "disabled", true );
						$("#submitterPaymentBank").prop( "disabled", true );
						$("#submitterPaymentBankClearingNo").prop( "disabled", true );
						$("#submitterPaymentBankAccountNo").prop( "disabled", true );
						break;

					case "bg":
						$("#submitterPaymentPg").prop( "disabled", true );
						$("#submitterPaymentBg").prop( "disabled", false );
						$("#submitterPaymentBank").prop( "disabled", true );
						$("#submitterPaymentBankClearingNo").prop( "disabled", true );
						$("#submitterPaymentBankAccountNo").prop( "disabled", true );
						break;

					case "account":
						$("#submitterPaymentPg").prop( "disabled", true );
						$("#submitterPaymentBg").prop( "disabled", true );
						$("#submitterPaymentBank").prop( "disabled", false );
						$("#submitterPaymentBankClearingNo").prop( "disabled", false );
						$("#submitterPaymentBankAccountNo").prop( "disabled", false );
						break;

					default:
						$("#submitterPaymentPg").prop( "disabled", true );
						$("#submitterPaymentBg").prop( "disabled", true );
						$("#submitterPaymentBank").prop( "disabled", true );
						$("#submitterPaymentBankClearingNo").prop( "disabled", true );
						$("#submitterPaymentBankAccountNo").prop( "disabled", true );
				}
			}

			function hideAndDisable() {
				if( $("#submitterType").val() == "company" ) {
					$("#submitterVatNo").attr( "disabled", false );
					$("#submitterFirstname").parent().show();
					$("#submitterSurname").parent().show();
				} else {
					$("#submitterVatNo").attr( "disabled", true );
					$("#submitterFirstname").parent().hide();
					$("#submitterSurname").parent().hide();
				}
			}

			$( function() {
				// Create a user from submitter
				$("#createFromUser").click( function() {
					$.get( "' . $oRouter->getPath( 'adminCustomerSearchAjax' ) . '", {
						selectTarget: "#submitterUserId",
						selectNameTarget: "#submitterUsername",
						onSelect: "submit"
					}, function( data ) {
						$("#userSearchPopup .wrapper").html( data );
						$("#userSearchPopup").show();
					} );
				} );

				// Create submitter without user (show form)
				$("#createWithoutUser").click( function() {
					$(".submitterFormAdd .formContainer.add").show();
				} );

				// Connect a user (on an existing sumitter)
				$("#connectUser").click( function() {
					$.get( "' . $oRouter->getPath( 'adminCustomerSearchAjax' ) . '", {
						selectTarget: "#submitterUserId",
						selectNameTarget: "#submitterUsername",
						onSelect: "close"
					}, function( data ) {
						$("#userSearchPopup").html( data ).show();
					} );
				} );

				$("#submitterPaymentToType").change( function() {
					enableAccountFields();
				} );

				$("#submitterType").change( function() {
					hideAndDisable();
				} );

				// Init
				enableAccountFields();
				hideAndDisable();
			} );
		</script>
	'
) );
