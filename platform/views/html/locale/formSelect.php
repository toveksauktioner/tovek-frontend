<?php

$oLocale = clRegistry::get( 'clLocale' );
$aLocales = $oLocale->read();

$aLocaleItems	= array();
$sEditLang = '';
foreach( $aLocales as &$aLocale ) {
	$aLocaleItems[ $aLocale['localeId'] ]	= $aLocale['localeTitle'];
	
	if( $aLocale['localeId'] == $_SESSION['langIdEdit'] ) {
		$sEditLang = strtoupper( substr($aLocale['localeCode'], 0, 2) );
	}
}

$aFormDataDict = array(
	'entLocaleSelect' => array(
		'langIdEdit' => array(
			'type' => 'array',
			'appearance' => 'full',
			'values' => $aLocaleItems,
			'title' => _( 'Editing language' ),
			'attributes' => array(
				'onchange' => 'this.form.submit();'
			)
		)
	)
);

$oLayout = clRegistry::get( 'clLayoutHtml' );
// Special cases to disable the select
if( !empty($_GET['navigationId']) && $oLayout->sLayoutKey == 'adminNavigation' ) {
	// Navigation, when editing a navigation item
	$aFormDataDict['entLocaleSelect']['langIdEdit']['attributes']['disabled'] = 'disabled';
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'data' => array(
		'langIdEdit' => $GLOBALS['langIdEdit']
	),
	'buttons' => array()
) );

echo $oOutputHtmlForm->render();

