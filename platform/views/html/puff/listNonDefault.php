<?php

require_once PATH_FUNCTION . '/fData.php';

$oRouter = clRegistry::get( 'clRouter' );
$oPuff = clRegistry::get( 'clPuff', PATH_MODULE . '/puff/models' );
$oPuff->oDao->aSorting = array(
	'puffSort' => 'ASC'
);

// Get published
$aPuffs = $oPuff->aHelpers['oJournalHelper']->read( 'puffId' );
$oPuff->oDao->setEntries( null );

$sPuffList = '';

if( !empty($aPuffs) ) {
	/**
	 * Images
	 */
	$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
	$oImage->oDao->aSorting = array(
		'imageSort' => 'ASC',
		'imageCreated' => 'ASC'
	);
	$oImage->oDao->setEntries( 1 );
	$oImage->setParams( array(
		'parentType' => $oPuff->sModuleName
	) );

	$aReadFields = array(
		'puffId',
		'puffLayoutKey',
		'puffUrlTextId',
		'puffStatus',
		'puffPublishStart',
		'puffPublishEnd',
		'puffSort',
		'puffUpdated',
		'puffCreated',
		'puffTitleTextId',
		'puffClass',
		'puffContentTextId',
		'puffShortContentTextId'
	);

	$aRouteToObject = $oRouter->oDao->readData( array(
		'entities' => 'entRouteToObject',
		'fields' => array( 'routeId', 'objectId', 'objectType' ),
		'criterias' => 'objectType = "Puff"'
	) );

	if( !empty($aRouteToObject) ) {
		// Puff data based on current route
		$aData = $oPuff->readByRoute( $oRouter->iCurrentRouteId, $aReadFields, arrayToSingle($aPuffs, null, 'puffId'), array( '(entPuff.puffLayoutKey != "" AND entPuff.puffLayoutKey != "' . PUFF_LAYOUT_DEFAULT . '")' ) );
	} else {
		// Normal
		$aData = $oPuff->read( $aReadFields, arrayToSingle($aPuffs, null, 'puffId'), array( '(entPuff.puffLayoutKey != "" AND entPuff.puffLayoutKey != "' . PUFF_LAYOUT_DEFAULT . '")' ) );
	}

	// Data
	if( !empty( $aData ) ) {
		$aClass = array();

		foreach( $aData as $iKey => $aEntry ) {
			if( empty( $aEntry['puffLayoutKey'] ) ) {
				$aEntry['puffLayoutKey'] = PUFF_LAYOUT_DEFAULT;
			}

			$sPuffLayout = $GLOBALS['puffLayout'][ $aEntry['puffLayoutKey'] ]['template'];

			// Database values
			foreach( $aEntry as $sField => $sValue ) {
				if( strpos( $sPuffLayout, $sField ) ) {
					$sPuffLayout = str_replace( '[' . $sField . ']', $sValue, $sPuffLayout );
				}
			}

			// Puff image
			if( strpos( $sPuffLayout, '[image]' ) ) {
				$aPuffImage = current( $oImage->readByParent( $aEntry['puffId'], array(
					'imageId',
					'imageFileExtension',
					'imageParentId',
					'imageAlternativeText',
					'imageParentType'
				) ) );

				if( !empty( $aPuffImage ) ) {
					$sPuffImage = '/images/custom/Puff/' . $aPuffImage['imageId'] . '.' . $aPuffImage['imageFileExtension'];
					$sPuffLayout = str_replace( '[image]', $sPuffImage, $sPuffLayout );
					$sPuffLayout = str_replace( '[imageAlt]', $aPuffImage['imageAlternativeText'], $sPuffLayout );
				}
			}

			// Views
			if( !empty($GLOBALS['puffLayout'][ $aEntry['puffLayoutKey'] ]['views']) ) {
				foreach( $GLOBALS['puffLayout'][ $aEntry['puffLayoutKey'] ]['views'] as $sPlaceholder => $sPath ) {
					$sViewContent = clRegistry::get( 'clLayoutHtml' )->renderView( $sPath );
					$sPuffLayout = str_replace( '[' . $sPlaceholder . ']', $sViewContent, $sPuffLayout );
				}
			}

			$sPuffList .= $sPuffLayout;
		}
	}
}

echo '<div class="view puffList">' . $sPuffList . '</div>';
