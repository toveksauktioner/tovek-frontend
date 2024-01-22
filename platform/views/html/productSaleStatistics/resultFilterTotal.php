<?php

clFactory::loadClassFile( 'clOutputHtmlTable' );

$aData = &$GLOBALS['saleStatistic']['filterdData'];

/**
 * List of customers
 */
$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
$aCustomerData = $oCustomer->read( array('customerId','infoName','customerDescription') );
$aCustomers = array( '0' => _( 'Guest' ) );
if( !empty($aCustomerData) ) {
	foreach( $aCustomerData as $aCustomer ) {
		$aCustomers[ $aCustomer['customerId'] ] = !empty($aCustomer['infoName']) ? $aCustomer['infoName'] : $aCustomer['customerDescription'];
	}
}

/**
 * List of products
 */
$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
$aProducts = valueToKey( 'productId', $oProduct->readWithTemplateData( array(
    'productId',
    'templateTitleTextId' => 'textContent',
    'routePath'
), arrayToSingle($aData['orderLines'], null, 'lineProductId') ) );

$aTableDict = array(
    'entTable' => array(
        'orderCount' => array(
            'title' => _( 'Amount of orders' )
        ),
        'productCount' => array(
            'title' => _( 'Sold products' )
        ),
        'quantityCount' => array(
            'title' => _( 'Sold quantity' )
        ),
        'orderValue' => array(
            'title' => _( 'Order value' )
        ),
        'orderProductValue' => array(
            'title' => _( 'Order product value' )
        )
    )
);

// Year
$aOutputYearData = array(
    'orderCount' => 0,
    'productCount' => array(),
    'quantityCount' => 0,
    'orderValue' => 0,
    'orderProductValue' => 0
);
$aToplistYearData = array(
    'cities' => array(
        'title' => _( 'Cities' ),
        'entries' => 5,
        'values' => array()
    ),
    'customers' => array(
        'title' => _( 'Customers' ),
        'entries' => 5,
        'values' => array()
    ),
    'products' => array(
        'title' => _( 'Products' ),
        'entries' => 5,
        'values' => array()
    )
);

// Month
$aOutputMonthData = array(
    'orderCount' => 0,
    'productCount' => array(),
    'quantityCount' => 0,
    'orderValue' => 0,
    'orderProductValue' => 0
);
$aToplistMonthData = array(
    'cities' => array(
        'title' => _( 'Cities' ),
        'entries' => 5,
        'values' => array()
    ),
    'customers' => array(
        'title' => _( 'Customers' ),
        'entries' => 5,
        'values' => array()
    ),
    'products' => array(
        'title' => _( 'Products' ),
        'entries' => 5,
        'values' => array()
    )
);

/**
 * Months
 */
$aMonthDateOrders = array();
$iMonthDate = strtotime( $_GET['year'] . '-01-15' );
while( $iMonthDate <= strtotime( $_GET['year'] . '-12-15' ) ) {
    $aMonthDateOrders[ date( 'Y-m', $iMonthDate ) ] = 0;
    $iMonthDate = strtotime( '+1 months', $iMonthDate );
}

/**
 * Weeks in current month
 */
$aWeekDateOrders = array();
$sStartDate = firstDateInWeekOfMonth( ltrim($_GET['month'], '0'), $_GET['year'] );
$sEndDate = lastDateInWeekOfMonth( ltrim($_GET['month'], '0'), $_GET['year'] );
$iWeekStart = strtotime( $sStartDate );
while( $iWeekStart < strtotime($sEndDate) ) {
    $aWeekDateOrders[ date( 'W', $iWeekStart ) ] = 0;
    $iWeekStart = strtotime( '+1 week', $iWeekStart );
}

/**
 * Orders by id and total count
 */
$aOrders = valueToKey( 'orderId', $aData['orders'] );
$aOutputYearData['orderCount'] = count( $aOrders );

/**
 * Order total
 */
