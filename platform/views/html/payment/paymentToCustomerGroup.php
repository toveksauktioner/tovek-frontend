<?php

if( empty($_GET['paymentId']) ) {
	$oRouter->redirect( $oRouter->getPath('adminPayments') );
}

$aErr = array();

$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );
$oPayment->oDao->setLang( $GLOBALS['langIdEdit'] );
$oPaymentToCustomerGroup = clRegistry::get( 'clPaymentToCustomerGroup', PATH_MODULE . '/payment/models' );

$aDataDict = $oPaymentToCustomerGroup->oDao->getDataDict();

$aPaymentData = $oPayment->read( 'paymentTitleTextId', $_GET['paymentId'] );
if( empty($aPaymentData) ) {
	$oRouter->redirect( $oRouter->getPath('adminPayments') );
}

$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
$aCustomerGroupList = arrayToSingle( $oCustomer->readCustomerGroup(), 'groupId', 'groupNameTextId' );

if( !empty($_POST['frmPaymentToCustomerGroupAdd']) ) {	
	if( array_key_exists('groupId', $_POST) && is_array($_POST['groupId']) ) {
		foreach( $_POST['groupId'] as $iGroupId ) {
			$aDataToInsert = array(
				'paymentId' => $_POST['paymentId'],
				'groupId' => $iGroupId
			);
			// Check if tie exists
			$oPaymentToCustomerGroup->oDao->setCriterias( array(
				'groupId' => array(
					'fields' => 'groupId',
					'value' => $iGroupId
				)
			) );
			$aRelationId = $oPaymentToCustomerGroup->readByPayment( 'relationId', $_POST['paymentId'] );
			$oPaymentToCustomerGroup->oDao->setCriterias(null);
			if( empty($aRelationId) ) {				
				$oPaymentToCustomerGroup->create( $aDataToInsert );
			}
		}
	} else {
		$_POST['groupId'] = array();
	}
	
	$aDataToRemove = array();
	$aKeys = array_diff( array_keys($aCustomerGroupList), $_POST['groupId']);	
	foreach( $aKeys as $sCustomerType ) {
		$oPaymentToCustomerGroup->deleteByRelation( $_POST['paymentId'], $sCustomerType );
	}
}

$aData = $oPaymentToCustomerGroup->readByPayment( 'groupId', $_GET['paymentId'] );
if( !empty($aData) ) {
	$_POST['groupId'] = arrayToSingle( $aData, null, 'groupId' );	
}

$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
$aCustomerGroupList = arrayToSingle( $oCustomer->readCustomerGroup(), 'groupId', 'groupNameTextId' );

$aFormDataDict = array(
	'paymentId' => array(
		'type' => 'hidden',
		'value' => $_GET['paymentId']
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
				'name' => 'frmPaymentToCustomerGroupAdd',
				'value' => true
			)
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

echo '
	<div class="view">
		<h1>', sprintf( _( 'Customer types valid for "%s"' ), $aPaymentData[0]['paymentTitleTextId']), '</h1>
		', $oOutputHtmlForm->render(), '
	</div>';