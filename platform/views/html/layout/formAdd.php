<?php

$aErr = array();
$sLayoutForm = '';
$sLayoutFileForm = '';

$oLayout = clRegistry::get( 'clLayoutHtml' );
$oLayout->setAcl( $oUser->oAcl );
$oLayout->oDao->setLang( $GLOBALS['langIdEdit'] );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

$aLayoutFiles = scandir( PATH_LAYOUT );
if( !empty($aLayoutFiles) ) {
	unset( $aLayoutFiles[0], $aLayoutFiles[1] );
	$aLayoutFiles = array_combine( $aLayoutFiles, $aLayoutFiles );
}

$aTemplateFiles = scandir( PATH_TEMPLATE );
if( !empty($aTemplateFiles) ) {
	unset( $aTemplateFiles[0], $aTemplateFiles[1] );
	$aTemplateFiles = array_combine( $aTemplateFiles, $aTemplateFiles );
}

$aLayoutFileFormDataDict = array(
	'layoutFile' => array(
		'type' => 'array',
		'values' => $aLayoutFiles
	)
);

$aLayoutFormDataDict = array(
	'layoutKey' => array()
);

if( empty($_GET['layoutKey']) ) {
	$aLayoutFormDataDict += $aLayoutFileFormDataDict;
} else {
	$aLayoutFormDataDict += array(
		'layoutFile' => array(
			'type' => 'hidden',
			'value' => null
		)
	);

	// For the acl view (currently not in use)
	//$_GET['aclType'] = 'layout';
	//$_GET['acoKey'] = $_GET['layoutKey'];
}

