<?php

$oLocale = clRegistry::get( 'clLocale' );
$aCurrencyData = $oLocale->readCurrency( array(
	'localeDefaultCurrency'
) );

$aCurrencies = array();
foreach( $aCurrencyData as $entry ) {
	$aCurrencies[$entry['localeDefaultCurrency']] = _( 'Currency' ) . ' (' . $entry['localeDefaultCurrency'] . ')';
}

$aFormDataDict = array(
	'entCurrencySelect' => array(
		'changeCurrency' => array(
			'type' => 'array',
			'values' => $aCurrencies,
			'title' => _( 'Select Currency' ),
			'attributes' => array(
				'onchange' => 'this.form.submit();'
			)
		)
	)
);

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'labelSuffix' => ':',
	'data' => array(
		'changeCurrency' => $GLOBALS['currency']
	),
	'buttons' => array()
) );

echo $oOutputHtmlForm->render();

