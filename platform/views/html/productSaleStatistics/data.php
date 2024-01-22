<?php

$aErr = array();

$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );
$oUserManager = clRegistry::get( 'clUserManager' );

/**
 * Variable and data container
 */
$GLOBALS['saleStatistic'] = array(
    // Variables
    'firstOrderDate' => null,
    'availableYears' => array(),
    'availableMonths' => array(),
    // Order data
    'unfilterdData' => array(),
    'filterdData' => array()
);

/**
 * Date of very first order
 */
$aData = current( $oOrder->read( array('MIN(orderCreated)') ) );
$GLOBALS['saleStatistic']['firstOrderDate'] = !empty($aData) ? current($aData) : null;

if( empty($GLOBALS['saleStatistic']['firstOrderDate']) ) {
	return;
}

// Years based on min date
for( $i = date( 'Y', strtotime($GLOBALS['saleStatistic']['firstOrderDate']) ); $i < (date('Y') +1 ); $i++ ) {
    $GLOBALS['saleStatistic']['availableYears'][$i] = $i;
}

// Month list
for( $i = 1; $i <= 12; $i++ ) {
	$sMonth = $i < 10 ? '0'. $i : $i;
    $GLOBALS['saleStatistic']['availableMonths'][$sMonth] = ucfirst( formatIntlDate('MMMM', mktime(0, 0, 0, $i, 1, 2010)) );
}

/**
 * Unfilterd data
 */
$GLOBALS['saleStatistic']['unfilterdData'] = array(
	'orders' => $oOrder->read(),
	'orderLines' => array()
);
$GLOBALS['saleStatistic']['unfilterdData']['orderLines'] = $oOrderLine->readByOrder( arrayToSingle($GLOBALS['saleStatistic']['unfilterdData']['orders'], null, 'orderId'), '*' );

// Default
$_GET += array(
	'year' => date( 'Y', time() ),
	'month' => date( 'm', time() ),
	'status' => 'completed'
);

/**
 * Date filters
 */
$oOrder->oDao->setCriterias( array(
	'orderStatus' => array(
		'type' => 'equals',
		'fields' => 'orderStatus',
		'value' => $_GET['status']
	)
) );
if( !empty($_GET['year']) && !empty($_GET['month']) ) {
	if( $_GET['year'] == '*' && $_GET['month'] != '*' ) {
		// Default to current year
		$_GET['year'] = date( 'Y', time() );
	}

	if( $_GET['month'] != '*' ) {
		//$oOrder->oDao->setCriterias( array(
		//	'fromDate' => array(
		//		'type' => 'like',
		//		'fields' => 'orderCreated',
		//		'value' => $_GET['year'] . '-' . $_GET['month'] . '%'
		//	)
		//) );
		$oOrder->oDao->setCriterias( array(
			'fromDate' => array(
				'type' => 'like',
				'fields' => 'orderCreated',
				'value' => $_GET['year'] . '%'
			)
		) );
	} elseif( $_GET['year'] != '*' ) {
		$oOrder->oDao->setCriterias( array(
			'fromDate' => array(
				'type' => 'like',
				'fields' => 'orderCreated',
				'value' => $_GET['year'] . '%'
			)
		) );
	}
}

/**
 * Filtered data
 */
$GLOBALS['saleStatistic']['filterdData'] = array(
	'orders' => $oOrder->read(),
	'orderLines' => array()
);
$GLOBALS['saleStatistic']['filterdData']['orderLines'] = $oOrderLine->readByOrder( arrayToSingle($GLOBALS['saleStatistic']['filterdData']['orders'], null, 'orderId'), '*' );
