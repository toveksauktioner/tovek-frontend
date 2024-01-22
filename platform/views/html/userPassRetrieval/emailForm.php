<?php

$aErr = array();
$sOutput = '';

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oNotification = clRegistry::get( 'clNotificationHandler' );
$oDataValidation = clRegistry::get( 'clDataValidation' );
$oUserManager = clRegistry::get( 'clUserManager' );
$oEmailQueue = clRegistry::get( 'clEmailQueue', PATH_MODULE . '/email/models' );
$oUserPassRetrieval = clRegistry::get( 'clUserPassRetrieval', PATH_MODULE . '/userPassRetrieval/models' );

if( isset($_POST['email']) ) $_POST['email'] = trim($_POST['email']);

if( !empty($_POST['frmUserEmailAccountDetails']) && !empty($_POST['email']) ) {
  if( $oDataValidation::isEmail($_POST['email']) ) {
    $oUserManager->oDao->setCriterias( array(
      'userGrantedStatus' => array(
        'type' => 'not',
        'fields' => 'userGrantedStatus',
        'value' => 'blocked'
      )
    ) );
    $aUserData = $oUserManager->readByEmail( $_POST['email'], array(
      'userId',
      'username',
      'userEmail',
      'userGrantedStatus'
    ) );

    // Construct email
		$sSubject = _( 'Återställ konto hos tovek.se' );
    $sTitle1 = _( 'Följ instruktionerna för att återställa' );
    $sMessage = '';

    if( !empty($aUserData) ) {
	    $sMessage .= '<p>' . _( 'Följande konton hittades på din e-postadress hos tovek.se.' ) . '</p>';
      $sMessage .= '
        <table>
          <tbody>';

      foreach( $aUserData as $aUser ) {

        switch( $aUser['userGrantedStatus'] ) {
          case 'active':
            $sUrl = $oRouter->getPath( 'guestUserChangePassword' );

            // Make temporary password to use the pass retrival function
            $sNewPass = generateRandomPass( 16 );
            $sActivationKey = $oUserPassRetrieval->createByUser( $aUser['userId'], $sNewPass, $aUser['userEmail'] );

            $sValid = ( USER_PASS_RETRIEVAL_VALID / 60 ) . ' ' . _( 'minuter' );
            $sAction = '<a href="https://' . SITE_DEFAULT_DOMAIN . $sUrl . '?k=' . $sActivationKey . '&e=' . $aUser['userEmail'] . '">' . _( 'Byt lösenord' ) . '</a> (' . sprintf( _('Länken fungerar i %s'), $sValid ) . ')';
            $sStatus = _( 'Detta konto är aktivt' );
            break;

          case 'inactive':
            $sStatus = _( 'Detta konto är inaktivt.' );
            $sAction = _( 'Kontakta oss på 0346-48770 för att aktivera.' );
            break;
        }

        $sMessage .= '
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>' . _( 'Användarnamn' ) . '</td>
            <td><strong>' . $aUser['username'] . '</strong></td>
          </tr>
          <tr>
            <td>' . _( 'Status' ) . '</td>
            <td>' . $sStatus . '</td>
          </tr>
          <tr>
            <td>' . _( 'Åtgärd' ) . '</td>
            <td><strong>' . $sAction . '</strong></td>
          </tr>';
      }

      $sMessage .= '
          </tbody>
        </table>';

    } else {
      $sUrl = $oRouter->getPath( 'guestUserSignup' );
	    $sMessage .= '
        <p>' . _('Inget konto hittades på din e-postadress hos tovek.se.' ) . '</p>
        <strong><a href="https://' . SITE_DEFAULT_DOMAIN . $sUrl . '?email=' . $_POST['email'] . '">' . _( 'Klicka här för att registrera dig' ) . '</a></strong>';
    }

    $iQueueId = $oEmailQueue->create( [
      'queueService' => 'sendgrid',
      'queueTo' => json_encode( [
        'email' => $_POST['email']
      ] ),
      'queueFrom' => SITE_MAIL_FROM,
      'queueTemplate' => 'frontend',
      'queueTemplateData' => json_encode( [
        'subject' => $sSubject,
        'title_0' => $sSubject,
        'title_1' => $sTitle1,
        'title_2' => '',
        'content' => $sMessage
      ] + SENDGRID_TEMPLATE_DEFAULT_DATA )
    ] );

    if( !empty($iQueueId) ) {
      $oNotification->aNotifications = [
        'dataSaved' => sprintf( _( 'Ett meddelande med instruktioner skickas till %s. Det kan ta någon minut innan det dyker upp i din inkorg.' ), $_POST['email'] )
      ];
		}

  } else {
  	$aErr = array(
  		'email' => sprintf( _( 'Felaktig e-postadress' ), $_POST['email'] )
  	);
  }
}

$aFormDataDict = array(
 'entUserPassRetrieval' => array(
   'email' => array(
     'type' => 'string',
     'title' => _( 'E-post' ),
     'fieldAttributes' => array(
       'class' => 'email'
     )
   ),
   'frmUserEmailAccountDetails' => array(
     'type' => 'hidden',
     'value' => true
   )
 )
);

if( !empty($aErr) ) {
  foreach( $aErr as $sField => $sError ) {
    if( !empty($aFormDataDict['entUserPassRetrieval'][ $sField ]) ) {
      $aFormDataDict['entUserPassRetrieval'][ $sField ]['suffixContent'] = '<div class="errMsg">' . $sError . '</div>';
    }
  }
}

$oOutputHtmlForm->init( $aFormDataDict, array(
 'method' => 'post',
 'action' => $oRouter->sPath,
 'attributes' => array( 'class' => 'newForm framed hideOldErrorList' ),
 'placeholders' => false,
 'errors' => $aErr,
 'data' => $_POST,
 'labelSuffix' => '',
 'recaptcha' => true,
 'buttons' => array( 'submit' => _( 'Send' ) )
) );
$sOutput .= $oOutputHtmlForm->render();



/**
 * Output
 */
echo '
	<div class="view userPassRetrieval emailForm">
		<h1>' . _( 'Glömt användarnamn eller lösenord?' ) . '</h1>
    <p>' . _( 'Ange din e-post så skickar vi ett mail med instruktioner' ) . '</p>
		' . $sOutput . '
	</div>';
