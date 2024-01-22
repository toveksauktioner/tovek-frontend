<?php

require_once PATH_MODULE . '/payment/models/clPaymentDibsHosted.php';
require_once PATH_MODULE . '/payment/config/cfPaymentDibsHostedInvoice.php';

class clPaymentDibsHostedInvoice extends clPaymentDibsHosted {

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
		$aOrderLines = array();
		$sOrderData = '';
		$iCount = 1;
		foreach( $aOrderLineData as $entry ) {
			$aOrderLines[] = '<orderItem VATPercent="' . ($entry['lineProductVat'] * 10000) .
							'" itemDescription="' . $entry['lineProductTitle'] .
							'" itemID="' . $entry['lineProductCustomId'] .
							'" orderRowNumber="' . $iCount .
							'" price="' . number_format( calculatePrice($entry['lineProductPrice']) * 100, 0, '.', '' ) .
							'" quantity="' . $entry['lineProductQuantity'] .
							'" unitCode="pcs"/>';
			++$iCount;
		}

		$sStructuredOrderInformation = '<?xml version="1.0" encoding="UTF-8"?>
<orderInformation>
' . implode( "\n", $aOrderLines ) . '
</orderInformation>';

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
			#'ip' => getUserIp(),
			'currency' => DIBS_DEFAULT_CURRENCY,
			'lang' => DIBS_DEFAULT_LANAGUAGE,
			'color' => DIBS_DEFAULT_COLOR,

			# PayByBill specifics
			'paytype' => 'PBB',
			'structuredOrderInformation' => htmlentities($sStructuredOrderInformation),
			'createInvoiceNow' => DIBS_CREATE_INVOICE_NOW ? 'true' : 'false',
			'doNotShowLastPage' => DIBS_INVOICE_DO_NOT_SHOW_LAST_PAGE ? 'true' : 'false',

			'md5key' => $sKey,
			'md5key2' => $sKey,
			'changeLang' => $GLOBALS['langId']
		);
		if( DIBS_UNIQUE_ORDER_ID ) $aData['uniqueoid'] = 'yes';

		$this->sendPostData( $aData, DIBS_URL );
	}

}