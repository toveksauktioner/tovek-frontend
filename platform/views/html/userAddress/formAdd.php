<?php

$aErr = null;

$oApi = clRegistry::get( 'clApi', PATH_MODULE . '/api/models' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

$aUserAddressDataDict = $oApi->call( '/desc/userAddress', 'GET' );

$aCountriesData = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, [
	'countryIsoCode2',
	'countryName'
] );
$aCountries = [];
foreach( $aCountriesData as $entry ) {
	$aCountries[ $entry['countryIsoCode2'] ] = _( $entry['countryName'] );
}

$aFormDataDict = [
	'addressType' => [],
	'addressName' => [],
	'addressContactPerson' => [],
	'addressAddress' => [],
	'addressZipCode' => [
		'fieldAttributes' => [
			'class' => 'number column column-25'
		],
    'attributes' => [
      'pattern' => '[0-9]*',
      'class' => 'zipcodeLookup',
      'data-target' => '#addressCity'
    ]
	],
	'addressCity' => [
    'fieldAttributes' => [
      'class' => 'column column-75 right'
    ]
	],
	'addressCountryCode'  => [
		'type' => 'array',
		'values' => $aCountries
	],
	'frmUserAddressAdd' => [
		'type' => 'hidden',
		'value' => 1
	]
];

if( !empty($_GET['addressId']) && empty($_POST) ) {
	$aResponse = $oApi->call( '/userAddress/' . $_GET['addressId'], 'GET' );
	if( !empty($aResponse['data']) ){
  	$_POST = current( $aResponse['data'] );
  }

} else {
  // Default values
  $_POST += [
    'addressCountryCode' => $aUserAddressDataDict['entUserAddress']['addressCountryCode']['default']
  ];
}

// Handle registration
if( isset($_POST['frmUserAddressAdd']) ) {
	unset( $_POST['frmUserAddressAdd'] );

	$aReplaceText = [
		'tovekContact' => 'Telefon: <a href="tel:' . SITE_CONTACT_PHONE . '">' . SITE_CONTACT_PHONE . '</a>',
		'addressInfo' => '
			' . $_POST['addressName'] . '<br>
			' . $_POST['addressContactPerson'] . '<br>
			' . $_POST['addressAddress'] . '<br>
			' . $_POST['addressZipCode'] . ' ' . $_POST['addressCity'] . '<br>
			' . $aCountries[ $_POST['addressCountryCode'] ]
	];

	$aData = array_intersect_key($_POST, $aFormDataDict) + [
		'addressUserId' => $_SESSION['userId']
	];

  if( !empty($_GET['addressId']) ) {
    $oApi->call( '/userAddress/' . $_GET['addressId'], 'POST', [
    	'data' => json_encode( $aData )
    ] );
		$aReplaceText['changeType'] = _( 'ändrats' );
  } else {
    $oResponse = $oApi->call( '/userAddress/', 'PUT', [
    	'data' => json_encode( $aData )
    ] );
		$aReplaceText['changeType'] = _( 'lagts till' );
  }

	// Send email with changes
	$oUserNotification = clRegistry::get( 'clUserNotification', PATH_MODULE . '/userNotification/models' );
	$oUserNotification->createFromText( 'USER_ADDRESS_CHANGE', [
		'notificationUserId' => $_SESSION['userId']
	], $aReplaceText );
}


$oOutputHtmlForm->init( $aUserAddressDataDict, [
	'attributes'	=> [
		'class' => 'newForm framed' . ( !empty($_GET['addressId']) ? ' editmode' : '' ),
		'id' => 'userAddressForm'
	],
	'labelSuffix'	=> '',
	'errors'		=> [],
	'data'			=> $_POST,
	'errors' 		=> $aErr,
	'method'		=> 'post',
	'buttons'		=> [
    'reset' => _( 'Ångra' ),
		'submit' => _( 'Spara' ),
    'button' => _( 'Lägg till adress' )
	]
] );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

echo '
  <div class="view userAddress formAdd">
    ' . $oOutputHtmlForm->render() . '
  </div>
  <script src="/js/modules/zipcode/getZipcode.js"></script>
  <script>
    $( function() {
      function hideForm() {
        $("#userAddressForm .field").hide();
        $("#userAddressForm button:not([type=button])").hide();
        $("#userAddressForm button[type=button]").show();
      }

      function showForm() {
        $("#userAddressForm .field").show();
        $("#userAddressForm button:not([type=button])").show();
        $("#userAddressForm button[type=button]").hide();
      }

      $("#userAddressForm button[type=button]").click( function() {
        showForm();
      } );
      $("#userAddressForm button[type=reset]").click( function() {
        hideForm();
      } );

      if( $("#userAddressForm").hasClass("editmode") ) {
        showForm();
      } else {
        hideForm();
      }

      $(".view.userAddress.tableEdit").after( $(".view.userAddress.formAdd") );
    } );
  </script>';
