<?php

$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );

// Limit to only active subscribers with unsubscribed set to no
$oNewsletterSubscriber->oDao->setCriterias( array(
	'subscriberActive' => array(
		'type' => '=',
		'value' => 'active',
		'fields' => 'subscriberStatus'
	),
	'subscriberSubscribed' => array(
		'type' => '=',
		'value' => 'no',
		'fields' => 'subscriberUnsubscribe'
	)
) );

$aSubscriberEmails = arrayToSingle( $oNewsletterSubscriber->read('subscriberEmail'), null, 'subscriberEmail' );

echo '
	<div class="newsletterSubscriberTable view">
		<h1>' . _( 'Newsletter subscribers' ) . '</h1>
		<textarea id="newsletterSubscribers" style="width: 98%; height: 400px; background: #fff; padding: 1%;">' . implode( '; ', $aSubscriberEmails ) . '</textarea>
	</div>';	
	
$oTemplate->addBottom( array(
	'key' => 'jsForm',
	'content' => '
	<script>
		$( function() {			
			$("#newsletterSubscribers").focus( function() {
				var $this = $(this);
				$this.select();
			
				// Work around Chromes little problem
				$this.mouseup( function() {
					// Prevent further mouseup intervention
					$this.unbind("mouseup");
					return false;
				} );
			} );
		} );
	</script>'
) );

