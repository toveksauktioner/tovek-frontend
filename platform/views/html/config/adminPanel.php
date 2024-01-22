<?php

$aErr = array();

$oConfig = clRegistry::get( 'clConfig' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

if( isset($_POST['btnTestMail']) || isset($_POST['btnOrderTestMail']) ) {
	if( isset($_POST['btnTestMail']) ) {
		$aMailParams = array(
			'mailService' => $_POST['SITE_MAIL_SERVICE'],
			'smtpHost' => $_POST['smtpHost'],
			'smtpUsername' => $_POST['smtpUsername'],
			'smtpPassword' => $_POST['smtpPassword'],
			'debug' => true
		);
		$aMail = array(
			'from' => $_POST['SITE_MAIL_FROM'],
			'to' => $_POST['sendTestTo']
		);

	} else {
		$aMailParams = array(
			'mailService' => $_POST['SITE_MAIL_SERVICE'],
			'smtpHost' => $_POST['orderSmtpHost'],
			'smtpUsername' => $_POST['orderSmtpUsername'],
			'smtpPassword' => $_POST['orderSmtpPassword'],
			'debug' => true
		);
		$aMail = array(
			'from' => $_POST['ORDER_EMAIL_FROM'],
			'to' => $_POST['orderSendTestTo']
		);
	}

	$oMailTemplate = new clTemplateHtml();
	$oMailTemplate->setTemplate( 'mail.php' );
	$oMailTemplate->setTitle( _( 'Test of mail - ' ) . SITE_DOMAIN );
	$oMailTemplate->setContent( _( 'This is a test mail from - ' ) . SITE_DOMAIN );
	$sMailHtmlOutput = $oMailTemplate->render();

	$oMailHandler = clRegistry::get( 'clMailHandler', null, $aMailParams );
	$oMailHandler->prepare( $aMail + array(
		'title' => _( 'Test of mail - ' ) . SITE_DOMAIN,
		'content' => $sMailHtmlOutput,
		'template' => 'empty.php'
	) );

	if( $oMailHandler->send() ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'mailResult' => _( 'The mail has been sent' )
		) );
	} else {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'mailResult' => _( 'The mail could not be sent' )
		) );
		// Do not save unsuccessful settings
		unset( $_POST['frmConfig'] );
	}
}

if( !empty($_POST['frmConfig']) ) {
	$aData = array();
	foreach( $_POST as $key => $entry ) {
		if( $key != 'frmConfig' ) {
			$aData[] = array(
				'configKey' => $key,
				'configValue' => $entry
			);
		}
	}

	unset( $oConfig->oDao->aDataDict['entConfig']['configValue']['required'] );

	foreach( $aData as $entry ) {
		$aDaoData = $oConfig->oDao->readDataByPrimary( $entry['configKey'] );

		if( !empty($aDaoData) ) {
			// Update
			$oConfig->update( $entry['configKey'], $entry );
			$aErr = clErrorHandler::getValidationError( 'updateConfig' );
		} else {
			if( in_array($entry['configKey'], array('sendTestTo', 'orderSendTestTo')) ) continue;

			// Create
			$entry['configGroupKey'] = 'Constant';
			$oConfig->create( $entry );
			$aErr = clErrorHandler::getValidationError( 'createConfig' );
		}
	}

	if( !empty($aErr) ) {
		$oNotification->set( array(
			'dataError' => _( 'Data was not saved' )
		) );
	}
	else {
		$oNotification->set( array(
			'dataSaved' => _( 'Data was saved' )
		) );
	}
}

