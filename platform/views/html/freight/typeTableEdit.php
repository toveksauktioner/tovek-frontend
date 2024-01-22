<?php

$aErr = array();
$bValidContinent = false;
$sOutput = '';

$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );

$aFreightTypeOutputDataDict = array(
	'freightTypeTitle' => array(),
	'freightTypePrice' => array(),
	'freightTypeStatus' => array(),
	'productRelationCount' => array(
		'title' => _( 'Available for' )
	)
);

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oFreight->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('freightTypeSort' => 'ASC') )
) );
$oSorting->setSortingDataDict( $aFreightTypeOutputDataDict );

$aFreightTypes = $oFreight->readType( array(
	'freightTypeId',
	'freightTypeTitle',
	'freightTypePrice',
	'freightTypeStatus',
	'freightTypeSort',
	'freightTypeCreated'
) );

if( !empty($aFreightTypes) ) {
	// Product relation count
	$oFreightRelation = clRegistry::get( 'clFreightRelation', PATH_MODULE . '/freight/models' );
	$oFreightRelation->oDao->setCriterias( array(
		'status' => array(
			'type' => '=',
			'value' => 'valid',
			'fields' => array( 'relationStatus' )
		)
	) );
	$aRelationData = $oFreightRelation->read( array(
		'relationFreightTypeId',
		'relationProductId',
		'relationStatus'
	) );
	$aRelationCount = array();
	foreach( $aRelationData as $aEntry ) {
		if( empty($aRelationCount[$aEntry['relationFreightTypeId']]) ) {
			$aRelationCount[$aEntry['relationFreightTypeId']] = 0;
		}
		++$aRelationCount[$aEntry['relationFreightTypeId']];
	}

	// Products total
	$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
	$iTotalProducts = count( $oProduct->oDao->read( array(
		'status' => null,
		'fields' => 'productId'
	) ) );

	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oFreight->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'freightTypeControls' => array(
			'title' => ''
		) )
	);

	$sEditUrl = $oRouter->getPath( 'adminFreightTypeAdd' );
	$sWeightTableUrl = $oRouter->getPath( 'adminFreightTypeWeight' );
	$sCurrencyTableUrl = $oRouter->getPath( 'adminFreightTypeCurrency' );
	$sCustomerGroupAdd = $oRouter->getPath( 'adminFreightToCustomerGroupAdd' );
	
	foreach( $aFreightTypes as $aEntry ) {
		$sFreightPrice = calculatePrice( $aEntry['freightTypePrice'], array(
			'profile' => 'human',
			'additional' => array(				
				'vatInclude' => true,
				'format' => array(
					'money' => true,
					'vatLabel' => true
				)
			)
		) );
		
		$aRow = array(
			'freightTypeTitle' => $aEntry['freightTypeTitle'],
			'freightTypePrice' => $sFreightPrice,
			'freightTypeStatus' => '<span class="' . $aEntry['freightTypeStatus'] . '">' . $oFreight->oDao->aDataDict['entFreightType']['freightTypeStatus']['values'][ $aEntry['freightTypeStatus'] ] . '</span>',
			'productRelationCount' => '<span>' . (
				!empty($aRelationCount[$aEntry['freightTypeId']]) ? $aRelationCount[$aEntry['freightTypeId']] : '0'
			). ' / ' . $iTotalProducts . ' ' . _( 'products' ) . '</span> <a class="icon iconRelation iconText linkConfirm" title="' . _( 'This will reset all relations to products for this freight type and create new for all with default values' ) . '" href="' . $oRouter->sPath . '?event=resetFreightTypeRelationToAllProducts&amp;resetFreightTypeRelationToAllProducts=' . $aEntry['freightTypeId'] . '&amp;' . stripGetStr( array('event', 'resetAllFreightRelations') ) . '"> ' . _( 'Reset' ) . '</a>',
			'freightTypeControls' => '
				<a class="icon iconPackageLink iconText" href="' . $sWeightTableUrl . '?freightTypeId=' . $aEntry['freightTypeId'] . '">' . _( 'Weights' ) . '</a>
				<a class="icon iconMoney iconText" href="' . $sCurrencyTableUrl . '?freightTypeId=' . $aEntry['freightTypeId'] . '">' . _( 'Currency' ) . '</a>
				<a class="icon iconUser iconText" href="' . $sCustomerGroupAdd . '?freightTypeId=' . $aEntry['freightTypeId'] . '">' . _( 'Customer types' ) . '</a>
				<a class="icon iconEdit iconText" href="' . $sEditUrl . '?freightTypeId=' . $aEntry['freightTypeId'] . '"><span>' . _( 'Edit' ) . '</span></a>
				<a class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '" href="' . $oRouter->sPath . '?event=deleteFreightType&amp;deleteFreightType=' . $aEntry['freightTypeId'] . '&amp;' . stripGetStr( array('event', 'deleteFreightType') ) . '"><span>' . _( 'Delete' ) . '</span></a>'
		);
		$oOutputHtmlTable->addBodyEntry( $aRow, array('id' => 'sortFreightType_' . $aEntry['freightTypeId']) );
	}

	$sOutput .= $oOutputHtmlTable->render();

} else {
	$sOutput .= '
	<p><strong>' . _( 'There are no items to show' ) . '</strong></p>';
}

echo '	
	<div class="view freightTypesTable">
		<h1>' . _( 'Freight' ) . '</h1>
		<section class="tools">
			<div class="tool">	
				<p>' . _( 'Here you can handle the freight. All freight prices you enter about freight is summed up to a total freight price. All freight prices is also always seen as VAT included.' ) . '</p>
			</div>
		</section>
		<hr />
		<h2>' . _( 'Freight types' ) . '</h2>
		<section class="tools">
			<div class="tool">	
				<a href="' . $oRouter->getPath( 'adminFreightTypeAdd' ) . '" class="icon iconText iconAdd">' . _( 'Create new freight type' ) . '</a>
			</div>
		</section>
		<section>
			' . $sOutput . '
		</section>
		<section class="tools">		
			<h5>' . _( 'Global reset option' ) . ':</h5>
			<a class="icon iconRelation iconText linkConfirm" title="' . _( 'This will DELETE ALL existing freight relations to products and create new for all with default values' ) . '" href="' . $oRouter->sPath . '?event=resetAllFreightRelations&amp;resetAllFreightRelations=true&amp;' . stripGetStr( array('event', 'resetAllFreightRelations') ) . '"> ' . _( 'Make all active freight types available for all products' ) . '</a>
		</section>
	</div>';

// Sortable
$oTemplate->addBottom( array(
	'key' => 'freightTypeSortable',
	'content' => '
	<script>
		$(".freightTypesTable table tbody").sortable({
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortFreightType&sortFreightType=1&" + $(this).sortable("serialize"));
			}
		});
	</script>'
) );