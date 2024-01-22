<?php

require_once PATH_FUNCTION . '/fOutputHtml.php';

$aErr = array();
$sOutput = '';

$oProductTemplate = clRegistry::get( 'clProductTemplate', PATH_MODULE . '/product/models' );
$oRouter = clRegistry::get( 'clRouter' );
$oNotification = clRegistry::get( 'clNotificationHandler' );

/**
 * Post translation
 */
if( !empty($_POST['frmTranslateData']) ) {
	$aData = array();	
	foreach( $_POST['templateTitleTextId'] as $iTemplateId => $sValue ) {
		$aData[$iTemplateId]['templateTitleTextId'] = $sValue;
	}
	foreach( $_POST['templateShortDescriptionTextId'] as $iTemplateId => $sValue ) {
		$aData[$iTemplateId]['templateShortDescriptionTextId'] = $sValue;
	}
	foreach( $_POST['templateDescriptionTextId'] as $iTemplateId => $sValue ) {
		$aData[$iTemplateId]['templateDescriptionTextId'] = $sValue;
	}
	
	// Set language
	$oProductTemplate->oDao->setLang( $_POST['writeLanguage'] );
	$oRouter->oDao->setLang( $_POST['writeLanguage'] );
	
	foreach( $aData as $iTemplateId => $aEntry ) {
		$oProductTemplate->update( $iTemplateId, $aEntry );
		$aErr = clErrorHandler::getValidationError( 'updateProductTemplate' );
		if( empty($aErr) ) {
			$sRoutePath = strToUrl( $oRouter->getPath( 'guestProductShow' ) . '/' . $aEntry['templateTitleTextId'] . '/' . $iTemplateId );
			$oProductTemplate->updateRoute( $sRoutePath, $iTemplateId );
			$aErr = clErrorHandler::getValidationError( 'updateRoute' );
		}
	}
	
	if( empty($aErr) ) {
		unset( clRegistry::$aEntries['clNotificationHandler'], $oNotification );
		
		$oNotification->aNotifications = array();
		$oNotification->set( array(
			'dataSaved' => _( 'Translation successful' )
		) );
		
	} else {
		unset( clRegistry::$aEntries['clNotificationHandler'], $oNotification );
		
		$oNotification->aErrors = array();
		$oNotification->set( array(
			'dataError' => _( 'Translation not successful' )
		) );
		
	}
	
	// Reset language
	$oProductTemplate->oDao->setLang( $GLOBALS['langId'] );
	$oRouter->oDao->setLang( $_POST['writeLanguage'] );
	
	if( isset($_POST['continueTranslate']) ) {
		$_POST = array(
			'readLanguage' => $_POST['readLanguage'],
			'writeLanguage' => $_POST['writeLanguage'],
			'amount' => $_POST['amount'],
			'frmTranslateSettings' => '1'
		);
	} else {
		$_POST = array();
	}
}

/**
 * Translation form
 */
