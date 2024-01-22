<?php

clFactory::loadClassFile( 'clOutputHtmlTable' );

$aData = &$GLOBALS['saleStatistic']['unfilterdData'];

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

$aOutputData = array(
    'orderCount' => 0,
    'productCount' => array(),
    'quantityCount' => 0,
    'orderValue' => 0,
    'orderProductValue' => 0
);

$aToplistData = array(
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
 * Six months period
 */
$aMonthDateOrders = array();
$iMonthDate = strtotime( '-5 months', time() );
while( $iMonthDate <= time() ) {
    $aMonthDateOrders[ date( 'Y-m', $iMonthDate ) ] = 0;
    $iMonthDate = strtotime( '+1 months', $iMonthDate );
}

/**
 * Orders by id and total count
 */
$aOrders = valueToKey( 'orderId', $aData['orders'] );
$aOutputData['orderCount'] = count( $aOrders );

/**
 * Order total
 */
foreach( $aOrders as $aOrder ) {
    $aOutputData['orderValue'] += $aOrder['orderTotal'];
    
    if( array_key_exists(date('Y-m', strtotime($aOrder['orderCreated'])), $aMonthDateOrders) ) {
        $aMonthDateOrders[ date('Y-m', strtotime($aOrder['orderCreated'])) ] += $aOrder['orderTotal'];
    }
    
    if( empty($aToplistData['cities']['values'][ $aOrder['orderDeliveryCity'] ]) ) {
        $aToplistData['cities']['values'][ $aOrder['orderDeliveryCity'] ] = 0;
    }
    if( empty($aToplistData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ]) ) {
        $aToplistData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ] = 0;
    }
    
    $aToplistData['cities']['values'][ $aOrder['orderDeliveryCity'] ] += $aOrder['orderTotal'];
    $aToplistData['customers']['values'][ $aCustomers[$aOrder['orderCustomerId']] ] += $aOrder['orderTotal'];
}

/**
 * Order line products
 */
$aOrderProducts = array();
foreach( $aData['orderLines'] as $aLine ) {
    $aOutputData['quantityCount'] += $aLine['lineProductQuantity'];
    
    if( empty($aOrderProducts[ $aLine['lineProductId'] ]) ) {
        $aOrderProducts[ $aLine['lineProductId'] ] = 0;
    }
    $aOrderProducts[ $aLine['lineProductId'] ]++;
    
    $fLineTotal = calculatePrice( ($aLine['lineProductPrice'] * $aLine['lineProductQuantity']), array(
        'additional' => array(
            'vatInclude' => true,
            'vat' => $aLine['lineProductVat']
        )
    ) );
    $aOutputData['orderProductValue'] += $fLineTotal;
    
    if( empty($aToplistData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ]) ) $aToplistData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ] = 0;
    $aToplistData['products']['values'][ $aProducts[$aLine['lineProductId']]['templateTitleTextId'] ] += $fLineTotal;
}
$aOutputData['productCount'] = count( $aOrderProducts );

/**
 * Chart data
 */
$aChart = array(
    'pie' => array(
        'labels' => json_encode( array(
            _( 'Products' ),
            _( 'Additional charges' )
        ) ),
        'values' => json_encode( array_map( 'intval', array(
            $aOutputData['orderProductValue'],
            $aOutputData['orderValue'] - $aOutputData['orderProductValue']
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
);

/**
 * Nicer output
 */
$aOutputData['orderValue'] = calculatePrice( $aOutputData['orderValue'], array(
    'profile' => 'human'
) );
$aOutputData['orderProductValue'] = calculatePrice( $aOutputData['orderProductValue'], array(
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
$oOutputHtmlTable->addBodyEntry( $oOutputHtmlTable->createDataRowByDataKey( $aOutputData ) );
$sOverviewTable = $oOutputHtmlTable->render();

/**
 * Toplists
 */
$sToplists = '';
$aTableDict = array(
    'entTable' => array(
        'label' => array(
            'title' => _( 'Label' )
        ),
        'value' => array(
            'title' => _( 'Value' )
        )
    )
);
foreach( $aToplistData as $sKey => $aToplist ) {
    $aTableDict['entTable']['label']['title'] = $aToplist['title'];
    
    $oOutputHtmlTable = new clOutputHtmlTable( $aTableDict );
    $oOutputHtmlTable->setTableDataDict( current($aTableDict) );
    
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

echo '
    <div class="col left">
        ' . $sOverviewTable . '
        <section class="chart bar"><canvas id="resultTotalBar"></canvas></section>
    </div>
    <div class="col right">
        <section class="chart pie"><canvas id="resultTotalPie"></canvas></section>
    </div>
    <div class="toplists">
        ' . $sToplists . '
    </div>';
    
$GLOBALS['saleStatistic']['chart']['scripts'][] = '
    var resultTotalPie = new Chart( $("#resultTotalPie"), {
        type: "pie",
        data: {
            labels: ' . $aChart['pie']['labels'] . ',
            datasets: [{
                label: "' . _( 'Order total' ) . '",
                data: ' . $aChart['pie']['values'] . ',
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
    var resultTotalBar = new Chart( $("#resultTotalBar"), {
        type: "bar",
        data: {
            labels: ' . $aChart['bar']['labels'] . ',
            //barBackground: "rgba(240, 240, 240, 1.0)",
            datasets: [{
                label: "' . _( 'Last six months from now' ) . '",
                data: ' . $aChart['bar']['values'] . ',
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