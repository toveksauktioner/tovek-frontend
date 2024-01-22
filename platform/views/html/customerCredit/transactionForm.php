<?php

$aErr = array();

$oConfig = clFactory::create( 'clConfig' );
$oCustomerCredit = clRegistry::get( 'clCustomerCredit', PATH_MODULE . '/customer/models' );
$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );

if( !empty($_POST['frmTransactionAdd']) ) {
	$_POST['creditValue'] = (float) $_POST['creditValue'];
	
	$aCreditData = current( $oCustomerCredit->readByCustomerId( $_POST['creditCustomerId'] ) );
	
	if( empty($aCreditData) ) {
		$iCreditId = $oCustomerCredit->create( array(
			'creditCustomerId' => $_POST['creditCustomerId'],
			'creditValue' => 0,
			'creditValueType' => 'credit'
		) );
	} else {
		$iCreditId = $aCreditData['creditId'];
	}
	
	if( ctype_digit($iCreditId) ) {
		if( $_POST['creditValue'] < 0 ) {
			$mResult = $oCustomerCredit->withdrawal( $_POST['creditValue'], $iCreditId );
		} else {
			$mResult = $oCustomerCredit->deposit( $_POST['creditValue'], $iCreditId );
		}
	}
	
	if( $mResult === true ) {
		$oRouter->redirect( $oRouter->sPath );
		
	} elseif( is_array($mResult) ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataError' => current( $mResult )
		) );
		
	} else {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataError' => _( 'Unkown error' )
		) );
		
	}
}

$aReadFields = array(
	'customerId',
	'customerNumber',
	'customerDescription',
	'customerUserId',
	'infoName'
);

$aCustomerData = $oCustomer->read( $aReadFields );
$aCustomers = array();
if( !empty($aCustomerData) ) {
	foreach( $aCustomerData as $aCustomer ) {
		$aCustomers[ $aCustomer['customerId'] ] = !empty($aCustomer['infoName']) ? $aCustomer['infoName'] : $aCustomer['customerDescription'];
	}
}

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oCustomerCredit->oDao->getDataDict(), array(
	'attributes'	=> array(
		'class'	=> 'marginal'
	),
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Make transaction' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'creditCustomerId' => array(
		'type' => 'array',
		'values' => $aCustomers
	),
	'creditValue' => array(),
	'frmTransactionAdd' => array(
		'type' => 'hidden',
		'value' => 1
	)
) );

echo '
	<div class="view transactionForm">
		<h1>' . _( 'Make a transaction' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
	</div>';