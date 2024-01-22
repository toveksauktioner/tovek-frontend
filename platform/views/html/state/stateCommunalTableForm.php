<?php

if( empty($_GET['stateId']) ) {
	$oRouter->redirect( $oRouter->getPath( 'adminStateCommunal' ) );
}

$aErr = array();

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlTable' );

$oState = clRegistry::get( 'clState', PATH_MODULE . '/state/models' );

// Sort
$oState->oDao->aSorting = array(
	'communalId' => 'ASC',
	'communalTitle' => 'ASC'
);

/**
 * Post
 */
if( !empty($_POST['frmAddStateCommunal']) ) {	
	if( !empty($_GET['communalId']) ) {
		// Update
		$_POST['communalUpdated'] = date( 'Y-m-d H:i:s' );
		$oState->updateCommunal( $_GET['communalId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateState' );
		$iStateId = $_GET['communalId'];
		
	} else {
		// Create		
		$_POST['communalCreated'] = date( 'Y-m-d H:i:s' );
		$iStateId = $oState->addCommunal( $_GET['stateId'], $_POST['communalTitle'] );
		$aErr = clErrorHandler::getValidationError( 'createState' );
		
		if( empty($aErr) ) {
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddState',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddStateCommunal").show();
					} );				
				</script>'
			) );
		}
	}
}

// Data
$aAllStates = valueToKey( 'communalId', $oState->readCommunalByState( $_GET['stateId'] ) );

if( !empty($_GET['communalId']) ) {
	// Edit
	$aData = $aAllStates[ $_GET['communalId'] ];
	$sTitle = '';
} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddStateCommunal" class="toggleShow icon iconText iconAdd">' . _( 'Add communal' ) . '</a>';
}

// Datadict
$aDataDict = array(
	'communalTitle' => array()
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oState->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(	
	'frmAddStateCommunal' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oState->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'communalCreated' => array(),
	'controls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
	'communalTitle' => $oOutputHtmlForm->renderFields( 'communalTitle' ),
	'communalCreated' => $oOutputHtmlForm->renderFields( 'frmAddStateCommunal' ),
	'controls' => $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllStates as $aEntry ) {
	if( !empty($_GET['communalId']) && $aEntry['communalId'] == $_GET['communalId'] ) {
		// Edit
		$aAddForm['controls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('communalId', 'event', 'deleteState') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'communalTitle' => $aEntry['communalTitle'],
			'communalCreated' => $aEntry['communalCreated'],
			'controls' => '
				<a href="?communalId=' . $aEntry['communalId'] . '&' . stripGetStr( array( 'deleteStateCommunal', 'event', 'communalId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				<a href="' . $oRouter->getPath( 'adminStateCommunal' ) . '?communalId=' . $aEntry['communalId'] . '" class="icon iconText iconList">' . _( 'Communals' ) . '</a>
				<a href="?event=deleteStateCommunal&deleteStateCommunal=' . $aEntry['communalId'] . '&' . stripGetStr( array( 'deleteStateCommunal', 'event') ) . '" class="icon iconText iconDelete stateConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array('id' => 'sortState_' . $aEntry['communalId']) );
	}
}

if( empty($_GET['communalId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddStateCommunal',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view communalTitle formTable">
		<h1>' . _( 'Communals' ) . '</h1>
		' . $sTitle . '		
		' . $sOutput . '
	</div>';

$oTemplate->addBottom( array(
	'key' => 'stateTableSortable',
	'content' => '
	<script>
		$(".communalTitle table tbody").sortable({
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortState&sortState=1&" + $(this).sortable("serialize"));
			}
		});
	</script>'
) );