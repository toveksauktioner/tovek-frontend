<?php

$aErr = array();

if( !empty($GLOBALS['saleStatistic']['firstOrderDate']) ) {
    /**
     * Filter form
     */
    $aFormDataDict = array(
        'entSelectDate' => array(
            'year' => array(
                'type' => 'array',
                'title' => _( 'Year' ),
                'values' => $GLOBALS['saleStatistic']['availableYears']
            ),
            'month' => array(
                'type' => 'array',
                'title' => _( 'Month' ),
                'values' => $GLOBALS['saleStatistic']['availableMonths']
            ),
            'status' => array(
                'type' => 'array',
                'title' => _( 'Status' ),
                'values' => array(				
                    'new' => _( 'New / received' ),
                    'intermediate' => _( 'Not completed / incompleted' ),
                    'processed' => _( 'Processed / managed' ),
                    'completed' => _( 'Completed / finished' ),
                    'cancelled' => _( 'Cancelled / removed' ),
                    '*' => _( 'All orders' )
                )
            )
        )
    );
    $oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
    $oOutputHtmlForm->init( $aFormDataDict, array(
        'action' => '#periodDetail',
        'attributes' => array( 'class' => 'inline' ),
        'errors' => $aErr,
        'data' => $_GET,
        'labelSuffix' => '',
        'method' => 'get',
        'buttons' => array(
            'submit' => _( 'Show' )
        )
    ) );
    $oOutputHtmlForm->setFormDataDict( current($aFormDataDict) );
    $aFilterForm = $oOutputHtmlForm->render();
    
    $GLOBALS['saleStatistic']['chart'] = array(
        'scripts' => array(),
        'colors' => array(
            '#419ca6',
            '#c3d9ad',
            '#f06859',
            '#ef4036',
            '#8c3030',
            '#1d5c79',
            // Repeat
            '#419ca6',
            '#c3d9ad',
            '#f06859',
            '#ef4036',
            '#8c3030',
            '#1d5c79'
        )
    );
    
    $oLayout = clRegistry::get( 'clLayoutHtml' );
    
    echo '
        <div class="view productSaleStatistics show">
            <h1>' . _( 'Sales statistics' ) . '</h1>
            <hr />
            <h2>' . _( 'Total overview' ) . '</h2>
            <section>
                ' . $oLayout->renderView( 'productSaleStatistics/resultTotal.php' ) . '
            </section>
            <hr /><hr />
            <h2 id="periodDetail">' . _( 'Period detail overview' ) . '</h2>
            <section class="tools">
                <div class="tool">
                    ' . $aFilterForm . '
                </div>
            </section>
            ' . $oLayout->renderView( 'productSaleStatistics/resultFilterTotal.php' ) . '
            <hr /><hr />
            <h2 id="productDetail">' . _( 'Product statistics' ) . '</h2>
            ' . $oLayout->renderView( 'productSaleStatistics/resultProductTotal.php' ) . '
        </div>';
        
    $oTemplate->addScript( array(
        'key' => 'requireJs',
        'src' => '/js/chartJs/Chart.min.js'
    ) );
    $oTemplate->addBottom( array(
        'key' => 'initChartJs',
        'content' => '
            <script>
                ' . implode( ' ', $GLOBALS['saleStatistic']['chart']['scripts'] ) . '
            </script>
        '
    ) );
    
} else {
    
    echo '
        <div class="view productSaleStatistics show">
            <h1>' . _( 'Sales statistics' ) . '</h1>
            <hr />
            <section>
                <strong class="icon iconText iconMissing">' . _( 'You do not have any orders yet' ) . '</strong>
            </section>
        </div>';
}