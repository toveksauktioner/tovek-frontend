<?php

if( empty($_GET['imageId']) ) {
	$oRouter->redirect( $oRouter->getPath( 'superImageTable' ) );
}

$aErr = array();

$oImage	= clRegistry::get( 'clImage', PATH_MODULE . '/image/models/' );
$oImageAltRoute	= clRegistry::get( 'clImageAltRoute', PATH_MODULE . '/image/models/' );
$oRouter = clRegistry::get( 'clRouter' );

$oImageAltRoute->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

/**
 * Selected image
 */
$aImage = current( $oImage->read( '*', $_GET['imageId'] ) );
$sImageSrc = sprintf( '/images/custom/%s/%s.%s', $aImage['imageParentType'], $aImage['imageId'], $aImage['imageFileExtension'] );
$sImageTnSrc = sprintf( '/images/custom/%s/tn/%s.%s', $aImage['imageParentType'], $aImage['imageId'], $aImage['imageFileExtension'] );

/**
 * Routes
 */
$aRouteData = $oRouter->oDao->read( array(
	'fields' => array(
		'routeId',
		'routePath',
		'routeLayoutKey'
	)
) );
$aRoutes = array( null => _( 'All pages' ) );
$aRouteByPath = array();
foreach( $aRouteData as $aEntry ) {
	if( substr( $aEntry['routeLayoutKey'], 0, 5 ) == 'guest' ) {
		$aRoutes[$aEntry['routeId']] = $aEntry['routePath'];
		$aRouteByPath[$aEntry['routePath']] = $aEntry['routeId'];
	}
}

/**
 * Post
 */
if( !empty($_POST['frmAddImageAltRoute']) ) {
	$_POST['entryRouteId'] = $aRouteByPath[ $_POST['entryRouteId'] ];
	
	/**
	 * Update
	 */
	if( !empty($_GET['entryId']) && ctype_digit($_GET['entryId']) ) {
		$_POST['entryUpdated'] = date( 'Y-m-d H:i:s' );
		$oImageAltRoute->update( $_GET['entryId'], $_POST );
		$aErr = clErrorHandler::getValidationError( 'updateImageAltRoute' );		
		
	/**
	 * Create
	 */
	} else {
		$_POST['entryCreated'] = date( 'Y-m-d H:i:s' );
		$iEntryId = $oImageAltRoute->create( $_POST );
		$aErr = clErrorHandler::getValidationError( 'createImageAltRoute' );
	}
	
	if( empty($aErr) ) {
		$oRouter->redirect( $oRouter->sPath . '?imageId=' . $_GET['imageId'] );
	}
}

// Image alt route data
$aData = valueToKey( 'entryId', $oImageAltRoute->readByImage( $_GET['imageId'], '*' ) );
$aEntryData = !empty($_GET['entryId']) && !empty($aData[ $_GET['entryId'] ]) ? $aData[ $_GET['entryId'] ] : array();
$aEntryData['entryRouteId'] = !empty($aEntryData['entryRouteId']) && !empty($aRoutes[ $aEntryData['entryRouteId'] ]) ? $aRoutes[ $aEntryData['entryRouteId'] ] : '';

// DataDict
$aDataDict = array(
	'entRouteToImageAlt' => array(
		'entryRouteId' => array(
			'type' => 'string',
			'title' => _( 'Path' ),
			'attributes' => array(
				'class' => 'text entryRouteId'
			)
		),
		'entryImageAlternativeTextTextId' => array(
			'type' => 'string',
			'title' => _( 'Alt text' )
		)
	)
);

// Form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aEntryData,
	'errors' => $aErr,
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	),
) );
$oOutputHtmlForm->setFormDataDict( array(
	'entryRouteId' => array(),
	'entryImageAlternativeTextTextId' => array(),
	'entryImageId' => array(
		'type' => 'hidden',
		'value' => $_GET['imageId']
	),
	'frmAddImageAltRoute' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table
clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $aDataDict );
$oOutputHtmlTable->setTableDataDict( array(
	'entryRouteId' => array(),
	'entryImageAlternativeTextTextId' => array(),
	'routeControls' => array(
		'title' => ''
	)
) );

// Add form
$aRouteForm = array(
	'entryRouteId' => $oOutputHtmlForm->renderFields( 'entryRouteId' ),
	'entryImageAlternativeTextTextId' => $oOutputHtmlForm->renderFields( 'entryImageAlternativeTextTextId' ),
	'routeControls' =>
		$oOutputHtmlForm->renderFields( array('entryImageId','frmAddImageAltRoute') ) .
		$oOutputHtmlForm->renderButtons()
);
$oOutputHtmlTable->addBodyEntry( $aRouteForm, array(
	'id' => 'frmImageAltRouteAdd'
) );

if( !empty($aData) ) {
	foreach( $aData as $aEntry ) {
		if( !empty($_GET['entryId']) && $aEntry['entryId'] == $_GET['entryId'] ) {
			$aRouteForm['routeControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('entryId', 'event', 'deleteImageRoute') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
			$oOutputHtmlTable->addBodyEntry( $aRouteForm );
			
		} else {		
			$aRow = array(
				'entryRouteId' => $aRoutes[ $aEntry['entryRouteId'] ],
				'entryImageAlternativeTextTextId' => $aEntry['entryImageAlternativeTextTextId'],
				'routeControls' => '
					<a href="' . $oRouter->sPath . '?imageId=' . $_GET['imageId'] . '&entryId=' . $aEntry['entryId'] . '" class="icon iconText iconEdit iconText">' . _( 'Edit' ) . '</a>
					<a href="' . $oRouter->sPath . '?imageId=' . $_GET['imageId'] . '&deleteImageRoute=' . $aEntry['entryRouteId'] . '" class="icon iconText iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			);
			$oOutputHtmlTable->addBodyEntry( $aRow );
		}
	}
}

$oImageAltRoute->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );

echo '
	<div class="view image altRouteTableForm">
		<h1>' . _( 'Image alternative texts' ) . '</h1>
		<section>
			<h3>' . _( 'Current image' ) . ':</h3>
			<div class="image">
				<a href="' . $sImageSrc . '"><img src="' . $sImageTnSrc . '" alt="" width="100" /></a>
			</div>
		</section>
		<section>
			<p><a href="#frmImageAltRouteAdd" class="toggleShow icon iconText iconAdd">' . _( 'Add route' ) . '</a></p>
			' . $oOutputHtmlForm->renderErrors() . $oOutputHtmlForm->renderForm( $oOutputHtmlTable->render() )
			. (empty($aData) ? '<strong>' . _( 'There are no items to show' ) . '</strong>' : '' ) . '
		</section>
	</div>';
	
$oTemplate->addBottom( array(
	'key' => 'leakageAutoComplete',
	'content' => '
	<script type="text/javascript">
		$(".entryRouteId").autocomplete( {
			source: ["' . implode( '", "', $aRoutes ) . '"],
			minLength: 0
		} );
	</script>'
) );