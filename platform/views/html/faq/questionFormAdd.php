<?php

$aErr = array();

$oFaqQuestion = clRegistry::get('clFaqQuestion', PATH_MODULE . '/faq/models' );
$oFaqCategory = clRegistry::get('clFaqCategory', PATH_MODULE . '/faq/models' );

$sUrlQuestions = $oRouter->getPath( 'adminFaqQuestions' );
$sUrlCategories = $oRouter->getPath( 'adminFaqCategories' );

$oFaqQuestion->oDao->setLang( $GLOBALS['langIdEdit'] );
$oFaqCategory->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

// Read all categories
$aCategoryList = arrayToSingle( $oFaqCategory->read(), 'categoryId', 'categoryTitleTextId' );

/**
 * Post
 */
if( !empty($_POST['frmQuestionAdd']) ) {	
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
		}
	}
    
    if( empty($aErr) && empty($_GET['questionId']) ) {
        $oRouter->redirect( $oRouter->sPath . '?questionId=' . $_GET['questionId'] );
    }
}

if( !empty($_GET['questionId']) ) {
	// Edit
	$aData = current( $oFaqQuestion->readAll( '*', $_GET['questionId'] ) );
	$sTitle = _( 'Edit question' );
	
} else {
	// New
	$aData = $_POST;
	$sTitle = _( 'Create question' );
}

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oFaqQuestion->oDao->getDataDict(), array(
	'attributes'	=> array(
		'class'	=> 'marginal'
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
	'questionTitleTextId' => array(),
	'questionAnswerTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'attributes' => array(
			'class' => 'editor'
		)
	),
    'questionCategoryId' => array(
		'type' => 'array',
		'values' => $aCategoryList
	),
	'questionStatus' => array(),
	'frmQuestionAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

$iCategoryId = !empty($_GET['categoryId']) ? $_GET['categoryId'] : (!empty($_POST['categoryId']) ? $_POST['categoryId'] : '');

echo '
	<div class="view faq questionFormAdd">
		<h1>' . $sTitle . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $sUrlQuestions . '?categoryId=' . $iCategoryId . '" class="icon iconText iconGoBack">' . _( 'Back to questions' ) . '</a>
			</div>
			<div class="tool">
				<a href="' . $sUrlCategories . '" class="icon iconText iconGoBack">' . _( 'Back to categories' ) . '</a>
			</div>
		</section>
		' . $oOutputHtmlForm->render() . '
	</div>';

$oFaqQuestion->oDao->setLang( $GLOBALS['langId'] );
$oFaqCategory->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );

$oTemplate->addScript( array(
	'key' => 'jsTinyMce',
	'src' => '/modules/tinymce/tiny_mce.js'
) );
$oTemplate->addScript( array(
	'key' => 'jsTinyMceConfig',
	'src' => '/modules/tinymce/config/basic.js.php'
) );