$aDataDict = array(
	'configPanel' => array(
		// Generic
		'SITE_TITLE' => array(
			'type' => 'string',
			'title' => _( 'Site title' )
		),
		'SITE_SLOGAN' => array(
			'type' => 'string',
			'title' => _( 'Site slogan' )
		),
		'SITE_DOMAIN' => array(
			'type' => 'string',
			'title' => _( 'Site domain' )
		),
		'SITE_STATUS' => array(
			'type' => 'array',
			'title' => _( 'Site status' ),
			'values' => array(
				'online' => 'Online',
				'offline' => 'Offline',
				'maintenance' => _( 'Maintenance' ),
				'construction' => _( 'Under construction' )
			)
		),
		'SITE_COOKIE' => array(
			'type' => 'array',
			'title' => _( 'Cookies' ),
			'values' => array(
				'false' => _( 'Not in use' ),
				'true' => _( 'In use' )
			)
		),
		'googleAnalyticsCode' => array(
			'type' => 'string',
			'title' => _( 'Google analytics code' )
		),
		'SITE_ADDITIONAL_SCRIPT' => array(
			'type' => 'array',
			'title' => _( 'Use of additional scripts' ),
			'values' => array(
				'false' => _( 'Not in use' ),
				'true' => _( 'In use' )
			)
		),
		// Site mail
		'SITE_MAIL_SERVICE' => array(
			'type' => 'array',
			'title' => _( 'Mail service' ),
			'values' => array(
				'default' => _( 'Default' ),
				'smtp' => _( 'SMTP' ),
				'phpMailer' => _( 'PHP Mailer' )
			)
		),
		'SITE_MAIL_FROM' => array(
			'type' => 'string',
			'title' => _( 'Site mail from' ),
			'extraValidation' => array(
				'email'
			)
		),
		'SITE_MAIL_TO' => array(
			'type' => 'string',
			'title' => _( 'Site mail to' ),
			'extraValidation' => array(
				'email'
			)
		),
		'smtpHost' => array(
			'type' => 'string',
			'title' => _( 'SMTP Host' )
		),
		'smtpPort' => array(
			'type' => 'string',
			'title' => _( 'SMTP Port' )
		),
		'smtpSecure' => array(
			'type' => 'array',
			'title' => _( 'SMTP Encryption' ),
			'values' => array(
				'' => _( 'None' ),
				'SSL' => 'SSL',
				'TLS' => 'TLS',
				'starttls' => 'STARTTLS'
			)
		),
		'smtpUsername' => array(
			'type' => 'string',
			'title' => _( 'SMTP Username' )
		),
		'smtpPassword' => array(
			'type' => 'string',
			'title' => _( 'SMTP Password' )
		),
		'sendTestTo' => array(
			'type' => 'string',
			'title' => _( 'Send test mail to' ),
			'fieldAttributes' => array(
				'class' => 'testMail'
			),
			'suffixContent' => $oOutputHtmlForm->createButton( 'submit', _( 'Test now' ), array('name' => 'btnTestMail') )
		)
	)
);
$aFormGroups = array(
	'generic' => array(
		'title' => _( 'Generic information' ),
		'fields' => array(
			'SITE_TITLE',
			'SITE_SLOGAN',
			'SITE_DOMAIN',
			'SITE_STATUS',
			'SITE_COOKIE',
			'googleAnalyticsCode',
			'SITE_ADDITIONAL_SCRIPT'
		)
	),
	'mail' => array(
		'title' => _( 'Mail' ),
		'fields' => array(
			'SITE_MAIL_SERVICE',
			'SITE_MAIL_FROM',
			'SITE_MAIL_TO',
			'smtpHost',
			'smtpPort',
			'smtpSecure',
			'smtpUsername',
			'smtpPassword',
			'sendTestTo'
		)
	)
);

if( is_dir(PATH_MODULE . '/freight') ) {
	$aDataDict['configPanel'] += array(
		'generalFreightFee' => array(
			'type' => 'string',
			'title' => _( 'General freight fee' )
		),
		'freeFreightLimit' => array(
			'type' => 'string',
			'title' => _( 'Free freight limit' )
		)
	);
	$aFormGroups['eCommerce'] = array(
		'title' => _( 'E-commerce' ),
		'fields' => array(
			'generalFreightFee',
			'freeFreightLimit'
		)
	);
}

