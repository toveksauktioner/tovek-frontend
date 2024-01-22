<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oHelpTopic = clRegistry::get('clHelpTopic', PATH_MODULE . '/help/models' );
$oHelpCategory = clRegistry::get('clHelpCategory', PATH_MODULE . '/help/models' );

// Sort
$oHelpTopic->oDao->aSorting = array(
	'helpTopicId' => 'ASC'
);

$sUrlTopicAdd = $oRouter->getPath( 'adminHelpTopicAdd' );
$sUrlCategories = $oRouter->getPath( 'adminHelpCategories' );

$oHelpTopic->oDao->setLang( $GLOBALS['langIdEdit'] );
$oHelpCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

// Read all categories
$aCategoryList = arrayToSingle( $oHelpCategory->read(), 'helpCategoryId', 'helpCategoryTitleTextId' );

/**
 * Post
 */
if( !empty($_POST['frmAddHelpTopic']) ) {
	if( !empty($_GET['topicId']) ) {
		// Update
		$_POST['helpTopicUpdated'] = date( 'Y-m-d H:i:s' );
		$oHelpTopic->update( $_GET['topicId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateHelpTopic' );
		$iTopicId = $_GET['topicId'];

		if( empty($aErr) ) {
			// Route
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );
				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestHelp') . '/' . _( 'fråga' ) . '/' . $_POST['helpTopicTitleTextId'] . '/' . $iTopicId );
			}
			if( $oRouter->updateRouteToObject( $iTopicId, $oHelpTopic->sModuleName, $_POST['routePath'], 'guestHelp' ) === false ) {
				// Found no route, create one instead
				if( $oRouter->createRouteToObject( $iTopicId, $oHelpTopic->sModuleName, $_POST['routePath'], 'guestHelp' ) ) {
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
		$_POST['helpTopicCreated'] = date( 'Y-m-d H:i:s' );
		$iTopicId = $oHelpTopic->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createHelpTopic' );

		if( empty($aErr) ) {
			// Route
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );
				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestHelp') . '/' . _( 'fråga' ) . '/' . $_POST['helpTopicTitleTextId'] . '/' . $iTopicId );
			}
			if( $oRouter->createRouteToObject( $iTopicId, $oHelpTopic->sModuleName, $_POST['routePath'], 'guestHelp' ) ) {
				// Success

			} else {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataError' => _( 'Problem with creating route' )
				) );
			}

			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddHelpTopic',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddHelpTopic").show();
					} );
				</script>'
			) );
		}
	}
}

// Data
$aReadFields = array(
	'entHelpTopic.helpTopicId',
  'helpTopicStatus',
  'helpTopicCreated',
  'helpTopicUpdated',
  'helpTopicTitleTextId',
  'helpTopicDescriptionTextId'
);
if( !empty($_GET['categoryId']) ) {
	$aCategoryData = current( $oHelpCategory->read( 'helpCategoryTitleTextId', $_GET['categoryId'] ) );

	$aAllData = $oHelpTopic->readAllByCategory( $_GET['categoryId'], $aReadFields );

	/**
	 * Check category route
	 */
	//$aRouteData = current( $oHelpCategory->read( 'routePath', $_GET['categoryId'] ) );
	//if( empty($aRouteData) ) {
	//	require_once( PATH_FUNCTION . '/fOutputHtml.php' );
	//	$sRoutePath = strToUrl( $oRouter->getPath('guestHelp') . '/' . $aCategoryData['categoryTitleTextId'] . '/' . $_GET['categoryId'] );
	//
	//	if( $oRouter->createRouteToObject( $_GET['categoryId'], $oHelpCategory->sModuleName, $sRoutePath, 'guestHelp' ) ) {
	//		$oNotification = clRegistry::get( 'clNotificationHandler' );
	//		$oNotification->set( array(
	//			'dataSaved' => _( 'An route did not exist and was created' )
	//		) );
	//	}
	//}

	/**
	 * Check topic routes
	 */
	//foreach( $aAllData as $aTopic ) {
	//	$aRouteData = current( $oHelpTopic->read( 'routePath', $aTopic['helpTopicId'] ) );
	//	if( empty($aRouteData) ) {
	//		require_once( PATH_FUNCTION . '/fOutputHtml.php' );
	//		$sCategoryRoutePath = strToUrl( $oRouter->getPath('guestHelp') . '/' . $aCategoryData['categoryTitleTextId'] . '/' . $_GET['categoryId'] );
	//		$sRoutePath = strToUrl( $sCategoryRoutePath . '/' . $aTopic['helpTopicTitleTextId'] . '/' . $aTopic['helpTopicId'] );
	//
	//		if( $oRouter->createRouteToObject( $aTopic['helpTopicId'], $oHelpTopic->sModuleName, $sRoutePath, 'guestHelp' ) ) {
	//			$oNotification = clRegistry::get( 'clNotificationHandler' );
	//			$oNotification->set( array(
	//				'dataSaved' => _( 'An route did not exist and was created' )
	//			) );
	//		}
	//	}
	//}

} else {
	$aAllData = $oHelpTopic->readAll( $aReadFields );
}

