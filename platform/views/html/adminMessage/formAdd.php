<?php

$aErr = array();

$oAdminMessage = clRegistry::get( 'clAdminMessage', PATH_MODULE . '/adminMessage/models' );
$oAdminMessage->oDao->setLang( $GLOBALS['langIdEdit'] );

/**
 * Post
 */
if( !empty($_POST['frmAdminMessageAdd']) ) {	
	/**
	 * Update
	 */
	if( !empty($_GET['messageId']) && ctype_digit($_GET['messageId']) ) {
		$_POST['messageUpdated'] = date( 'Y-m-d H:i:s' );
		$oAdminMessage->update( $_GET['messageId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateAdminMessage' );
		if( empty($aErr) ) {
			$iMessageId = $_GET['messageId'];
		}

	/**
	 * Create
	 */
	} else {
		$_POST['messageCreated'] = date( 'Y-m-d H:i:s' );
		$iMessageId = $oAdminMessage->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createAdminMessage' );
		
	}

	if( empty($aErr) && empty($_GET['messageId']) ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->setSessionNotifications( array(
			'dataSaved' => _( 'The data has been saved' )
		) );
		$oRouter->redirect( $oRouter->sPath . '?messageId=' . $iMessageId );
	}
}

/**
 * Edit
 */
if( !empty($_GET['messageId']) && ctype_digit($_GET['messageId']) ) {
	$aMessageData = current( $oAdminMessage->readAll('*', $_GET['messageId']) );
	$sTitle = _( 'Edit message' );
	
/**
 * New
 */
} else {	
	$aMessageData = $_POST;
	$sTitle = _( 'Create new message' );
}

/**
 * Form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oAdminMessage->oDao->getDataDict(), array(
	'attributes'	=> array(
		'class'	=> 'marginal'
	),
	'data' => $aMessageData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict(  array(
	'messageLabel' => array(),
	'messageTitleTextId' => array(),
	'messageStatus' => array(),
	'messageContentTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'attributes' => array(
			'class' => 'editor'
		)
	),
	'frmAdminMessageAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

echo '
	<div class="view adminMessage formAdd">
		<h1>' . $sTitle . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $oRouter->getPath( 'superAdminMessages' ) . '" class="icon iconText iconPrevious">' . _( 'Back' ) . '</a>
			</div>
		</section>
		' . $oOutputHtmlForm->render() . '
	</div>';

$oAdminMessage->oDao->setLang( $GLOBALS['langId'] );

$oTemplate->addScript( array(
	'key' => 'jsTinyMce',
	'src' => '/modules/tinymce/tiny_mce.js'
) );
$oTemplate->addScript( array(
	'key' => 'jsTinyMceConfig',
	'src' => '/modules/tinymce/config/basic.js.php'
) );







