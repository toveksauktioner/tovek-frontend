<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oFaqCategory = clRegistry::get('clFaqCategory', PATH_MODULE . '/faq/models' );

$sUrlQuestions = $oRouter->getPath( 'adminFaqQuestions' );

$oFaqCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

// Sort
$oFaqCategory->oDao->aSorting = array(
	'categorySort' => 'ASC',
	'categoryId' => 'ASC'
);

/**
 * Post
 */
if( !empty($_POST['frmAddFaqCategory']) ) {	
	if( !empty($_GET['categoryId']) ) {
		// Update
		$_POST['categoryUpdated'] = date( 'Y-m-d H:i:s' );
		$oFaqCategory->update( $_GET['categoryId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateFaqCategory' );
		$iCategoryId = $_GET['categoryId'];
		
		if( empty($aErr) ) {
			// Route
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );
				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestFaq') . '/' . $_POST['categoryTitleTextId'] . '/' . $iCategoryId );
			}
			if( $oRouter->updateRouteToObject( $iCategoryId, $oFaqCategory->sModuleName, $_POST['routePath'], 'guestFaq' ) === false ) {
				// Found no route, create one instead
				if( $oRouter->createRouteToObject( $iCategoryId, $oFaqCategory->sModuleName, $_POST['routePath'], 'guestFaq' ) ) {
					// Success
					
				} else {
					$oNotification = clRegistry::get( 'clNotificationHandler' );
					$oNotification->set( array(
						'dataError' => _( 'Problem with updating route' )
					) );
				}
			}
		}
		
	} else {
		// Create		
		$_POST['categoryCreated'] = date( 'Y-m-d H:i:s' );
		$iCategoryId = $oFaqCategory->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createFaqCategory' );
		
		if( empty($aErr) ) {
			// Route
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );
				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestFaq') . '/' . $_POST['categoryTitleTextId'] . '/' . $iCategoryId );
			}
			if( $oRouter->createRouteToObject( $iCategoryId, $oFaqCategory->sModuleName, $_POST['routePath'], 'guestFaq' ) ) {
				// Success
				
			} else {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataError' => _( 'Problem with creating route' )
				) );
			}
			
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddFaqCategory',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddFaqCategory").show();
					} );				
				</script>'
			) );
		}
	}
}

$aReadFields = array(
	'categoryId',
	'categoryTitleTextId',
	'categoryDescriptionTextId',
	'categoryStatus',
	'categoryPublishStart',
	'categoryPublishEnd',
	'categorySort',
	'categoryCreated',
	'categoryUpdated'
);

// All packages
$aAllData = valueToKey( 'categoryId', $oFaqCategory->read( $aReadFields ) );

if( !empty($_GET['categoryId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['categoryId'] ];
	$sTitle = '';
	
} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddFaqCategory" class="toggleShow icon iconText iconAdd">' . _( 'Add' ) . '</a>';
}

// Datadict
$aDataDict = array(
	'categoryTitleTextId' => array(),
    'categoryDescriptionTextId' => array(
		'type' => 'string',
		'appearance' => 'full'
	),
    'categoryStatus' => array(),
    'categoryPublishStart' => array(
		'attributes' => array(
			'class' => 'datetimepicker text'
		)
	),
    'categoryPublishEnd' => array(
		'attributes' => array(
			'class' => 'datetimepicker text'
		)
	)
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oFaqCategory->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(	
	'frmAddFaqCategory' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oFaqCategory->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'rowControls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
    'categoryTitleTextId' => $oOutputHtmlForm->renderFields( 'categoryTitleTextId' ),
	'categoryDescriptionTextId' => $oOutputHtmlForm->renderFields( 'categoryDescriptionTextId' ),
	'categoryStatus' => $oOutputHtmlForm->renderFields( 'categoryStatus' ),
	'categoryPublishStart' => $oOutputHtmlForm->renderFields( 'categoryPublishStart' ),
	'categoryPublishEnd' => $oOutputHtmlForm->renderFields( 'categoryPublishEnd' ),   
	'rowControls' => $oOutputHtmlForm->renderFields( 'frmAddFaqCategory' ) . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllData as $aEntry ) {
	if( !empty($_GET['categoryId']) && $aEntry['categoryId'] == $_GET['categoryId'] ) {
		// Edit
		$aAddForm['rowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('categoryId', 'event', 'deleteFaqCategory') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'categoryTitleTextId' => $aEntry['categoryTitleTextId'],
			'categoryDescriptionTextId' => wordStr( $aEntry['categoryDescriptionTextId'], 30, ' [..]' ),
			'categoryStatus' => '<span class="' . $aEntry['categoryStatus'] . '">' .$oFaqCategory->oDao->aDataDict['entFaqCategory']['categoryStatus']['values'][ $aEntry['categoryStatus'] ] . '</span>',
			'categoryPublishStart' => $aEntry['categoryPublishStart'],
            'categoryPublishEnd' => $aEntry['categoryPublishEnd'],
			'rowControls' => '
				<a href="?categoryId=' . $aEntry['categoryId'] . '&' . stripGetStr( array( 'deleteFaqCategory', 'event', 'categoryId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				&nbsp;|&nbsp;
				<a href="' . $sUrlQuestions . '?categoryId=' . $aEntry['categoryId'] . '" class="icon iconText iconQuestion">' . _( 'Questions' ) . '</a>
				&nbsp;|&nbsp;
				<a href="?event=deleteFaqCategory&deleteFaqCategory=' . $aEntry['categoryId'] . '&' . stripGetStr( array( 'deleteFaqCategory', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array('id' => 'sortFaqCategory_' . $aEntry['categoryId']) );
	}
}

if( empty($_GET['categoryId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddFaqCategory',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view faq categoryTableForm">
		<h1>' . _( 'FAQ Categories' ) . '</h1>
		<section class="tools">
			<div class="tool">
				' . $sTitle . '
			</div>
		</section>
		' . $sOutput . '
	</div>';

$oFaqCategory->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );
	
$oTemplate->addBottom( array(
	'key' => 'categoryTableSortable',
	'content' => '
	<script>
		$(".categoryTableForm table tbody").sortable({
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortFaqCategory&sortFaqCategory=1&" + $(this).sortable("serialize"));
			}
		});
	</script>'
) );
$oTemplate->addStyle( array(
	'key' => 'categoryTableStyle',
	'content' => '
	.dataTable tbody tr td textarea { width: 90%; max-height: 6em; }'
) );