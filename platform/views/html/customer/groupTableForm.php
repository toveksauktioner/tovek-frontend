<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );
$oCustomer->oDao->setLang( $GLOBALS['langIdEdit'] );

// Sort
$oCustomer->oDao->aSorting = array(
	'groupId' => 'ASC'
);

if( !empty($_POST['frmAddGroup']) ) {	
	if( !empty($_GET['groupId']) ) {
		// Update
		$oCustomer->updateGroup( $_GET['groupId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateCustomerGroup' );
		
		if( empty($aErr) ) {
			$oRouter->redirect( $oRouter->sPath );
		}	
		
	} else {
		// Create		
		$iGroupId = $oCustomer->createGroup( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createCustomerGroup' );
		
		if( empty($aErr) ) {
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddCustomerGroup',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddGroup").show();
					} );				
				</script>'
			) );
		}
	}
}

// All groups
$aCustomerGroups = valueToKey( 'groupId', $oCustomer->readCustomerGroup() );

if( !empty($_GET['groupId']) ) {
	// Edit
	$aData = $aCustomerGroups[ $_GET['groupId'] ];
	$sTitle = '';
} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddGroup" class="toggleShow icon iconText iconAdd">' . _( 'Add group' ) . '</a>';
}

// Datadict
$aDataDict = array(
	'groupId' => array(),
	'groupNameTextId' => array(),
	'groupVatInclusion' => array(),
	'groupAutoGrantedUsage' => array()
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oCustomer->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(	
	'frmAddGroup' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oCustomer->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'tableRowControls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
	'groupId' => '',
	'groupNameTextId' => $oOutputHtmlForm->renderFields( 'groupNameTextId' ),
	'groupVatInclusion' => $oOutputHtmlForm->renderFields( 'groupVatInclusion' ),	
	'groupAutoGrantedUsage' => $oOutputHtmlForm->renderFields( 'groupAutoGrantedUsage' ),	
	'tableRowControls' => $oOutputHtmlForm->renderFields( 'frmAddGroup' ) . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aCustomerGroups as $aEntry ) {
	if( !empty($_GET['groupId']) && $aEntry['groupId'] == $_GET['groupId'] ) {
		// Edit
		$aAddForm['tableRowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('groupId', 'event', 'deleteCustomerGroup') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'groupId' => $aEntry['groupId'],			
			'groupNameTextId' => $aEntry['groupNameTextId'],
			'groupVatInclusion' => $oCustomer->oDao->aDataDict['entCustomerGroup']['groupVatInclusion']['values'][ $aEntry['groupVatInclusion'] ],
			'groupAutoGrantedUsage' => $oCustomer->oDao->aDataDict['entCustomerGroup']['groupAutoGrantedUsage']['values'][ $aEntry['groupAutoGrantedUsage'] ],
			'tableRowControls' => '
				<a href="?groupId=' . $aEntry['groupId'] . '&' . stripGetStr( array( 'deleteCustomerGroup', 'event', 'groupId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				<a href="' . $oRouter->getPath( 'adminOrderFieldToCustomerGroupEdit' ) . '?groupId=' . $aEntry['groupId'] . '" class="icon iconText iconRelation">' . _( 'Order fields' ). '</a>
				<a href="?event=deleteCustomerGroup&deleteCustomerGroup=' . $aEntry['groupId'] . '&' . stripGetStr( array( 'deleteCustomerGroup', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array('id' => 'sortCustomerGroup_' . $aEntry['groupId']) );
	}
}

if( empty($_GET['groupId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddGroup',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view customerGroupTableEdit">
		<h1>' . _( 'Customer groups' ) . '</h1>
		' . $sTitle . '		
		' . $sOutput . '
	</div>';

$oCustomer->oDao->setLang( $GLOBALS['langId'] );
	
//$oTemplate->addBottom( array(
//	'key' => 'linkTableSortable',
//	'content' => '
//	<script>
//		$(".dashboardLinkTableEdit table tbody").sortable({
//			update : function () {
//				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortCustomerGroup&sortCustomerGroup=1&" + $(this).sortable("serialize"));
//			}
//		});
//	</script>'
//) );