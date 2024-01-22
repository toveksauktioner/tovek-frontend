<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oDashboardLink = clRegistry::get( 'clDashboardLink', PATH_MODULE . '/dashboard/models' );

// Sort
$oDashboardLink->oDao->aSorting = array(
	'linkSort' => 'ASC',
	'linkId' => 'ASC'
);

if( !empty($_GET['sync']) && $_GET['sync'] == 'true' ) {	
	$oDashboardLink->sync();
	$oRouter->redirect( $oRouter->sPath );
}

if( !empty($_POST['frmAddDashboardLink']) ) {	
	if( !empty($_GET['linkId']) ) {
		// Update
		$_POST['linkUpdated'] = date( 'Y-m-d H:i:s' );
		$oDashboardLink->update( $_GET['linkId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateDashboardLink' );
		$iLinkId = $_GET['linkId'];
		
	} else {
		// Create		
		$_POST['linkCreated'] = date( 'Y-m-d H:i:s' );
		$iLinkId = $oDashboardLink->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createDashboardLink' );
		
		if( empty($aErr) ) {
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddDashboardLink',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddDashboardLink").show();
					} );				
				</script>'
			) );
		}
	}
}

// All links
$aAllData = valueToKey( 'linkId', $oDashboardLink->read() );

if( !empty($_GET['linkId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['linkId'] ];
	$sTitle = '';
} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddDashboardLink" class="toggleShow icon iconText iconAdd">' . _( 'Add link' ) . '</a>';
}

// Datadict
$aDataDict = array(
	'linkTextSwedish' => array(),
	'linkTextEnglish' => array(),
	'linkUrl' => array(),
	'linkDescription' => array(),
	'linkType' => array()
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oDashboardLink->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(	
	'frmAddDashboardLink' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oDashboardLink->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'linkCreated' => array(),
	'linkUpdated' => array(),
	'tableRowControls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
	'linkTextSwedish' => $oOutputHtmlForm->renderFields( 'linkTextSwedish' ) . $oOutputHtmlForm->renderFields( 'linkTextSwedish' ),
	'linkTextEnglish' => $oOutputHtmlForm->renderFields( 'linkTextEnglish' ) . $oOutputHtmlForm->renderFields( 'linkTextEnglish' ),
	'linkUrl' => $oOutputHtmlForm->renderFields( 'linkUrl' ) . $oOutputHtmlForm->renderFields( 'linkUrl' ),
	'linkDescription' => $oOutputHtmlForm->renderFields( 'linkDescription' ) . $oOutputHtmlForm->renderFields( 'linkDescription' ),
	'linkType' => $oOutputHtmlForm->renderFields( 'linkType' ) . $oOutputHtmlForm->renderFields( 'linkType' ),
	'linkCreated' => $oOutputHtmlForm->renderFields( 'frmAddDashboardLink' ),
	'linkUpdated' => '',
	'tableRowControls' => $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllData as $aEntry ) {
	if( !empty($_GET['linkId']) && $aEntry['linkId'] == $_GET['linkId'] ) {
		// Edit
		$aAddForm['tableRowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('linkId', 'event', 'deleteDashboardLink') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'linkTextSwedish' => $aEntry['linkTextSwedish'],			
			'linkTextEnglish' => $aEntry['linkTextEnglish'],
			'linkUrl' => $aEntry['linkUrl'],			
			'linkDescription' => $aEntry['linkDescription'],
			'linkType' => $oDashboardLink->oDao->aDataDict['entDashboardLink']['linkType']['values'][ $aEntry['linkType'] ],
			'linkCreated' => $aEntry['linkCreated'],
			'linkUpdated' => $aEntry['linkUpdated'],
			'tableRowControls' => '
				<a href="?linkId=' . $aEntry['linkId'] . '&' . stripGetStr( array( 'deleteDashboardLink', 'event', 'linkId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				<a href="?event=deleteDashboardLink&deleteDashboardLink=' . $aEntry['linkId'] . '&' . stripGetStr( array( 'deleteDashboardLink', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array('id' => 'sortDashboardLink_' . $aEntry['linkId']) );
	}
}

if( empty($_GET['linkId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddDashboardLink',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view dashboardLinkTableEdit">
		<h1>' . _( 'External links' ) . '</h1>
		<p>' . _( 'Links that are listed in Aroma CMS\'s dashboard' ) . '</p><br />
		' . $sTitle . '		
		' . $sOutput . '
		<p>
			<a href="?sync=true" class="icon iconText iconDbImport">' . _( 'Sync data from external source' ) . '</a><br />
			<em style="font-size: 0.9em; opacity: 0.5;">(' . sprintf( _( 'External source is: %s' ), DASHBOARD_LINK_IMPORT_SOURCE_URL ) . ')</em>
		</p>
	</div>';

$oTemplate->addBottom( array(
	'key' => 'linkTableSortable',
	'content' => '
	<script>
		$(".dashboardLinkTableEdit table tbody").sortable({
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortDashboardLink&sortDashboardLink=1&" + $(this).sortable("serialize"));
			}
		});
	</script>'
) );