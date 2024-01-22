<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oCustomerService = clRegistry::get( 'clCustomerService', PATH_MODULE . '/customer/models' );

// Sort
$oCustomerService->oDao->aSorting = array(
	'serviceTitleTextId' => 'ASC',
	'serviceId' => 'ASC'
);

/**
 * Post
 */
if( !empty($_POST['frmAddCustomerService']) ) {	
	if( !empty($_GET['serviceId']) ) {
		// Update
		$_POST['serviceUpdated'] = date( 'Y-m-d H:i:s' );
		$oCustomerService->update( $_GET['serviceId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateCustomerService' );
		$iServiceId = $_GET['serviceId'];
		
	} else {
		// Create		
		$_POST['serviceCreated'] = date( 'Y-m-d H:i:s' );
		$iServiceId = $oCustomerService->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createCustomerService' );
		
		if( empty($aErr) ) {
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddCustomerService',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddCustomerService").show();
					} );				
				</script>'
			) );
		}
	}
}

// All services
$aAllData = valueToKey( 'serviceId', $oCustomerService->read() );

if( !empty($_GET['serviceId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['serviceId'] ];
	$sTitle = '';
} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddCustomerService" class="toggleShow icon iconText iconAdd">' . _( 'Add service' ) . '</a>';
}

// Datadict
$aDataDict = array(
	'serviceTitleTextId' => array(),
	'serviceDescriptionTextId' => array()
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oCustomerService->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(	
	'frmAddCustomerService' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oCustomerService->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'serviceCreated' => array(),
	'serviceUpdated' => array(),
	'tableRowControls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
	'serviceTitleTextId' => $oOutputHtmlForm->renderFields( 'serviceTitleTextId' ),
	'serviceDescriptionTextId' => $oOutputHtmlForm->renderFields( 'serviceDescriptionTextId' ),	
	'serviceCreated' => $oOutputHtmlForm->renderFields( 'frmAddCustomerService' ),
	'serviceUpdated' => '',
	'tableRowControls' => $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllData as $aEntry ) {
	if( !empty($_GET['serviceId']) && $aEntry['serviceId'] == $_GET['serviceId'] ) {
		// Edit
		$aAddForm['tableRowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('serviceId', 'event', 'deleteCustomerService') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'serviceTitleTextId' => $aEntry['serviceTitleTextId'],			
			'serviceDescriptionTextId' => $aEntry['serviceDescriptionTextId'],			
			'serviceCreated' => $aEntry['serviceCreated'],
			'serviceUpdated' => $aEntry['serviceUpdated'],
			'tableRowControls' => '
				<a href="?serviceId=' . $aEntry['serviceId'] . '&' . stripGetStr( array( 'deleteCustomerService', 'event', 'serviceId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				<a href="?event=deleteCustomerService&deleteCustomerService=' . $aEntry['serviceId'] . '&' . stripGetStr( array( 'deleteCustomerService', 'event') ) . '" class="icon iconText iconDelete serviceConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array('id' => 'sortCustomerService_' . $aEntry['serviceId']) );
	}
}

if( empty($_GET['serviceId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddCustomerService',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view customer serviceFormTable">
		<h1>' . _( 'Customer services' ) . '</h1>
		' . $sTitle . '		
		' . $sOutput . '
	</div>';

$oTemplate->addBottom( array(
	'key' => 'serviceTableSortable',
	'content' => '
	<script>
		$(".customerServiceServiceTableEdit table tbody").sortable( {
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortCustomerService&sortCustomerService=1&" + $(this).sortable("serialize"));
			}
		} );
	</script>'
) );