if( !empty($_GET['topicId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['topicId'] ];
	$sTitle = '';

} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddHelpTopic" class="toggleShow icon iconText iconAdd">' . _( 'Add' ) . '</a>';
}

// Datadict
$aDataDict = array(
	'helpTopicTitleTextId' => array(),
  'helpTopicDescriptionTextId' => array(
		'type' => 'string',
		'appearance' => 'full'
	),
  'helpTopicStatus' => array()
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oHelpTopic->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(
	'frmAddHelpTopic' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oHelpTopic->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'rowControls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
  'helpTopicTitleTextId' => $oOutputHtmlForm->renderFields( 'helpTopicTitleTextId' ),
	'helpTopicDescriptionTextId' => $oOutputHtmlForm->renderFields( 'helpTopicDescriptionTextId' ),
	'helpTopicStatus' => $oOutputHtmlForm->renderFields( 'helpTopicStatus' ),
	'rowControls' => $oOutputHtmlForm->renderFields( 'frmAddHelpTopic' ) . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllData as $aEntry ) {
	if( !empty($_GET['topicId']) && $aEntry['helpTopicId'] == $_GET['topicId'] ) {
		// Edit
		$aAddForm['rowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('topicId', 'event', 'deleteHelpTopic') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );

	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'helpTopicTitleTextId' => $aEntry['helpTopicTitleTextId'],
			'helpTopicDescriptionTextId' => wordStr( strip_tags($aEntry['helpTopicDescriptionTextId']), 30, ' [..]' ),
			'helpTopicStatus' => '<span class="' . $aEntry['helpTopicStatus'] . '">' . $oHelpTopic->oDao->aDataDict['entHelpTopic']['helpTopicStatus']['values'][ $aEntry['helpTopicStatus'] ] . '</span>',
			'rowControls' => '
				<a href="?topicId=' . $aEntry['helpTopicId'] . '&' . stripGetStr( array( 'deleteHelpTopic', 'event', 'topicId' ) )  . '" class="icon iconText iconEdit">' . _( 'Fast edit' ) . '</a>
				&nbsp;|&nbsp;
				<a href="' . $sUrlTopicAdd . '?topicId=' . $aEntry['helpTopicId'] . '&' . stripGetStr( array( 'deleteHelpTopic', 'event', 'topicId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				&nbsp;|&nbsp;
				<a href="?event=deleteHelpTopic&deleteHelpTopic=' . $aEntry['helpTopicId'] . '&' . stripGetStr( array( 'deleteHelpTopic', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		), array('id' => 'sortHelpTopic_' . $aEntry['helpTopicId']) );
	}
}

if( empty($_GET['topicId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddHelpTopic',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view help topicTableForm">
		<h1>' . _( 'Hjälp - fråga' ) . '</h1>
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

$oHelpTopic->oDao->setLang( $GLOBALS['langId'] );
$oHelpCategory->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );

$oTemplate->addBottom( array(
	'key' => 'topicTableSortable',
	'content' => '
	<script>
		$(".topicTableForm table tbody").sortable( {
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&categoryId=' . $_GET['categoryId'] . '&event=sortHelpTopic&sortHelpTopic=1&" + $(this).sortable("serialize"));
			}
		} );
	</script>'
) );
$oTemplate->addStyle( array(
	'key' => 'topicTableStyle',
	'content' => '
	.dataTable tbody tr td textarea { width: 90%; max-height: 6em; }'
) );