$aLayoutFormDataDict += array(
	'layoutTemplateFile' => array(
		'type' => 'array',
		'values' => $aTemplateFiles
	),
	'layoutTitleTextId' => array(),
	'layoutKeywordsTextId' => array(),
	'layoutDescriptionTextId' => array(),
	'layoutCanonicalUrlTextId' => array(),
	'layoutSuffixContent' => array(
		'appearance' => 'full'
	),
	'layoutBodyClass' => array(),
	'layoutProtected' => array(),
	'layoutDynamicChildrenRoute' => array(),
	'frmLayoutAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);

if( !empty($_POST['frmLayoutAdd']) ) {
	$sButton = isset($_POST['btnSubmitNext']) ? 'btnSubmitNext' : 'btnSubmit';
	$_POST = array_intersect_key( $_POST, $aLayoutFormDataDict );
	
	// Update
	if( !empty($_GET['layoutKey']) ) {
		$oLayout->update( $_GET['layoutKey'], $_POST );
		$aErr['layoutAdd'] = clErrorHandler::getValidationError( 'updateLayout' );
		if( empty($aErr['layoutAdd']) ) $oRouter->redirect( $oRouter->getPath('superLayouts') );
	// Create
	} else {
		$oLayout->create($_POST);
		$aErr['layoutAdd'] = clErrorHandler::getValidationError( 'createLayout' );
		if( empty($aErr['layoutAdd']) ) {
			if( $sButton == 'btnSubmitNext' ) {
				// Keep post data without redirect
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->aNotifications = array(
					'dataSaved' => _( 'The data has been saved' ) . '. ' . _( 'The POST-data has been saved as a copy' )
				);
			} else {
				$oRouter->redirect( $oRouter->sPath . '?layoutKey=' . $_POST['layoutKey'] );
			}			
		}
	}

}

if( !empty($_GET['canonicalRouteId']) ) {
	/**
	 * Save given route as canonical url
	 */
	$_POST['layoutCanonicalUrlTextId'] = current(current( $oRouter->read( 'routePath', $_GET['canonicalRouteId'] ) ));
	$oLayout->update( $_GET['layoutKey'], $_POST );
	$aErr['layoutAdd'] = clErrorHandler::getValidationError( 'updateLayout' );
	if( empty($aErr['layoutAdd']) ) $oRouter->redirect( $oRouter->sPath . '?layoutKey=' . $_GET['layoutKey'] );
}

// Update layout file
if( !empty($_POST['frmLayoutFileAdd']) && !empty($_GET['layoutKey']) ) {
	$bKeepViews = $_POST['keepViews'] == 'yes' ? true : false;
	$oLayout->updateLayoutFile( $_GET['layoutKey'], $_POST['layoutFile'], $bKeepViews );
}

$oOutputHtmlForm->init( $oLayout->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'marginal' ),
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => array(
			'content' => _( 'Save' ),
			'attributes' => array(
				'name' => 'btnSubmit',
				'type' => 'submit'
			)
		),
		'submitNext' => array(
			'content' => _( 'Save & continue with next' ),
			'attributes' => array(
				'name' => 'btnSubmitNext',
				'type' => 'submit'
			)
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( $aLayoutFormDataDict );

// Edit layout
if( !empty($_GET['layoutKey']) ) {
	$aLayoutData = current( $oLayout->read('*', $_GET['layoutKey']) );

	$oOutputHtmlForm->setData( $aLayoutData );
	if( !empty($aErr['layoutAdd']) ) $oOutputHtmlForm->aErr = $aErr['layoutAdd'];
	$sLayoutForm =  '
		<section class="layoutFormAdd">
			<h2>' . _( 'Information' ) . '</h2>
			' . $oOutputHtmlForm->render() . '
		</section>';
	$sTitle = _( 'Edit layout' );

	$oOutputHtmlForm->setFormDataDict( $aLayoutFileFormDataDict + array(
		'keepViews' => array(
			'type' => 'array',
			'title' => _( 'Keep views' ),
			'values' => array(
				'no' => _( 'No' ),
				'yes' => _( 'Yes' )
			)
		),
		'frmLayoutFileAdd' => array(
			'type' => 'hidden',
			'value' => true
		)
	) );
	if( !empty($aErr['layoutFileAdd']) ) $oOutputHtmlForm->aErr = $aErr['layoutFileAdd'];

	$sLayoutFileForm = '
		<section class="layoutFileAdd">
			<h2>' . _( 'Change layout file' ) . '</h2>
			' . $oOutputHtmlForm->render() . '
		</section>';
} else {
	$sTitle = _( 'Add layout' );
	$sLayoutForm = $oOutputHtmlForm->render();
}

$sTools = '
	<div class="tool">
		<a href="' . $oRouter->getPath( 'superLayouts' ) . '" class="icon iconText iconGoBack">' . _( 'Go back' ) . '</a>
	</div>
	<div class="tool">
		<a href="' . $oRouter->sPath . '" class="icon iconText iconAdd">' . _( 'New layout' ) . '</a>
	</div>';

if( !empty($_GET['layoutKey']) ) {
	$oLayout->oDao->aSorting = array(
		'layoutKey' => 'ASC'
	);
	$aAllLayouts = arrayToSingle( $oLayout->read(), null, 'layoutKey' );
	$oLayout->oDao->aSorting = null;
	
	$sPrevUrl = '';
	$sNextUrl = '';
	foreach( $aAllLayouts as $iKey => $sLayoutKey ) {		
		if( $sLayoutKey == $_GET['layoutKey'] ) {		
			if( !empty($aAllLayouts[ ($iKey - 1) ]) ) $sPrevUrl = $oRouter->sPath . '?layoutKey=' . $aAllLayouts[ ($iKey - 1) ];
			if( !empty($aAllLayouts[ ($iKey + 1) ]) ) $sNextUrl = $oRouter->sPath . '?layoutKey=' . $aAllLayouts[ ($iKey + 1) ];
			break;
		}
	}
	
	$sTools .= '			
		<div class="tool">
			' . (!empty($sPrevUrl) ? '
			<a href="' . $sPrevUrl . '" class="icon iconText iconPrevious">' . _( 'Previous layout' ) . '</a>
			' : '
			<a href="#" class="icon iconText iconPrevious disabled">' . _( 'Previous layout' ) . '</a>
			') . '				
			&nbsp;&nbsp;|&nbsp;&nbsp;
			' . (!empty($sNextUrl) ? '
			<a href="' . $sNextUrl . '" class="icon iconText iconNext">' . _( 'Next layout' ) . '</a>
			' : '
			<a href="#" class="icon iconText iconNext disabled">' . _( 'Next layout' ) . '</a>
			') . '
		</div>';
		
}

echo '
	<div class="view layout formAdd">
		<h1>' . $sTitle . '</h1>
		<section class="tools">
			' . $sTools . '
		</section>
		' . $sLayoutForm . '
		' . $sLayoutFileForm . '
	</div>';
	
$oLayout->oDao->setLang( $GLOBALS['langId'] );