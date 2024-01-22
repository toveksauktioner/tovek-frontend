<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlGridTable' );

$oHelpCategory = clRegistry::get('clHelpCategory', PATH_MODULE . '/help/models' );

$sUrlTopics = $oRouter->getPath( 'adminHelpTopics' );

$oHelpCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

// Sort
$oHelpCategory->oDao->aSorting = array(
	'helpCategorySort' => 'ASC',
	'helpCategoryId' => 'ASC'
);

/**
 * Post
 */
if( !empty($_POST['frmAddHelpCategory']) ) {
	if( !empty($_GET['categoryId']) ) {
		// Update
		$oHelpCategory->update( $_GET['categoryId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateHelpCategory' );
		$iCategoryId = $_GET['categoryId'];

		if( empty($aErr) ) {
			// Route
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );

				$sTitle = str_replace( [
					'&shy;'
				], [
					''
				], $_POST['helpCategoryTitleTextId'] );

				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestHelp') . '/' . $sTitle . '/' . $iCategoryId );
			}
			if( $oRouter->updateRouteToObject( $iCategoryId, $oHelpCategory->sModuleName, $_POST['routePath'], 'guestHelp' ) === false ) {
				// Found no route, create one instead
				if( $oRouter->createRouteToObject( $iCategoryId, $oHelpCategory->sModuleName, $_POST['routePath'], 'guestHelp' ) ) {
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
		$iCategoryId = $oHelpCategory->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createHelpCategory' );

		if( empty($aErr) ) {
			// Route
			if( empty($_POST['routePath']) ) {
				require_once( PATH_FUNCTION . '/fOutputHtml.php' );

				$sTitle = str_replace( [
					'&shy;'
				], [
					''
				], $_POST['helpCategoryTitleTextId'] );

				$_POST['routePath'] = strToUrl( $oRouter->getPath('guestHelp') . '/' . $sTitle . '/' . $iCategoryId );
			}
			if( $oRouter->createRouteToObject( $iCategoryId, $oHelpCategory->sModuleName, $_POST['routePath'], 'guestHelp' ) ) {
				// Success

			} else {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'dataError' => _( 'Problem with creating route' )
				) );
			}

			// Show new entry row
			$oTemplate->addBottom( array(
				'key' => 'jsShowFrmAddHelpCategory',
				'content' => '
				<script>
					$(document).ready( function() {
						$("tr#frmAddHelpCategory").show();
					} );
				</script>'
			) );
		}
	}
}

$aReadFields = array(
	'helpCategoryId',
	'helpCategoryIcon',
	'helpCategoryTitleTextId',
	'helpCategoryDescriptionTextId',
	'helpCategoryStatus',
	'helpCategoryPublishStart',
	'helpCategoryPublishEnd',
	'helpCategorySort',
	'helpCategoryCreated',
	'helpCategoryUpdated'
);

// All packages
$aAllData = valueToKey( 'helpCategoryId', $oHelpCategory->read( $aReadFields ) );

if( !empty($_GET['categoryId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['categoryId'] ];
	$sTitle = '';

} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmAddHelpCategory" class="toggleShow icon iconText iconAdd">' . _( 'Add' ) . '</a>';
}

// Datadict
$aDataDict = array(
	'helpCategoryIcon' => array(),
	'helpCategoryTitleTextId' => array(),
  'helpCategoryDescriptionTextId' => array(
		'type' => 'string',
		'appearance' => 'full'
	),
  'helpCategoryStatus' => array(),
  'helpCategoryPublishStart' => array(
		'attributes' => array(
			'class' => 'datetimepicker text'
		)
	),
  'helpCategoryPublishEnd' => array(
		'attributes' => array(
			'class' => 'datetimepicker text'
		)
	)
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oHelpCategory->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'newForm' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(
	'frmAddHelpCategory' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlGridTable( $oHelpCategory->oDao->getDataDict(), array(
	'rowGridStyle' => '3em 10em auto 4em 7em 7em 14em',
	'attributes' => array(
		'class' => 'headerZero'
	)
) );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'rowControls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
  'helpCategoryIcon' => $oOutputHtmlForm->renderFields( 'helpCategoryIcon' ),
  'helpCategoryTitleTextId' => $oOutputHtmlForm->renderFields( 'helpCategoryTitleTextId' ),
	'helpCategoryDescriptionTextId' => $oOutputHtmlForm->renderFields( 'helpCategoryDescriptionTextId' ),
	'helpCategoryStatus' => $oOutputHtmlForm->renderFields( 'helpCategoryStatus' ),
	'helpCategoryPublishStart' => $oOutputHtmlForm->renderFields( 'helpCategoryPublishStart' ),
	'helpCategoryPublishEnd' => $oOutputHtmlForm->renderFields( 'helpCategoryPublishEnd' ),
	'rowControls' => $oOutputHtmlForm->renderFields( 'frmAddHelpCategory' ) . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllData as $aEntry ) {
	if( !empty($_GET['categoryId']) && $aEntry['helpCategoryId'] == $_GET['categoryId'] ) {
		// Edit
		$aAddForm['rowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('categoryId', 'event', 'deleteHelpCategory') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );

	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'helpCategoryIcon' => '<i class="' . $aEntry['helpCategoryIcon'] . '"></i>',
			'helpCategoryTitleTextId' => $aEntry['helpCategoryTitleTextId'],
			'helpCategoryDescriptionTextId' => wordStr( $aEntry['helpCategoryDescriptionTextId'], 30, ' [..]' ),
			'helpCategoryStatus' => '<span class="' . $aEntry['helpCategoryStatus'] . '">' .$oHelpCategory->oDao->aDataDict['entHelpCategory']['helpCategoryStatus']['values'][ $aEntry['helpCategoryStatus'] ] . '</span>',
			'helpCategoryPublishStart' => $aEntry['helpCategoryPublishStart'],
      'helpCategoryPublishEnd' => $aEntry['helpCategoryPublishEnd'],
			'rowControls' => '
				<a href="' . $sUrlTopics . '?categoryId=' . $aEntry['helpCategoryId'] . '" class="button small narrow"><i class="fas fa-question"></i> ' . _( 'Frågor' ) . '</a>
				<a href="?categoryId=' . $aEntry['helpCategoryId'] . '&' . stripGetStr( array( 'deleteHelpCategory', 'event', 'categoryId' ) )  . '" class="button submit small narrow"><i class="fas fa-pencil-alt"></i></a>
				<a href="?event=deleteHelpCategory&deleteHelpCategory=' . $aEntry['helpCategoryId'] . '&' . stripGetStr( array( 'deleteHelpCategory', 'event') ) . '" class="button small narrow cancel linkConfirm" title="' . _( 'Kategorin och alla frågor tas bort. Är du säker?' ) . '"><i class="fas fa-trash-alt"></i></a>'
		), array('id' => 'sortHelpCategory_' . $aEntry['helpCategoryId']) );
	}
}

if( empty($_GET['categoryId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddHelpCategory',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view faq categoryTableForm">
		<h1>' . _( 'Hjälp-kategorier' ) . '</h1>
		<section class="tools">
			<div class="tool">
				' . $sTitle . '
			</div>
		</section>
		' . $sOutput . '
	</div>';

$oHelpCategory->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );

$oTemplate->addBottom( array(
	'key' => 'categoryTableSortable',
	'content' => '
	<script>
		$(".categoryTableForm table tbody").sortable({
			update : function () {
				$.get("' . $oRouter->sPath . '", "ajax=true&event=sortHelpCategory&sortHelpCategory=1&" + $(this).sortable("serialize"));
			}
		});
	</script>'
) );
