<?php

 // https://checkoutapi.svea.com/docs/#/data-types?id=merchantsettings
 // Check the PushUri section in the above url for info

$oPutData = fopen( 'php://input', 'r' );
$aPutData = json_decode( fread($oPutData, 1024), true );
fclose( $oPutData );

// 0. Log incoming data
$GLOBALS['logEngine'] = 'database';
clFactory::loadClassFile( 'clLogger' );
clLogger::log( '-----------------------------------', 'callbackSvea' );
clLogger::log( 'GET data: ' . json_encode( $_GET ), 'callbackSvea' );
// clLogger::log( 'POST data: ' . json_encode( $_POST ), 'callbackSvea' );
// clLogger::log( 'PUT data: ' . json_encode( $aPutData ), 'callbackSvea' );

// Callbacks are made on failed an denied as well
// These are only done for logging purposes and will not do anything furter down.

// Svea only "pings" this site and expect it to check out the status of the order
// If "sveaOrderId" is not set it is either incorrect or not ready
if( empty($_GET['sveaOrderId']) ) return;


$oPaymentSvea = clRegistry::get( 'clPaymentSvea', PATH_MODULE . '/payment/models' );
$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );

// Check order status
$aReturnData = $oPaymentSvea->sveaGetOrder( (int) $_GET['sveaOrderId'] );

if( !empty($aReturnData) && ($aReturnData['result'] == 'success') ) {
  $aSveaOrderData = $aReturnData['response'];
  $iInvoiceOrderId = $aSveaOrderData['ClientOrderNumber'];

  switch( $aSveaOrderData['Status'] ) {
    case 'Final':
      $aInvoiceOrderStatus = $oInvoiceEngine->getInvoiceOrderStatus_in_InvoiceOrder( $iInvoiceOrderId );

      if( $aInvoiceOrderStatus != 'completed' ) {
        // Order cannot already be completed - avoid double payment logs

  		  if( !empty($aSveaOrderData['Cart']['Items']) ) {
          foreach( $aSveaOrderData['Cart']['Items'] as $aInvoiceItem ) {
            $iInvoiceId = $aInvoiceItem['ArticleNumber'];

      			$aPaymentData = [
      				'logType' => 'credit',
      				'logAmount' => ( $aInvoiceItem['UnitPrice'] / 100 ),
      				'logSource' => 'svea' . $aSveaOrderData['Payment']['PaymentMethodType'],
      				'logMessage' => 'Svea - ' . SVEA_PAYMENT_TYPES[ $aSveaOrderData['Payment']['PaymentMethodType'] ],
      				'logDate' => date( 'Y-m-d' ),
      				'logInvoiceId' => $iInvoiceId
      			];
            
      			$oInvoiceEngine->create( 'InvoicePaymentLog', $aPaymentData );
            $oInvoiceEngine->update( 'Invoice', $iInvoiceId, [
              'invoiceStatus' => 'paid'
            ] );
            $oInvoiceEngine->update( 'InvoiceOrder', $iInvoiceOrderId, [
              'invoiceOrderCustomId' => $_GET['sveaOrderId']
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

    case 'Cancelled':
      // Cancel order
      $oInvoiceEngine->setInvoiceOrderCancelled_in_InvoiceOrder( $iInvoiceOrderId );
      break;

    case 'Created':
      // Order is active but waiting finalization
      // Do nothing;
      break;
  }
  
}

// Print nothing - Svea doesn't want any response
clLogger::log( 'Order data: ' . json_encode( $aReturnData ), 'callbackSvea' );
