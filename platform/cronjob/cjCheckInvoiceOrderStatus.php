<?php

die();

/**
 * 5 * * * * /usr/bin/php -f /home/httpd/platform/cronjob/cjCheckInvoiceOrderStatus.php >/dev/null
 * ('crontab -e' on server to edit)
 */

// The code handling order update is sama as in /platform/view/html/callbackEcsterPay.php

try {
    // Bootstrap platform
	require_once( dirname(dirname(__FILE__)) . '/core/bootstrap.php' );
	$_SERVER['REQUEST_URI'] = ''; // Cronjob fix for router

	ini_set( 'error_reporting', E_ALL );
	ini_set( 'display_errors', true );
	ini_set( 'display_startup_errors', true );
	ini_set( 'memory_limit', '1G' );
	set_time_limit( 0 );

	/**
     * Cronjob error handling
     */
	function cronjobErrorHandler( $iLevel, $sMsg, $sFilename = '', $iLineNr = '' ) {
		switch ( $iLevel ) {
			case E_USER_ERROR:
				$sError = sprintf( _('Fatal Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			case E_USER_WARNING:
				$sError = sprintf( _('Warning: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			case E_USER_NOTICE:
				$sError = sprintf( _('Notice: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
			default:
				$sError = sprintf( _('Unknown Error: "%s" on line %s in file %s'), $sMsg, $iLineNr, $sFilename );
				break;
		}
		echo $sError;

		file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionEndErrors.log', date('Y-m-d H:i:s') . ' ' . $sError . "\n", FILE_APPEND );
		return true;
	}
	set_error_handler( 'cronjobErrorHandler' );
	function setException( $oException ) {
		echo sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() );
		file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjAuctionEndErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );
		return true;
	}
	set_exception_handler( 'setException' );

	// Error (not exception) variable
	$aError = array();

	// Dependency files
	// require_once( PATH_FUNCTION . '/fData.php' );
  $oPaymentEcsterPay = clRegistry::get( 'clPaymentEcsterPay', PATH_MODULE . '/payment/models' );
  $oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
    $oInvoiceOrderDao = $oInvoiceEngine->getDao( 'InvoiceOrder' );


  // DO SPECIFIC STUFF
  $fStartTime = time();
  $aOrdersUpdated = [];
  $iLast24h = $fStartTime - 86400;

  $oInvoiceOrderDao->setCriterias( [
    'createdDate' => [
      'type' => '>=',
      'value' => date( 'Y-m-d H:i:s', $iLast24h ),
      'fields' => 'invoiceOrderCreated'
    ],
    'status' => [
      'type' => 'in',
      'value' => [ 'new', 'intermediate', 'processed' ],
      'fields' => 'invoiceOrderStatus'
    ],
    'ecsterPay' => [
      'type' => '=',
      'value' => 6,           // 6 = Ecster Pay
      'fields' => 'invoiceOrderPaymentId'
    ]
  ] );
  $aInvoiceOrders = arrayToSingle( $oInvoiceEngine->read('InvoiceOrder', [
    'invoiceOrderId',
    'invoiceOrderStatus'
  ]), 'invoiceOrderId', 'invoiceOrderStatus' );

  if( !empty($aInvoiceOrders) ) {
    foreach( $aInvoiceOrders as $iInvoiceOrderId => $sInvoiceOrderStatus ) {
      $aEcsterOrderSearch = current( $oPaymentEcsterPay->ecsterSearchOrder([
        'orderReference' => $iInvoiceOrderId
      ]) );

      if(!empty($aEcsterOrderSearch['id']) ) {
        $aEcsterOrderData = $oPaymentEcsterPay->ecsterGetOrder( $aEcsterOrderSearch['id'] );
      }

      if( !empty($aEcsterOrderData['orderReference']) && !empty($aEcsterOrderData['status']) && !empty($aEcsterOrderData['transactions']) ) {
        $aLastTransaction = end($aEcsterOrderData['transactions']);
        $iInvoiceOrderId = $aEcsterOrderData['orderReference'];

        switch( $aEcsterOrderData['status'] ) {
          case 'READY':
          case 'PARTIALLY_DELIVERED':
          case 'FULLY_DELIVERED':
            if( $sInvoiceOrderStatus != 'completed' ) {
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
                    'invoiceOrderCustomId' => $aEcsterOrderData['id']
                  ] );
                }
                $aOrdersUpdated[] = $iInvoiceOrderId . ' (completed)';
        		    $oInvoiceEngine->setInvoiceOrderStatus_in_InvoiceOrder( $iInvoiceOrderId, 'completed' );

              } else {
                // No invoices connected - cancel order
                // $aOrdersUpdated[] = $iInvoiceOrderId . ' (cancelled)';
                // $oInvoiceEngine->setInvoiceOrderCancelled_in_InvoiceOrder( $iInvoiceOrderId );
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
            $aOrdersUpdated[] = $iInvoiceOrderId . ' (cancelled)';
            $oInvoiceEngine->setInvoiceOrderCancelled_in_InvoiceOrder( $iInvoiceOrderId );
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
      }
    }
  }


	// Log
	if( !empty($aOrdersUpdated) ) {
		clFactory::loadClassFile( 'clLogger' );
		clLogger::log( '- Cronjob started @ ' . date( 'Y-m-d H:i:s', $fStartTime ) . ' -', 'cjCheckInvoiceOrderStatus.log' );
		clLogger::log( 'Orders updated: ' . implode( ' | ', $aOrdersUpdated ) , 'cjCheckInvoiceOrderStatus.log' );
		clLogger::log( 'Cronjob finished @ ' . number_format( microtime(true) - $fStartTime, 4 ) . 's.', 'cjCheckInvoiceOrderStatus.log' );
		clLogger::logRotate( 'cjCheckInvoiceOrderStatus.log', '8M' );
	}

} catch( Throwable $oThrowable ) {
    // Exception logging
	file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjCheckInvoiceOrderStatusErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );

} catch( Exception $oException ) {
	// Exception logging
	file_put_contents( dirname(dirname(__FILE__)) . '/logs/cjCheckInvoiceOrderStatusErrors.log', date('Y-m-d H:i:s') . ' ' . sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ), FILE_APPEND );

}
