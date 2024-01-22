<?php

/**
 * This view required freight module
 */
if( !is_dir(PATH_MODULE . '/freight') ) return;

$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );
$oFreightRelation = clRegistry::get( 'clFreightRelation', PATH_MODULE . '/freight/models' );
$oFreightTypeToCustomerGroup = clRegistry::get( 'clFreightTypeToCustomerGroup', PATH_MODULE . '/freight/models' );
$oFreightWeight = clRegistry::get( 'clFreightWeight', PATH_MODULE . '/freight/models' );
$oFreightCurrency = clRegistry::get( 'clFreightCurrency', PATH_MODULE . '/freight/models' );

$sOutput = '';
$aStatusList = array();

$aFreightTypes = valueToKey( 'freightTypeId', $oFreight->readType() );

/**
 * Product relation count
 */
$aStatusList['productRelation'] = array(
	'title' => _( 'Product relation' ),
	'entries' => array()
);
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

/**
 * Freight weight check
 */
$aStatusList['weightData'] = array(
	'title' => _( 'Weight data' ),
	'entries' => array()
);
$aFreightWeightData = groupByValue( 'freightTypeId', $oFreightWeight->read() );

/**
 * Loop thru all freight types
 */
foreach( $aFreightTypes as $aType ) {
	/**
	 * Product relation count
	 */
	$iRelationCount = !empty($aRelationCount[ $aType['freightTypeId'] ]) ? $aRelationCount[ $aType['freightTypeId'] ] : 0;
	if( $iRelationCount < $iTotalProducts ) {
		$aStatusList['productRelation']['entries'][ $aType['freightTypeTitle'] ] = '<span class="no">' . $iRelationCount . '</span> / ' . $iTotalProducts . ' <span class="no">(' . _( 'not accessible to all products' ) . ')</span>';
	} else {
		$aStatusList['productRelation']['entries'][ $aType['freightTypeTitle'] ] = $iRelationCount . ' / ' . $iTotalProducts;
	}
	
	/**
	 * Freight weight check
	 */
	if( !array_key_exists($aType['freightTypeId'], $aFreightWeightData) ) {
		$aStatusList['weightData']['entries'][ $aType['freightTypeTitle'] ] = '<span class="no">' . _( 'No' ) . '</span>';
	} else {
		$aStatusList['weightData']['entries'][ $aType['freightTypeTitle'] ] = '<span class="yes">' . _( 'Yes' ) . '</span> (' . count( $aFreightWeightData[ $aType['freightTypeId'] ] ) . ')';
	}
}

if( !empty($aStatusList) ) {
	foreach( $aStatusList as $aList ) {
		$aEntries = array();
		foreach( $aList['entries'] as $sTitle => $sValue ) {
			$aEntries[] = '
				<dt>' . $sTitle . '</dt>
				<dd>' . $sValue . '</dd>';
		}		
		$sOutput .= '<div><h2>' . $aList['title'] . '</h2><dl>' . implode("", $aEntries) . '</dl></div><br />';
	}		
}

echo '
	<div class="view dashboard freightStatus">
		<h3>' . _( 'Freight status' ) . '</h3>
		<section>
			' . $sOutput . '
		</section>
	</div>';