<?php

$aErr = array();

$oConfig = clFactory::create( 'clConfig' );
$oCustomerCredit = clRegistry::get( 'clCustomerCredit', PATH_MODULE . '/customer/models' );

if( !empty($_POST['frmEditConfig']) ) {	
	unset( $_POST['frmEditConfig'] );
	
	unset( $oConfig->oDao->aDataDict['entConfig']['configValue']['required'] );
	
	foreach( $_POST as $sConfigKey => $sConfigValue ) {
		$oConfig->upsert( $sConfigKey, array(
			'configKey' => $sConfigKey,
			'configValue' => $sConfigValue,
			'configGroupKey' => CUSTOMER_CREDIT_CONFIG_KEY
		) );
		$aErr = clErrorHandler::getValidationError( 'upsertConfig' );		
	}
	
	if( empty($aErr) ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => _( 'The data has been saved' )
		) );
	}
}

/**
 * Read config values
 */
$aConfigData = arrayToSingle( $oConfig->oDao->readData( array(
	'fields' => '*',
	'criterias' => 'configGroupKey = ' . $oConfig->oDao->oDb->escapeStr( 'CustomerCredit' )
) ), 'configKey', 'configValue' );

// Datadict
$aConfigDataDict = array( $GLOBALS['creditConfigDataDict'] );

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aConfigDataDict, array(
	'attributes'	=> array(
		'class'	=> 'marginal'
	),
	'data' => $aConfigData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( $GLOBALS['creditConfigDataDict'] + array(	
	'frmEditConfig' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

$sOutput = $oOutputHtmlForm->render();

echo '
	<div class="view customerCreditConfig">
		<h1>' . _( 'Settings' ) . '</h1>	
		' . $sOutput . '
	</div>';