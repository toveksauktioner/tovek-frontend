<?php

// Both a key and email address must be provided
if( empty($_GET['k']) || empty($_GET['e']) ) return;

$aErr = array();

// Default output
$sOutput = '<p>Länken var inte giltig. <a href="' . $oRouter->getPath( 'guestUserAccountRetrieval' ) . '">Skaffa en ny</a></p>';

$oUserManager = clRegistry::get( 'clUserManager' );
$oUserPassRetrieval = clRegistry::get( 'clUserPassRetrieval', PATH_MODULE . '/userPassRetrieval/models' );

// Log all requests
// clFactory::loadClassFile( 'clLogger' );
// $sLogFile = 'activateAndChangePass.log';
// clLogger::log( 'Indata: ' . json_encode($_GET), $sLogFile );

// Set time limit for key request
$oUserPassRetrieval->oDao->setCriterias( array(
  'timeLimit' => array(
    'type' => '>=',
    'fields' => 'retrievalCreated',
    'value' => date( 'Y-m-d H:i:s', (time() - USER_PASS_RETRIEVAL_VALID) )
  )
) );

// Get activation key info and match it to the email
$iKeyUserId = $oUserPassRetrieval->readUserIdByKey( $_GET['k'] );

if( !empty($iKeyUserId) ) {
  $aUserData = current( $oUserManager->read( array(
    'userId',
    'userEmail',
    'username'
  ), $iKeyUserId ) );

  if( !empty($aUserData) && (trim($aUserData['userEmail']) == trim($_GET['e'])) ) {
    // Data checks out. Present and handle form

    // Handle form
    if( isset($_POST['frmUserChangePass']) ) {
      $oUser = new clUser( $iKeyUserId );

    	// Make sure that passwords are equal
    	if( $_POST['userNewPass'] !== $_POST['userNewPassConfirm'] ) $aErr['userNewPassConfirm'] = _( 'The passwords don´t match' );

      // Passwords cannot be empty
    	if( empty($aErr) ) {
      	if( empty($_POST['userNewPass']) ) $aErr['userNewPass'] = _( 'Lösenorden får inte vara tomma' );
      	if( empty($_POST['userNewPassConfirm']) ) $aErr['userNewPassConfirm'] = _( 'Lösenorden får inte vara tomma' );
      }

    	if( empty($aErr) ) {
        $oUserPassRetrieval->activateByKey( $_GET['k'] );

    		if( $oUser->updatePass($_POST['userNewPass']) ) {
    			$oNotification->set( array('updateUserPass' => _('Your password has changed.')) );
      		clLogger::log( 'New password set', $sLogFile );

          $oUser->login( $aUserData['username'], $_POST['userNewPass'] );
          $oRouter->redirect( $oRouter->getPath('userHomepage') );
          exit;
    		}
    	}
    }

    $oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

    $aFormDataDict = array(
      'entUserChangePass' => array(
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
      )
    );

    if( !empty($aErr) ) {
      foreach( $aErr as $sField => $sError ) {
        if( !empty($aFormDataDict['entUserChangePass'][ $sField ]) ) {
          $aFormDataDict['entUserChangePass'][ $sField ]['suffixContent'] = '<div class="errMsg">' . $sError . '</div>';
        }
      }
    }

    $oOutputHtmlForm->init( $aFormDataDict, array(
     'method' => 'post',
     'attributes' => array( 'class' => 'newForm framed hideOldErrorList' ),
     'placeholders' => false,
     'errors' => $aErr,
     'labelSuffix' => '',
     'recaptcha' => true,
     'buttons' => array( 'submit' => _( 'Byt lösenord' ) )
    ) );

    $sOutput = $oOutputHtmlForm->render();

		// clLogger::log( 'Form presented', $sLogFile );

  } else {
		// clLogger::log( 'Invalid email', $sLogFile );
  }
} else {
  // clLogger::log( 'Invalid key', $sLogFile );
}

// clLogger::log( '-------------------------------------------------', $sLogFile );

echo '
  <div class="view userPassRetrieval activateAndChangePassForm">
    <h1>' . _( 'Byt lösenord' ) . '</h1>
    ' . $sOutput . '
  </div>';