$aMonthOrderIds = array();
foreach( $aOrders as $aOrder ) {
    $aOutputYearData['orderValue'] += $aOrder['orderTotal'];
    if( date('Y-m', strtotime($aOrder['orderCreated'])) == $_GET['year'] . '-' . $_GET['month'] ) {
        $aOutputMonthData['orderValue'] += $aOrder['orderTotal'];
        $aMonthOrderIds[] = $aOrder['orderId'];

        if( empty($aToplistMonthData['cities']['values'][ $aOrder['orderDeliveryCity'] ]) ) {
            $aToplistMonthData['cities']['values'][ $aOrder['orderDeliveryCity'] ] = 0;
        }
        if( empty($aToplistMonthData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ]) ) {
            $aToplistMonthData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ] = 0;
        }

        $aToplistMonthData['cities']['values'][ $aOrder['orderDeliveryCity'] ] += $aOrder['orderTotal'];
        $aToplistMonthData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ] += $aOrder['orderTotal'];
    }

    if( array_key_exists(date('Y-m', strtotime($aOrder['orderCreated'])), $aMonthDateOrders) ) {
        $aMonthDateOrders[ date('Y-m', strtotime($aOrder['orderCreated'])) ] += $aOrder['orderTotal'];
    }

    if( array_key_exists(date('W', strtotime($aOrder['orderCreated'])), $aWeekDateOrders) ) {
        $aWeekDateOrders[ date('W', strtotime($aOrder['orderCreated'])) ] += $aOrder['orderTotal'];
    }

    if( empty($aToplistYearData['cities']['values'][ $aOrder['orderDeliveryCity'] ]) ) {
        $aToplistYearData['cities']['values'][ $aOrder['orderDeliveryCity'] ] = 0;
    }
    if( empty($aToplistYearData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ]) ) {
        $aToplistYearData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ] = 0;
    }

    $aToplistYearData['cities']['values'][ $aOrder['orderDeliveryCity'] ] += $aOrder['orderTotal'];
    $aToplistYearData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ] += $aOrder['orderTotal'];
}

/**
 * Order line products
 */
$aOrderProducts = array();
$aMonthOrderProducts = array();
foreach( $aData['orderLines'] as $aLine ) {
    $aOutputYearData['quantityCount'] += $aLine['lineProductQuantity'];
    if( in_array($aLine['lineOrderId'], $aMonthOrderIds) ) {
        $aOutputMonthData['quantityCount'] += $aLine['lineProductQuantity'];
    }

    if( empty($aOrderProducts[ $aLine['lineProductId'] ]) ) {
        $aOrderProducts[ $aLine['lineProductId'] ] = 0;
    }
    $aOrderProducts[ $aLine['lineProductId'] ]++;
    if( in_array($aLine['lineOrderId'], $aMonthOrderIds) ) {
        if( empty($aMonthOrderProducts[ $aLine['lineProductId'] ]) ) {
            $aMonthOrderProducts[ $aLine['lineProductId'] ] = 0;
        }
        $aMonthOrderProducts[ $aLine['lineProductId'] ]++;
    }


    $fLineTotal = calculatePrice( ($aLine['lineProductPrice'] * $aLine['lineProductQuantity']), array(
        'additional' => array(
            'vatInclude' => true,
            'vat' => $aLine['lineProductVat']
        )
    ) );
    $aOutputYearData['orderProductValue'] += $fLineTotal;
    if( in_array($aLine['lineOrderId'], $aMonthOrderIds) ) {
        $aOutputMonthData['orderProductValue'] += $fLineTotal;

        if( empty($aToplistMonthData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ]) ) $aToplistMonthData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ] = 0;
        $aToplistMonthData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ] += $fLineTotal;
    }

    if( empty($aToplistYearData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ]) ) $aToplistYearData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ] = 0;
    $aToplistYearData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ] += $fLineTotal;
}
$aOutputYearData['productCount'] = count( $aOrderProducts );
$aOutputMonthData['productCount'] = count( $aMonthOrderProducts );

/**
 * Chart data
 */
$aChart = array(
    'year' => array(
        'pie' => array(
            'labels' => json_encode( array(
                _( 'Products' ),
                _( 'Additional charges' )
            ) ),
            'values' => json_encode( array_map( 'intval', array(
                $aOutputYearData['orderProductValue'],
                $aOutputYearData['orderValue'] - $aOutputYearData['orderProductValue']
            ) ) ),
            'backgrounds' => json_encode( array_map('strval', $GLOBALS['saleStatistic']['chart']['colors']) ),
            //'borders' => json_encode( array_map('strval', array_values($aTotalBorder)) )
        ),
        'bar' => array(
            'labels' => json_encode( array_keys($aMonthDateOrders) ),
            'values' => json_encode( array_map( 'intval', array_values($aMonthDateOrders) ) ),
            'backgrounds' => json_encode( array_map('strval', $GLOBALS['saleStatistic']['chart']['colors']) ),
            //'borders' => json_encode( array_map('strval', array_values($aTotalBorder)) )
        )
    ),
    'month' => array(
        'pie' => array(
            'labels' => json_encode( array(
                _( 'Products' ),
                _( 'Additional charges' )
            ) ),
            'values' => json_encode( array_map( 'intval', array(
                $aOutputMonthData['orderProductValue'],
                $aOutputMonthData['orderValue'] - $aOutputMonthData['orderProductValue']
            ) ) ),
            'backgrounds' => json_encode( array_map('strval', $GLOBALS['saleStatistic']['chart']['colors']) ),
            //'borders' => json_encode( array_map('strval', array_values($aTotalBorder)) )
        ),
        'bar' => array(
            'labels' => json_encode( array_keys($aWeekDateOrders) ),
            'values' => json_encode( array_map( 'intval', array_values($aWeekDateOrders) ) ),
            'backgrounds' => json_encode( array_map('strval', $GLOBALS['saleStatistic']['chart']['colors']) ),
            //'borders' => json_encode( array_map('strval', array_values($aTotalBorder)) )
        )
    )
);

