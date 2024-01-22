<?php

// Ping handler for Wasa Kredit API

clFactory::loadClassFile( 'clLogger' );
$sLogFile = 'financing-wasakreditPing.log';

$sLogType = 'ALL';  // ERR | NONE | ALL

$iResponseCode = http_response_code();
$aResponseBody = json_decode( file_get_contents('php://input'), true );
// $aResponseBody = array(
//   'order_id' => 'b9647511-1652-4038-a426-eb654531ef05',
//   'order_status' => 'initialized'
// );

if( ($iResponseCode == 200) && !empty($aResponseBody['order_id']) ) {
  // Handle successful ping back

  $oFinancing = clRegistry::get( 'clFinancingWasaKredit', PATH_MODULE . '/financing/models' );

  $aOrderStatus = ( !empty($aResponseBody['order_status']) ? $aResponseBody['order_status'] : 'initialized' );

  // Existing order?
  $aFinancingData = $oFinancing->readFinancingByExternalId( $aResponseBody['order_id'], 'financingId' );

  if( !empty($aFinancingData)  ) {
    // Update existing financing status
    $iFinancingId = current( current($aFinancingData) );
    $oFinancing->updateFinancing( $iFinancingId, array(
      'financingStatus' => $aOrderStatus
    ) );

  } else {
    // Create new financing instance
    $iFinancingId = $oFinancing->initFinancing( array(
      'financingExternalOrderId' => $aResponseBody['order_id'],
      'financingStatus' => $aOrderStatus
    ) );
  }

  // Add Order Reference to WasaKredit
  $oFinancing->apiAddOrderReference( $aResponseBody['order_id'], array(
    'key' => 'tovek_financing_id',
    'value' => $iFinancingId
  ) );

  // Get live order data and store locally
  $aWasakreditOrderData = $oFinancing->apiGetOrder( $aResponseBody['order_id'] );

  if( is_array($aWasakreditOrderData) ) {
    // Store live order data locally

    $iUserId = null;
    $sInternalRef = null;
    if( !empty($aWasakreditOrderData['order_references']) ) {
      // Get order references
      foreach( $aWasakreditOrderData['order_references'] as $aOrderReference ) {
        if( $aOrderReference['key'] == 'tovek_user_id' ) {
          $iUserId = $aOrderReference['value'];
        }
        if( $aOrderReference['key'] == 'tove_ref_string' ) {
          $sInternalRef = $aOrderReference['value'];
        }
      }
    }

    // Create connections to item
    $fTotalValue = 0;
    foreach( $aWasakreditOrderData['cart_items'] as $aCartItem ) {
      $fTotalValue += $aCartItem['price_ex_vat']['amount'] + $aCartItem['vat_amount']['amount'];

      $aReadParams = array(
  			'financingId' => $iFinancingId,
  			'itemId' => $aCartItem['product_id']
  		);
      $aWriteParams = $aReadParams + array(
        'userId' => $iUserId,
        'requestedValue' => $aCartItem['price_ex_vat']['amount']
      );

      // Connection exists?
      $aItemConnection = $oFinancing->readFinancingToItem( $aReadParams );

      if( !empty($aItemConnection) ) {
        $oFinancing->updateFinancingToItem( $aWriteParams );
      } else {
        $oFinancing->createFinancingToItem( $aWriteParams );
      }

    }

    // Update financing item data
    $oFinancing->updateFinancing( $iFinancingId, array(
      'financingInternalOrderId' => $sInternalRef,
      'financingTotalValue' => $fTotalValue,
      'financingOrgNo' => $aWasakreditOrderData['customer_organization_number'],
      'financingUserId' => $iUserId
    ) );


  } else if ( $sLogType != 'NONE' ) {
    clLogger::log( '------------------------------------', $sLogFile );
    clLogger::log( 'Response Body: ' . print_r($aResponseBody, true), $sLogFile );
    clLogger::log( 'Get Order response: ' . $iResponseCode, $sLogFile );
  }


  if( $sLogType == 'ALL' ) {
    clLogger::log( '------------------------------------', $sLogFile );
    clLogger::log( 'Response Code: ' . $iResponseCode, $sLogFile );
    clLogger::log( 'Response Body: ' . print_r($aResponseBody, true), $sLogFile );
    clLogger::log( 'Created/Updated financing with id: ' . $iFinancingId, $sLogFile );
  }

} else {
  // Log errors

  if( $sLogType != 'NONE' ) {
    clLogger::log( '------------------------------------', $sLogFile );
    clLogger::log( 'Response Code: ' . $iResponseCode, $sLogFile );
    clLogger::log( 'Response Body: ' . print_r($aResponseBody, true), $sLogFile );
  }
}
