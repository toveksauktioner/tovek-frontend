<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oFaqQuestion = clRegistry::get('clFaqQuestion', PATH_MODULE . '/faq/models' );
$oFaqCategory = clRegistry::get('clFaqCategory', PATH_MODULE . '/faq/models' );

// Sort
$oFaqQuestion->oDao->aSorting = array(
	'questionSort' => 'ASC',
	'questionId' => 'ASC'
);

$sUrlQuestionAdd = $oRouter->getPath( 'adminFaqQuestionAdd' );
$sUrlCategories = $oRouter->getPath( 'adminFaqCategories' );

$oFaqQuestion->oDao->setLang( $GLOBALS['langIdEdit'] );
$oFaqCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

// Read all categories
$aCategoryList = arrayToSingle( $oFaqCategory->read(), 'categoryId', 'categoryTitleTextId' );

/**
 * Post
 */
if( !empty($_POST['frmAddFaqQuestion']) ) {	
	if( !empty($_GET['questionId']) ) {
		// Update
		$_POST['questionUpdated'] = date( 'Y-m-d H:i:s' );
		$oFaqQuestion->update( $_GET['questionId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateFaqQuestion' );
		$iQuestionId = $_GET['questionId'];
		
		if( empty($aErr) ) {
			// Route
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );
				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestFaq') . '/' . $aCategoryList[ $_POST['questionCategoryId'] ] . '/' . $_POST['questionTitleTextId'] . '/' . $iQuestionId );
			}
			if( $oRouter->updateRouteToObject( $iQuestionId, $oFaqQuestion->sModuleName, $_POST['routePath'], 'guestFaq' ) === false ) {
				// Found no route, create one instead
				if( $oRouter->createRouteToObject( $iQuestionId, $oFaqQuestion->sModuleName, $_POST['routePath'], 'guestFaq' ) ) {
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
		$_POST['questionCreated'] = date( 'Y-m-d H:i:s' );
		$iQuestionId = $oFaqQuestion->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createFaqQuestion' );
		
		if( empty($aErr) ) {
			// Route
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );
				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestFaq') . '/' . $aCategoryList[ $_POST['questionCategoryId'] ] . '/' . $_POST['questionTitleTextId'] . '/' . $iQuestionId );
			}
			if( $oRouter->createRouteToObject( $iQuestionId, $oFaqQuestion->sModuleName, $_POST['routePath'], 'guestFaq' ) ) {
				// Success
				
			} else {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataError' => _( 'Problem with creating route' )
				) );
			}
			
			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddFaqQuestion',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddFaqQuestion").show();
					} );				
				</script>'
			) );
		}
	}
}

// Data
$aReadFields = array(
	'questionId',
    'questionCategoryId',
    'questionStatus',
    'questionSort',
    'questionCreated',
    'questionUpdated',
    'questionTitleTextId',
    'questionAnswerTextId'
);
if( !empty($_GET['categoryId']) ) {
	$aCategoryData = current( $oFaqCategory->read( 'categoryTitleTextId', $_GET['categoryId'] ) );
	
	$aAllData = $oFaqQuestion->readAllByCategory( $_GET['categoryId'], $aReadFields );
	
	/**
	 * Check category route
	 */
	//$aRouteData = current( $oFaqCategory->read( 'routePath', $_GET['categoryId'] ) );	
	//if( empty($aRouteData) ) {		
	//	require_once( PATH_FUNCTION . '/fOutputHtml.php' );
	//	$sRoutePath = strToUrl( $oRouter->getPath('guestFaq') . '/' . $aCategoryData['categoryTitleTextId'] . '/' . $_GET['categoryId'] );
	//	
	//	if( $oRouter->createRouteToObject( $_GET['categoryId'], $oFaqCategory->sModuleName, $sRoutePath, 'guestFaq' ) ) {
	//		$oNotification = clRegistry::get( 'clNotificationHandler' );
	//		$oNotification->set( array(
	//			'dataSaved' => _( 'An route did not exist and was created' )
	//		) );	
	//	}
	//}
	
	/**
	 * Check question routes
	 */
	//foreach( $aAllData as $aQuestion ) {
	//	$aRouteData = current( $oFaqQuestion->read( 'routePath', $aQuestion['questionId'] ) );
	//	if( empty($aRouteData) ) {		
	//		require_once( PATH_FUNCTION . '/fOutputHtml.php' );
	//		$sCategoryRoutePath = strToUrl( $oRouter->getPath('guestFaq') . '/' . $aCategoryData['categoryTitleTextId'] . '/' . $_GET['categoryId'] );
	//		$sRoutePath = strToUrl( $sCategoryRoutePath . '/' . $aQuestion['questionTitleTextId'] . '/' . $aQuestion['questionId'] );
	//		
	//		if( $oRouter->createRouteToObject( $aQuestion['questionId'], $oFaqQuestion->sModuleName, $sRoutePath, 'guestFaq' ) ) {
	//			$oNotification = clRegistry::get( 'clNotificationHandler' );
	//			$oNotification->set( array(
	//				'dataSaved' => _( 'An route did not exist and was created' )
	//			) );	
	//		}
	//	}
	//}
	
} else {
	$aAllData = $oFaqQuestion->readAll( $aReadFields );
}

