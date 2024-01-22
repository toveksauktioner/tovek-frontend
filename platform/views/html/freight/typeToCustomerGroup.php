<?php

if( empty($_GET['freightTypeId']) ) {
	$oRouter->redirect( $oRouter->getPath('adminFreightTypes') );
}

$aErr = array();

$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );
$oFreight->oDao->setLang( $GLOBALS['langIdEdit'] );
$oFreightTypeToCustomerGroup = clRegistry::get( 'clFreightTypeToCustomerGroup', PATH_MODULE . '/freight/models' );

$aDataDict = $oFreightTypeToCustomerGroup->oDao->getDataDict();

$aFreightTypeData = $oFreight->readType( 'freightTypeTitle', $_GET['freightTypeId'] );
if( empty($aFreightTypeData) ) {
	$oRouter->redirect( $oRouter->getPath('adminFreightTypes') );
}

$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
$aCustomerGroupList = arrayToSingle( $oCustomer->readCustomerGroup(), 'groupId', 'groupNameTextId' );

if( !empty($_POST['frmFreightTypeToCustomerGroupAdd']) ) {
	if( array_key_exists('groupId', $_POST) && is_array($_POST['groupId']) ) {
		foreach( $_POST['groupId'] as $sCustomerType ) {
			$aDataToInsert = array(
				'freightTypeId' => $_POST['freightTypeId'],
				'groupId' => $sCustomerType
			);
			// Check if tie exists
			$oFreightTypeToCustomerGroup->oDao->setCriterias( array(
				'groupId' => array(
					'fields' => 'groupId',
					'value' => $sCustomerType
				)
			) );
			$aRelationId = $oFreightTypeToCustomerGroup->readByFreight( 'relationId', $_POST['freightTypeId'] );
			$oFreightTypeToCustomerGroup->oDao->setCriterias(null);
			if( empty($aRelationId) ) {
				$oFreightTypeToCustomerGroup->create( $aDataToInsert );
			}
		}
	} else {
		$_POST['groupId'] = array();
	}
	
	$aDataToRemove = array();
	$aKeys = array_diff( array_keys($aCustomerGroupList), $_POST['groupId']);	
	foreach( $aKeys as $sCustomerType ) {
		$oFreightTypeToCustomerGroup->deleteByRelation( $_POST['freightTypeId'], $sCustomerType );
	}
	
}

$aData = $oFreightTypeToCustomerGroup->readByFreight( 'groupId', $_GET['freightTypeId'] );
if( !empty($aData) ) {
	$_POST['groupId'] = arrayToSingle( $aData, null, 'groupId' );	
}

$aFormDataDict = array(
	'freightTypeId' => array(
		'type' => 'hidden',
		'value' => $_GET['freightTypeId']
	),	
	'groupId' => array(
		'type' => 'arraySet',
		'appearance' => 'full',
		'values' => $aCustomerGroupList,
		'defaultValue' => 'null'
	)
);

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'attributes'	=> array(
		'class' => 'marginal'
	),
	'labelSuffix'	=> ':',
	'errors'		=> array(),
	'data'			=> $_POST,
	'errors' 		=> $aErr,
	'method'		=> 'post',
	'buttons'		=> array(
		'submit' => array(
			'content' => _( 'Save' ),
			'attributes' => array(
				'name' => 'frmFreightTypeToCustomerGroupAdd',
				'value' => true
			)
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

echo '
	<div class="view">
		<h1>', sprintf( _( 'Customer types valid for "%s"' ), $aFreightTypeData[0]['freightTypeTitle']), '</h1>
		', $oOutputHtmlForm->render(), '
	</div>';