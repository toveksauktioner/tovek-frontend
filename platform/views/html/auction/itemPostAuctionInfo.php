<?php

// Print information important to the winner of one or more items
// It reads from backend to work even on old auctions

// One or more item is mandatory as input
if( empty($_GET['itemId']) ) return;

$sOutput = '';

$aItems = explode( ',', $_GET['itemId'] );
if( !empty($aItems) ) {
  // Get item connections
  $oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
  $oBackEnd->setSource( 'entAuctionItem', 'itemId' );
  $oBackEnd->oDao->setCriterias( array(
    'itemId' => array(
      'fields' => 'itemId',
      'type' => 'in',
      'value' => $aItems
    )
  ) );
  $aItemData = valueToKey( 'itemId', $oBackEnd->read( array(
    'itemId',
    'itemSortNo',
    'itemTitle',
    'itemAddressId',
    'itemVehicleDataId'
  ) ) );
  $oBackEnd->oDao->sCriterias = null;


  if( !empty($aItemData) ) {

    // Get address data
    $oBackEnd->setSource( 'entAuctionAddress', 'addressId' );
    $oBackEnd->oDao->setCriterias( array(
      'addressId' => array(
        'fields' => 'addressId',
        'type' => 'in',
        'value' => arrayToSingle( $aItemData, null, 'itemAddressId' )
      )
    ) );
    $aAddresses = valueToKey( 'addressId', $oBackEnd->read('*') );
    $oBackEnd->oDao->sCriterias = null;

    // Get vehicle data
    $oBackEnd->setSource( 'entVehicleData', 'vehicleDataId' );
    $oBackEnd->oDao->setCriterias( array(
      'vehicleDataId' => array(
        'fields' => 'vehicleDataId',
        'type' => 'in',
        'value' => arrayToSingle( $aItemData, null, 'itemVehicleDataId' )
      )
    ) );
    $aVehicles = valueToKey( 'vehicleDataId', $oBackEnd->read( array(
      'vehicleDataId',
      'vehicleLicencePlate',
      'vehicleRegStatus'
    ) ) );
    $oBackEnd->oDao->sCriterias = null;
  }

  // Create printout for item vehicles
  if( !empty($aVehicles) ) {
    $aVehicleToItem = arrayToSingle( $aItemData, 'itemVehicleDataId', 'itemId' );

    $sOutput .= '
      <h1>' . _( 'Fordon' ) . '</h1>
      <hr>';

    foreach( $aVehicles as $iVehicleDataId => $aVehicle ) {
      $aVehicleItem = $aItemData[ $aVehicleToItem[$iVehicleDataId] ];
      $sOutput .= '
        <h2>' . _( 'Rop' ) . ' ' . $aVehicleItem['itemSortNo'] . ': <strong>' . $aVehicleItem['itemTitle'] . '</strong></h2>
        <p>[ ' . $aVehicle['vehicleLicencePlate'] . ' ]</p>
        <p>' . _( 'Fordonet kommer automatiskt att <strong>byta ägare till den person/adress som står angivet på fakturan</strong> och fordonet <strong>kommer bli avställt</strong>.' ) . '</p>
        <p>&nbsp;</p>
        <p>' . sprintf( _('Om du vill ha andra ägaruppgifter eller sätta det i trafik så ring %s.'), '<a href="tel:034648772">0346&#8209;487&nbsp;72</a>' ) . '</p>
        <hr>';
    }

    $sOutput .= '
      <p>&nbsp;</p>
      <p>&nbsp;</p>';
  }

  // Create printout for item location
  if( !empty($aAddresses) ) {
    $aAddressItems = groupByValue( 'itemAddressId', $aItemData );

    $sOutput .= '
      <h1>' . _( 'Avhämtning av rop' ) . '</h1>
      <hr>';

    foreach( $aAddressItems as $iAddressId => $aThisAddressItems ) {
      $aAddressData = $aAddresses[ $iAddressId ];

      foreach( $aThisAddressItems as $aAddressItemData ) {
        $sOutput .= '
          <h3><i class="fas fa-angle-right">&nbsp;</i><small>' . _( 'Rop' ) . ' ' . $aAddressItemData['itemSortNo'] . ':</small> ' . $aAddressItemData['itemTitle'] . '</h3>';
      }

      $sOutput .= '
        <p>&nbsp;</p>
        <p><strong>' . $aAddressData['addressTitle'] . ':</strong> <a href="https://maps.google.com/?q=' . urlencode($aAddressData['addressAddress']) . '" target="_blank">' . $aAddressData['addressAddress'] . '</a>';

      if( !empty($aAddressData['addressAddressDescription']) ) {
        $sOutput .= '
          </p>
          <p>
            <strong>' . _( 'Vägbeskrivning' ) . '</strong>
            ' . $aAddressData['addressAddressDescription'] . '';
      }

			if( !empty(trim($aAddressData['addressCollectSpecial'])) ) {
				$sOutput .= '
          </p>
          <p>&nbsp;</p>
					<p><strong>' . _( 'Tid enligt överenskommelse på telefon' ) . ':</strong> ' . $aAddressData['addressCollectSpecial'] . '</p>';

			} else if( !empty($aAddressData['addressCollectStart']) && ($aAddressData['addressCollectStart'] != '0000-00-00 00:00:00') ) {
				$iCollectStartTime = strtotime( $aAddressData['addressCollectStart'] );
				$iCollectEndTime = strtotime( $aAddressData['addressCollectEnd'] );
				$sOutput .= '
          <strong>' . formatIntlDate( 'EEEE', $iCollectStartTime ) . 'en den ' . formatIntlDate( 'd MMM', $iCollectStartTime ) . ' mellan kl. ' . formatIntlDate( 'HH:mm', $iCollectStartTime ) . '-' . formatIntlDate( 'HH:mm', $iCollectEndTime ) . '</strong>.</p>';
      }

			if( !empty($aAddressData['addressCollectInfo']) ) {
				$sOutput .= '
          <p>&nbsp;</p>
					<h3>' . _( 'Information' ) . '</h3>
          ' . $aAddressData['addressCollectInfo'];
			}

      $sOutput .= '
        <hr>';
    }
  }
}

echo '
  <div class="view auction itemPostAuctionInfo">
    ' . $sOutput . '
  </div>';
