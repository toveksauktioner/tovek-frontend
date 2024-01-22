<?php

$sOutput = '';

$oApi = clRegistry::get( 'clApi', PATH_MODULE . '/api/models' );
$oUserManager = clRegistry::get( 'clUserManager' );

// Get data dict via API
$aUserAddressDataDict = $oApi->call( '/desc/userAddress', 'GET' );
$aFreightRequestDataDict = $oApi->call( '/desc/freightRequest', 'GET' );

// Get users requests
$aResponse = $oApi->call( '/freightRequests/' . $_SESSION['userId'], 'GET' );
$aFreightRequestData = ( !empty($aResponse['data']) ? $aResponse['data'] : null );

if( !empty($aFreightRequestData) ) {
  clFactory::loadClassFile( 'clOutputHtmlGridTable' );
  $oOutputHtmlTable = new clOutputHtmlGridTable( $aFreightRequestDataDict['entFreightRequest'], [
    'rowGridStyle' => '6em auto 8em 10em'
  ] );
  $oOutputHtmlTable->setTableDataDict( [
    'requestTransporter' => [
      'title' => _( 'Leverantör' )
    ],
    'requestReceiverAddressLiteral' => [
      'title' => _( 'Adress' )
    ],
    'requestCost' => [
      'title' => _( 'Kostnad' )
    ],
  	'controls' => [
  		'title' => ''
  	]
  ] );

  $aReceiverAddresses = [];

  // Get user addresses
  $aResponse = $oApi->call( '/userAddresses/' . $_SESSION['userId'], 'GET' );
  $aUserAddress = ( !empty($aResponse['data']) ? $aResponse['data'] : null );
  $aUserAddress = valueToKey( 'addressId', $aUserAddress );


  foreach( $aFreightRequestData as $aRequest ) {
    switch( $aRequest['requestStatus'] ) {
      case 'suggested':
        $sButtons = '<a href="?ajax=1&view=freightRequest/show.php&requestId=' . $aRequest['requestId'] . '" class="popupLink button submit small fullWidth">' . _( 'Se förslag' ) . '</a>';
        break;

      case 'requested':
        $sButtons = '<a href="?ajax=1&view=freightRequest/show.php&requestId=' . $aRequest['requestId'] . '" class="popupLink button disabled small fullWidth">' . _( 'Förslag begärt' ) . '</a>';
        break;

      case 'declined':
        $sButtons = '<a href="?ajax=1&view=freightRequest/show.php&requestId=' . $aRequest['requestId'] . '" class="popupLink button cancel small fullWidth">' . _( 'Avfärdat' ) . '</a>';
        break;

      case 'accepted':
        $sButtons = '<a href="?ajax=1&view=freightRequest/show.php&requestId=' . $aRequest['requestId'] . '" class="popupLink button disabled small fullWidth">' . _( 'Redo för frakt' ) . '</a>';
        break;

      case 'labelCreated':
      case 'shipped':
        $sButtons = '<a href="' . $aFreightRequestDataDict['definitions']['FREIGHT_UNIFAUN_TRACKING_ENDPOINT'][ $aRequest['requestTransporter'] ] . $aRequest['requestTransporter'] . '" target="_blank" class="button declinedFreight button small fullWidth">' . _( 'Spåra paket' ) . '</a>';
        break;

      default:
        $sButtons = $aRequest['requestStatus'];
    }
    $iReceiverAddressId = ( !empty($aRequest['requestReceiverAddressId']) ? $aRequest['requestReceiverAddressId'] : 0 );


    if( !empty($aRequest['requestReceiverAddressLiteral']) ) {
      $aAddress = json_decode( $aRequest['requestReceiverAddressLiteral'], true );
    } else {
      $aAddress = $aUserAddress[ $iReceiverAddressId ];
    } 

    $sAddress = '';
    if( !empty($aAddress) ) {
      $sAddress = implode( ', ', [
        '<strong>' . $aAddress['addressAddress'] . '</strong>',
        '<strong>' . $aAddress['addressZipCode'] . ' '  . $aAddress['addressCity'] . '</strong>',
        $aAddress['addressName'] . ( !empty($aAddress['addressContactPerson']) ? ' (' . $aAddress['addressContactPerson'] . ')' : '' ),
        '<small><strong>[' . $aUserAddressDataDict['entUserAddress']['addressType']['values'][ $aAddress['addressType'] ] . ']</strong></small>',
      ] );
    }

    $row = [
      'requestTransporter' => $aRequest['requestTransporter'],
      'requestReceiverAddressLiteral' => $sAddress,
      'requestCost' => calculatePrice( $aRequest['requestCost'], INVOICE_PRICE_FORMAT ),
      'controls' => $sButtons
    ];
    $oOutputHtmlTable->addBodyEntry( $row, [
      'data-id' => $aRequest['requestId']
    ] );
  }

  $sOutput = $oOutputHtmlTable->render();
}

echo '
  <div class="view freightRequest tableEdit">
    <h1>' . _( 'Fraktförfrågningar' ) . '</h1>
    ' . $sOutput . '
  </div>';
