<?php

if( empty($_GET['freightTypeId']) ) {
	// No freight type selected
	$oRouter->redirect( $oRouter->getPath( 'adminFreights' ) );
}

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oFreightCurrency = clRegistry::get( 'clFreightCurrency', PATH_MODULE . '/freight/models' );
$oCurrency = clRegistry::get( 'clCurrency', PATH_MODULE . '/currency/models' );
$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );

/**
 * Sort
 */
$oFreight->oDao->aSorting = null;
$oFreightCurrency->oDao->aSorting = array(
	'entryId' => 'ASC'
);

/**
 * Post
 */
if( !empty($_POST['frmAddFreightCurrency']) ) {
	if( !empty($_GET['entryId']) ) {
		/**
		 * Update
		 */
		$_POST['entryUpdated'] = date( 'Y-m-d H:i:s' );
		$oFreightCurrency->update( $_GET['entryId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateFreightCurrency' );
		
	} else {
		/**
		 * Create
		 */
		$_POST['entryCreated'] = date( 'Y-m-d H:i:s' );
		$iEntryId = $oFreightCurrency->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createFreightCurrency' );
		
	}
	
	$oRouter->redirect( $oRouter->sPath . '?freightTypeId=' . $_GET['freightTypeId'] );
}

// All currencies
$aCurrencies = valueToKey( 'currencyId', $oCurrency->read() );

// Freight type data
$aFreightType = current( $oFreight->readType( '*', $_GET['freightTypeId'] ) );

// Entry data
$aData = valueToKey( 'entryId', $oFreightCurrency->readByFreightType( $_GET['freightTypeId'], '*', '*' ) );
$aDataByCurrency = valueToKey( 'entryCurrencyId', $aData );

/**
 * Data
 */
if( !empty($_GET['addCurrencyId']) ) {
	/**
	 * Add
	 */
	$aData = array(
		'entryFreightTypeId' => $_GET['freightTypeId'],
		'entryCurrencyId' => $_GET['addCurrencyId']
	);
	
} elseif( !empty($_GET['entryId']) ) {
	/**
	 * Edit
	 */
	$aData = $aData[ $_GET['entryId'] ];
	
} else {
	/**
	 * New
	 */ 
	$aData = $_POST;
}

/**
 * Form init
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oFreightCurrency->oDao->getDataDict(), array(
	'method' => 'post',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr	
) );
$oOutputHtmlForm->setFormDataDict( array(
	'entryFreightTypeId' => array(
		'type' => 'hidden',
	),
	'entryCurrencyId' => array(
		'type' => 'hidden',
	),
	'entryFreightCurrencyAddition' => array(),
	'entryStatus' => array(),
	'frmAddFreightCurrency' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

/**
 * Table init
 */
$oOutputHtmlTable = new clOutputHtmlTable( $oFreightCurrency->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( array(
	'entryCurrencyId' => array(
		'title' => _( 'Currency' )
	),
	'entryFreightCurrencyAddition' => array(),
	'entryStatus' => array(),
	'entryCreated' => array(),
	'entryUpdated' => array(),
	'controls' => array(
		'title' => ''
	)
) );

/**
 * Table rows
 */
foreach( $aCurrencies as $aCurrency ) {
	// Entry by currency ID
	$aEntry = !empty($aDataByCurrency[ $aCurrency['currencyId'] ]) ? $aDataByCurrency[ $aCurrency['currencyId'] ] : array();
	
	$aHiddenFields = array(
		'entryFreightTypeId',
		'entryCurrencyId',
		'frmAddFreightCurrency'
	);
	
	if( !empty($_GET['addCurrencyId']) && $aCurrency['currencyId'] == $_GET['addCurrencyId'] ) {
		/**
		 * Add
		 */
		$oOutputHtmlTable->addBodyEntry( array(
			'entryCurrencyId' => $aCurrency['currencyTitle'],
			'entryFreightCurrencyAddition' => $oOutputHtmlForm->renderFields( 'entryFreightCurrencyAddition' ),
			'entryStatus' => $oOutputHtmlForm->renderFields( 'entryStatus' ),
			'entryCreated' => '',
			'entryUpdated' => $oOutputHtmlForm->renderFields( $aHiddenFields ),
			'controls' => '
				' . $oOutputHtmlForm->createButton( 'submit', _( 'Add' ) ) . '
				<a href="?freightTypeId=' . $aFreightType['freightTypeId'] . '" class="icon iconText iconEdit">' . _( 'Back' ) . '</a>'
		), array(
			'class' => 'row add'
		) );
		
	} elseif( !empty($_GET['entryId']) && !empty($aEntry) && $aEntry['entryId'] == $_GET['entryId'] ) {
		/**
		 * Edit
		 */
		$oOutputHtmlTable->addBodyEntry( array(
			'entryCurrencyId' => $aCurrency['currencyTitle'],
			'entryFreightCurrencyAddition' => $oOutputHtmlForm->renderFields( 'entryFreightCurrencyAddition' ),
			'entryStatus' => $oOutputHtmlForm->renderFields( 'entryStatus' ),
			'entryCreated' => substr( $aEntry['entryCreated'], 0, 16 ),
			'entryUpdated' => substr( $aEntry['entryUpdated'], 0, 16 ),
			'controls' => '
				' . $oOutputHtmlForm->renderFields( $aHiddenFields ) . '
				' . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) ) . '
				&nbsp;&nbsp;
				<a href="?freightTypeId=' . $aFreightType['freightTypeId'] . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>'
		), array(
			'class' => 'row edit'
		) );
		
	} elseif( !empty($aEntry) ) {
		/**
		 * Data row
		 */
		$oOutputHtmlTable->addBodyEntry( array(
			'entryCurrencyId' => $aCurrency['currencyTitle'],
			'entryFreightCurrencyAddition' => calculatePrice( $aEntry['entryFreightCurrencyAddition'], array('profile' => 'human') ),
			'entryStatus' => '<span class="' . $aEntry['entryStatus'] . '">' . _( ucfirst($aEntry['entryStatus']) ) . '</span>',
			'entryCreated' => substr( $aEntry['entryCreated'], 0, 16 ),
			'entryUpdated' => substr( $aEntry['entryUpdated'], 0, 16 ),
			'controls' => '
				<a href="?' . stripGetStr( array( 'event', 'entryId' ) )  . '&entryId=' . $aEntry['entryId'] . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>'
		), array(
			'class' => 'row data'
		) );
		
	} else {
		/**
		 * Empty row
		 */
		$oOutputHtmlTable->addBodyEntry( array(
			'entryCurrencyId' => $aCurrency['currencyTitle'],
			'entryFreightCurrencyAddition' => calculatePrice( 0, array('profile' => 'human') ),
			'entryStatus' => '<span class="empty">' . _( 'Not in use' ) . '</span>',
			'entryCreated' => '',
			'entryUpdated' => '',
			'controls' => '
				<a href="?addCurrencyId=' . $aCurrency['currencyId'] . '&' . stripGetStr( array( 'event', 'entryId' ) )  . '" class="icon iconText iconEdit">' . _( 'Add' ) . '</a>'
		), array(
			'class' => 'row empty'
		) );
		
	}
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view freight currencyTableEdit">
		<h1>' . sprintf( _( 'Currency addons for %s' ), $aFreightType['freightTypeTitle'] ) . '</h1>	
		' . $sOutput . '
	</div>';
	
$oTemplate->addStyle( array(
	'key' => 'viewCustomStylesheet',
	'content' => '
		.row.empty td:not(.controls) { opacity: .6; }
	'
) );