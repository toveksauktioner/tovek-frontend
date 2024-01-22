<?php

$aErr = array();

$oRouter = clRegistry::get( 'clRouter' );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

if( !empty($_POST['frmAddPuffRoute']) ) {
	$oRouter->oDao->setCriterias( array(
		'routePath' => array(
			'type' => '=',
			'value' => $_POST['routePath'],
			'fields' => array( 'routePath' )
		),
		'routePathLangId' => array(
			'type' => '=',
			'value' => $GLOBALS['langIdEdit'],
			'fields' => array( 'routePathLangId' )
		)
	) );
	$aRouteData = $oRouter->read( '*' );
	$oRouter->oDao->sCriterias = null;

	if( !empty($aRouteData) && !empty($_POST['puffId']) ) {
		$aRouteData = current( $aRouteData );

		$aParams = array(
			'entities' => 'entRouteToObject'
		);
		$oRouter->oDao->createData( array(
			'objectId' => (int) $_POST['puffId'],
			'objectType' => 'Puff',
			'routeId' => (int) $aRouteData['routeId']
		), $aParams );
		$aErr = clErrorHandler::getValidationError( 'createRoute' );
		if( empty($aErr) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The route has been added' )
			) );
		}
	} else {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataError' => _( 'Given route path did not exists' )
		) );
	}
}

if( !empty($_GET['deletePuffRoute']) ) {
	$oRouter->oDao->deleteData( array(
		'entities' => 'entRouteToObject',
		'criterias' => '
			routeId = ' . $oRouter->oDao->oDb->escapeStr( $_GET['deletePuffRoute'] ) . ' AND
			objectId = ' . $oRouter->oDao->oDb->escapeStr( $_GET['puffId'] ) . ' AND
			objectType = "Puff"'
	) );
	$aErr = clErrorHandler::getValidationError( 'deleteRoute' );
	if( empty($aErr) ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => _( 'The route has been removed' )
		) );
		unset( $_GET['deletePuffRoute'] );
	}
}

// Routes
$aData = $oRouter->oDao->read( array(
	'fields' => array(
		'routeId',
		'routePath',
		'routeLayoutKey'
	)
) );
$aRoutes = array( null => _( 'All pages' ) );
foreach( $aData as $entry ) {
	if( substr( $entry['routeLayoutKey'], 0, 5 ) == 'guest' ) {
		$aRoutes[$entry['routeId']] = $entry['routePath'];
	}
}

if( !empty($_GET['puffId']) ) {
	// Data
	$aPuffRouteData = $oRouter->readByObject( $_GET['puffId'], 'Puff', '*', $GLOBALS['langIdEdit'] );

	// DataDict
	$aDataDict = array(
		'entRouteToPuff' => array(
			'routePath' => array(
				'type' => 'string',
				'title' => _( 'Path' )
			)
		)
	);

	// Form
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $aDataDict, array(
		'action' => '',
		'attributes' => array( 'class' => 'inTable' ),
		'data' => $aPuffRouteData,
		'errors' => $aErr,
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Save' )
		),
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'routePath' => array(),
		'puffId' => array(
			'type' => 'hidden',
			'value' => $_GET['puffId']
		),
		'frmAddPuffRoute' => array(
			'type' => 'hidden',
			'value' => true
		)
	) );

	// Table
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $aDataDict );
	$oOutputHtmlTable->setTableDataDict( array(
		'routePath' => array(),
		'routeControls' => array(
			'title' => ''
		)
	) );

	// Add form
	$aRouteForm = array(
		'routePath' => $oOutputHtmlForm->renderFields( 'routePath' ),
		'routeControls' =>
			$oOutputHtmlForm->renderFields( 'puffId' ) .
			$oOutputHtmlForm->renderFields( 'frmAddPuffRoute' ) .
			$oOutputHtmlForm->renderButtons()
	);
	$oOutputHtmlTable->addBodyEntry( $aRouteForm, array(
		'id' => 'frmRouteAdd'
	) );

	if( !empty($aPuffRouteData) ) {
		foreach( $aPuffRouteData as $entry ) {
			$row = array(
				'routePath' => $entry['routePath'],
				'routeControls' => '
					<a href="' . $oRouter->sPath . '?puffId=' . $_GET['puffId'] . '&deletePuffRoute=' . $entry['routeId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			);
			$oOutputHtmlTable->addBodyEntry( $row );
		}
	}

	$sOutput = '
		<p><a href="#frmRouteAdd" class="toggleShow icon iconText iconAdd">' . _( 'Add route' ) . '</a></p>
		' . $oOutputHtmlForm->renderErrors() . $oOutputHtmlForm->renderForm( $oOutputHtmlTable->render() )
		. (empty($aPuffRouteData) ? '<strong>' . _( 'There are no items to show' ) . '</strong>' : '' );

} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view puffRouteTable">
		<h1>' . _( 'Routes' ) . '</h1>
		' . $sOutput . '
	</div>';

$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

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
		$("#routePath").autocomplete(["' . implode( '", "', $aRoutes ) . '"], {
			minChars: 0
		});
	</script>'
) );