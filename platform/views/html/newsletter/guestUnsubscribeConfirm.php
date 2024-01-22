<?php

$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );
$oNotification = clRegistry::get( 'clNotificationHandler' );

if( !empty($_GET['subscriber']) ) {
	$aUrlData = explode('|', $_GET['subscriber']);
	// Set temporary access
	$oAcl = new clAcl();
	$oAcl->setAcl( array(
		'readNewsletterSubscriber' => 'allow',
		'writeNewsletterSubscriber' => 'allow'
	) );
	$oNewsletterSubscriber->setAcl( $oAcl );

	$aData = current( $oNewsletterSubscriber->readByEmail( $aUrlData[1], '*' ) );

	
	if( !empty($aData) ) {
		$sCheckSum = md5( $aData['subscriberEmail'] . $aData['subscriberUnsubscribe'] . $aData['subscriberCreated'] );
		
		if( $aUrlData[0] == $sCheckSum ) {
			$oNewsletterSubscriber->oDao->updateDataByPrimary( $aData['subscriberId'], array(
				'subscriberUnsubscribe' => 'yes'
			) );
			$oNotification->add( sprintf( _( 'We have successfully confirmed your unsubscription, for e-mail %s' ), $aData['subscriberEmail'] ) );
		}
		
	} else {
		$oNotification->addError( _( 'There was a problem with your unsubsciption' ) );
	}
	
	// Reset access to default
	$oNewsletterSubscriber->setAcl( $oUser->oAcl );
	
} else {
	$oRouter->redirect( '/' );
}