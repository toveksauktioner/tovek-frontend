<?php

$aErr = array();
$sOutput = '';

$oCurrency = clRegistry::get( 'clCurrency', PATH_MODULE . '/currency/models' );

if( !empty($_POST['frmCurrencyAdd']) ) {
	// Update
	if( !empty($_GET['currencyId']) ) {
		$oCurrency->update( $_GET['currencyId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateCurrency' );
		if( empty($aErr) ) $oRouter->redirect( $oRouter->sPath );
	// Create
	} else {
		$oCurrency->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createCurrency' );
	}
}

// External exchange rates
$oCurrencySource = clRegistry::get( 'clCurrencySwedishCentralBank', PATH_MODULE . '/currency/sources' );
$aCurrencies = $oCurrencySource->readRates( arrayToSingle($oCurrency->read(), null, 'currencyCode') );
$aExternCurrencies = arrayToSingle( $aCurrencies, 'code', 'rate' );
$aAvailableCurrencies = $oCurrencySource->getAvailableCurrencies();

// Edit
if( !empty($_GET['currencyId']) ) {

	$aCurrencyData = current( $oCurrency->read('*', $_GET['currencyId']) );
	$sTitle = '';

	// Set external rate
	if( !empty($_GET['action']) ) {
		switch( $_GET['action'] ) {
			case 'setExternalRate':
				if( array_key_exists($aCurrencyData['currencyCode'], $aExternCurrencies) ) {
					$oCurrency->update( $_GET['currencyId'], array( 'currencyRate' => number_format($aExternCurrencies[$aCurrencyData['currencyCode']], 3)) );
					$aErr = clErrorHandler::getValidationError( 'updateCurrency' );
					if( empty($aErr) ) {
						unset( $_GET['currencyId'] );
					}
				}
		}
	}
}

if( isset($_GET['setExternalAllRate']) ) {
	$oCurrency->updateAllCurrencyCodes( $aExternCurrencies );
}

if( empty($_GET['currencyId']) ) {
	$aCurrencyData = $_POST;
	$sTitle = '<a href="#frmCurrencyAdd" class="toggleShow icon iconText iconAdd">' . _( 'Add currency' ) . '</a>';
}

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oCurrency->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('currencyTitle' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'currencyTitle' => array(),
	'currencyCode' => array(),
	'currencyRate' => array()
) );

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oCurrency->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aCurrencyData,
	'errors' => $aErr,
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	),
) );
$oOutputHtmlForm->setFormDataDict( array(
	'currencyTitle' => array(),
	'currencyCode' => array(),
	'currencyRate' => array(),
	'frmCurrencyAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

$sOutput .= '
	<div class="currencyTable">
	' . $oOutputHtmlForm->renderErrors() . '
	' . $sTitle;

clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $oCurrency->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
	'currencyExternalRate' => array(
		'title' => _( 'External rate' )
	),
	'currencyControls' => array(
		'title' => ''
	)
) );

// Add form
$aCurrencyForm = array(
	'currencyTitle' => $oOutputHtmlForm->renderFields( 'currencyTitle' ),
	'currencyCode' => $oOutputHtmlForm->renderFields( 'currencyCode' ),
	'currencyRate' => $oOutputHtmlForm->renderFields( 'currencyRate' ),
	'currencyExternalRate' => '',
	'currencyControls' => $oOutputHtmlForm->renderFields( 'frmCurrencyAdd' ) . $oOutputHtmlForm->renderFields( 'currencyControls' ) . $oOutputHtmlForm->renderButtons()
);
if( empty($_GET['currencyId']) ) {
	$oOutputHtmlTable->addBodyEntry( $aCurrencyForm, array(
		'id' => 'frmCurrencyAdd'
	) );
}

$aCurrencies = $oCurrency->read();

if( !empty($aCurrencies) ) {

	foreach( $aCurrencies as $entry ) {
		if( !empty($_GET['currencyId']) && $_GET['currencyId'] == $entry['currencyId'] ) {
			// Add form
			$aCurrencyForm['currencyExternalRate'] = ( array_key_exists($entry['currencyCode'], $aExternCurrencies) ? number_format($aExternCurrencies[$entry['currencyCode']], 3) : '' );
			$oOutputHtmlTable->addBodyEntry( $aCurrencyForm );
		} else {
			$oOutputHtmlTable->addBodyEntry( array(
				'currencyTitle' => $entry['currencyTitle'],
				'currencyCode' => $entry['currencyCode'],
				'currencyRate' => (float) $entry['currencyRate'],
				'currencyExternalRate' => '
					' . (in_array($entry['currencyCode'], $aAvailableCurrencies) ? '
					<a href="' . $oRouter->sPath . '?action=setExternalRate&amp;currencyId=' . $entry['currencyId'] . '&amp;' . stripGetStr( array('currencyId', 'action') ) . '" class="icon iconDbImport">' . _( 'Set rate' ) . '</a> ' . ( array_key_exists($entry['currencyCode'], $aExternCurrencies) ? number_format($aExternCurrencies[$entry['currencyCode']], 3) : '' ) . '
					' : ''),
				'currencyControls' => '
					' . (in_array($entry['currencyCode'], $aAvailableCurrencies) ? '
					<a href="' . $oRouter->sPath . '?currencyId=' . $entry['currencyId'] . '&action=setExternalRate" class="icon iconText iconDbImport">' . _( 'Update currency' ) . '</a>
					' : '') . '
					<a href="' . $oRouter->sPath . '?currencyId=' . $entry['currencyId'] . '&amp;' . stripGetStr( array('currencyId', 'action') ) . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
					<a href="' . $oRouter->sPath . '?event=deleteCurrency&amp;deleteCurrency=' . $entry['currencyId'] . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			) );
		}
	}

}

$sOutput .= '
		' . $oOutputHtmlTable->render() . '
		' . ( empty($aCurrencies) ? '<strong>' . _('There are no items to show') . '</strong>' : '' ) . '
	</div>';

echo '
	<div class="view currency tableEdit">
		<h1>' . _( 'Currencies' ) . '</h1>
		' . $oOutputHtmlForm->renderForm( $sOutput ),
		'<p><a href="?setExternalAllRate" class="icon iconDbImport">' . _( 'Set all external rates' ) . '</a> <a href="?setExternalAllRate">' . _( 'Set all external rates' ) . '</a></p>
		<p>(' . _( 'External rates comming from' ) . ': <strong>' . $oCurrencySource->sSourceTitle . '</strong>)</p>
	</div>';

$oTemplate->addScript( array(
	'key' => 'jqueryAutoCompleteJs',
	'src' => '/js/jquery.autocomplete.js'
) );
$oTemplate->addLink( array(
	'key' => 'jqueryAutoCompleteCss',
	'href' => '/css/jquery.autocomplete.css'
) );
$oTemplate->addBottom( array(
	'key' => 'leakageAutoComplete',
	'content' => '
	<script type="text/javascript">
		$("#currencyCode").autocomplete(["' . implode( '", "', $aAvailableCurrencies ) . '"], {
			minChars: 0
		});
	</script>'
) );