/**
 * Nicer price output
 */
$aOutputYearData['orderValue'] = calculatePrice( $aOutputYearData['orderValue'], array(
    'profile' => 'human'
) );
$aOutputYearData['orderProductValue'] = calculatePrice( $aOutputYearData['orderProductValue'], array(
    'profile' => 'human'
) );
$aOutputMonthData['orderValue'] = calculatePrice( $aOutputMonthData['orderValue'], array(
    'profile' => 'human'
) );
$aOutputMonthData['orderProductValue'] = calculatePrice( $aOutputMonthData['orderProductValue'], array(
    'profile' => 'human'
) );

$aTableDict = array(
    'entTable' => array(
        'orderCount' => array(
            'title' => _( 'Amount of orders' )
        ),
        'productCount' => array(
            'title' => _( 'Sold products' )
        ),
        'quantityCount' => array(
            'title' => _( 'Sold quantity' )
        ),
        'orderValue' => array(
            'title' => _( 'Order value' )
        ),
        'orderProductValue' => array(
            'title' => _( 'Order product value' )
        )
    )
);

$oOutputHtmlTable = new clOutputHtmlTable( $aTableDict );
$oOutputHtmlTable->setTableDataDict( current($aTableDict) );
$oOutputHtmlTable->addBodyEntry( $oOutputHtmlTable->createDataRowByDataKey( $aOutputYearData ) );
$sYearOverviewTable = $oOutputHtmlTable->render();

/**
 * Toplists
 */
$sToplists = '';
$aToplistTableDict = array(
    'entTable' => array(
        'label' => array(
            'title' => _( 'Label' )
        ),
        'value' => array(
            'title' => _( 'Value' )
        )
    )
);
foreach( $aToplistYearData as $sKey => $aToplist ) {
    $aToplistTableDict['entTable']['label']['title'] = $aToplist['title'];

    $oOutputHtmlTable = new clOutputHtmlTable( $aToplistTableDict );
    $oOutputHtmlTable->setTableDataDict( current($aToplistTableDict) );

    foreach( $aToplist['values'] as $key => $value ) {
        $value = calculatePrice( $value, array(
            'profile' => 'human'
        ) );

        $oOutputHtmlTable->addBodyEntry( array(
            'label' => $key,
            'value' => $value
        ) );
    }

    $sToplists .= '
        <div class="list">
            <h3>' . sprintf( _( 'Toplist for %s best %s' ), $aToplist['entries'], $aToplist['title'] ) .'</h3>
            ' . $oOutputHtmlTable->render() . '
        </div>';
}

/**
 * Year
 */
echo '
    <section>
        <h2>' . date( 'Y', strtotime($_GET['year'] . '-' . $_GET['month']) ) . '</h2>
        <div class="col left">
            ' . $sYearOverviewTable . '
            <section class="chart bar"><canvas id="resultFilterYearBar"></canvas></section>
        </div>
        <div class="col right">
            <section class="chart pie"><canvas id="resultFilterYearPie"></canvas></section>
        </div>
        <div class="toplists">
            ' . $sToplists . '
        </div>
    </section>';

$oOutputHtmlTable = new clOutputHtmlTable( $aTableDict );
$oOutputHtmlTable->setTableDataDict( current($aTableDict) );
$oOutputHtmlTable->addBodyEntry( $oOutputHtmlTable->createDataRowByDataKey( $aOutputMonthData ) );
$sMonthOverviewTable = $oOutputHtmlTable->render();

