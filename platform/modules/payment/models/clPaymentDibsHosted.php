<?php

function convertStrToCurrencyId($sCurrency = null) {
	switch( $sCurrency ) {
		case 'USD':
			$iDibsCurrency = 840;
			break;
		case 'EUR':
			$iDibsCurrency = 978;
			break;
		default: // SEK
			$iDibsCurrency = 752;
	}

	return $iDibsCurrency;
}

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentDibsHosted.php';

class clPaymentDibsHosted extends clPaymentBase implements ifPaymentMethod {

	public function __construct() {
		$this->initBase();
	}

	public function init( $iOrderId, $aParams = array() ) {
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );

		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );

		if( !empty($aOrderData) ) {
			# Update order status
			$this->oOrder->update( $iOrderId, array(
				'orderStatus' => 'intermediate'
			) );
		}

		$aOrderLineData = $this->oOrderLine->readByOrder( $iOrderId, array(
			'lineProductId',
			'lineProductCustomId',
			'lineProductTitle',
			'lineProductQuantity',
			'lineProductPrice',
			'lineProductVat'
		) );
		$aOrderLines = array(
			'ordline0-1' => _( 'Product ID' ),
			'ordline0-2' => _( 'Name' ),
			'ordline0-3' => _( 'Quantity' ),
			'ordline0-4' => _( 'Price' ),
		);
		$sOrderData = '';
		$iCount = 1;
		foreach( $aOrderLineData as $entry ) {
			$aOrderLines['ordline' . $iCount . '-1'] = $entry['lineProductCustomId'];
			$aOrderLines['ordline' . $iCount . '-2'] = $entry['lineProductTitle'];
			$aOrderLines['ordline' . $iCount . '-3'] = $entry['lineProductQuantity'];
			$aOrderLines['ordline' . $iCount . '-4'] = number_format( $oProduct->calculatePrice($entry['lineProductPrice'], array(
				'vat' => $entry['lineProductVat'],
				'vatInclude' => true
			)) * 100, 0, '.', '' );
			++$iCount;
		}

		// Price info
		$aPriceInfo = array(
			'priceinfo1.Freight' => $aOrderData['orderFreight'],
			'priceinfo2.Payment' => $aOrderData['orderPaymentPrice'],
			'priceinfo3.Vat' => $aOrderData['orderVatTotal']
		);

		/*
		$iLastSpaceChar = strrpos( $aOrderData['orderDeliveryName'], ' ' );
		$sOrderFirstName = $iLastSpaceChar !== false ? substr( $aOrderData['orderDeliveryName'], 0, $iLastSpaceChar ) : substr( $aOrderData['orderDeliveryName'], 0 );
		$sOrderLastName = substr( strrchr($aOrderData['orderDeliveryName'], ' '), 1 );
		*/

		$aOrderData['orderTotal'] *= 100;

		$sKey = md5( DIBS_SECRET_KEY2 . md5(DIBS_SECRET_KEY1 . 'merchant=' . DIBS_ID . '&orderid=' . $iOrderId . '&currency=' . DIBS_DEFAULT_CURRENCY . '&amount=' . $aOrderData['orderTotal']) );

		// Check the technical manual for additional data types / flags
		$aData = array(
			'merchant' => DIBS_ID,
			'amount' => $aOrderData['orderTotal'],
			'currency' => DIBS_DEFAULT_CURRENCY,
			'orderid' => $iOrderId,
			'accepturl' => DIBS_ACCEPT_URL,
			'cancelurl' => DIBS_CANCEL_URL,
			'callbackurl' => DIBS_CALLBACK_URL,
			'ip' => getUserIp(),
			'lang' => DIBS_DEFAULT_LANAGUAGE,
			'color' => DIBS_DEFAULT_COLOR,
			'md5key' => $sKey,
			'md5key2' => $sKey,
			'changeLang' => $GLOBALS['langId']
		) + $aOrderLines + $aPriceInfo;
		if( DIBS_UNIQUE_ORDER_ID ) $aData['uniqueoid'] = 'yes';
		if( DIBS_CAPTURE_NOW ) $aData['capturenow'] = 'yes';
		if( DIBS_TEST_MODE ) $aData['test'] = 'yes';
		if( DIBS_CALCFEE ) $aData['calcfee'] = 'yes';

		$this->sendPostData( $aData, DIBS_URL );
	}

	/*
	 * Function for using the special module "invoice order" instead of normal "order" to initiate payment
	 * */
	public function initInvoiceOrder( $iInvoiceOrderId, $aParams = array() ) {
		$oInvoiceDao = $this->oInvoiceEngine->getDao( 'Invoice' );
		$aInvoiceDataDict = $oInvoiceDao->getDataDict();

		$aInvoiceOrderData = current( $this->oInvoiceEngine->read('InvoiceOrder', '*', $iInvoiceOrderId) );
		if( !empty($aInvoiceOrderData) ) {
			# Update invoice order status
			$this->oInvoiceEngine->update( 'InvoiceOrder', $iInvoiceOrderId, array(
				'invoiceOrderStatus' => 'intermediate'
			) );
		}

		$aInvoiceData = $this->oInvoiceEngine->readByInvoiceOrder_in_Invoice( $iInvoiceOrderId, array(
			'invoiceId',
			'invoiceNo',
			'invoiceType',
			'invoiceTotalAmount',
			'invoiceTotalVat'
		) );
		if( !empty($aInvoiceData) ) {
			# Make the payment lines
			$aInvoiceOrderLines = array(
				'ordline0-1' => _( 'Invoice no' ),
				'ordline0-2' => _( 'Type' ),
				'ordline0-3' => _( 'Price' ),
			);

			$iCount = 1;
			foreach( $aInvoiceData as $entry ) {
				// All this invoice's payments
				$aInvoicePaymentData = arrayToSingle( $this->oInvoiceEngine->readByInvoice_in_InvoicePaymentLog($entry['invoiceId'], array('logAmount')), null, 'logAmount' ) ;
				$fSumRemaining = $entry['invoiceTotalAmount'] - array_sum( $aInvoicePaymentData );

				$aInvoiceOrderLines['ordline' . $iCount . '-1'] = $entry['invoiceNo'];
				$aInvoiceOrderLines['ordline' . $iCount . '-2'] = $aInvoiceDataDict['entInvoice']['invoiceType']['values'][$entry['invoiceType']];
				$aInvoiceOrderLines['ordline' . $iCount . '-3'] = number_format( calculatePrice($fSumRemaining, array(
					'vat' => $entry['invoiceTotalVat'],
					'vatInclude' => true
				)) * 100, 0, '.', '' );
				++$iCount;
			}

			// Price info
			$aPriceInfo = array(
				'priceinfo2.Payment' => $aInvoiceOrderData['invoiceOrderTotalAmount'],
				'priceinfo3.Vat' => $aInvoiceOrderData['invoiceOrderTotalVat']
			);
		}

		$aInvoiceOrderData['invoiceOrderTotalAmount'] *= 100;

		$sDibsOrderId = DIBS_INVOICE_PREFIX . $iInvoiceOrderId;

		$sKey = md5( DIBS_SECRET_KEY2 . md5(DIBS_SECRET_KEY1 . 'merchant=' . DIBS_ID . '&orderid=' . $sDibsOrderId . '&currency=' . DIBS_DEFAULT_CURRENCY . '&amount=' . $aInvoiceOrderData['invoiceOrderTotalAmount']) );

		// Check the technical manual for additional data types / flags
		$aData = array(
			'merchant' => DIBS_ID,
			'amount' => $aInvoiceOrderData['invoiceOrderTotalAmount'],
			'currency' => DIBS_DEFAULT_CURRENCY,
			'orderid' => $sDibsOrderId,
			'accepturl' => DIBS_INVOICE_ACCEPT_URL,
			'cancelurl' => DIBS_INVOICE_CANCEL_URL,
			'callbackurl' => DIBS_INVOICE_CALLBACK_URL,
			'ip' => getUserIp(),
			'lang' => DIBS_DEFAULT_LANAGUAGE,
			'color' => DIBS_DEFAULT_COLOR,
			'md5key' => $sKey,
			'md5key2' => $sKey,
			'changeLang' => $GLOBALS['langId']
		) + $aInvoiceOrderLines + $aPriceInfo;
		if( DIBS_UNIQUE_ORDER_ID ) $aData['uniqueoid'] = 'yes';
		if( DIBS_CAPTURE_NOW ) $aData['capturenow'] = 'yes';
		if( DIBS_TEST_MODE ) $aData['test'] = 'yes';
		if( DIBS_CALCFEE ) $aData['calcfee'] = 'yes';

		$this->sendPostData( $aData, DIBS_URL );
	}

	public function checkStatus() {
		if( !empty($_POST['transact']) && !empty($_POST['orderid']) && !empty($_POST['md5key']) ) return true;
		$this->aErr[] = _( 'Payment could not be completed' );
		return false;
	}

	public function finalizeOrder( $iOrderId ) {
		if( empty($_POST['transact']) || empty($_POST['md5key2']) ) return false;
		$aOrderData = current( $this->oOrder->read('*', $iOrderId) );
		$iDibsCurrency = convertStrToCurrencyId( $aOrderData['orderCurrency'] );

		$sKey = md5( DIBS_SECRET_KEY2 . md5(DIBS_SECRET_KEY1 . 'merchant=' . DIBS_ID . '&orderid=' . $iOrderId . '&currency=' . $iDibsCurrency . '&amount=' . ($aOrderData['orderTotal'] * 100)) );

		if( $_POST['md5key2'] != $sKey ) return false;

		$this->oOrderHistory->create( array(
			'orderHistoryOrderId' => $iOrderId,
			'orderHistoryUserId' => ( !empty($_SESSION['userId']) ? $_SESSION['userId'] : 0 ),
			'orderHistoryGroupKey' => 'Payment',
			'orderHistoryMessage' => "The order was marked as Paid by DIBS callback", // Do not use gettext as the string will be permanent
			'orderHistoryData' => ''
		) );

		$aData = array(
			'orderStatus' => 'new',
			'orderPaymentCustomId' => $_POST['transact'],
			'orderPaymentStatus' => 'paid'
		);
		$bOrderUpdate = $this->oOrder->update( $iOrderId, $aData );
		parent::finalizeOrder( $iOrderId );
		return $bOrderUpdate;
	}

	/*
	 * Function for finalizing payment of invoice
	 * $mInvoiceOrderId can be mixed, either just the invoice order id or with the prefix for dibs payments
	 * */
	public function finalizeInvoiceOrder( $mInvoiceOrderId ) {

		// Determine the type of order id and make the corresponding variables for the rest of the process
		if( substr($mInvoiceOrderId, 0, strlen(DIBS_INVOICE_PREFIX)) == DIBS_INVOICE_PREFIX ) {
			$sDibsOrderId = $mInvoiceOrderId;
			$iInvoiceOrderId = substr( $mInvoiceOrderId, strlen(DIBS_INVOICE_PREFIX) );
		} else {
			$iInvoiceOrderId = $mInvoiceOrderId;
			$sDibsOrderId = DIBS_INVOICE_PREFIX . $mInvoiceOrderId;
		}

		if( empty($_POST['transact']) || empty($_POST['md5key2']) ) return false;
		$aInvoiceOrderData = current( $this->oInvoiceEngine->read('InvoiceOrder', '*', $iInvoiceOrderId) );
		$iDibsCurrency = DIBS_DEFAULT_CURRENCY;

		$sKey = md5( DIBS_SECRET_KEY2 . md5(DIBS_SECRET_KEY1 . 'merchant=' . DIBS_ID . '&orderid=' . $sDibsOrderId . '&currency=' . $iDibsCurrency . '&amount=' . ($aInvoiceOrderData['invoiceOrderTotalAmount'] * 100)) );

		if( $_POST['md5key2'] != $sKey ) return false;

		// Fetch invoice data
		$aInvoice = $this->oInvoiceEngine->readByInvoiceOrder_in_Invoice( $iInvoiceOrderId, array(
			'invoiceId',
			'invoiceTotalAmount'
		) );

		// Set the invoices as paid
		$aInvoiceId = arrayToSingle( $aInvoice, null, 'invoiceId' );
		$this->oInvoiceEngine->update( 'Invoice', $aInvoiceId, array(
			'invoiceStatus' => 'paid'
		) );

		// Insert into payment log
		foreach( $aInvoice as $entry ) {
			// All this invoice's payments
			$aInvoicePaymentData = arrayToSingle( $this->oInvoiceEngine->readByInvoice_in_InvoicePaymentLog($entry['invoiceId'], array('logAmount')), null, 'logAmount' ) ;
			$fSumRemaining = $entry['invoiceTotalAmount'] - array_sum( $aInvoicePaymentData );

			// Accounting date (this is set to the date based on a day from 23.00:00 to 22:59:59)
			// I.e. an payment made at 2014-11-06 23:01:00 should be considerd payed on 2014-11-07 for accounting reasons
			// Reasons for this is not all clear but it is the way DIBS records the payments.
			// So, by adding an hour the date will be the same date as DIBS records
			$iDibsTime = time() + 3600;
			$sDibsDate = date( 'Y-m-d', $iDibsTime );

			$aData = array(
				'logType' => 'credit',
				'logAmount' => $fSumRemaining,
				'logSource' => 'dibs',
				'logMessage' => $_POST['transact'],
				'logDate' => $sDibsDate,
				'logInvoiceId' => $entry['invoiceId']
			);

			$this->oInvoiceEngine->create( "InvoicePaymentLog", $aData );
		}

		// Set the invoice order to "new" with transaction id
		$aData = array(
			'invoiceOrderStatus' => 'new',
			'invoiceOrderCustomId' => $_POST['transact']
		);
		$bInvoiceOrderUpdate = $this->oInvoiceEngine->update( 'InvoiceOrder', $iInvoiceOrderId, $aData );
		parent::finalizeInvoiceOrder( $iInvoiceOrderId );
		return $bInvoiceOrderUpdate;
	}

}
