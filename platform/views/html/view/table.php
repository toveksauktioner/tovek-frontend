<?php

$aErr = array();
$sOutput = '';
$sAvailableViews = '';

$oRouter = clRegistry::get( 'clRouter' );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );
$oView = clRegistry::get( 'clViewHtml' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oNotification = clRegistry::get( 'clNotificationHandler' );

clFactory::loadClassFile( 'clOutputHtmlSorting' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

/**
 * REQUEST (POST)
 */
if( !empty($_REQUEST['frmAddView']) ) {	
	// Update
	if( !empty($_GET['viewId']) ) {
		$oView->update( $_GET['viewId'], $_REQUEST );
		$aErr = clErrorHandler::getValidationError( 'updateView' );
		if( empty($aErr) ) $oRouter->redirect( $oRouter->sPath );
		
	// Create
	} else {
		foreach( $_REQUEST['addView'] as $sViewPath ) {
			$aView = explode( '/', $sViewPath );
			$iViewId = $oView->create( array(
				'viewModuleKey' => $aView[0],
				'viewFile' => $aView[1]
			) );
			$aErr = clErrorHandler::getValidationError( 'createView' );					
		}
		
		if( count($_REQUEST['addView']) == 1 ) {
			$oRouter->redirect( $oRouter->sPath . '#viewId' . $iViewId );
		} else {
			$oRouter->redirect( $oRouter->sPath . '#addedViews' );
		}		
	}
}

// Edit
if( !empty($_GET['viewId']) ) {
	$aViewData = $oView->read( '*', $_GET['viewId'] );
	$sTitle = '';
} else {
	$aViewData = $_POST;
	$sTitle = '<a href="#frmViewAdd" class="toggleShow icon iconText iconAdd">' . _( 'Add view' ) . '</a>';
}


$oSorting = new clOutputHtmlSorting( $oView->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('viewModuleKey' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'viewId' => array(),
	'viewModuleKey' => array(),
	'viewFile' => array()
) );

// List
$aViews = $oView->read( '*' );

if( true ) {
	// Read all available views
	// Fetch $aViews and compare with the available views.
	// List the views thats not yet added.
	// List removed views.

	/*** Read all available views ***/
	foreach( scandir(PATH_VIEW_HTML) as $sFolder ) {
		if( in_array($sFolder, array('.', '..')) ) continue;

		foreach( scandir(PATH_VIEW_HTML . '/' . $sFolder) as $sFile ) {
			if( in_array($sFile, array('.', '..')) ) continue;
			$aAvailableViews[$sFolder][] = $sFile;
		}
	}

	/*** Fetch added views ***/
	$bRemoveAllNote = false;
	foreach( $aViews as $entry ) {
		$aAddedViews[$entry['viewModuleKey']][] = $entry['viewFile'];

		if( !empty($aAvailableViews[$entry['viewModuleKey']]) && in_array($entry['viewFile'], $aAvailableViews[$entry['viewModuleKey']]) ) {
			continue;
		}

		$aNoView[$entry['viewModuleKey']][] = $entry['viewFile'];

		if( !empty($aNoView) && $bRemoveAllNote === false ) {
			$oNotification->addError( '# ' . _('Remove all non existing views at once.') . ' ' . '<a href="' . $oRouter->sPath . '?removeNotExistingViews=true" class="icon iconDelete iconText">' . _( 'Delete all' ) . '</a>' );
			$bRemoveAllNote = true;
		}

		if( isset($_GET['removeNotExistingViews']) && $_GET['removeNotExistingViews'] == 'true' ) {
			$oView->delete( $entry['viewId'] );
		} else {
			$oNotification->addError( sprintf( _('The file "%s" that belongs to the module "%s" doesn´t exist.'), $entry['viewFile'], $entry['viewModuleKey'] ) . ' ' . '<a href="' . $oRouter->sPath . '?event=deleteView&deleteView=' . $entry['viewId'] . '" class="icon iconDelete iconText">' . _( 'Delete' ) . '</a>' );
		}
	}
	if( isset($_GET['removeNotExistingViews']) ) {
		$oRouter->redirect( $oRouter->sPath );
	}

	$oOutputHtmlTable = new clOutputHtmlTable( $oView->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( array(
		'viewSelect' => array(
			'title' => _( 'Add' )
		),
		'viewControls' => array(
			'title' => ''
		),
		'viewModuleKey' => array(),
		'viewFile' => array()		
	) );

	foreach( $aAvailableViews as $sFolder => $sFiles ) {
		foreach( $sFiles as $sFile ) {
			if( array_key_exists($sFolder, $aAddedViews) && in_array($sFile, $aAddedViews[$sFolder]) ) continue;

			$oOutputHtmlTable->addBodyEntry( array(
				'viewSelect' => '<input type="checkbox" name="addView[]" value="' . $sFolder . '/' . $sFile . '" />',
				'viewControls' => '<a href="' . $oRouter->sPath . '?frmAddView=true&amp;addView[]=' . $sFolder . '/' . $sFile . '" class="icon iconText iconAdd"><span>' . _( 'Add' ) . '</span></a>',
				'viewModuleKey' => $sFolder,
				'viewFile' => $sFile				
			) );
		}
	}

	$sAvailableViews = '
		<form method="post" action="">
			<button type="submit">' . _( 'Add' ) . '</button><br /><br />
			' . $oOutputHtmlTable->render() . '
			<input type="hidden" name="frmAddView" value="1" />
			<button type="submit">' . _( 'Add' ) . '</button>
		</form>';
}

$oOutputHtmlForm->init( $oView->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aViewData,
	'errors' => $aErr,
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	),
) );
$oOutputHtmlForm->setFormDataDict( array(
	'viewId' => array(),
	'viewModuleKey' => array(),
	'viewFile' => array(),
	'frmAddView' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

$oOutputHtmlTable = new clOutputHtmlTable( $oView->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
	'viewControls' => array(
		'title' => ''
	)
) );

$sUrlViewEdit = $oRouter->sPath;
$sUrlViewCssEdit = $oRouter->getPath( 'superViewCss' );
if( empty($sUrlViewCssEdit) ) $sUrlViewCssEdit = '#';
$sUrlAclAcoAdd = $oRouter->getPath( 'superAclAcoAdd' );

// Add form
$aViewForm = array(
	'viewId' => !empty( $_GET['viewId'] ) ? $_GET['viewId'] : '',
	'viewModuleKey' => $oOutputHtmlForm->renderFields( 'viewModuleKey' ),
	'viewFile' => $oOutputHtmlForm->renderFields( 'viewFile' ),
	'viewControls' => $oOutputHtmlForm->renderFields( 'frmAddView' ) . $oOutputHtmlForm->renderFields( 'viewControls' ) . $oOutputHtmlForm->renderButtons()
);
if( empty($_GET['viewId']) ) {
	$oOutputHtmlTable->addBodyEntry( $aViewForm, array(
		'id' => 'frmViewAdd'
	) );
}

foreach( $aViews as $entry ) {
	$aAttributes = array(
		'id' => 'viewId' . $entry['viewId']
	);

	if( !empty($_GET['viewId']) && $_GET['viewId'] == $entry['viewId'] ) {
		// Add form
		$row = $aViewForm;
	} else {
		$row = array(
			'viewId' => $entry['viewId'],
			'viewModuleKey' => $entry['viewModuleKey'],
			'viewFile' => $entry['viewFile'],
			'viewControls' => '
			<a href="' . $sUrlAclAcoAdd . '?aclType=view&amp;acoKey=' . $entry['viewId'] . '" class="icon iconLock"><span>' . _( 'ACL' ) . '</span></a>
			<a href="' . $sUrlViewCssEdit . '?viewId=' . $entry['viewId'] . '" class="icon iconCss"><span>' . _( 'Css' ) . '</span></a>
			<a href="' . $sUrlViewEdit . '?viewId=' . $entry['viewId'] . '#viewId' . $entry['viewId'] . '" class="icon iconEdit"><span>' . _( 'Edit' ) . '</span></a>
			<a href="' . $sUrlViewEdit . '?event=deleteView&amp;deleteView=' . $entry['viewId'] . '&amp;' . stripGetStr( array('event', 'deleteView') ) . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>'
		);
	}

	if( !empty($aNoView[$entry['viewModuleKey']]) && in_array($entry['viewFile'], $aNoView[$entry['viewModuleKey']]) ) {
		$aAttributes['style'] = 'background:red;';
		$oOutputHtmlTable->addBodyEntry( $row, $aAttributes );
		continue;
	}

	$oOutputHtmlTable->addBodyEntry( $row, $aAttributes );
}

echo '
	<div class="view table">
	<h1>' . _( 'Available views' ) . '</h1>
	<section>' . $sAvailableViews . '</section>
	<h1 id="addedViews">' . _( 'Added views' ) . '</h1>
	' . $oOutputHtmlForm->renderForm(
		'<div class="viewTable">
			' . $oOutputHtmlForm->renderErrors() . '
			' . $sTitle . '
			' . $oOutputHtmlTable->render() . '
			' . ( empty($aViews) ? '<strong>' . _('There are no items to show') . '</strong>' : '' ) . '
		</div>'
	) . '
';
$oRouter->oDao->setLang( $GLOBALS['langId'] );