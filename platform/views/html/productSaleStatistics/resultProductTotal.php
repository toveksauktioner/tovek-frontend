<?php

$aErr = array();

$oProductTemplate = clRegistry::get( 'clProductTemplate', PATH_MODULE . '/product/models' );
$oProductCategory = clRegistry::get( 'clProductCategory', PATH_MODULE . '/product/models' );
$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );

// List of categories
$aCategoryList = arrayToSingle( $oProductCategory->read( array(
    'categoryId',
    'categoryTitleTextId'
) ), 'categoryId', 'categoryTitleTextId' );

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oProductTemplate->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 10
) );

/**
 * Data
 */
if( !empty($_GET['category']) ) {
    $aTemplateProducts = $oProductTemplate->readByCategory( $_GET['category'], array(
        'entProductTemplate.templateId',
        'templateProductId',
        'templateTitleTextId',
        'productPrice'
    ) );   
} else {
    $aTemplateProducts = $oProductTemplate->read( array(
        'templateId',
        'templateProductId',
        'templateTitleTextId',
        'productPrice'
    ) );   
}


// Pagination
$sPagination = $oPagination->render();

$sOutput = '';

if( !empty($aTemplateProducts) ) {
    /**
     * Additional product data, grouped by product template
     */
    $aProductByTemplateProduct = groupByValue( 'productTemplateId', $oProduct->readWithTemplateData( array(
        'productId',
        'templateId',
        'productTemplateId',
        'productCustomId',
        'templateTitleTextId' => 'textContent',
        'productPrice',
        'productStatus',
        'templateStatus'
    ) ) );
    
    /**
     * Order values based on product
     */
    $aProductOrderValues = array();
    foreach( $GLOBALS['saleStatistic']['unfilterdData']['orderLines'] as $aLine ) {
        if( !array_key_exists($aLine['lineProductId'], $aProductOrderValues) ) {
            $aProductOrderValues[ $aLine['lineProductId'] ] = 0;
        }
        $fLineTotal = calculatePrice( ($aLine['lineProductPrice'] * $aLine['lineProductQuantity']), array(
            'additional' => array(
                'vatInclude' => true,
                'vat' => $aLine['lineProductVat']
            )
        ) );
        $aProductOrderValues[ $aLine['lineProductId'] ] += $fLineTotal;
    }
    
    $aTableDict = array(
        'entTable' => array(
            'productName' => array(
                'title' => _( 'Product' )
            ),
            'productCustomId' => array(
                'title' => _( 'Article number' )
            ),
            'productCategory' => array(
                'title' => _( 'Category' )
            ),
            'orderValue' => array(
                'title' => _( 'Order value' )
            )
        )
    );
    
    clFactory::loadClassFile( 'clOutputHtmlTable' );
    $oOutputHtmlTable = new clOutputHtmlTable( $aTableDict );
    $oOutputHtmlTable->setTableDataDict( current($aTableDict) );
    
    foreach( $aTemplateProducts as $aTemplateProduct ) {
        $aCategoryIds = arrayToSingle( $oProductTemplate->readTemplateCategories( $aTemplateProduct['templateId'] ), null, 'categoryId' );
        $aCategories = current( $oProductCategory->read( '*', $aCategoryIds ) );
        
        foreach( $aProductByTemplateProduct[ $aTemplateProduct['templateId'] ] as $aProduct ) {
            if( array_key_exists($aProduct['productId'], $aProductOrderValues) ) {
                $sValue = calculatePrice( $aProductOrderValues[ $aProduct['productId'] ], array(
                    'profile' => 'human'
                ) );
            } else {
                $sValue = calculatePrice( 0, array(
                    'profile' => 'human'
                ) );
            }
            
            $oOutputHtmlTable->addBodyEntry( array(
                'productName' => $aProduct['templateTitleTextId'],
                'productCustomId' => $aProduct['productCustomId'],
                'productCategory' => $aCategories['categoryTitleTextId'],
                'orderValue' => $sValue
            ) );
        }
    }
    
    $sOutput = $oOutputHtmlTable->render() . $sPagination;
    
} else {
   $sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

/**
 * Filter form
 */
$aFormDataDict = array(
	'entSelect' => array(
		'category' => array(
			'type' => 'array',
			'title' => _( 'Category' ),
			'values' => array( '*' => _( 'All categories' ) ) + $aCategoryList
		)
	)
);
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'action' => '#productDetail',
	'attributes' => array( 'class' => 'inline' ),
	'errors' => $aErr,
	'data' => !empty($_GET['category']) ? array( 'category' => $_GET['category'] ) : array(),
	'labelSuffix' => '',
	'method' => 'get',
    'includeQueryStr' => false,
	'buttons' => array(
		'submit' => _( 'Show' )
	)
) );
$oOutputHtmlForm->setFormDataDict( current($aFormDataDict) );
$aFilterForm = $oOutputHtmlForm->render();

echo '    
    <section class="tools">
        <div class="tool">
            ' . $aFilterForm . '
        </div>
    </section>
    <section>
        ' . $sOutput . '
    </section>';