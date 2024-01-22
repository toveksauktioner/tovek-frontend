<?php

$oContact = clRegistry::get( 'clContact', PATH_MODULE . '/contact/models' );
$oContact->oDao->setLang( $GLOBALS['langIdEdit'] );
$_GET['contactId'] = '1'; // We might want more contact forms in the future. Force 1 for now
$aErr = array();

$aContactData = current( $oContact->read('*', $_GET['contactId']) );
if( !empty($_POST['frmContactAdd']) ) {
	// Update
	if( !empty($_GET['contactId']) && ctype_digit($_GET['contactId']) ) {
		$oContact->update( $_GET['contactId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateContact' );
		if( empty($aErr) ) {
			$aContactData = $_POST;
		}
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oContact->oDao->getDataDict(), array(
	'attributes'	=> array(
		'class'	=> 'marginal'
	),
	'data' => $aContactData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );

$aContact = array(
	'contactButtonTextId' => array(
		'title' => _( 'Submit button text' ),
		'required' => true,
	),
	'contactSubmitMessageTextId' => array(
		'title' => _( 'Submit message text' ),
	),
	'frmContactAdd' => array(
		'type' => 'hidden',
		'value' => 'true',
	)
);
$oOutputHtmlForm->setFormDataDict( $aContact );

echo '
	<div class="view contact formEdit">
		<h1>' . _( 'Contact' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
	</div>';