if( !empty($_POST['frmTranslateSettings']) ) {		
	/**
	 * Read five untranslated products
	 */
	$aData = $oProductTemplate->oDao->oDb->query( "
		SELECT
			table1.templateId,
			text1.textContent AS templateTitleTextId,
			text2.textContent AS templateDescriptionTextId,
			text3.textContent AS templateShortDescriptionTextId
		
		FROM entProductTemplate AS table1
		
		LEFT JOIN entProductText AS text1 ON table1.templateTitleTextId = text1.textId
		AND text1.textLangId = '" . $_POST['readLanguage'] . "'
		
		LEFT JOIN entProductText AS text2 ON table1.templateDescriptionTextId = text2.textId
		AND text2.textLangId = '" . $_POST['readLanguage'] . "'
		
		LEFT JOIN entProductText AS text3 ON table1.templateShortDescriptionTextId = text3.textId
		AND text3.textLangId = '" . $_POST['readLanguage'] . "'
		
		WHERE table1.templateId NOT IN(
			SELECT table2.templateId
			FROM entProductTemplate AS table2
			LEFT JOIN entProductText AS text4 ON table2.templateTitleTextId = text4.textId
			AND text4.textLangId = '" . $_POST['writeLanguage'] . "'
			WHERE text4.textLangId IS NOT NULL
		)
		
		ORDER BY table1.templateId ASC
		LIMIT " . $_POST['amount'] . "
	" );
	
	if( !empty($aData) ) {
		$aFormDataDict['entProductTemplateTranslate'] = array();
		$aFormData = array();
		
		foreach( $aData as $aEntry ) {
			$aFormDataDict['entProductTemplateTranslate'] += array(
				'templateId[' . $aEntry['templateId'] . ']' => array(
					'type' => 'string',
					'title' => _( 'ID' ),
					'attributes' => array(
						'readOnly' => 'readOnly',
						'class' => 'readOnly'
					)
				),
				'templateTitleTextId[' . $aEntry['templateId'] . ']' => array(
					'type' => 'string',
					'title' => _( 'Title' ),
					'suffixContent' => '&nbsp;&nbsp;(' . $aEntry['templateTitleTextId'] . ')',
					'required' => true
				),
				'templateShortDescriptionTextId[' . $aEntry['templateId'] . ']' => array(
					'type' => 'string',
					'appearance' => 'full',
					'title' => _( 'Short description' ),
					'suffixContent' => '&nbsp;&nbsp;(' . $aEntry['templateShortDescriptionTextId'] . ')'
				),
				'templateDescriptionTextId[' . $aEntry['templateId'] . ']' => array(
					'type' => 'string',
					'appearance' => 'full',
					'title' => _( 'Description' ),
					'suffixContent' => '&nbsp;&nbsp;(' . $aEntry['templateDescriptionTextId'] . ')'
				)
			);
			
			$aFormData += array(
				'templateId[' . $aEntry['templateId'] . ']' => $aEntry['templateId'],
				'templateTitleTextId[' . $aEntry['templateId'] . ']' => $aEntry['templateTitleTextId'],
				'templateShortDescriptionTextId[' . $aEntry['templateId'] . ']' => $aEntry['templateShortDescriptionTextId'],
				'templateDescriptionTextId[' . $aEntry['templateId'] . ']' => $aEntry['templateDescriptionTextId']					
			);
		}
		
		$aFormDataDict['entProductTemplateTranslate'] += array(
			'readLanguage' => array(
				'type' => 'hidden',
				'value' => $_POST['readLanguage']
			),
			'writeLanguage' => array(
				'type' => 'hidden',
				'value' => $_POST['writeLanguage']
			),
			'amount' => array(
				'type' => 'hidden',
				'value' => $_POST['amount']
			),
			'frmTranslateData' => array(
				'type' => 'hidden',
				'value' => true
			)
		);
		
		// Form
		$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
		$oOutputHtmlForm->init( $aFormDataDict, array(
			'data' => $aFormData,
			'errors' => $aErr,
			'labelSuffix' => ':',
			'method' => 'post',
			'buttons' => array(
				'submitTranslate' => array(
					'content' => _( 'Translate' ),
					'attributes' => array(
						'name' => 'submitTranslate',
						'type' => 'submit'
					)
				),
				'continueTranslate' => array(
					'content' => _( 'Translate & continue' ),
					'attributes' => array(
						'name' => 'continueTranslate',
						'type' => 'submit'
					)
				)
			)
		) );
		$oOutputHtmlForm->setFormDataDict( $aFormDataDict['entProductTemplateTranslate'] );
		
		$sFrom = $oOutputHtmlForm->renderErrors();
		foreach( $aData as $aEntry ) {
			$sFrom .= '
				<fieldset class="fieldGroup">
					' . $oOutputHtmlForm->renderFields( 'templateId[' . $aEntry['templateId'] . ']' ) . '
					' . $oOutputHtmlForm->renderFields( 'templateTitleTextId[' . $aEntry['templateId'] . ']' ) . '
					' . $oOutputHtmlForm->renderFields( 'templateShortDescriptionTextId[' . $aEntry['templateId'] . ']' ) . '
					' . $oOutputHtmlForm->renderFields( 'templateDescriptionTextId[' . $aEntry['templateId'] . ']' ) . '
				</fieldset>';
		}
		$sFrom .= $oOutputHtmlForm->renderButtons();
		
		$sOutput = $oOutputHtmlForm->createForm( 'post', '', $sFrom, array( 'class' => 'marginal' ) );		
		
	} else {
		$sOutput = _( 'Did not find any more products that translate' );
		
	}

/**
 * Language selection for translation form
 */
} else {
	// Languages
	$oLocales = clRegistry::get( 'clLocale' );
	$aLocales = arrayToSingle( $oLocales->read(), 'localeId', 'localeTitle' );
	// Reversed locales
	$aRevLocales = array_combine( array_reverse(array_keys( $aLocales )), array_reverse( $aLocales ) );
	
	$aFormDataDict = array(
		'entProductTemplateTranslate' => array(
			'readLanguage' => array(
				'type' => 'array',
				'title' => _( 'From language' ),
				'values' => $aLocales,
				'defaultValue' => $_SESSION['langId']
			),
			'writeLanguage' => array(
				'type' => 'array',
				'title' => _( 'To language' ),
				'values' => $aRevLocales,
				'defaultValue' => $_SESSION['langIdEdit']
			),
			'amount' => array(
				'type' => 'array',
				'title' => _( 'Amount per page' ),
				'values' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
					'9' => '9',
					'10' => '10',
					'11' => '11',
					'12' => '12',
					'13' => '13',
					'14' => '14',
					'15' => '15'
				)
			),
			'frmTranslateSettings' => array(
				'type' => 'hidden',
				'value' => true
			)
		)
	);

	// Form
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $aFormDataDict, array(
		'attributes' => array( 'class' => 'marginal' ),
		'errors' => $aErr,
		'labelSuffix' => ':',
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Continue' )
		)
	) );
	
	$oOutputHtmlForm->setFormDataDict( $aFormDataDict['entProductTemplateTranslate'] );
	
	$oOutputHtmlForm->setGroups( array(
		'langSelection' => array(
			'title' => _( 'Preference' ),
			'fields' => array(
				'readLanguage',
				'writeLanguage',
				'amount'
			)
		)
	) );
	
	$sOutput = $oOutputHtmlForm->render();

}

echo '
	<div class="view translateForm">
		<h1>' . _( 'Fast translate products' ) . '</h1>
		' . $sOutput . '
	</div>';

$oTemplate->addStyle( array(
	'key' => 'stylesheet',
	'content' => '
		input.readOnly { border: none; background: none; }
		span.suffixContent { color: #999; }
		textarea { background: #fff; }
	'
) );