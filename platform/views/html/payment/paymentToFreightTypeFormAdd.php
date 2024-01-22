<?php

$aErr = array();

$oPayment = clRegistry::get( 'clPayment', PATH_MODULE . '/payment/models' );
$oPaymentToFreightType = clRegistry::get( 'clPaymentToFreightType', PATH_MODULE . '/payment/models' );
$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );

require_once PATH_FUNCTION . '/fData.php';

// Post
if( !empty($_POST['frmRelationAdd']) ) {
	$aData = array();
	foreach( $_POST['freightTypeId'] as $entry ) {
		$aData[] = array(
			'paymentId' => $_POST['paymentId'],
			'freightTypeId' => $entry
		);
	}
	
	// Delete
	$oPaymentToFreightType->deleteByPayment( $_POST['paymentId'] );
	
	// Create
	$oPaymentToFreightType->oDao->createMultipleData( $aData, array(
		'entities' => 'entPaymentToFreightType',
		'fields' => array(
			'paymentId',			
			'freightTypeId',
		),
		'groupKey' => 'createPaymentToFreightType'
	) );
	
	if( empty($aErr) ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => _( 'The data has been saved' )
		) );
	}
}

// Available freight types
$oFreight->oDao->setCriterias( array(
	'getActiveFreights' => array(
		'type' => '=',
		'value' => 'active',
		'fields' => array( 'entFreightType.freightTypeStatus' )
	)
) );	
$aFreightTypeData = $oFreight->readType( array('freightTypeId','freightTypeTitle') );
$aFreightTypeData = arrayToSingle( $aFreightTypeData, 'freightTypeId', 'freightTypeTitle' );

if( !empty($_GET['paymentId']) ) {

	// Data
	$aData = $oPaymentToFreightType->readByPayment( array( 'freightTypeId' ), $_GET['paymentId'] );
	if( !empty($aData) ) {
		// Edit	
		$aData['freightTypeId'] = arrayToSingle( $aData, 'freightTypeId', 'freightTypeId' );
	} else {
		// Create
		$aData['freightTypeId'] = '*';
	}
	
	// Form
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $oPaymentToFreightType->oDao->getDataDict(), array(
		'data' => $aData,
		'errors' => $aErr,
		'labelSuffix' => ':',
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Save' )
		)
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'paymentId' => array(
			'type' => 'hidden',
			'value' => $_GET['paymentId']
		),
		'freightTypeId' => array(
			'type' => 'arraySet',
			'appearance' => 'full',
			'values' => $aFreightTypeData,
			'title' => _( 'Available freight types' )
		),
		'frmRelationAdd' => array(
			'type' => 'hidden',
			'value' => true
		)
	) );
	
	echo '
		<div class="view adminPaymentToFreightTypeAdd">
			' . $oOutputHtmlForm->render() . '
		</div>';	

} else {
	
	echo '
		<div class="view adminPaymentToFreightTypeAdd">
			<strong>' . _( 'No payment method selected' ) . '</strong>
		</div>';
	
}
