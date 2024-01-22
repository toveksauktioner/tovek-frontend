<?php

require_once PATH_FUNCTION . '/fData.php';

$oRouter = clRegistry::get( 'clRouter' );
$oPuff = clRegistry::get( 'clPuff', PATH_MODULE . '/puff/models' );
$oPuff->oDao->aSorting = array(
	'puffSort' => 'ASC'
);

// Not notifications
$oPuff->oDao->setCriterias( array(
  'notNotifications' => array(
    'type' => 'notIn',
    'value' => array(
			'notification',
			'bigNotification'
		),
    'fields' => 'puffLayoutKey'
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
	$oImage->oDao->setEntries( 0 );
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
		$aData = $oPuff->readByRoute( $oRouter->iCurrentRouteId, $aReadFields, arrayToSingle($aPuffs, null, 'puffId') );
	} else {
		// Normal
		$aData = $oPuff->read( $aReadFields, arrayToSingle($aPuffs, null, 'puffId') );
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

			// Puff images
			if( strpos( $sPuffLayout, '[images]' ) ) {
				$aPuffImage = $oImage->readByParent( $aEntry['puffId'], array(
					'imageId',
					'imageFileExtension',
					'imageParentId',
					'imageAlternativeText',
					'imageParentType'
				) );

				if( !empty( $aPuffImage ) ) {
					$sImages = '<!--' . count($aPuffImage) . '-->';
					foreach( $aPuffImage as $aPuffImageData ) {
						$sPuffImage = '/images/custom/Puff/' . $aPuffImageData['imageId'] . '.' . $aPuffImageData['imageFileExtension'];
						$sImages .= '<img src="' . $sPuffImage . '" alt="' . $aPuffImageData['imageAlternativeText'] . '" />';
					}

					$sPuffLayout = str_replace( '[images]', $sImages, $sPuffLayout );
				}


				if( stristr($sPuffLayout, '[foreach_image]') ) {
					if( !empty($sImages) ) {
						$sPuffLayout = str_replace( array(
							'[foreach_image]',
							'[/foreach_image]'
						), array(
							'',
							''
						), $sPuffLayout );
					} else {
						$sMatch = '/\[foreach\_image\]([\s\S]*?)\[\/foreach\_image\]/';
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
	echo '<div class="view puff list">' . $sPuffList . '</div>';
}

$oPuff->oDao->sCriterias = null;
