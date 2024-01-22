<?php

$aErr = array();

$oHelpTopic = clRegistry::get('clHelpTopic', PATH_MODULE . '/help/models' );
$oHelpCategory = clRegistry::get('clHelpCategory', PATH_MODULE . '/help/models' );

$sUrlTopics = $oRouter->getPath( 'adminHelpTopics' );
$sUrlCategories = $oRouter->getPath( 'adminHelpCategories' );

$oHelpTopic->oDao->setLang( $GLOBALS['langIdEdit'] );
$oHelpCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

// Read all categories
$aCategoryList = arrayToSingle( $oHelpCategory->read(), 'helpCategoryId', 'helpCategoryTitleTextId' );

/**
 * Post
 */
if( !empty($_POST['frmTopicAdd']) ) {
	if( !empty($_GET['topicId']) ) {
		// Update

		// Help Topic Category Id is an relationship and not an field in help topic table
		$aCategoryIds = $_POST['helpTopicCategoryId'];
		unset( $_POST['helpTopicCategoryId'] );

		$oHelpTopic->update( $_GET['topicId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateHelpTopic' );
		$iTopicId = $_GET['topicId'];

		if( empty($aErr) ) {
			// Categories
			$oHelpTopic->deleteTopicToCategory( $_GET['topicId'] );
			if( !empty($aCategoryIds) ) $oHelpTopic->createTopicToCategory( $_GET['topicId'], $aCategoryIds );

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

		// Help Topic Category Id is an relationship and not an field in help topic table
		$aCategoryIds = $_POST['helpTopicCategoryId'];
		unset( $_POST['helpTopicCategoryId'] );

		$iTopicId = $oHelpTopic->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createHelpTopic' );

		if( empty($aErr) ) {
			// Categories
			if( !empty($aCategoryIds) ) $oHelpTopic->createTopicToCategory( $iTopicId, $aCategoryIds );

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
		}
	}

    if( empty($aErr) && empty($_GET['topicId']) ) {
        $oRouter->redirect( $oRouter->sPath . '?topicId=' . $_GET['topicId'] );
    }
}

if( !empty($_GET['topicId']) ) {
	// Edit
	$aData = current( $oHelpTopic->readAll(null, $_GET['topicId']) );
	$aData['helpTopicCategoryId'] = arrayToSingle( $oHelpTopic->readTopicToCategory($_GET['topicId']), null, 'helpCategoryId' );
	// $aData['help']
	$sTitle = _( 'Edit topic' );

} else {
	// New
	$aData = $_POST;
	$sTitle = _( 'Create topic' );
}

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oHelpTopic->oDao->getDataDict(), array(
	'attributes'	=> array(
		'class'	=> 'newForm'
	),
	'data' => $aData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'helpTopicTitleTextId' => array(),
	'helpTopicDescriptionTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'attributes' => array(
			'class' => 'editor'
		)
	),
  'helpTopicCategoryId' => array(
		'type' => 'arraySet',
		'values' => $aCategoryList,
		'appearance' => 'full',
		'title' => _( 'Kategori' )
	),
	'helpTopicStatus' => array(),
	'frmTopicAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

$iCategoryId = !empty($_GET['categoryId']) ? $_GET['categoryId'] : (!empty($_POST['helpCategoryId']) ? $_POST['helpCategoryId'] : '');

echo '
	<div class="view help topicFormAdd">
		<h1>' . $sTitle . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $sUrlTopics . '?categoryId=' . $iCategoryId . '" class="icon iconText iconGoBack">' . _( 'Back to topics' ) . '</a>
			</div>
			<div class="tool">
				<a href="' . $sUrlCategories . '" class="icon iconText iconGoBack">' . _( 'Back to categories' ) . '</a>
			</div>
		</section>
		' . $oOutputHtmlForm->render() . '
	</div>';

$oHelpTopic->oDao->setLang( $GLOBALS['langId'] );
$oHelpCategory->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );
