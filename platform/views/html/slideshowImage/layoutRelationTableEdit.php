<?php

$aErr = array();

$oSlideshowImageToLayout = clRegistry::get( 'clSlideshowImageToLayout', PATH_MODULE . '/slideshowImage/models' );

$oLayout = clRegistry::get( 'clLayoutHtml' );
$oLayout->setAcl( $oUser->oAcl );

$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
$oInfoContent->oDao->setLang( $GLOBALS['langIdEdit'] );
$aInfoContentDataDict = $oInfoContent->oDao->getDataDict('entInfoContent');

// Create
if( !empty($_GET['action']) && $_GET['action'] == 'addSlideshowImageToLayout' ) {
	$oSlideshowImageToLayout->oDao->setCriterias( array(
		'slideshowImageId' => array(
			'type' => '=',
			'value' => $_GET['slideshowImageId'],
			'fields' => array( 'slideshowImageId' )
		),
		'layoutKey' => array(
			'type' => '=',
			'value' => $_GET['layoutKey'],
			'fields' => array( 'layoutKey' )
		)
	) );
	$aRelationData = $oSlideshowImageToLayout->read();
	$oSlideshowImageToLayout->oDao->sCriterias = null;
	if( empty( $aRelationData ) ) {
		$oSlideshowImageToLayout->create( array(
			'slideshowImageId' => $_GET['slideshowImageId'],
			'layoutKey' => $_GET['layoutKey']
		) );
		$aErr = clErrorHandler::getValidationError( 'createSlideshowImageToLayout' );
		if( empty($aErr) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
		}
	}
}

// Delete
if( !empty($_GET['action']) && $_GET['action'] == 'deleteSlideshowImageToLayout' ) {
	$oSlideshowImageToLayout->delete( $_GET['relationId'] );
	$aErr = clErrorHandler::getValidationError( 'deleteSlideshowImageToLayout' );
	if( empty($aErr) ) {
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => _( 'The data has been saved' )
		) );
	}
}

if( !empty($_GET['slideshowImageId']) ) {
	$aTableDataDict = array(
		'layoutRelationTable' => array(
			'layoutTitleTextId' => array(
				'type' => 'string',
				'title' => _( 'Title' )
			),
			'contentStatus' => array(
				'type' => 'string',
				'title' => _( 'Status' )
			),
			'contentUpdated' => array(
				'type' => 'string',
				'title' => _( 'Updated' )
			)
		)
	);

	// All pages
	$aLayoutData = $oLayout->readCustom();

	if( !empty($aLayoutData) ) {
		// Add additional data
		foreach( $aLayoutData as $key => $entry ) {
			// Read view data
			$oLayout->oDao->setCriterias( array() );
			$aViewData = $oLayout->readSectionsAndViews($entry['layoutKey']);
			// Filter out views that is not infoContent
			if( count($aViewData) == 1 ) {
				$iViewId = $aViewData[0]['viewId'];
			} else {
				foreach( $aViewData as $value ) {
					if( $value['viewModuleKey'] == 'infoContent' && $value['viewFile'] == 'show.php' ) {
						$iViewId = $value['viewId'];
						break;
					}
				}
			}
			// Read infoContent to Layouts
			$aInfoContent = current( $oInfoContent->readByView( $iViewId, array(
				'contentId',
				'contentStatus',
				'contentUpdated'
			) ) );
			$aLayoutData[$key] += array(
				'contentId' => $aInfoContent['contentId'],
				'contentStatus' => $aInfoContent['contentStatus'],
				'contentUpdated' => $aInfoContent['contentUpdated']
			);
		}
		$aLayoutData = valueToKey( 'layoutKey', $aLayoutData );
	}

	// Relation data
	$aSlideshowImageToLayout = $oSlideshowImageToLayout->readBySlideshowImageId( $_GET['slideshowImageId'] );
	if( !empty($aSlideshowImageToLayout) ) {
		$aSlideshowImageToLayout = arrayToSingle( $aSlideshowImageToLayout, 'layoutKey', 'relationId' );
	}

	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $aTableDataDict );
	$oOutputHtmlTable->setTableDataDict( $aTableDataDict['layoutRelationTable'] + array(
		'relationControls' => array(
			'title' => ''
		)
	) );
	$oOutputHtmlTable2 = new clOutputHtmlTable( $aTableDataDict );
	$oOutputHtmlTable2->setTableDataDict( $aTableDataDict['layoutRelationTable'] + array(
		'relationControls' => array(
			'title' => ''
		)
	) );

	foreach( $aLayoutData as $sLayoutKey => $entry ) {
		$sEvent = 'action=addSlideshowImageToLayout&amp;slideshowImageId=' . $_GET['slideshowImageId'] . '&amp;layoutKey=' . $sLayoutKey;
		$sLink = '<a href="' . $oRouter->sPath . '?' . $sEvent . '" class="icon iconAdd iconText linkConfirm" title="' . _( 'Do you really want to add this item?' ) . '">' . _( 'Add' ) . '</a>';
		$sAltLink = '<a href="' . $oRouter->sPath . '?' . $sEvent . '" class="icon iconAdd linkConfirm" title="' . _( 'Do you really want to add this item?' ) . '"></a>';

		if( array_key_exists($sLayoutKey ,$aSlideshowImageToLayout) ) {
			$sEvent = 'action=deleteSlideshowImageToLayout&amp;slideshowImageId=' . $_GET['slideshowImageId'] . '&amp;relationId=' . $aSlideshowImageToLayout[$sLayoutKey];
			$sLink = '<a href="' . $oRouter->sPath . '?' . $sEvent . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>';
			$sAltLink = '<a href="' . $oRouter->sPath . '?' . $sEvent . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"></a>';

			$row = array(
				'layoutTitleTextId' => $sAltLink . ' ' . htmlspecialchars( $entry['layoutTitleTextId'] ),
				'contentStatus' => '<span class="' . $aInfoContent['contentStatus'] . '">' . $aInfoContentDataDict['entInfoContent']['contentStatus']['values'][ $aInfoContent['contentStatus'] ] . '</span>',
				'contentUpdated' => $entry['contentUpdated'],
				'relationControls' => $sLink
			);
			$oOutputHtmlTable->addBodyEntry( $row );
		}

		$row = array(
			'layoutTitleTextId' => $sAltLink . ' ' . htmlspecialchars( $entry['layoutTitleTextId'] ),
			'contentStatus' => '<span class="' . $aInfoContent['contentStatus'] . '">' . $aInfoContentDataDict['entInfoContent']['contentStatus']['values'][ $aInfoContent['contentStatus'] ] . '</span>',
			'contentUpdated' => $entry['contentUpdated'],
			'relationControls' => $sLink
		);
		$oOutputHtmlTable2->addBodyEntry( $row );
	}

	if( empty($aSlideshowImageToLayout) ) {
		$row = array(
			'layoutTitleTextId' => '<strong>' . _('There are no items to show') . '</strong>',
			'contentStatus' => '',
			'contentUpdated' => '',
			'relationControls' => ''
		);
		$oOutputHtmlTable->addBodyEntry( $row );
	}

	echo '
		<div class="view slideshowImage layoutRelationTableEdit">
			<h1>' . _( 'Shown on' ) . '</h1>
			<section>' . $oOutputHtmlTable->render() . '</section>
			<h1>' . _( 'Pages' ) . '</h1>
			<section>' . $oOutputHtmlTable2->render() . '</section>
		</div>';
}