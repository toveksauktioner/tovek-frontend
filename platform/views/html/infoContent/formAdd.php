<?php

$oTemplate = clRegistry::get( 'clTemplateHtml' );
$oTemplate->addScript( array(
	'key' => 'jsTinyMce',
	'src' => '/modules/tinymce/tiny_mce.js'
) );
$oTemplate->addScript( array(
	'key' => 'jsTinyMceConfig',
	'src' => '/modules/tinymce/config/basic.js.php'
) );

$aErr = array();
$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );

$oInfoContent->oDao->setLang( $GLOBALS['langIdEdit'] );

if( !empty($_POST['frmInfoContentAdd']) ) {
	// Update
	if( !empty($_GET['contentId']) && ctype_digit($_GET['contentId']) ) {
		$oInfoContent->update( $_GET['contentId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateInfoContent' );
	// Create
	} else {
		$iInfoContentId = $oInfoContent->create($_POST);
		$aErr = clErrorHandler::getValidationError( 'createInfoContent' );
		if( empty($aErr) ) {
			$iViewId = current( current($oInfoContent->read('contentViewId', $iInfoContentId)) );
			// ACL
			$oAcl = clRegistry::get( 'clAcl' );
			$oAcl->aroId = array();
			$aAroIds = array(
				'guest',
				'user',
				'admin'
			);
			if( !empty($iViewId) ) $oAcl->createByAco( $iViewId, 'view', $aAroIds, 'userGroup' );
			
			$oRouter->redirect( $oRouter->sPath . '?contentId=' . $iInfoContentId );
		}
	}
}

// Edit
if( !empty($_GET['contentId']) && ctype_digit($_GET['contentId']) ) {
	$aInfoContentData = current( $oInfoContent->read('*', $_GET['contentId']) );
	$sTitle = _( 'Edit information content' );
} else {
	$aInfoContentData = $_POST;
	$sTitle = _( 'Add information content' );
}

// Add form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oInfoContent->oDao->getDataDict(), array(
	'action' => '',
	'data' => $aInfoContentData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'contentKey' => array(),
	'contentTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'attributes' => array(
			'class' => 'editor'
		)
	),
	'frmInfoContentAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );
$sInfoContentForm = $oOutputHtmlForm->render();

echo '
	<div class="view infoContentFormAdd">
		<h1>' . $sTitle . '</h1>
		' . $sInfoContentForm . '
	</div>';

$oInfoContent->oDao->setLang( $GLOBALS['langId'] );
