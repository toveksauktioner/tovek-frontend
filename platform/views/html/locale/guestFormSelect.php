<?php

$oLocale = clRegistry::get( 'clLocale' );
$aLocaleData = $oLocale->read( array(
	'localeId',
	'localeTitle',
	'localeCode'
) );

$aLocales = array();
foreach( $aLocaleData as $entry ) {
	$aLocales[$entry['localeId']] = _( 'Language' ) . ' (' . $entry['localeTitle'] . ')';
}

$aFormDataDict = array(
	'entLocaleSelect' => array(
		'changeLang' => array(
			'type' => 'array',
			'values' => $aLocales,
			'title' => _( 'Select language' ),
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
		'changeLang' => $GLOBALS['langId']
	),
	'buttons' => array()
) );

echo $oOutputHtmlForm->render();

