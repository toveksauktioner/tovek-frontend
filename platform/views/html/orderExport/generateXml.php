<?php
// Generate a order export

$fStart = microtime(true);

if( !empty($_GET['orderId']) ) $_GET['orderId'] = array_map( 'intval', (array) $_GET['orderId'] );

$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );

require_once( PATH_FUNCTION . '/fData.php' );

function washForXml( $sString ) {
	var_dump($sString);
	if( empty($sString) ) return $sString;
	$sString = htmlspecialchars( $sString, ENT_COMPAT, 'UTF-8' );
	return $sString;
}

// Template data
$aOrders = $oOrder->read( array(
	'orderId',
	'orderCustomId',
	'orderUserId',
	'orderEmail',
	'orderUserPin',
	'orderCustomerType',
	'orderDeliveryName',
	'orderDeliveryAddress',
	'orderDeliveryZipCode',
	'orderDeliveryCity',
	'orderDeliveryCountry',
	'orderDeliveryPhone',
	'orderPaymentName',
	'orderPaymentAddress',
	'orderPaymentZipCode',
	'orderPaymentCity',
	'orderPaymentCountry',
	'orderPaymentPhone',
	'orderPaymentCellPhone',
	'orderPaymentReference',
	'orderPaymentType',
	'orderPaymentTypeTitle',
	'orderPaymentCustomId',
	'orderPaymentUrl',
	'orderPaymentToken', 
	'orderFreightPrice',
	'orderFreightPriceVat',
	'orderFreightTypeTitle',
	'orderPackageId',
	'orderPaymentPrice',
	'orderPaymentPriceVat',
	'orderTotal',
	'orderVatTotal',
	'orderCurrency',
	'orderCurrencyRate',
	'orderDiscountCodeKey',
	'orderDiscountCodeDiscount',
	'orderDiscountCodeType',
	'orderMessage',
	'orderStatus',
	'orderPaymentStatus',
	'orderQuantityUpdated',
	'orderCreated'	
), ( !empty($_GET['orderId']) ? $_GET['orderId'] : null ) );

if( empty($aOrders) ) {
	echo '<orders></orders>';
	return;
}

// Order lines
if( !empty($aOrders) ) {
	$aOrderLines = $oOrderLine->readByOrder( arrayToSingle($aOrders, null, 'orderId'), array(
		'lineId',
		'lineOrderId',
		'lineProductId',
		'lineProductCustomId',
		'lineProductTitle',
		'lineProductQuantity',
		'lineProductDeliveryTime',
		'lineProductWeight',
		'lineProductPrice',
		'lineProductVat'
	) );
}
// Sort lines by order
foreach( $aOrderLines as $key1 => $aLine ) {
	foreach( $aOrders as $key2 => $aOrder ) {
		if( $aLine['lineOrderId'] == $aOrder['orderId'] ) {
			$aOrders[$key2]['lines'][] = $aLine;
			break;
		}
	}	
}

// Output below
echo '<!-- ', number_format( (microtime(true) - $fStart), 5 ), ' -->
<orders>';
foreach( $aOrders as $iKey => $aOrder ) {
	echo "\r\n\t", '<order>';
	
	foreach( $aOrder as $sField => $sData ) {
		if( $sField == 'lines' ) continue;
		echo "\r\n\t\t" . '<' . $sField . '>', washForXml($sData), '</' . $sField . '>' ;
	}
	
	// Lines
	echo "\r\n\t\t", '<lines>';
	if( !empty($aOrder['lines']) ) {
		foreach( $aOrder['lines'] as $aLine ) {
			foreach( $aLine as $sField => $sData ) {			
				echo "\r\n\t\t\t" . '<' . $sField . '>', washForXml($sData), '</' . $sField . '>' ;
			}
		}
	}
	echo "\r\n\t\t", '</lines>';
	
	echo "\r\n\t", '</order>';
	//break;
}

echo "\r\n", '</orders>';
