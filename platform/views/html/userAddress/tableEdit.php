<?php

$oApi = clRegistry::get( 'clApi', PATH_MODULE . '/api/models' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );

$aCountriesData = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryIsoCode2',
	'countryName'
) );
$aCountries = array();
foreach( $aCountriesData as $entry ) {
	$aCountries[ $entry['countryIsoCode2'] ] = _( $entry['countryName'] );
}

$aUserAddressDataDict = $oApi->call( '/desc/userAddress', 'GET' );

$aTypeValues = $aUserAddressDataDict['entUserAddress']['addressType']['values'];

// Delete address
if( !empty($_GET['deleteAddress']) ) $oApi->call( '/userAddress/' . $_GET['deleteAddress'], 'DELETE' );

// Get addresses
$aResponse = $oApi->call( '/userAddresses/' . $_SESSION['userId'], 'GET' );
$aAddresses = ( !empty($aResponse['data']) ? $aResponse['data'] : null );

// Temporary (fix so that user has at least one address)
if( empty($aAddresses) ) {
  $aUserAccountAddress = current( $oUser->oDao->read([
    'fields' => [
      'userId AS addressUserId',
      'userType AS addressType',
      'infoName AS addressName',
      'infoContactPerson AS addressContactPerson',
      'infoAddress AS addressAddress',
      'infoZipCode AS addressZipCode',
      'infoCity AS addressCity',
      'infoCountryCode AS addressCountryCode'
    ],
    'userId' => $_SESSION['userId']
  ]) );
  $oApi->call( '/userAddress/', 'PUT', [
    'data' => json_encode( $aUserAccountAddress )
  ] );  
  $aResponse = $oApi->call( '/userAddresses/' . $_SESSION['userId'], 'GET' );
  $aAddresses = ( !empty($aResponse['data']) ? $aResponse['data'] : null );
}

clFactory::loadClassFile( 'clOutputHtmlGridTable' );
$oOutputHtmlTable = new clOutputHtmlGridTable( $aUserAddressDataDict );
$oOutputHtmlTable->setTableDataDict( [
	'addressName' => [],
  'addressContactPerson' => [],
  'addressAddress' => [],
	'addressType' => [],
  'controls' => [
    'title' => ''
  ]
] );

if( !empty($aAddresses) ) {
  foreach( $aAddresses as $entry ) {
    $oOutputHtmlTable->addBodyEntry( [
      'addressName' => '<strong>' . $entry['addressName'] . '</strong>',
      'addressContactPerson' => $entry['addressContactPerson'],
      'addressAddress' => '<strong>' . $entry['addressAddress'] . '</strong>, ' . $entry['addressZipCode'] . ' ' . $entry['addressCity'] . ', ' . $aCountries[ $entry['addressCountryCode'] ],
			'addressType' => '<span>' . $aTypeValues[ $entry['addressType'] ] . '</span>',
      'controls' => '
        <a href="?addressId=' . $entry['addressId'] . '" class="button small narrow" title="' . _( 'Ändra' ) . '"><i class="fas fa-edit"></i></a>
        <a href="#" class="button cancel small narrow deleteAddressBtn" data-address-id="' . $entry['addressId'] . '" data-prompt="' . _( 'Är du säker på att du vill ta bort adressen? Det går inte att ångra.' ) . '" title="' . _( 'Ta bort' ) . '"><i class="fas fa-trash-alt"></i></a>'
    ] );
  }
}

echo '
  <div class="view userAddress tableEdit">
    <h1>' . _( 'Adresser' ) . '</h1>
    ' . $oOutputHtmlTable->render() . '
  </div>
  <script>
    $( function() {
      $(".deleteAddressBtn").click( function() {
        let addressId = $( this ).data( "address-id" );
        let promptMessage = $( this ).data( "prompt" );

        if( confirm(promptMessage) ) {
          location.href = "' . $oRouter->sPath . '?deleteAddress=" + addressId;
        }
      } );
    } );
  </script>';
