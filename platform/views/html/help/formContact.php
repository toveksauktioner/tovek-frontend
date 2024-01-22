<?php

$bFormSent = false;

// Help form constants
require_once PATH_MODULE . '/help/config/cfHelp.php';

$oUserManager = clRegistry::get( 'clUserManager' );
$oHelpCategory = clRegistry::get('clHelpCategory', PATH_MODULE . '/help/models' );

// Read all categories
$aCategoryList = arrayToSingle( $oHelpCategory->read(array(
  'helpCategoryId',
  'helpCategoryTitleTextId'
)), 'helpCategoryId', 'helpCategoryTitleTextId' );

// Get Browser Data
$aUserAgentData = []; //get_browser( null, true );
$aBrowserFields = array(
  'platform' => _( 'Operativsystem' ),
  'browser' => _( 'Webbläsare' ),
  'version' => _( 'Version' )
);

$aBrowserData = array();
foreach( $aBrowserFields as $key => $value ) {
  if( array_key_exists($key, $aUserAgentData) ) {
    $aBrowserData[] = $value . ': ' . $aUserAgentData[ $key ];
  }
}

// Form data (prefill with user data if exists)
$aFormData = $_POST;
if( !empty($oUser->iId) ) {
  $aUserData = current( $oUserManager->read(array(
    'username',
    'infoFirstname',
    'infoSurname',
    'userEmail',
    'infoPhone',
    'infoCellPhone'
  ), $oUser->iId) );

  if( !empty($aUserData) ) {
    $aPhones = array();
    if($aUserData['infoPhone']) $aPhones[] = $aUserData['infoPhone'];
    if($aUserData['infoCellPhone']) $aPhones[] = $aUserData['infoCellPhone'];

    $aFormData += array(
      'contactName' => $aUserData['infoFirstname'] . ' ' . $aUserData['infoSurname'],
      'contactEmail' => $aUserData['userEmail'],
      'contactPhone' => implode( ' | ', $aPhones ),
    );
    $aBrowserData[] = _( 'Användarnamn' ) . ': ' . $aUserData['username'];
  }
}
$aFormData += array(
  'contactBrowser' => implode( ' | ', $aBrowserData )
);

// Form Data Dict
$aFormDataDict = array(
	'contactCategory' => array(
		'type' => 'array',
		'title' => _( 'Välj avdelning' ),
		'values' => array( 0 => '' ) + $aCategoryList
	),
  'contactName' => array(
    'type' => 'string',
    'title' => _( 'Namn' )
  ),
  'contactEmail' => array(
    'type' => 'string',
    'title' => _( 'E-post' ),
    'required' => true,
    'fieldAttributes' => array(
      'class' => 'email'
    )
  ),
  'contactPhone' => array(
    'type' => 'string',
    'title' => _( 'Telefon' ),
    'fieldAttributes' => array(
      'class' => 'phone'
    )
  ),
  'contactMessage' => array(
    'type' => 'string',
    'title' => _( 'Meddelande' ),
    'appearance' => 'full',
    'required' => true,
    'fieldAttributes' => array(
      'class' => 'columnSpanFull editor'
    )
  ),
  'contactBrowser' => array(
    'type' => 'boolean',
    'title' => '<strong>' . _( 'Jag godkänner att följande information om min webbläsare skickas med i felsökningssyfte' ) . '</strong><br>' . $aFormData['contactBrowser'],
    'values' => array( $aFormData['contactBrowser'] => $aFormData['contactBrowser'] ),
    'appearance' => 'full',
    'fieldAttributes' => array(
      'class' => 'checkbox columnSpanFull'
    )
  ),
	'frmHelpContact' => array(
		'type' => 'hidden',
		'value' => '1'
	)
);

// Send form
if( !empty($_POST['frmHelpContact']) ) {
  unset( $_POST['frmHelpContact'] );

  $sMailContent = '';
  $aReceivers = array( HELP_CONTACT_DEFAULT_EMAIL );

  foreach( $_POST as $key => $value ) {
    $sTitle = $aFormDataDict[ $key ]['title'];

    if( $key == 'contactBrowser' ) {
      $sTitle = str_replace( array(
        $value,
        '<br>'
      ), array(
        '',
        ''
      ), $sTitle );

    } else if( ($key == 'contactCategory') && !empty($aCategoryList[ $value ])) {
      $value = $aCategoryList[ $value ];
      if( !empty(HELP_CONTACT_CATEGORY_EMAIL[ $value]) ) {
        foreach( HELP_CONTACT_CATEGORY_EMAIL[ $value ] as $sEmail ) {
          $aReceivers[] = $sEmail;
        }
      }
    }

    $sMailContent .= '<p><strong>' . $sTitle . '</strong><br>' . $value . '</p>';
  }

  if( !empty($aReceivers) ) {
    $oMailHandler = clRegistry::get( 'clMailHandler' );
    $oMailHandler->prepare( array(
      'from' => 'Toveks Auktioner <' . SITE_MAIL_FROM . '>',
      'replyTo' => $_POST['contactEmail'],
      'to' => $aReceivers,
      'title' => _( 'Hjälpformulär på tovek.se' ),
      'content' => $sMailContent
    ) );
    $oMailHandler->send();
    $bFormSent = true;
  }
}

if( $bFormSent ) {
  // Form was sent
  $sFormOutput = '
    <div class="formMessageSent">
      <i class="far fa-check-circle"></i>
      <div class="title">' . _( 'Formuläret har skickats.' ) . '</div>
      <div class="message">' .  _( 'Vi svarar så snart vi kan på den e-post du angivit.' ) . '</div>
    </div>';

} else {
  // Form not sent yet
  $oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
  $oOutputHtmlForm->init( array($aFormDataDict), array(
  	'attributes' => array('class' => 'newForm framed columns helpContactForm'),
  	'data' => $aFormData,
  	'labelSuffix' => '',
    'placeholders' => false,
  	'method' => 'post',
  	'buttons' => array(
  		'submit' => _( 'Skicka' )
  	)
  ) );
  $sFormOutput = $oOutputHtmlForm->render();
}

if( !empty($_GET['ajax']) ) {
  echo $sFormOutput;
  exit;
}

echo '
  <div class="view help formContact">
    ' . $sFormOutput . '
  </div>
  <script>
    $( document ).on( "submit", ".helpContactForm", function(ev) {
      ev.preventDefault();

      $( this ).parent().load( "' . $oRouter->getPath('guestHelpContact') . '?ajax=1", $(this).serializeArray() );
    } );
  </script>';