if( !empty($_GET['questionId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['questionId'] ];
	$sTitle = '';
	
} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddFaqQuestion" class="toggleShow icon iconText iconAdd">' . _( 'Add' ) . '</a>';
	
	if( !empty($_GET['categoryId']) ) {
		$aData['questionCategoryId'] = $_GET['categoryId'];
	}
}

// Datadict
$aDataDict = array(
	'questionTitleTextId' => array(),
    'questionAnswerTextId' => array(
		'type' => 'string',
		'appearance' => 'full'
	),
	'questionCategoryId' => array(
		'type' => 'array',
		'values' => $aCategoryList
	),
    'questionStatus' => array()
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oFaqQuestion->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(	
	'frmAddFaqQuestion' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oFaqQuestion->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'rowControls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
    'questionTitleTextId' => $oOutputHtmlForm->renderFields( 'questionTitleTextId' ),
	'questionAnswerTextId' => $oOutputHtmlForm->renderFields( 'questionAnswerTextId' ),
	'questionCategoryId' => $oOutputHtmlForm->renderFields( 'questionCategoryId' ),
	'questionStatus' => $oOutputHtmlForm->renderFields( 'questionStatus' ), 
	'rowControls' => $oOutputHtmlForm->renderFields( 'frmAddFaqQuestion' ) . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllData as $aEntry ) {
	if( !empty($_GET['questionId']) && $aEntry['questionId'] == $_GET['questionId'] ) {
		// Edit
		$aAddForm['rowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('questionId', 'event', 'deleteFaqQuestion') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'questionTitleTextId' => $aEntry['questionTitleTextId'],
			'questionAnswerTextId' => wordStr( $aEntry['questionAnswerTextId'], 30, ' [..]' ),
			'questionCategoryId' => $aCategoryList[ $aEntry['questionCategoryId'] ],
			'questionStatus' => '<span class="' . $aEntry['questionStatus'] . '">' . $oFaqQuestion->oDao->aDataDict['entFaqQuestion']['questionStatus']['values'][ $aEntry['questionStatus'] ] . '</span>',
			'rowControls' => '
				<a href="?questionId=' . $aEntry['questionId'] . '&' . stripGetStr( array( 'deleteFaqQuestion', 'event', 'questionId' ) )  . '" class="icon iconText iconEdit">' . _( 'Fast edit' ) . '</a>
				&nbsp;|&nbsp;
				<a href="' . $sUrlQuestionAdd . '?questionId=' . $aEntry['questionId'] . '&' . stripGetStr( array( 'deleteFaqQuestion', 'event', 'questionId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				&nbsp;|&nbsp;
				<a href="?event=deleteFaqQuestion&deleteFaqQuestion=' . $aEntry['questionId'] . '&' . stripGetStr( array( 'deleteFaqQuestion', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array('id' => 'sortFaqQuestion_' . $aEntry['questionId']) );
	}
}

if( empty($_GET['questionId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddFaqQuestion',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view faq questionTableForm">
		<h1>' . _( 'FAQ Questions' ) . '</h1>
		<section class="tools">
			<div class="tool">
				' . $sTitle . '
			</div>
			<div class="tool">
				<a href="' . $sUrlCategories . '" class="icon iconText iconGoBack">' . _( 'Back to categories' ) . '</a>
			</div>
		</section>
		' . $sOutput . '
	</div>';

$oFaqQuestion->oDao->setLang( $GLOBALS['langId'] );
$oFaqCategory->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );
	
$oTemplate->addBottom( array(
	'key' => 'questionTableSortable',
	'content' => '
	<script>
		$(".questionTableForm table tbody").sortable( {
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&categoryId=' . $_GET['categoryId'] . '&event=sortFaqQuestion&sortFaqQuestion=1&" + $(this).sortable("serialize"));
			}
		} );
	</script>'
) );
$oTemplate->addStyle( array(
	'key' => 'questionTableStyle',
	'content' => '
	.dataTable tbody tr td textarea { width: 90%; max-height: 6em; }'
) );