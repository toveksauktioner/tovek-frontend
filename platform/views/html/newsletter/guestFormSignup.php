<?php

$aErr = array();

if( !empty($_POST['frmAddSubscriber']) ) {
	$oNewsletterSubscriber = clRegistry::get( 'clNewsletterSubscriber', PATH_MODULE . '/newsletter/models' );	
	
	$_POST += array(
		'subscriberCreated' => date( 'Y-m-d H:i:s' ),
		'subscriberStatus' => 'active'
	);
	$iSubscriberId = $oNewsletterSubscriber->create( $_POST );
	$aErr = clErrorHandler::getValidationError( 'createSubscriber' );
	
	if( empty($aErr) ) {
		echo '
			<script>
				alert("' . _('Thank you for your subscription!') . '");
			</script>';
			unset($_POST['subscriberEmail']);
	}
}

// Datadict
$aFormDataDict = array(
	'newsletterSubscribe' => array(
		'subscriberEmail' => array(
			'type' => 'string',
			'title' => _( 'E-mail' ),
			'required' => true
		)
	)
);

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'errors' => $aErr,
	'labelSuffix' => '',
	'labelRequiredSuffix' => '',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'GÅ MED' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'subscriberEmail' => array(
		'title' => 'Din mail',
		'extraValidation' => array(
			'Email'
		)
	),
	'frmAddSubscriber' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Output
echo '
	<div class="view newsletterSubscriptionGuestForm">
		' . $oOutputHtmlForm->render() . '
	</div>
	<script src="/js/jquery.defaultValue.js"></script>
	<script>
		$(".newsletterSubscriptionGuestForm form .field label").each( function() {
			if( $( ".newsletterSubscriptionGuestForm form  #" + $(this).attr("for") ).val() == "" ) {				
				$( ".newsletterSubscriptionGuestForm form  #" + $(this).attr("for") ).defaultvalue( $(this).text() );
			}
		} );
		$(".newsletterSubscriptionGuestForm form").submit( function() {
			$(".newsletterSubscriptionGuestForm form .field label").each( function() {
				if( $(this).text() == $( ".newsletterSubscriptionGuestForm form #" + $(this).attr("for") ).val() ) {
					$( ".newsletterSubscriptionGuestForm form  #" + $(this).attr("for") ).val("");
				}
			} );
		} );
	</script>';
