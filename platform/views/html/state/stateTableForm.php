<?php

$aErr = array();

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlTable' );

$oState = clRegistry::get( 'clState', PATH_MODULE . '/state/models' );

// Sort
$oState->oDao->aSorting = array(
	'stateId' => 'ASC',
	'stateTitle' => 'ASC'
);

/**
 * Post
 */
if( !empty($_POST['frmAddState']) ) {	
	if( !empty($_GET['stateId']) ) {
		// Update
		$_POST['stateUpdated'] = date( 'Y-m-d H:i:s' );
		$oState->update( $_GET['stateId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateState' );
		$iStateId = $_GET['stateId'];
		
	} else {
		// Create		
		$_POST['stateCreated'] = date( 'Y-m-d H:i:s' );
		$iStateId = $oState->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createState' );
		
		if( empty($aErr) ) {
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddState',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddState").show();
					} );				
				</script>'
			) );
		}
	}
}

// Data
$aAllStates = valueToKey( 'stateId', $oState->read() );

if( !empty($_GET['stateId']) ) {
	// Edit
	$aData = $aAllStates[ $_GET['stateId'] ];
	$sTitle = '';
} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddState" class="toggleShow icon iconText iconAdd">' . _( 'Add state' ) . '</a>';
}

// Datadict
$aDataDict = array(
	'stateTitle' => array()
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
	'frmAddState' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oState->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'stateCreated' => array(),
	'controls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
	'stateTitle' => $oOutputHtmlForm->renderFields( 'stateTitle' ),
	'stateCreated' => $oOutputHtmlForm->renderFields( 'frmAddState' ),
	'controls' => $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllStates as $aEntry ) {
	if( !empty($_GET['stateId']) && $aEntry['stateId'] == $_GET['stateId'] ) {
		// Edit
		$aAddForm['controls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('stateId', 'event', 'deleteState') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'stateTitle' => $aEntry['stateTitle'],
			'stateCreated' => $aEntry['stateCreated'],
			'controls' => '
				<a href="?stateId=' . $aEntry['stateId'] . '&' . stripGetStr( array( 'deleteState', 'event', 'stateId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				<a href="' . $oRouter->getPath( 'adminStateCommunal' ) . '?stateId=' . $aEntry['stateId'] . '" class="icon iconText iconList">' . _( 'Communals' ) . '</a>
				<a href="?event=deleteState&deleteState=' . $aEntry['stateId'] . '&' . stripGetStr( array( 'deleteState', 'event') ) . '" class="icon iconText iconDelete stateConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array('id' => 'sortState_' . $aEntry['stateId']) );
	}
}

if( empty($_GET['stateId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddState',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view stateTitle formTable">
		<h1>' . _( 'States' ) . '</h1>
		' . $sTitle . '		
		' . $sOutput . '
	</div>';

$oTemplate->addBottom( array(
	'key' => 'stateTableSortable',
	'content' => '
	<script>
		$(".stateTitle table tbody").sortable({
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortState&sortState=1&" + $(this).sortable("serialize"));
			}
		});
	</script>'
) );