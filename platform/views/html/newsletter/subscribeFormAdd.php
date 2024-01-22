<?php

$aErr = array();

$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );

if( !empty($_POST['frmSubscriberAdd']) ) {
	// Update
	if( !empty($_GET['subscriberId']) && ctype_digit($_GET['subscriberId']) ) {		
		$oNewsletterSubscriber->update( $_GET['subscriberId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateSubscriber' );
	// Create
	} else {
		$_POST['subscriberCreated'] = date( 'Y-m-d H:i:s' );
		
		$iSubscriberId = $oNewsletterSubscriber->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createSubscriber' );
		if( empty($aErr) ) {
			$oRouter->redirect( $oRouter->sPath . '?subscriberId=' . $iSubscriberId );
		}
	}
}

// Data
if( !empty($_GET['subscriberId']) && ctype_digit($_GET['subscriberId']) ) {
	$aSubscriberData = current( $oNewsletterSubscriber->read( '*', $_GET['subscriberId'] ) );
	$aSubscriberGroupData = $oNewsletterSubscriber->readSubscriberToGroup( $_GET['subscriberId'] );
	
	$aSubscriberData['subscriberGroup'] = array();
	foreach($aSubscriberGroupData as $aGroup) {
		$aSubscriberData['subscriberGroup'][] = $aGroup['groupId'];
	}
	
	$sTitle = _( 'Edit subscriber' );
} else {
	$aSubscriberData = $_POST;
	$sTitle = _( 'Add subscriber' );
}

// Group list
$aGroupList = array();
$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );
$aGroupData = $oNewsletterSubscriber->readGroup( array(
	'groupId',
	'groupNameTextId'
) );
foreach( $aGroupData as $entry ) {
	$aGroupList[$entry['groupId']] = $entry['groupNameTextId'];
}

// Form DataDict
$aFormDataDict = array(
	'subscriberName' => array(),
	'subscriberEmail' => array(),
	'subscriberStatus' => array(),
	'subscriberUnsubscribe' => array(),
	'frmSubscriberAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);
if( !empty($aGroupList) ) {
	$aFormDataDict['subscriberGroup'] = array(
		'type' => 'arraySet',
		'appearance' => 'full',
		'values' => $aGroupList,
		'defaultValue' => 'none',
		'title' => _( 'Group' )
	);
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oNewsletterSubscriber->oDao->getDataDict(), array(
	'attributes' => array('class' => 'marginal'),
	'data' => $aSubscriberData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

echo '
	<div class="newsletterSubscriberFormAdd view">
		<h1>' . $sTitle . '</h1>
		' . $oOutputHtmlForm->render() . '
	</div>';