if( is_dir(PATH_MODULE . '/order') ) {
	$aDataDict['configPanel'] += array(
		'ORDER_EMAIL_FROM' => array(
			'type' => 'string',
			'title' => _( 'Order mail from' ),
			'extraValidation' => array(
				'email'
			)
		),
		'ORDER_EMAIL_TO' => array(
			'type' => 'string',
			'title' => _( 'Order mail to' ),
			'extraValidation' => array(
				'email'
			)
		),
		'orderSmtpHost' => array(
			'type' => 'string',
			'title' => _( 'SMTP Host' )
		),
		'orderSmtpPort' => array(
			'type' => 'string',
			'title' => _( 'SMTP Port' )
		),
		'orderSmtpSecure' => array(
			'type' => 'array',
			'title' => _( 'SMTP Encryption' ),
			'values' => array(
				'' => _( 'None' ),
				'SSL' => 'SSL',
				'TLS' => 'TLS'
			)
		),
		'orderSmtpUsername' => array(
			'type' => 'string',
			'title' => _( 'SMTP Username' )
		),
		'orderSmtpPassword' => array(
			'type' => 'string',
			'title' => _( 'SMTP Password' )
		),
		'orderSendTestTo' => array(
			'type' => 'string',
			'title' => _( 'Send test mail to' ),
			'fieldAttributes' => array(
				'class' => 'testMail'
			),
			'suffixContent' => $oOutputHtmlForm->createButton( 'submit', _( 'Test now' ), array('name' => 'btnOrderTestMail') )
		),
		'ORDER_ADJUST_PRODUCT_QUANTITY' => array(
			'type' => 'array',
			'title' => _( 'Product quantity is adjusted' ),
			'values' => array(
				'checkout' => _( 'At checkout' ),
				'processed' => _( 'At processed' ),
				'completed' => _( 'At completed' ),
				'no' => _( 'At no point' )
			)
		)
	);
	$aFormGroups['orderMail'] = array(
		'title' => _( 'Order mail' ),
		'fields' => array(
			'ORDER_EMAIL_FROM',
			'ORDER_EMAIL_TO',
			'orderSmtpHost',
			'orderSmtpPort',
			'orderSmtpSecure',
			'orderSmtpUsername',
			'orderSmtpPassword',
			'orderSendTestTo'
		)
	);
	if( empty($aFormGroups['eCommerce']) ) {
		$aFormGroups['eCommerce'] = array(
			'title' => _( 'E-commerce' ),
			'fields' => array(
				'ORDER_ADJUST_PRODUCT_QUANTITY'
			)
		);
	} else {
		$aFormGroups['eCommerce']['fields'][] = 'ORDER_ADJUST_PRODUCT_QUANTITY';
	}
}

if( is_dir(PATH_MODULE . '/product') ) {
	$aDataDict['configPanel'] += array(
		'PRODUCT_ADD_OUT_OF_STOCK' => array(
			'type' => 'array',
			'title' => _( 'Allow sales when out of stock' ),
			'values' => array(
				'false' => _( 'No' ),
				'true' => _( 'Yes' )
			)
		),
		//'PRODUCT_ADD_OUT_OF_STOCK_MESSAGE' => array(
		//	'type' => 'string',
		//	'title' => _( 'Out of stock message' )
		//)
	);
	if( empty($aFormGroups['eCommerce']) ) {
		$aFormGroups['eCommerce'] = array(
			'title' => _( 'E-commerce' ),
			'fields' => array(
				'PRODUCT_ADD_OUT_OF_STOCK',
				//'PRODUCT_ADD_OUT_OF_STOCK_MESSAGE'
			)
		);
	} else {
		$aFormGroups['eCommerce']['fields'][] = 'PRODUCT_ADD_OUT_OF_STOCK';
		//$aFormGroups['eCommerce']['fields'][] = 'PRODUCT_ADD_OUT_OF_STOCK_MESSAGE';
	}
}

// Data
$aFormData = array();
$aData = $oConfig->read();
foreach( $aData as $entry ) {
	$aFormData[$entry['configKey']] = $entry['configValue'];
}

$oOutputHtmlForm->init( $aDataDict, array(
	'attributes' => array('class' => 'marginal'),
	'data' => $aFormData,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	)
) );
$aFormDataDict = $aDataDict['configPanel'] + array(
	'frmConfig' => array(
		'type' => 'hidden',
		'value' => true
	)
);
$oOutputHtmlForm->setFormDataDict( $aFormDataDict );

$oOutputHtmlForm->setGroups( $aFormGroups );

echo '
	<div class="adminConfigPanel view">
		<h1>' . _( 'Settings' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
	</div>';
$oTemplate->addBottom( array(
	'key' => 'enterKeyHack',
	'content' => '
	<script>
	$(document).keypress(function(e) {
		if(e.which == 13) {
			e.preventDefault();
			$(".adminConfigPanel.view form.marginal").submit();
		}
	});
	</script>'
) );
