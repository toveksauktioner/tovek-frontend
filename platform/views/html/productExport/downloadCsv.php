<?php

$aConfig = array(
	'headline' => true,
	'status' => 'active'
);

$aFileDict = array(
	'templateId' => 'ID',
	'productCustomId' => 'Art no',
	'templateTitleTextId' => 'Title',
	'templateStatus' => 'Status',
	'templateCreated' => 'Created'
);

$oProductTemplate = clRegistry::get( 'clProductTemplate', PATH_MODULE . '/product/models' );

$aReadAdditionalFields = array(
	'templateProductId'
);

/**
 * Data
 */
$aProducts = $oProductTemplate->oDao->read( array(
	'fields' => array_merge( array_keys( $aFileDict ), $aReadAdditionalFields ),
	'status' => $aConfig['status']
) );

$aProductsSorted = array();

/**
 * Custom manual sort
 */
$iCount = 0;
foreach( $aProducts as $aProduct ) {
	$aProductsSorted[$iCount] = $aFileDict;
	foreach( $aProduct as $key => $sValue ) {
		if( array_key_exists($key, $aFileDict) ) {
			$aProductsSorted[$iCount][$key] = $sValue;
		}
	}
	$iCount++;
}

$aOutputLines = array();

/**
 * Headline
 */
if( $aConfig['headline'] === true ) {
	$aOutputLines[] = implode( ';', $aFileDict ) . "\n";
}

/**
 * Format output data
 */
foreach( $aProductsSorted as $aProduct ) {	
	$aOutputLines[] = implode( ';', $aProduct ) . "\n";
	//$aOutputLines[] = implode( ';', array_intersect_key( $aProduct, $aFileDict ) ) . "\n";
}
$sOutput = implode( '', $aOutputLines );

/**
 * Headers
 */
header( "Content-Transfer-Encoding: UTF-8" );
header( "Content-Type: text/csv" );
header( "Content-Disposition: attachment; filename=products-export.csv" );
header( "Cache-Control: no-cache, no-store, must-revalidate" ); 
header( "Pragma: no-cache" );
header( "Expires: 0" );

echo $sOutput;
exit;