<?php

$aErr = array();

/**
 * Post payment
 */
if( !empty($_POST['frmSwishPay']) ) {
	$oSwish = clRegistry::get( 'clPaymentSwish', PATH_MODULE . '/payment/models' );
	$sPaymentUrl = $oSwish->initManualPayment( array(
		'payeePaymentReference' => $_POST['swishReference'],
		'payerAlias' => $_POST['swishPhone'],
		'amount' => $_POST['swishAmount'],
		'message' => $_POST['swishMessage']
	) );
	
	if( !empty($sPaymentUrl) ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => sprintf( _( 'Payment was created successfully: %s' ), $sPaymentUrl )
		) );
	}
}

/**
 * Datadict
 */
$aDataDict = array(
	'swishPaymentForm' => array(
		'swishReference' => array(
			'title' => _( 'Reference' ),
			'type' => 'string',
			'attributes' => array(
				'placeholder' => _( 'reference' )
			)
		),
		'swishPhone' => array(
			'title' => _( 'Phone number' ),
			'type' => 'string',
			'attributes' => array(
				'placeholder' => 'ex. 467123345678'
			)
		),
		'swishAmount' => array(
			'title' => _( 'Amount' ),
			'type' => 'string',
			'attributes' => array(
				'placeholder' => _( 'price, between 1 & 999999999999.99' )
			)
		),
		'swishMessage' => array(
			'title' => _( 'Message' ),
			'type' => 'string',
			'attributes' => array(
				'placeholder' => _( 'message' )
			)
		),
		'frmSwishPay' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
);

/**
 * Form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => $oRouter->sPath,
	'error' => $aErr,
	'attributes' => array(
		'class' => 'marginal'
	),
	'includeQueryStr' => false,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Do payment' )
	)
) );

echo '
	<div class="view payment swishPaymentForm">
		<h2>' . _( 'Initialize an Swish payment' ) . '</h2>
		' . $oOutputHtmlForm->render() . '
	</div>';