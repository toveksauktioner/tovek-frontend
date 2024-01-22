<?php

require_once PATH_MODULE . '/payment/models/clPaymentBase.php';
require_once PATH_MODULE . '/payment/config/cfPaymentSamportHosted.php';

class clPaymentSamportHosted extends clPaymentBase implements ifPaymentMethod {

	public function __construct() {
		$this->initBase();
	}

	public function init( $iOrderId, $aParams = array() ) {
		$aParams += array(
			'language' => SAMPORT_ISO_LANGUAGE,
			'currency' => SAMPORT_ISO_CURRENCY,
			'transactionType' => SAMPORT_TRANSACTION_TYPE
		);

		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );

		$aOrderData = current($this->oOrder->read( '*', $iOrderId ));
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
		foreach( $aOrderLineData as $entry ) {
			$aOrderLines[] = $entry['lineProductCustomId'] . ':' . str_replace( array('”', '’', ';', ':', ',', '=', '&', '?'), '', $entry['lineProductTitle'] ) . ':' . $entry['lineProductQuantity'] . ':' . number_format( $oProduct->calculatePrice($entry['lineProductPrice'], array(
				'vat' => $entry['lineProductVat'],
				'vatInclude' => true
			)) * 100, 0, '.', '' );
		}

		if( !empty($aOrderLines) ) $sOrderData = implode( ',', $aOrderLines );

		$iLastSpaceChar = strrpos( $aOrderData['orderDeliveryName'], ' ' );
		$sOrderFirstName = $iLastSpaceChar !== false ? substr( $aOrderData['orderDeliveryName'], 0, $iLastSpaceChar ) : substr( $aOrderData['orderDeliveryName'], 0 );
		$sOrderLastName = substr( strrchr($aOrderData['orderDeliveryName'], ' '), 1 );

		// Check the technical manual for additional data types / flags
		$sData =
			'TP01=' . SAMPORT_DIRECT_CAPTURE .
			'&TP700=' . SAMPORT_ID .
			'&TP701=' . $iOrderId .
			'&TP740=' . rawurlencode( $sOrderData ) .
			'&TP901=' . $aParams['transactionType'] .
			'&TP491=' . $aParams['language'] .
			'&TP490=' . $aParams['currency'] .
			'&TP5411=' . SAMPORT_FREIGHT .
			'&TP801=' . $aOrderData['orderEmail'] .
			'&TP8021=' . rawurlencode( $sOrderFirstName ) .
			'&TP8022=' . rawurlencode( $sOrderLastName ) .
			'&TP803=' . rawurlencode( $aOrderData['orderDeliveryAddress'] ) .
			'&TP804=' . rawurlencode( $aOrderData['orderDeliveryZipCode'] ) .
			'&TP805=' . rawurlencode( $aOrderData['orderDeliveryCity'] ) .
			'&TP806=' . rawurlencode( $aOrderData['orderDeliveryCountry'] ) .
			//'&TP8071=$CustomerPersonalNumber/ID
			'&TP900=' . getUserIp() .
			'&TP950=#PHPSessionID=' . session_id() . '#CustomerID=' . $aOrderData['orderUserId'];

		$sUrl = 'http://unix.telluspay.com/Add/?' . $sData;
		$rHandle = fopen( $sUrl, 'r' );

		$sTellusPayKey = str_replace( ' ', '%20', fread($rHandle, 1000000) );
		$oRouter = clRegistry::get( 'clRouter' );
		$oRouter->redirect( 'https://secure.telluspay.com/WebOrder/?' . $sTellusPayKey );
	}

	public function checkStatus() {
		switch( $_GET['R'] ) {
			// Success
			case '00':
			case 'T0':
				return true;
				break;
			// Denied
			default:
				$this->aErr[] = _( 'Payment could not be completed' );
				return false;
		}
	}

	public function finalizeOrder( $iOrderId ) {
		parent::finalizeOrder( $iOrderId );
		$aData = array(
			'orderPaymentStatus' => 'paid'
		);
		return $this->oOrder->update( $iOrderId, $aData );
	}

}
