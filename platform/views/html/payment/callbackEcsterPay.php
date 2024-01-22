<?php

/* *****************************************
 * Last step in the schema on:
 * https://developer.ecster.se/integration-guide/preparation/#/page-preparation
 **************************************** */

$oPutData = fopen( 'php://input', 'r' );
$aPutData = json_decode( fread($oPutData, 1024), true );
fclose( $oPutData );

// 0. Log incoming data
$GLOBALS['logEngine'] = 'database';
clFactory::loadClassFile( 'clLogger' );
clLogger::log( '-----------------------------------', 'callbackEcsterPay' );
clLogger::log( 'GET data: ' . json_encode( $_GET ), 'callbackEcsterPay' );
clLogger::log( 'POST data: ' . json_encode( $_POST ), 'callbackEcsterPay' );
clLogger::log( 'PUT data: ' . json_encode( $aPutData ), 'callbackEcsterPay' );


// Callbacks are made on failed an denied as welll
// These are only done for logging purposes and will not do anything furter down.
// They are called with the $_POST['functionCalled'] set to either onPaymentFailure or onPaymentDenied

$aReturnData = [];

$oPaymentEcsterPay = clRegistry::get( 'clPaymentEcsterPay', PATH_MODULE . '/payment/models' );
$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );

// 1. Make sure it is a legit call
// Calls can come from Ecster directly or from internal ajax requests
if( !empty($_POST['functionCalled']) && ($_POST['functionCalled'] == 'onPaymentSuccess') ) {
  // Internal call

  if( !empty($_POST['ecsterData']['internalReference']) ) {
    $sEcsterOrderId = $_POST['ecsterData']['internalReference'];
  }

} else if( !empty($aPutData['orderId']) ) {
  $sEcsterOrderId = $aPutData['orderId'];
}

// 2. Update status
if( !empty($sEcsterOrderId) ) {
  $aEcsterOrderData = $oPaymentEcsterPay->ecsterGetOrder( $sEcsterOrderId );
  clLogger::log( 'Ecster data: ' . json_encode($aEcsterOrderData), 'callbackEcsterPay' );

  if( !empty($aEcsterOrderData['orderReference']) && !empty($aEcsterOrderData['status']) && !empty($aEcsterOrderData['transactions']) ) {
    $aLastTransaction = end($aEcsterOrderData['transactions']);
    $iInvoiceOrderId = $aEcsterOrderData['orderReference'];

    switch( $aEcsterOrderData['status'] ) {
      case 'READY':
      case 'PARTIALLY_DELIVERED':
      case 'FULLY_DELIVERED':
        $aInvoiceOrderStatus = $oInvoiceEngine->getInvoiceOrderStatus_in_InvoiceOrder( $iInvoiceOrderId );

        if( $aInvoiceOrderStatus != 'completed' ) {
          // Order cannot already be completed - avoid double payment logs

    		  if( !empty($aLastTransaction['rows']) ) {
            foreach( $aLastTransaction['rows'] as $aTransactionRow ) {
              $iInvoiceId = $aTransactionRow['partNumber'];

        			$aPaymentData = array(
        				'logType' => 'credit',
        				'logAmount' => ( $aTransactionRow['unitAmount'] / 100 ),
        				'logSource' => 'ecster' . $aEcsterOrderData['properties']['method'],
        				'logMessage' => 'Ecster - ' . $aTransactionRow['name'],
        				'logDate' => substr( $aLastTransaction['created'], 0, 10 ),
        				'logInvoiceId' => $iInvoiceId
        			);

        			$oInvoiceEngine->create( 'InvoicePaymentLog', $aPaymentData );
              $oInvoiceEngine->update( 'Invoice', $iInvoiceId, [
                'invoiceStatus' => 'paid'
              ] );
              $oInvoiceEngine->update( 'InvoiceOrder', $iInvoiceOrderId, [
                'invoiceOrderCustomId' => $sEcsterOrderId
              ] );
            }
    		    $oInvoiceEngine->setInvoiceOrderStatus_in_InvoiceOrder( $iInvoiceOrderId, 'completed' );

          } else {
            // No invoices connected - cancel order
            $oInvoiceEngine->setInvoiceOrderCancelled_in_InvoiceOrder( $iInvoiceOrderId );
          }

          $aReturnData = [
            'result' => 'success'
          ];

        }

        break;

      case 'DENIED':
      case 'FAILED':
      case 'ABORTED':
      case 'ANNULLED':
      case 'EXPIRED':
      case 'BLOCKED':
        // Cancel order
        $oInvoiceEngine->setInvoiceOrderCancelled_in_InvoiceOrder( $aEcsterOrderData['orderReference'] );
        break;

      case 'PENDING_PAYMENT':
      case 'PENDING_DECISION':
      case 'PENDING_SIGNATURE':
      case 'PENDING_PROCESSING':
      case 'MANUAL_PROCESSING':
        // Order cannot be delivered
        // Do nothing;
        break;
    }

  } else {
    $aReturnData = [
      'result' => 'failure',
      'error' => _( 'Ecster get order data failed' )
    ];
  }

} else {
  $aReturnData = [
    'result' => 'failure',
    'error' => _( 'No valid data provided' )
  ];
}

// 3. Do nothing - Ecster expects a 200 respons
echo json_encode( $aReturnData );
