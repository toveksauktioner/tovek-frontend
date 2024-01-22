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

if( !empty($_POST['frmBatchArea']) ) {
	if( !empty($_POST['routeBatch']) ) {
		$aLines = explode( ";", $_POST['routeBatch'] );
		
		foreach( $aLines as $sLine ) {
			$sLine = str_replace( '"', '', $sLine );
			$aValues = explode( ",", $sLine);
			$aRouteData = array(
				'statusDomain' => $aValues[0],
				'statusRoutePath' => $aValues[1],
				'statusLayoutKey' => $aValues[2],
				'statusLangId' => $aValues[3],
				'statusCode' => $aValues[4],
				'statusData' => $aValues[5],
				'statusAddiditonalHeader' => $aValues[6],
				'statusContinueRequest' => $aValues[7]
			);
			$iStatusId = $oRouter->createHttpStatus( $aRouteData );
			$aErr = clErrorHandler::getValidationError( 'createRoute' );
		}
	}
}

// Sort
$oRouter->oDao->aSorting = array(
	'statusId' => 'DESC'
);

// All links
$aAllData = valueToKey( 'statusId', $oRouter->readAllHttpStatus() );

// Reset sort
$oRouter->oDao->aSorting = array();

if( !empty($_GET['statusId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['statusId'] ];
	$sTitle = '';
} else {
	// New
	$aData = $_POST;
	$sTitle = '<p><a href="#frmAddHttpStatus" class="toggleShow icon iconText iconAdd">' . _( 'Add status' ) . '</a></p>';
}

// Datadict
$aDataDict = array(
	'statusDomain' => array(),
	'statusRoutePath' => array(),
	'statusLayoutKey' => array(),
	'statusLangId' => array(),	
	'statusCode' => array(),
	'statusData' => array(),
	'statusAddiditonalHeader' => array(),
	'statusContinueRequest' => array()
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
	'statusRoutePath' => $oOutputHtmlForm->renderFields( 'statusRoutePath' ),
	'statusLayoutKey' => $oOutputHtmlForm->renderFields( 'statusLayoutKey' ),
	'statusLangId' => $oOutputHtmlForm->createField( 'statusLangId', _( 'Language' ), $sLocaleSelect ),	
	'statusCode' => $oOutputHtmlForm->renderFields( 'statusCode' ),
	'statusData' => $oOutputHtmlForm->renderFields( 'statusData' ),
	'statusAddiditonalHeader' => $oOutputHtmlForm->renderFields( 'statusAddiditonalHeader' ),
	'statusContinueRequest' => $oOutputHtmlForm->renderFields( 'statusContinueRequest' ),
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
			'statusRoutePath' => $aEntry['statusRoutePath'],
			'statusLayoutKey' => $aEntry['statusLayoutKey'],
			'statusLangId' => $aEntry['statusLangId'],	
			'statusCode' => $aEntry['statusCode'],
			'statusData' => $aEntry['statusData'],
			'statusAddiditonalHeader' => $aEntry['statusAddiditonalHeader'],
			'statusContinueRequest' => $aEntry['statusContinueRequest'],
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
			<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="' . $oRouter->getPath( 'superHttpStatusTableForm' ) . '">' . _( 'HTTP status' ) . '</a></li>
			<li class="ui-state-default ui-corner-top"><a href="' . $oRouter->getPath( 'superRedirectDomainTableForm' ) . '">' . _( 'Domain redirect' ) . '</a></li>
		</ul>
		<div class="view httpStatusTableEdit ui-tabs-panel ui-widget-content ui-corner-bottom ui-helper-clearfix">
			<h2>' . _( 'Router http status table' ) . '</h2>
			' . $sTitle . '		
			' . $sOutput . '
		</div>
	</div>
	<hr />
	<h1>Batch</h1>
	<div class="view batchArea">
		<section>						
			<p><em>"routePath","Layoutkey",Language,HTTP-Status,"data","additional-header",Continue-request;</em></p>
			<form action="' . $oRouter->sPath .'" method="post">
				<textarea name="routeBatch" style="width: 100%; height: 120px;"></textarea>
				<button class="raised" name="frmBatchArea" value="true">Batch</button>
			</form>
			<div class="meta">
				<h3>Example:</h3>
				<p>"/old-page","",1,301,"/new-page","",yes;</p>
				<p>Encapsulate strings with quotes, language and HTTP-status are integers, line ends with semicolons;</p>
				<p>Delimeters are commas</p>
			</div>
		</section>
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