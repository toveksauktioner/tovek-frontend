<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oRouter = clRegistry::get( 'clRouter' );

if( !empty($_POST['frmAddHttpStatus']) ) {	
	if( !empty($_GET['statusId']) ) {
		// Update
		$oRouter->updateHttpStatus( $_GET['statusId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateRoute' );
		$iStatusId = $_GET['statusId'];
		
	} else {
		// Create
		$_POST += array(
			'statusLayoutKey' => '',
			'statusLangId' => $_SESSION['langIdEdit'],
			'statusRoutePath' => '/',
			'statusAddiditonalHeader' => '',
			'statusContinueRequest' => 'yes'
		);
		$iStatusId = $oRouter->createHttpStatus( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createRoute' );
		
		if( empty($aErr) ) {
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddHttpStatus',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddHttpStatus").show();
					} );				
				</script>'
			) );
		}
	}
}

// Sort
$oRouter->oDao->aSorting = array(
	'statusId' => 'DESC'
);

/**
 * Data by criterias
 */
$oRouter->oDao->setCriterias( array(
	'statusCode' => array(
		'type' => 'equals',				
		'fields' => 'statusCode',
		'value' => 301
	),
	'statusDomain' => array(
		'type' => 'not',				
		'fields' => 'statusDomain',
		'value' => ''
	)
) );
$aAllData = valueToKey( 'statusId', $oRouter->readAllHttpStatus() );

// Reset
$oRouter->oDao->aSorting = array();
$oRouter->oDao->setCriterias( array() );

if( !empty($_GET['statusId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['statusId'] ];
	$sTitle = '';
} else {
	// New
	$aData = $_POST;
	$sTitle = '<p><a href="#frmAddHttpStatus" class="toggleShow icon iconText iconAdd">' . _( 'Add' ) . '</a></p>';
}

// Datadict
$aDataDict = array(
	'statusDomain' => array(),	
	'statusCode' => array(
		'values' => array (
			300 => _( 'Multiple Choices' ),
			301 => _( 'Moved Permanently' ),
			302 => _( 'Found' ),
			303 => _( 'See Other' ),
		)
	),
	'statusData' => array(
		'title' => _( 'Target domain' )
	)
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oRouter->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(	
	'frmAddHttpStatus' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oRouter->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'tableRowControls' => array(
		'title' => ''
	)
) );

// Locale list
$oLocale = clRegistry::get( 'clLocale' );
$aLocales = arrayToSingle( $oLocale->read( array('localeId', 'localeTitle') ), 'localeId', 'localeTitle' );
$sLocaleSelect = $oOutputHtmlForm->createSelect( 'statusLangId', '', $aLocales );

/**
 * Form row
 */
$aAddForm = array(
	'statusDomain' => $oOutputHtmlForm->renderFields( 'statusDomain' ),
	'statusCode' => $oOutputHtmlForm->renderFields( 'statusCode' ),
	'statusData' => $oOutputHtmlForm->renderFields( 'statusData' ),
	'tableRowControls' => $oOutputHtmlForm->renderFields( 'frmAddHttpStatus' ) . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllData as $aEntry ) {
	if( !empty($_GET['statusId']) && $aEntry['statusId'] == $_GET['statusId'] ) {
		// Edit
		$aAddForm['tableRowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('statusId', 'event', 'deleteDashboardLink') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'statusDomain' => $aEntry['statusDomain'],
			'statusCode' => $aEntry['statusCode'],
			'statusData' => $aEntry['statusData'],
			'tableRowControls' => '
				<a href="?statusId=' . $aEntry['statusId'] . '&' . stripGetStr( array( 'deleteHttpStatus', 'event', 'statusId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				<a href="?event=deleteHttpStatus&deleteHttpStatus=' . $aEntry['statusId'] . '&' . stripGetStr( array( 'deleteHttpStatus', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array(
			//'id' => 'sortHttpStatus_' . $aEntry['statusId']
		) );
	}
}

if( empty($_GET['statusId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddHttpStatus',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">
		<h1>' . _( 'Router tools' ) . '</h1>
		<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">
			<li class="ui-state-default ui-corner-top"><a href="' . $oRouter->getPath( 'superHttpStatusTableForm' ) . '">' . _( 'HTTP status' ) . '</a></li>
			<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="' . $oRouter->getPath( 'superRedirectDomainTableForm' ) . '">' . _( 'Domain redirect' ) . '</a></li>
		</ul>
		<div class="view httpStatusTableEdit ui-tabs-panel ui-widget-content ui-corner-bottom ui-helper-clearfix">
			<h2>' . _( 'Redirect domain table' ) . '</h2>
			' . $sTitle . '		
			' . $sOutput . '
		</div>
	</div>';

/**
 * Layout list auto complete
 */
$oLayout = clRegistry::get( 'clLayoutHtml' );
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
		$("#statusLayoutKey").autocomplete(["' . implode( '", "', arrayToSingle( $oLayout->read( 'layoutKey' ), null, 'layoutKey' ) ) . '"], {
			minChars: 0
		});
	</script>'
) );