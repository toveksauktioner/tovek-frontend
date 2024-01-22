<?php

require_once PATH_FUNCTION . '/fData.php';

$oRouter = clRegistry::get( 'clRouter' );
$oPuff = clRegistry::get( 'clPuff', PATH_MODULE . '/puff/models' );
$oPuff->oDao->setEntries( 1 );
$oPuff->oDao->aSorting = array(
	'puffSort' => 'ASC',
	'puffCreated' => 'DESC'
);

// Route specific puffs
$aRouteToObject = $oRouter->oDao->readData( array(
	'entities' => 'entRouteToObject',
	'fields' => array( 'routeId', 'objectId', 'objectType' ),
	'criterias' => 'objectType = "Puff" AND routeId = ' . (int) $oRouter->iCurrentRouteId
) );

// Only notifications for this route
$oPuff->oDao->setCriterias( array(
  'onlyNotifications' => array(
    'type' => 'in',
    'value' => array(
			'notification',
			'bigNotification'
		),
    'fields' => 'puffLayoutKey'
  ),
	'route' => array(
		'type' => 'in',
		'value' => arrayToSingle( $aRouteToObject, null, 'objectId' ),
		'fields' => 'puffId'
	)
) );

// Get published
$aPuffs = $oPuff->aHelpers['oJournalHelper']->read( 'puffId' );

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
	$aData = $oPuff->read( $aReadFields, arrayToSingle($aPuffs, null, 'puffId') );

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

					if( stristr($sPuffLayout, '[if_' . $sField . ']') ) {
						if( !empty($sValue) ) {
							$sPuffLayout = str_replace( array(
								'[if_' . $sField . ']',
								'[/if_' . $sField . ']'
							), array(
								'',
								''
							), $sPuffLayout );
						} else {
							$sMatch = '/\[if\_' . $sField . '\]([\s\S]*?)\[\/if\_' . $sField . '\]/';
							$sPuffLayout = preg_replace( $sMatch, '', $sPuffLayout );
						}
					}

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

				if( stristr($sPuffLayout, '[if_image]') ) {
					if( !empty($sPuffImage) ) {
						$sPuffLayout = str_replace( array(
							'[if_image]',
							'[/if_image]'
						), array(
							'',
							''
						), $sPuffLayout );
					} else {
						$sMatch = '/\[if\_image\]([\s\S]*?)\[\/if\_image\]/';
						$sPuffLayout = preg_replace( $sMatch, '', $sPuffLayout );
					}
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

if( !empty($sPuffList) ) {
	echo '<div class="view puff listNotification">' . $sPuffList . '</div>';
}


$oPuff->oDao->sCriterias = null;
$oPuff->oDao->setEntries( null );