/**
 * Toplists
 */
$sToplists = '';
$aToplistTableDict = array(
    'entTable' => array(
        'label' => array(
            'title' => _( 'Label' )
        ),
        'value' => array(
            'title' => _( 'Value' )
        )
    )
);
foreach( $aToplistMonthData as $sKey => $aToplist ) {
    $aToplistTableDict['entTable']['label']['title'] = $aToplist['title'];

    $oOutputHtmlTable = new clOutputHtmlTable( $aToplistTableDict );
    $oOutputHtmlTable->setTableDataDict( current($aToplistTableDict) );

    foreach( $aToplist['values'] as $key => $value ) {
        $value = calculatePrice( $value, array(
            'profile' => 'human'
        ) );

        $oOutputHtmlTable->addBodyEntry( array(
            'label' => $key,
            'value' => $value
        ) );
    }

    $sToplists .= '
        <div class="list">
            <h3>' . sprintf( _( 'Toplist for %s best %s' ), $aToplist['entries'], $aToplist['title'] ) .'</h3>
            ' . $oOutputHtmlTable->render() . '
        </div>';
}

/**
 * Month
 */
echo '
    <section>
        <h2>' . ucfirst( formatIntlDate('MMMM Y', strtotime($_GET['year'] . '-' . $_GET['month'] . '-15')) ) . '</h2>
        <div class="col left">
            ' . $sMonthOverviewTable . '
            <section class="chart bar"><canvas id="resultFilterMonthBar"></canvas></section>
        </div>
        <div class="col right">
            <section class="chart pie"><canvas id="resultFilterMonthPie"></canvas></section>
        </div>
        <div class="toplists">
            ' . $sToplists . '
        </div>
    </section>';

/**
 * Year
 */
$GLOBALS['saleStatistic']['chart']['scripts'][] = '
    var resultFilterYearPie = new Chart( $("#resultFilterYearPie"), {
        type: "pie",
        data: {
            labels: ' . $aChart['year']['pie']['labels'] . ',
            datasets: [{
                label: "' . _( 'Order total' ) . '",
                data: ' . $aChart['year']['pie']['values'] . ',
                backgroundColor: ["#ef4036","#d12727"],
                borderWidth: 1
            }]
        },
        //options: {
        //    scales: {
        //        yAxes: [{
        //            ticks: {
        //                beginAtZero:true
        //            }
        //        }]
        //    }
        //}
    } );';
$GLOBALS['saleStatistic']['chart']['scripts'][] = '
    var resultFilterYearBar = new Chart( $("#resultFilterYearBar"), {
        type: "bar",
        data: {
            labels: ' . $aChart['year']['bar']['labels'] . ',
            //barBackground: "rgba(240, 240, 240, 1.0)",
            datasets: [{
                label: "' . _( 'Order value' ) . '",
                data: ' . $aChart['year']['bar']['values'] . ',
                backgroundColor: ["#ef4036","#ef4036","#ef4036","#ef4036","#ef4036","#ef4036","#ef4036","#ef4036","#ef4036","#ef4036","#ef4036","#ef4036"],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true
                    }
                }]
            }
        }
    } );';

/**
 * Month
 */
$GLOBALS['saleStatistic']['chart']['scripts'][] = '
    var resultFilterMonthPie = new Chart( $("#resultFilterMonthPie"), {
        type: "pie",
        data: {
            labels: ' . $aChart['month']['pie']['labels'] . ',
            datasets: [{
                label: "' . _( 'Order total' ) . '",
                data: ' . $aChart['month']['pie']['values'] . ',
                backgroundColor: ["#ef4036","#d12727"],
                borderWidth: 1
            }]
        },
        //options: {
        //    scales: {
        //        yAxes: [{
        //            ticks: {
        //                beginAtZero:true
        //            }
        //        }]
        //    }
        //}
    } );';
$GLOBALS['saleStatistic']['chart']['scripts'][] = '
    var resultFilterMonthBar = new Chart( $("#resultFilterMonthBar"), {
        type: "bar",
        data: {
            labels: ' . $aChart['month']['bar']['labels'] . ',
            //barBackground: "rgba(240, 240, 240, 1.0)",
            datasets: [{
                label: "' . _( 'Order value' ) . '",
                data: ' . $aChart['month']['bar']['values'] . ',
                backgroundColor: ["#ef4036","#ef4036","#ef4036","#ef4036","#ef4036","#ef4036"],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true
                    }
                }]
            }
        }
    } );';
