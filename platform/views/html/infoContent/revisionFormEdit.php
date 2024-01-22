<?php

if( empty($_GET['revisionId']) ) $oRouter->redirect( '/' );

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

if( !empty($_POST['frmInfoContentRevisionUpdate']) ) {
	// Update
	if( !empty($_GET['revisionId']) && ctype_digit($_GET['revisionId']) ) {
		$oInfoContent->updateRevision( $_GET['revisionId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateInfoContent' );
	}
}

// Read data
$aInfoContentData = current( $oInfoContent->readRevisionByPrimary('revisionContent', $_GET['revisionId']) );
if( empty($aInfoContentData) ) $oRouter->redirect( '/' );

// Add form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oInfoContent->oDao->getDataDict('entInfoContentRevision'), array(
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
	'revisionContent' => array(
		'type' => 'string',
		'appearance' => 'full',
		'attributes' => array(
			'class' => 'editor'
		)
	),
	'frmInfoContentRevisionUpdate' => array(
		'type' => 'hidden',
		'value' => true
	)
) );
$sInfoContentForm = $oOutputHtmlForm->render();

echo '
	<div class="infoContentAdd">
		<h3>' . _( 'Edit revision' ) . '</h3>
		' . $sInfoContentForm . '
	</div>';

$oInfoContent->oDao->setLang( $GLOBALS['langId'] );
