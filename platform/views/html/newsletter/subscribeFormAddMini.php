<?php

if( empty($_GET['userId']) ) return;

$aErr = array();

$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );
$oEditUser = new clUser( $_GET['userId'] );

// Find out the newsletterSubscriber based on the email address
$_POST['subscriberEmail'] = $oEditUser->readData( 'userEmail' );
$aSubscriberData = $oNewsletterSubscriber->readByEmail( $_POST['subscriberEmail'], 'subscriberId' );
if( !empty($aSubscriberData) ) {
	$_GET['subscriberId'] = current( current($aSubscriberData) );
}

if( !empty($_POST['frmSubscriberAdd']) && !empty($_POST['subscriberSubscribed']) ) {
	
	switch( $_POST['subscriberSubscribed'] ) {
		case 'yes':
			$_POST['subscriberStatus'] = 'active';
			$_POST['subscriberUnsubscribe'] = 'no';
			break;
		
		case 'no':
			$_POST['subscriberStatus'] = 'inactive';
			$_POST['subscriberUnsubscribe'] = 'yes';
			break;
	}
	
	// Update
	if( !empty($_GET['subscriberId']) && ctype_digit($_GET['subscriberId']) ) {		
		$oNewsletterSubscriber->update( $_GET['subscriberId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateSubscriber' );
	// Create
	} else {
		$_POST['subscriberCreated'] = date( 'Y-m-d H:i:s' );
		
		$iSubscriberId = $oNewsletterSubscriber->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createSubscriber' );
	}
}

// Data
if( !empty($_GET['subscriberId']) && ctype_digit($_GET['subscriberId']) ) {
	$aSubscriberData = current( $oNewsletterSubscriber->read( '*', $_GET['subscriberId'] ) );
	$aSubscriberGroupData = $oNewsletterSubscriber->readSubscriberToGroup( $_GET['subscriberId'] );
	
	if( ($aSubscriberData['subscriberStatus'] == 'active') && ($aSubscriberData['subscriberUnsubscribe'] == 'no') ) {
		$aSubscriberData['subscriberSubscribed'] = 'yes';
	} else {
		$aSubscriberData['subscriberSubscribed'] = 'no';
	}
	
	$aSubscriberData['subscriberGroup'] = array();
	foreach($aSubscriberGroupData as $aGroup) {
		$aSubscriberData['subscriberGroup'][] = $aGroup['groupId'];
	}

} else {
	$aSubscriberData = $_POST;
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
	'entSubscriber' => array(
		'subscriberSubscribed' => array(
			'type' => 'array',
			'values' => array(
				'' => _( 'Select' ),
				'yes' => _( 'Yes' ),
				'no' => _( 'No' )
			),
			'title' => _( 'Subscribed to newsletter' )
		),
		'frmSubscriberAdd' => array(
			'type' => 'hidden',
			'value' => true
		)
	)
);

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'attributes' => array('class' => 'marginal'),
	'data' => $aSubscriberData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );

echo '
	<div class="newsletterSubscriberFormAdd view">
		<div class="email">' . $_POST['subscriberEmail'] . '</div>
		' . $oOutputHtmlForm->render() . '
		<br class="clear" />
	</div>';